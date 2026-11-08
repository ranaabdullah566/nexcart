<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCheckout;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeTo('reports.view');
        [$from, $to, $key] = $this->range($request);

        $orders = $this->orders($from, $to)->get();
        $live = $orders->reject(fn ($o) => in_array($o->status, ['cancelled', 'refunded'], true));
        $span = $from->diffInDays($to) + 1;
        $prevFrom = $from->copy()->subDays($span);
        $prev = $this->orders($prevFrom, $from->copy()->subSecond())->get()->reject(fn ($o) => in_array($o->status, ['cancelled', 'refunded'], true));

        $revenue = (float) $live->sum('total');
        $emails = $live->pluck('email')->map(fn ($e) => strtolower($e))->unique();
        $returning = $emails->filter(fn ($e) => Order::whereRaw('lower(email) = ?', [$e])->where('placed_at', '<', $from)->exists())->count();

        $tz = setting('store.timezone', config('app.timezone'));
        $days = collect(range(0, $span - 1))->map(fn ($i) => $from->copy()->timezone($tz)->addDays($i)->format('Y-m-d'));
        $byDay = $live->groupBy(fn ($o) => $o->placed_at->copy()->timezone($tz)->format('Y-m-d'));

        $items = OrderItem::whereIn('order_id', $live->pluck('id'))->get();

        return view('admin.reports.index', [
            'key' => $key, 'from' => $from, 'to' => $to,
            'kpi' => [
                'revenue' => $revenue, 'orders' => $live->count(), 'aov' => $live->count() ? $revenue / $live->count() : 0.0,
                'items' => (int) $items->sum('qty'), 'customers' => $emails->count(), 'new' => $emails->count() - $returning,
                'repeat' => $emails->count() ? round($returning / $emails->count() * 100) : 0,
                'revenue_change' => $this->change($revenue, (float) $prev->sum('total')), 'orders_change' => $this->change($live->count(), $prev->count()),
            ],
            'chart' => ['labels' => $days->map(fn ($d) => Carbon::parse($d)->format('d M'))->all(), 'revenue' => $days->map(fn ($d) => round((float) ($byDay[$d] ?? collect())->sum('total'), 2))->all(), 'orders' => $days->map(fn ($d) => ($byDay[$d] ?? collect())->count())->all()],
            'statuses' => $orders->countBy('status')->all(),
            'top' => $items->groupBy(fn ($i) => $i->product_id ?: 'n:'.$i->name)->map(fn (Collection $g) => ['name' => $g->first()->name, 'qty' => (int) $g->sum('qty'), 'revenue' => (float) $g->sum('total')])->sortByDesc('revenue')->take(10)->values(),
            'categories' => $this->byCategory($items),
            'payments' => $live->groupBy('payment_method')->map(fn ($g) => ['orders' => $g->count(), 'total' => (float) $g->sum('total')])->all(),
            'coupons' => $live->whereNotNull('coupon_code')->groupBy('coupon_code')->map(fn ($g) => ['orders' => $g->count(), 'discount' => (float) $g->sum('discount')])->all(),
            'abandoned' => [
                'captured' => AbandonedCheckout::whereBetween('created_at', [$from, $to])->count(),
                'reminded' => AbandonedCheckout::whereBetween('created_at', [$from, $to])->whereNotNull('reminded_at')->count(),
                'recovered' => AbandonedCheckout::whereBetween('created_at', [$from, $to])->whereNotNull('recovered_at')->count(),
                'open_value' => (float) AbandonedCheckout::whereBetween('created_at', [$from, $to])->whereNull('recovered_at')->sum('total'),
                'rows' => AbandonedCheckout::latest('id')->limit(30)->get(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeTo('reports.view');
        [$from, $to] = $this->range($request);
        $tz = setting('store.timezone', config('app.timezone'));

        return response()->streamDownload(function () use ($from, $to, $tz) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['number', 'placed_at', 'customer', 'email', 'phone', 'status', 'payment_status', 'payment_method', 'shipping', 'city', 'coupon', 'subtotal', 'discount', 'shipping_cost', 'tax', 'total']);
            $this->orders($from, $to)->chunk(500, function ($chunk) use ($out, $tz) {
                foreach ($chunk as $o) {
                    fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v, [
                        $o->number, $o->placed_at?->copy()->timezone($tz)->format('Y-m-d H:i'), $o->name, $o->email, $o->phone, $o->status, $o->payment_status, $o->payment_method,
                        $o->shipping_method, $o->city, $o->coupon_code, $o->subtotal, $o->discount, $o->shipping_cost, $o->tax, $o->total,
                    ]));
                }
            });
            fclose($out);
        }, 'orders-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function orders(Carbon $from, Carbon $to)
    {
        return Order::whereBetween('placed_at', [$from, $to])->orderBy('placed_at');
    }

    /** @return array{Carbon, Carbon, string} start / end of the period in UTC (day boundaries follow the store's timezone) and which preset was used */
    private function range(Request $request): array
    {
        $tz = setting('store.timezone', config('app.timezone'));
        $data = $request->validate(['range' => ['nullable', 'in:7,30,90,custom'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
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

    private function change(float|int $now, float|int $before): ?float
    {
        return $before > 0 ? round(($now - $before) / $before * 100, 1) : null;
    }

    private function byCategory(Collection $items): array
    {
        $cats = Product::withTrashed()->whereIn('id', $items->pluck('product_id')->filter())->with('category')->get()->keyBy('id');

        return $items->groupBy(fn ($i) => $cats[$i->product_id]->category?->name ?? __('Uncategorised'))
            ->map(fn ($g) => (float) $g->sum('total'))->sortDesc()->take(8)->all();
    }
}
