<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCheckout;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Review;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reports hub: one page per area of the business (sales, products, customers, inventory, finance, operations, marketing),
 * each with KPIs, charts and detailed tables that can be exported to CSV one by one.
 *
 * Conventions (so numbers always agree across reports):
 *  - "live" orders = every order that is not cancelled or refunded;
 *  - revenue = sum of order totals of live orders (tax and shipping included);
 *  - days / hours follow the store's timezone;
 *  - cost-based figures use the product's current cost.
 */
class ReportHubController extends Controller
{
    public const TYPES = [
        'sales' => ['Sales', 'graph-up-arrow'], 'products' => ['Products', 'box-seam-fill'], 'customers' => ['Customers', 'people-fill'],
        'inventory' => ['Inventory', 'boxes'], 'finance' => ['Finance & tax', 'cash-coin'], 'operations' => ['Operations', 'truck'],
        'marketing' => ['Marketing', 'megaphone-fill'],
    ];

    public function show(Request $request, string $type): View|StreamedResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $this->authorizeTo('reports.view');
        $this->authorizeTo("reports.{$type}");

        [$from, $to, $key] = $this->range($request);
        $tz = setting('store.timezone', config('app.timezone'));
        $report = $this->{$type}($from, $to, $tz);

        if ($request->filled('export')) {
            $this->authorizeTo('reports.export');

            return $this->csv($report, (string) $request->query('export'), $type, $from, $to);
        }

