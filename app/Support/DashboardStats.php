<?php

namespace App\Support;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrescriptionRequest;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Everything the admin dashboard shows, computed on the store's clock. All sums exclude cancelled / refunded orders. */
class DashboardStats
{
    private string $tz;

    private CarbonImmutable $today;

    public function __construct()
    {
        $this->tz = setting('store.timezone', config('app.timezone'));
        $this->today = CarbonImmutable::now($this->tz)->startOfDay();
    }

    private function live()
    {
        return Order::whereNotIn('status', ['cancelled', 'refunded']);
    }

    private function utc(CarbonImmutable $d): CarbonImmutable
    {
        return $d->utc();
    }

    private static function pct(float $now, float $before): ?float
    {
        if ($before <= 0) {
            return $now > 0 ? null : 0.0;   // null = "new" (nothing to compare with)
        }

        return round(($now - $before) / $before * 100, 1);
    }

    /** Sales numbers, charts and rankings. */
    public function sales(): array
    {
        $t = $this->today;
        $from90 = $t->subDays(89);
        $orders = $this->live()->where('placed_at', '>=', $this->utc($from90->subDays(0)))->get(['id', 'user_id', 'total', 'placed_at', 'payment_method', 'city']);
        $local = fn ($o) => CarbonImmutable::parse($o->placed_at)->setTimezone($this->tz);
        $orders->each(fn ($o) => $o->setAttribute('local', $local($o)));

        $days = collect(range(89, 0))->map(fn ($i) => $t->subDays($i));
        $byDay = $orders->groupBy(fn ($o) => $o->local->format('Y-m-d'));
        $series = [
            'labels' => $days->map->format('d M')->all(),
            'revenue' => $days->map(fn ($d) => round((float) $byDay->get($d->format('Y-m-d'), collect())->sum('total'), 2))->all(),
            'orders' => $days->map(fn ($d) => $byDay->get($d->format('Y-m-d'), collect())->count())->all(),
        ];

        $in = fn (CarbonImmutable $a, ?CarbonImmutable $b = null) => $orders->filter(fn ($o) => $o->local >= $a && ($b === null || $o->local < $b));
        $today = $in($t);
        $yesterday = $in($t->subDay(), $t);
        $last30 = $in($t->subDays(29));
        $prev30 = $in($t->subDays(59), $t->subDays(29));

        $sum = fn (Collection $c) => round((float) $c->sum('total'), 2);

        // 30-day breakdowns
        $statusRows = Order::where('placed_at', '>=', $this->utc($t->subDays(29)))->get(['status'])->countBy('status');
        $payments = $last30->groupBy('payment_method')->map(fn ($g) => ['n' => $g->count(), 'sum' => round((float) $g->sum('total'), 2)])->sortByDesc('sum');
        $hours = array_fill(0, 24, 0);
        $weekdays = array_fill(0, 7, 0);
        foreach ($orders as $o) {   // 90 days gives a steadier picture than 30
            $hours[(int) $o->local->format('G')]++;
            $weekdays[(int) $o->local->format('N') - 1]++;
        }

        $ids = $last30->pluck('id');
        $items = $ids->isEmpty() ? collect() : OrderItem::whereIn('order_id', $ids)->get(['product_id', 'name', 'qty', 'total']);
        $top = $items->groupBy(fn ($i) => $i->product_id ?? 'x:'.$i->name)->map(fn ($g) => ['name' => $g->first()->name, 'qty' => (int) $g->sum('qty'), 'total' => round((float) $g->sum('total'), 2)])
            ->sortByDesc('qty')->take(7)->values();
        $catOf = Product::whereIn('id', $items->pluck('product_id')->filter()->unique())->pluck('category_id', 'id');
        $catNames = Category::withTrashed()->pluck('name', 'id');
        $byCategory = $items->groupBy(fn ($i) => $catNames[$catOf[$i->product_id] ?? null] ?? __('Uncategorised'))
            ->map(fn ($g) => round((float) $g->sum('total'), 2))->sortDesc()->take(6);
        $cities = $last30->groupBy(fn ($o) => trim((string) $o->city) ?: __('Unknown'))->map->count()->sortDesc()->take(6);

        // repeat customers: share of last-30-day orders from people with an earlier order
        $buyers = $last30->pluck('user_id')->filter()->unique();
        $repeat = $buyers->isEmpty() ? 0 : Order::whereIn('user_id', $buyers)->where('placed_at', '<', $this->utc($t->subDays(29)))->distinct('user_id')->count('user_id');

        return [
            'kpi' => [
                'revenue_today' => $sum($today), 'revenue_today_delta' => self::pct($sum($today), $sum($yesterday)),
                'orders_today' => $today->count(), 'orders_today_delta' => self::pct($today->count(), $yesterday->count()),
                'revenue_30' => $sum($last30), 'revenue_30_delta' => self::pct($sum($last30), $sum($prev30)),
                'orders_30' => $last30->count(), 'orders_30_delta' => self::pct($last30->count(), $prev30->count()),
                'avg' => $last30->count() ? round($sum($last30) / $last30->count(), 2) : 0.0,
                'avg_delta' => self::pct($last30->count() ? $sum($last30) / $last30->count() : 0, $prev30->count() ? $sum($prev30) / $prev30->count() : 0),
                'open' => Order::whereNotIn('status', ['completed', 'cancelled', 'refunded'])->count(),
                'unpaid' => Order::where('payment_status', 'unpaid')->whereNotIn('status', ['cancelled', 'refunded'])->count(),
                'cancel_rate' => ($all = Order::where('placed_at', '>=', $this->utc($t->subDays(29)))->count()) ? round(Order::where('placed_at', '>=', $this->utc($t->subDays(29)))->whereIn('status', ['cancelled', 'refunded'])->count() / $all * 100, 1) : 0.0,
                'repeat_buyers' => $repeat, 'buyers' => $buyers->count(),
            ],
            'series' => $series,
            'status' => $statusRows->all(),
            'payments' => $payments->map(fn ($v, $k) => ['label' => (string) $k, 'n' => $v['n'], 'sum' => $v['sum']])->values()->all(),
            'hours' => array_values($hours), 'weekdays' => array_values($weekdays),
            'topProducts' => $top->all(),
            'categories' => ['labels' => $byCategory->keys()->values()->all(), 'values' => $byCategory->values()->all()],
            'cities' => ['labels' => $cities->keys()->values()->all(), 'values' => $cities->values()->all()],
        ];
    }

