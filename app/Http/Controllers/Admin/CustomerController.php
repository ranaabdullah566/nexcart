<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

/** Customers are derived from orders, so guests and account holders both appear. */
class CustomerController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('customers.view');

        $rows = Order::selectRaw("lower(email) as email, max(name) as name, max(phone) as phone, count(*) as orders, sum(case when status in ('cancelled','refunded') then 0 else total end) as spent, max(placed_at) as last_order, max(case when user_id is null then 0 else 1 end) as has_account")
            ->groupByRaw('lower(email)')->orderByDesc('last_order')->limit(2000)->get();

        return view('admin.customers.index', ['customers' => $rows]);
    }
}
