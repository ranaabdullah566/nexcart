<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use App\Support\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Only people who received an order can review what they bought. */
class ReviewController extends Controller
{
    public function store(Request $request, string $number, string $token, int $item): RedirectResponse
    {
        abort_unless(BusinessProfile::hasModule('reviews'), 404);

        $order = Order::where('number', $number)->firstOrFail();
        abort_unless(hash_equals($order->public_token, $token) && $order->status === 'completed', 404);
        $line = $order->items()->findOrFail($item);
        abort_unless($line->product_id, 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', new \App\Rules\NoMarkup, 'string', 'max:120'],
            'body' => ['nullable', new \App\Rules\NoMarkup, 'string', 'max:2000'],
            'name' => ['required', new \App\Rules\NoMarkup, 'string', 'max:80'],
        ]);

        if (Review::where('order_id', $order->id)->where('product_id', $line->product_id)->exists()) {
            return redirect($order->trackUrl())->with('error', __('You have already reviewed this product.'));
        }

        Review::create($data + [
            'product_id' => $line->product_id, 'order_id' => $order->id, 'user_id' => $order->user_id,
            'status' => setting('growth.reviews_auto', false) ? 'approved' : 'pending',
        ]);

        return redirect($order->trackUrl())->with('success', setting('growth.reviews_auto', false) ? __('Thanks for your review!') : __('Thanks! Your review will appear once the store approves it.'));
    }
}