        return view('admin.reports.hub', ['type' => $type, 'title' => self::TYPES[$type][0], 'report' => $report, 'from' => $from, 'to' => $to, 'key' => $key, 'types' => self::TYPES]);
    }

    // ---------------------------------------------------------------- reports

    private function sales(Carbon $from, Carbon $to, string $tz): array
    {
        $live = $this->live($from, $to);
        $revenue = (float) $live->sum('total');
        $items = $this->items($live);
        $span = $from->diffInDays($to) + 1;
        $prevLive = $this->live($from->copy()->subDays($span), $from->copy()->subSecond());

        $byDay = $live->groupBy(fn ($o) => $this->local($o->placed_at, $tz)->format('Y-m-d'));
        $days = collect(range(0, $span - 1))->map(fn ($i) => $from->copy()->timezone($tz)->addDays($i)->format('Y-m-d'));
        $hours = array_fill(0, 24, ['orders' => 0, 'revenue' => 0.0]);
        $weekdays = array_fill(0, 7, ['orders' => 0, 'revenue' => 0.0]);
        foreach ($live as $o) {
            $l = $this->local($o->placed_at, $tz);
            $hours[(int) $l->format('G')]['orders']++;
            $hours[(int) $l->format('G')]['revenue'] += (float) $o->total;
            $weekdays[(int) $l->format('w')]['orders']++;
            $weekdays[(int) $l->format('w')]['revenue'] += (float) $o->total;
        }
        $names = [__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')];

        return [
            'kpis' => [
                ['Revenue', $revenue, 'money', $this->change($revenue, (float) $prevLive->sum('total'))],
                ['Orders', $live->count(), 'int', $this->change($live->count(), $prevLive->count())],
                ['Average order', $live->count() ? $revenue / $live->count() : 0, 'money', null],
                ['Items sold', (int) $items->sum('qty'), 'int', null],
                ['Best day', $byDay->map(fn ($g) => $g->sum('total'))->max() ?? 0, 'money', null],
                ['Cancelled / refunded', $this->orders($from, $to)->get()->whereIn('status', ['cancelled', 'refunded'])->count(), 'int', null],
            ],
            'charts' => [
                ['id' => 'day', 'type' => 'line', 'title' => 'Revenue by day', 'labels' => $days->map(fn ($d) => Carbon::parse($d)->format('d M'))->all(), 'money' => true, 'datasets' => [['label' => 'Revenue', 'data' => $days->map(fn ($d) => round((float) ($byDay[$d] ?? collect())->sum('total'), 2))->all()]]],
                ['id' => 'hour', 'type' => 'bar', 'title' => 'Orders by hour of day', 'labels' => array_map(fn ($h) => sprintf('%02d:00', $h), range(0, 23)), 'datasets' => [['label' => 'Orders', 'data' => array_column($hours, 'orders')]]],
                ['id' => 'wd', 'type' => 'bar', 'title' => 'Revenue by weekday', 'labels' => $names, 'money' => true, 'datasets' => [['label' => 'Revenue', 'data' => array_map(fn ($w) => round($w['revenue'], 2), array_values($weekdays))]]],
            ],
            'tables' => [
                $this->table('daily', 'Daily sales', ['Date', 'Orders', 'Items', 'Revenue', 'Average order'], ['text', 'int', 'int', 'money', 'money'],
                    $days->map(fn ($d) => [$d, ($g = $byDay[$d] ?? collect())->count(), (int) $this->items($g)->sum('qty'), (float) $g->sum('total'), $g->count() ? $g->sum('total') / $g->count() : 0])->reverse()->values()->all()),
                $this->table('monthly', 'Monthly sales', ['Month', 'Orders', 'Revenue'], ['text', 'int', 'money'],
                    $live->groupBy(fn ($o) => $this->local($o->placed_at, $tz)->format('Y-m'))->map(fn ($g, $m) => [$m, $g->count(), (float) $g->sum('total')])->values()->all()),
                $this->table('payment', 'By payment method', ['Method', 'Orders', 'Revenue'], ['text', 'int', 'money'],
                    $live->groupBy('payment_method')->map(fn ($g, $m) => [$this->payLabel($m), $g->count(), (float) $g->sum('total')])->sortByDesc(2)->values()->all()),
                $this->table('city', 'By city', ['City', 'Orders', 'Revenue'], ['text', 'int', 'money'],
                    $live->groupBy(fn ($o) => $o->city ?: __('Pickup / unknown'))->map(fn ($g, $c) => [$c, $g->count(), (float) $g->sum('total')])->sortByDesc(2)->take(25)->values()->all()),
                $this->table('shipping', 'By delivery method', ['Method', 'Orders', 'Shipping charged', 'Revenue'], ['text', 'int', 'money', 'money'],
                    $live->groupBy(fn ($o) => $o->is_pickup ? __('Pickup') : ($o->shipping_method ?: __('Delivery')))->map(fn ($g, $m) => [$m, $g->count(), (float) $g->sum('shipping_cost'), (float) $g->sum('total')])->values()->all()),
                $this->table('language', 'By customer language', ['Language', 'Orders', 'Revenue'], ['text', 'int', 'money'],
                    $live->groupBy(fn ($o) => $o->locale ?: 'en')->map(fn ($g, $l) => [strtoupper($l), $g->count(), (float) $g->sum('total')])->values()->all()),
            ],
        ];
    }

    private function products(Carbon $from, Carbon $to, string $tz): array
    {
        $live = $this->live($from, $to);
        $items = $this->items($live);
        $products = Product::withTrashed()->with('category', 'brand')->get()->keyBy('id');
        $lines = $items->groupBy(fn ($i) => $i->product_id ?: 'n:'.$i->name)->map(function (Collection $g) use ($products) {
            $p = $products[$g->first()->product_id] ?? null;
            $revenue = (float) $g->sum('total');
            $cost = $p?->cost !== null ? (float) $p->cost * (int) $g->sum('qty') : null;

            return ['name' => $g->first()->name, 'sku' => $g->first()->sku ?: ($p?->sku ?? ''), 'category' => $p?->category?->name ?? '—', 'qty' => (int) $g->sum('qty'), 'revenue' => $revenue, 'cost' => $cost, 'profit' => $cost === null ? null : $revenue - $cost, 'id' => $p?->id];
        });
        $soldIds = $lines->pluck('id')->filter()->all();
        $byQty = $lines->sortByDesc('qty')->values();
        $byRev = $lines->sortByDesc('revenue')->values();

        return [
            'kpis' => [
                ['Products sold', $lines->count(), 'int', null], ['Units sold', (int) $items->sum('qty'), 'int', null],
                ['Active products', Product::where('status', 'active')->count(), 'int', null],
                ['Never sold in period', Product::where('status', 'active')->whereNotIn('id', $soldIds)->count(), 'int', null],
                ['Gross profit (where cost is set)', (float) $lines->sum(fn ($l) => $l['profit'] ?? 0), 'money', null],
            ],
            'charts' => [
                ['id' => 'top', 'type' => 'bar', 'title' => 'Top 10 by revenue', 'labels' => $byRev->take(10)->pluck('name')->all(), 'money' => true, 'horizontal' => true, 'datasets' => [['label' => 'Revenue', 'data' => $byRev->take(10)->pluck('revenue')->map(fn ($v) => round($v, 2))->all()]]],
            ],
            'tables' => [
                $this->table('best-revenue', 'Best sellers by revenue', ['Product', 'SKU', 'Category', 'Units', 'Revenue', 'Profit'], ['text', 'text', 'text', 'int', 'money', 'money'],
                    $byRev->take(50)->map(fn ($l) => [$l['name'], $l['sku'], $l['category'], $l['qty'], $l['revenue'], $l['profit']])->all()),
                $this->table('best-qty', 'Best sellers by units', ['Product', 'Units', 'Revenue'], ['text', 'int', 'money'], $byQty->take(50)->map(fn ($l) => [$l['name'], $l['qty'], $l['revenue']])->all()),
                $this->table('worst', 'Slowest sellers (sold, lowest units)', ['Product', 'Units', 'Revenue'], ['text', 'int', 'money'], $lines->sortBy('qty')->take(25)->values()->map(fn ($l) => [$l['name'], $l['qty'], $l['revenue']])->all()),
                $this->table('never', 'Active products with no sales', ['Product', 'SKU', 'Price', 'Stock'], ['text', 'text', 'money', 'int'],
                    Product::where('status', 'active')->whereNotIn('id', $soldIds)->orderBy('name')->limit(200)->get()->map(fn ($p) => [$p->name, $p->sku ?? '', (float) $p->price, $p->totalStock()])->all()),
                $this->table('category', 'By category', ['Category', 'Units', 'Revenue'], ['text', 'int', 'money'],
                    $lines->groupBy('category')->map(fn ($g, $c) => [$c, (int) $g->sum('qty'), (float) $g->sum('revenue')])->sortByDesc(2)->values()->all()),
                $this->table('brand', 'By brand', ['Brand', 'Units', 'Revenue'], ['text', 'int', 'money'],
                    $items->groupBy(fn ($i) => ($products[$i->product_id] ?? null)?->brand?->name ?? __('No brand'))->map(fn ($g, $b) => [$b, (int) $g->sum('qty'), (float) $g->sum('total')])->sortByDesc(2)->values()->all()),
                $this->table('variant', 'By variant / option', ['Product', 'Variant', 'Units', 'Revenue'], ['text', 'text', 'int', 'money'],
                    $items->whereNotNull('variant_id')->groupBy('variant_id')->map(fn ($g) => [$g->first()->name, $g->first()->variant_name, (int) $g->sum('qty'), (float) $g->sum('total')])->sortByDesc(3)->values()->take(50)->all()),
            ],
        ];
    }

    private function customers(Carbon $from, Carbon $to, string $tz): array
    {
        $live = $this->live($from, $to);
        $emails = $live->pluck('email')->map(fn ($e) => strtolower($e))->unique();
        $returning = $emails->filter(fn ($e) => Order::whereRaw('lower(email) = ?', [$e])->where('placed_at', '<', $from)->exists());
        $byCustomer = $live->groupBy(fn ($o) => strtolower($o->email));
        $freq = $byCustomer->map->count()->countBy(fn ($n) => $n >= 5 ? '5+' : (string) $n)->sortKeys();

        return [
            'kpis' => [
                ['Active customers', $emails->count(), 'int', null], ['New customers', $emails->count() - $returning->count(), 'int', null], ['Returning customers', $returning->count(), 'int', null],
                ['Registered accounts (all time)', User::doesntHave('roles')->count(), 'int', null],
                ['Average spend per customer', $emails->count() ? $live->sum('total') / $emails->count() : 0, 'money', null],
                ['Guest orders', $live->whereNull('user_id')->count(), 'int', null],
            ],
            'charts' => [
                ['id' => 'mix', 'type' => 'doughnut', 'title' => 'New vs returning', 'labels' => [__('New'), __('Returning')], 'datasets' => [['label' => 'Customers', 'data' => [$emails->count() - $returning->count(), $returning->count()]]]],
                ['id' => 'freq', 'type' => 'bar', 'title' => 'Orders per customer', 'labels' => $freq->keys()->all(), 'datasets' => [['label' => 'Customers', 'data' => $freq->values()->all()]]],
            ],
            'tables' => [
                $this->table('top', 'Top customers by spend', ['Customer', 'Email', 'Phone', 'Orders', 'Spent', 'Last order'], ['text', 'text', 'text', 'int', 'money', 'text'],
                    $byCustomer->map(fn ($g) => [$g->first()->name, $g->first()->email, $g->first()->phone, $g->count(), (float) $g->sum('total'), $this->local($g->max('placed_at'), $tz)->format('Y-m-d')])->sortByDesc(4)->take(50)->values()->all()),
                $this->table('city', 'Customers by city', ['City', 'Customers', 'Orders', 'Revenue'], ['text', 'int', 'int', 'money'],
                    $live->groupBy(fn ($o) => $o->city ?: __('Pickup / unknown'))->map(fn ($g, $c) => [$c, $g->pluck('email')->map('strtolower')->unique()->count(), $g->count(), (float) $g->sum('total')])->sortByDesc(3)->take(25)->values()->all()),
                $this->table('signups', 'New accounts by day', ['Date', 'Accounts'], ['text', 'int'],
                    User::doesntHave('roles')->whereBetween('created_at', [$from, $to])->get()->groupBy(fn ($u) => $this->local($u->created_at, $tz)->format('Y-m-d'))->map(fn ($g, $d) => [$d, $g->count()])->sortKeysDesc()->values()->all()),
                $this->table('lapsed', 'Customers who have not returned (90+ days)', ['Customer', 'Email', 'Orders', 'Spent', 'Last order'], ['text', 'text', 'int', 'money', 'text'],
                    Order::whereNotIn('status', ['cancelled', 'refunded'])->get()->groupBy(fn ($o) => strtolower($o->email))->filter(fn ($g) => $g->max('placed_at')->lt(now()->subDays(90)))
                        ->map(fn ($g) => [$g->first()->name, $g->first()->email, $g->count(), (float) $g->sum('total'), $this->local($g->max('placed_at'), $tz)->format('Y-m-d')])->sortByDesc(3)->take(50)->values()->all()),
            ],
        ];
    }

    private function inventory(Carbon $from, Carbon $to, string $tz): array
    {
        $products = Product::with('variants', 'category')->where('track_stock', true)->get();
        $rows = $products->map(function ($p) {
            $qty = $p->totalStock();
            $cost = (float) ($p->cost ?? 0);

            return ['p' => $p, 'qty' => $qty, 'cost_value' => $qty * $cost, 'retail_value' => $qty * (float) ($p->isVariable() && $p->variants->isNotEmpty() ? $p->variants->min('price') : $p->price)];
        });
        $moves = StockMovement::whereBetween('created_at', [$from, $to])->get();

        return [
            'kpis' => [
                ['Stock value (at cost)', (float) $rows->sum('cost_value'), 'money', null], ['Stock value (at price)', (float) $rows->sum('retail_value'), 'money', null],
                ['Low stock', $rows->filter(fn ($r) => $r['p']->isLowStock() && $r['qty'] > 0)->count(), 'int', null], ['Out of stock', $rows->where('qty', '<=', 0)->count(), 'int', null],
                ['Units on hand', (int) $rows->sum('qty'), 'int', null],
                ['Batches expiring in 30 days', ProductBatch::where('qty', '>', 0)->whereBetween('expires_at', [now()->toDateString(), now()->addDays(30)->toDateString()])->count(), 'int', null],
            ],
            'charts' => [
                ['id' => 'reason', 'type' => 'doughnut', 'title' => 'Stock movements by reason', 'labels' => $moves->pluck('reason')->unique()->values()->all(), 'datasets' => [['label' => 'Movements', 'data' => $moves->pluck('reason')->unique()->values()->map(fn ($r) => $moves->where('reason', $r)->count())->all()]]],
            ],
            'tables' => [
                $this->table('low', 'Low and out-of-stock products', ['Product', 'SKU', 'Category', 'On hand', 'Alert level'], ['text', 'text', 'text', 'int', 'int'],
                    $rows->filter(fn ($r) => $r['p']->isLowStock())->sortBy('qty')->map(fn ($r) => [$r['p']->name, $r['p']->sku ?? '', $r['p']->category?->name ?? '—', $r['qty'], $r['p']->low_stock_threshold])->values()->all()),
                $this->table('valuation', 'Stock valuation', ['Product', 'On hand', 'Cost value', 'Price value'], ['text', 'int', 'money', 'money'],
                    $rows->sortByDesc('cost_value')->take(100)->map(fn ($r) => [$r['p']->name, $r['qty'], $r['cost_value'], $r['retail_value']])->values()->all()),
                $this->table('expiry', 'Batches expiring soon (or expired)', ['Product', 'Batch', 'Expires', 'Qty'], ['text', 'text', 'text', 'int'],
                    ProductBatch::with('product')->where('qty', '>', 0)->whereNotNull('expires_at')->where('expires_at', '<=', now()->addDays(60)->toDateString())->orderBy('expires_at')->limit(200)->get()->map(fn ($b) => [$b->product?->name ?? '—', $b->batch_no, Carbon::parse($b->expires_at)->format('Y-m-d'), $b->qty])->all()),
                $this->table('movements', 'Stock movements in period', ['When', 'Product', 'Change', 'Balance', 'Reason', 'By'], ['text', 'text', 'int', 'int', 'text', 'text'],
                    StockMovement::with('product', 'user')->whereBetween('created_at', [$from, $to])->latest('id')->limit(300)->get()->map(fn ($m) => [$this->local($m->created_at, $tz)->format('Y-m-d H:i'), $m->product?->name ?? '—', $m->change, $m->balance, $m->reason, $m->user?->name ?? '—'])->all()),
            ],
        ];
    }

    private function finance(Carbon $from, Carbon $to, string $tz): array
    {
        $live = $this->live($from, $to);
        $all = $this->orders($from, $to)->get();
        $gross = (float) $live->sum('subtotal');
        $discount = (float) $live->sum('discount');
        $refunded = (float) $all->where('status', 'refunded')->sum('total');
        $byDay = $live->groupBy(fn ($o) => $this->local($o->placed_at, $tz)->format('Y-m-d'));

        return [
            'kpis' => [
                ['Revenue (total charged)', (float) $live->sum('total'), 'money', null], ['Subtotal (items)', $gross, 'money', null], ['Discounts given', $discount, 'money', null],
                ['Shipping collected', (float) $live->sum('shipping_cost'), 'money', null], ['Tax collected', (float) $live->sum('tax'), 'money', null], ['Refunded', $refunded, 'money', null],
                ['Paid', (float) $live->where('payment_status', 'paid')->sum('total'), 'money', null], ['Awaiting payment', (float) $live->where('payment_status', '!=', 'paid')->sum('total'), 'money', null],
            ],
            'charts' => [
                ['id' => 'tax', 'type' => 'bar', 'title' => 'Tax collected by day', 'labels' => $byDay->keys()->sort()->values()->all(), 'money' => true, 'datasets' => [['label' => 'Tax', 'data' => $byDay->sortKeys()->map(fn ($g) => round((float) $g->sum('tax'), 2))->values()->all()]]],
            ],
            'tables' => [
                $this->table('daily', 'Daily ledger', ['Date', 'Items', 'Discounts', 'Shipping', 'Tax', 'Total'], ['text', 'money', 'money', 'money', 'money', 'money'],
                    $byDay->sortKeysDesc()->map(fn ($g, $d) => [$d, (float) $g->sum('subtotal'), (float) $g->sum('discount'), (float) $g->sum('shipping_cost'), (float) $g->sum('tax'), (float) $g->sum('total')])->values()->all()),
                $this->table('taxrate', 'Tax by rate', ['Rate %', 'Orders', 'Taxable base', 'Tax'], ['text', 'int', 'money', 'money'],
                    $live->groupBy(fn ($o) => (string) (float) $o->tax_rate)->map(fn ($g, $r) => [$r, $g->count(), (float) $g->sum('total') - (float) $g->sum('tax'), (float) $g->sum('tax')])->values()->all()),
                $this->table('paystatus', 'Payment status', ['Status', 'Orders', 'Amount'], ['text', 'int', 'money'],
                    $all->groupBy('payment_status')->map(fn ($g, $s) => [$s, $g->count(), (float) $g->sum('total')])->values()->all()),
                $this->table('refunds', 'Refunded and cancelled orders', ['Order', 'Date', 'Status', 'Total'], ['text', 'text', 'text', 'money'],
                    $all->whereIn('status', ['refunded', 'cancelled'])->map(fn ($o) => [$o->number, $this->local($o->placed_at, $tz)->format('Y-m-d'), $o->status, (float) $o->total])->values()->all()),
            ],
        ];
    }

    private function operations(Carbon $from, Carbon $to, string $tz): array
    {
        $all = $this->orders($from, $to)->with('rider')->get();
        $done = $all->where('status', 'completed')->filter(fn ($o) => $o->completed_at && $o->placed_at);
        $avg = $done->count() ? $done->avg(fn ($o) => $o->placed_at->diffInMinutes($o->completed_at)) : 0;
        $counts = $all->countBy('status');

        return [
            'kpis' => [
                ['Orders placed', $all->count(), 'int', null], ['Completed', $done->count(), 'int', null],
                ['Average time to complete (min)', round($avg), 'int', null],
                ['Cancel rate %', $all->count() ? round($all->where('status', 'cancelled')->count() / $all->count() * 100, 1) : 0, 'pct', null],
                ['Pickup orders', $all->where('is_pickup', true)->count(), 'int', null], ['Prescription orders', $all->where('requires_prescription', true)->count(), 'int', null],
            ],
            'charts' => [
                ['id' => 'status', 'type' => 'doughnut', 'title' => 'Orders by status', 'labels' => $counts->keys()->map(fn ($s) => ucfirst(str_replace('_', ' ', $s)))->all(), 'datasets' => [['label' => 'Orders', 'data' => $counts->values()->all()]]],
            ],
            'tables' => [
                $this->table('status', 'Status breakdown', ['Status', 'Orders', 'Value'], ['text', 'int', 'money'], $all->groupBy('status')->map(fn ($g, $s) => [ucfirst(str_replace('_', ' ', $s)), $g->count(), (float) $g->sum('total')])->values()->all()),
                $this->table('riders', 'Rider performance', ['Rider', 'Assigned', 'Delivered', 'Avg minutes'], ['text', 'int', 'int', 'int'],
                    $all->whereNotNull('rider_id')->groupBy('rider_id')->map(fn ($g) => [$g->first()->rider?->name ?? '—', $g->count(), $g->where('status', 'completed')->count(), (int) round($g->where('status', 'completed')->filter(fn ($o) => $o->completed_at && $o->rider_assigned_at)->avg(fn ($o) => $o->rider_assigned_at->diffInMinutes($o->completed_at)) ?? 0)])->sortByDesc(2)->values()->all()),
                $this->table('slots', 'Delivery slots used', ['Slot', 'Orders'], ['text', 'int'], $all->whereNotNull('slot_label')->groupBy('slot_label')->map(fn ($g, $l) => [$l, $g->count()])->sortByDesc(1)->values()->all()),
                $this->table('rx', 'Prescription reviews', ['Result', 'Orders'], ['text', 'int'], $all->where('requires_prescription', true)->groupBy(fn ($o) => $o->prescription_status ?: 'pending')->map(fn ($g, $s) => [$s, $g->count()])->values()->all()),
            ],
        ];
    }

    private function marketing(Carbon $from, Carbon $to, string $tz): array
    {
        $live = $this->live($from, $to);
        $ab = AbandonedCheckout::whereBetween('created_at', [$from, $to])->get();
        $reviews = Review::whereBetween('created_at', [$from, $to])->get();

        return [
            'kpis' => [
                ['Orders with a coupon', $live->whereNotNull('coupon_code')->count(), 'int', null], ['Coupon discount given', (float) $live->whereNotNull('coupon_code')->sum('discount'), 'money', null],
                ['Abandoned carts captured', $ab->count(), 'int', null], ['Recovered carts', $ab->whereNotNull('recovered_at')->count(), 'int', null],
                ['Recovery rate %', $ab->count() ? round($ab->whereNotNull('recovered_at')->count() / $ab->count() * 100, 1) : 0, 'pct', null],
                ['Average rating', $reviews->count() ? round($reviews->avg('rating'), 2) : 0, 'dec', null],
            ],
            'charts' => [
                ['id' => 'rating', 'type' => 'bar', 'title' => 'Reviews by rating', 'labels' => ['1★', '2★', '3★', '4★', '5★'], 'datasets' => [['label' => 'Reviews', 'data' => array_map(fn ($r) => $reviews->where('rating', $r)->count(), [1, 2, 3, 4, 5])]]],
            ],
            'tables' => [
                $this->table('coupons', 'Coupon performance', ['Code', 'Orders', 'Discount given', 'Revenue'], ['text', 'int', 'money', 'money'],
                    $live->whereNotNull('coupon_code')->groupBy('coupon_code')->map(fn ($g, $c) => [$c, $g->count(), (float) $g->sum('discount'), (float) $g->sum('total')])->sortByDesc(2)->values()->all()),
                $this->table('coupon-list', 'All coupons', ['Code', 'Used', 'Limit', 'Active'], ['text', 'int', 'text', 'text'],
                    Coupon::orderBy('code')->get()->map(fn ($c) => [$c->code, $c->used_count, $c->usage_limit ?: '∞', $c->is_active ? __('Yes') : __('No')])->all()),
                $this->table('abandoned', 'Abandoned checkouts', ['Email', 'Items', 'Value', 'Reminded', 'Recovered'], ['text', 'int', 'money', 'text', 'text'],
                    $ab->sortByDesc('id')->take(100)->map(fn ($a) => [$a->email, $a->items_count, (float) $a->total, $a->reminded_at ? 'yes' : 'no', $a->recovered_at ? 'yes' : 'no'])->values()->all()),
                $this->table('reviews', 'Review status', ['Status', 'Reviews', 'Average rating'], ['text', 'int', 'dec'],
                    $reviews->groupBy('status')->map(fn ($g, $s) => [$s, $g->count(), round($g->avg('rating'), 2)])->values()->all()),
            ],
        ];
    }

    // ---------------------------------------------------------------- helpers

    private function table(string $key, string $title, array $cols, array $types, array $rows): array
    {
        return compact('key', 'title', 'cols', 'types', 'rows');
    }

    private function orders(Carbon $from, Carbon $to)
    {
        return Order::whereBetween('placed_at', [$from, $to])->orderBy('placed_at');
    }

    private function live(Carbon $from, Carbon $to): Collection
    {
        return $this->orders($from, $to)->get()->reject(fn ($o) => in_array($o->status, ['cancelled', 'refunded'], true))->values();
    }

    private function items(Collection $orders): Collection
    {
        return $orders->isEmpty() ? collect() : OrderItem::whereIn('order_id', $orders->pluck('id'))->get();
    }

    private function local($date, string $tz): Carbon
    {
        return Carbon::parse($date)->timezone($tz);
    }

    private function payLabel(?string $m): string
    {
        return ['cod' => __('Cash on delivery'), 'bank' => __('Bank transfer'), 'stripe' => __('Card (Stripe)')][$m] ?? (string) $m;
    }

    private function change(float|int $now, float|int $before): ?float
    {
        return $before > 0 ? round(($now - $before) / $before * 100, 1) : null;
    }

    private function csv(array $report, string $key, string $type, Carbon $from, Carbon $to): StreamedResponse
    {
        $table = collect($report['tables'])->firstWhere('key', $key);
        abort_unless($table, 404);

        return response()->streamDownload(function () use ($table) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_map('__', $table['cols']));
            foreach ($table['rows'] as $row) {
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v, $row));
            }
            fclose($out);
        }, "{$type}-{$key}-".$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{Carbon, Carbon, string} */
    private function range(Request $request): array
    {
        $tz = setting('store.timezone', config('app.timezone'));
        $data = $request->validate(['range' => ['nullable', 'in:7,30,90,365,custom'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $key = $data['range'] ?? '30';
        if ($key === 'custom' && filled($data['from'] ?? null) && filled($data['to'] ?? null)) {
            $from = Carbon::parse($data['from'], $tz)->startOfDay();
            $to = Carbon::parse($data['to'], $tz)->endOfDay();
            if ($to->lt($from) || $from->diffInDays($to) > 366) {
                [$from, $to, $key] = [now($tz)->subDays(29)->startOfDay(), now($tz)->endOfDay(), '30'];
            }
        } else {
            $key = $key === 'custom' ? '30' : $key;
            $from = now($tz)->subDays((int) $key - 1)->startOfDay();
            $to = now($tz)->endOfDay();
        }

        return [$from->utc(), $to->utc(), $key];
    }
}