    public function customers(): array
    {
        $t = $this->today;
        $new = User::whereDoesntHave('roles')->where('created_at', '>=', $this->utc($t->subDays(59)))->get(['created_at']);
        $days = collect(range(29, 0))->map(fn ($i) => $t->subDays($i));
        $by = $new->groupBy(fn ($u) => CarbonImmutable::parse($u->created_at)->setTimezone($this->tz)->format('Y-m-d'));
        $last = $new->filter(fn ($u) => CarbonImmutable::parse($u->created_at) >= $this->utc($t->subDays(29)))->count();

        return [
            'total' => User::whereDoesntHave('roles')->count(), 'new30' => $last, 'new30_delta' => self::pct($last, $new->count() - $last),
            'labels' => $days->map->format('d M')->all(), 'values' => $days->map(fn ($d) => $by->get($d->format('Y-m-d'), collect())->count())->all(),
            'top' => Order::selectRaw('user_id, name, count(*) as n, sum(total) as spent')->whereNotNull('user_id')->whereNotIn('status', ['cancelled', 'refunded'])
                ->groupBy('user_id', 'name')->orderByDesc('spent')->limit(5)->get()->map(fn ($r) => ['name' => $r->name, 'n' => (int) $r->n, 'spent' => (float) $r->spent])->all(),
        ];
    }

    public function reviews(): array
    {
        $dist = Review::where('status', 'approved')->selectRaw('rating, count(*) n')->groupBy('rating')->pluck('n', 'rating');
        $count = (int) $dist->sum();

        return [
            'dist' => collect(range(5, 1))->map(fn ($r) => (int) ($dist[$r] ?? 0))->all(), 'count' => $count,
            'avg' => $count ? round(collect($dist)->map(fn ($n, $r) => $n * $r)->sum() / $count, 1) : 0.0,
            'pending' => Review::where('status', 'pending')->count(),
            'recent' => Review::with('product:id,name')->latest('id')->limit(4)->get(),
        ];
    }

    /** Things waiting for a person. [label, count, route, icon, tone] — only counts above zero are shown by the view. */
    public function attention(): array
    {
        $u = auth()->user();
        $out = [];
        if ($u->can('orders.view')) {
            $out[] = [__('Orders to confirm'), Order::where('status', 'pending')->count(), route('admin.orders.index', ['status' => 'pending']), 'receipt-cutoff', 'warn'];
        }
        if ($u->can('prescriptions.view') && BusinessProfile::hasModule('prescriptions')) {
            $out[] = [__('Prescriptions to check'), Order::where('requires_prescription', true)->where('prescription_status', 'pending')->count(), route('admin.prescriptions.index'), 'prescription2', 'bad'];
            $out[] = [__('Prescription requests'), PrescriptionRequest::where('status', 'new')->count(), route('admin.prescription-requests.index'), 'camera-fill', 'warn'];
        }
        if ($u->can('storefront.manage')) {
            $out[] = [__('New messages'), ContactMessage::where('status', 'new')->count(), route('admin.messages.index'), 'envelope-paper-fill', 'vio'];
        }
        if ($u->can('reviews.manage') && BusinessProfile::hasModule('reviews')) {
            $out[] = [__('Reviews to approve'), Review::where('status', 'pending')->count(), route('admin.reviews.index'), 'chat-heart-fill', 'vio'];
        }

        return $out;
    }

    public function stock(): array
    {
        $products = Product::with('variants')->where('track_stock', true)->get();
        $low = $products->filter->isLowStock();

        return [
            'tracked' => $products->count(),
            'out' => $low->filter(fn ($p) => $p->totalStock() <= 0)->count(),
            'low' => $low->count(),
            'list' => $low->sortBy(fn ($p) => $p->totalStock())->take(6)->values(),
        ];
    }
}
