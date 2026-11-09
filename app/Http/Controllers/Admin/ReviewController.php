<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Support\BusinessProfile;
use App\Rules\NoMarkup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $this->guard();
        $status = $request->query('status', 'pending');
        abort_unless(in_array($status, [...Review::STATUSES, 'all'], true), 404);

        $q = Review::with('product')->latest('id');
        if ($status !== 'all') {
            $q->where('status', $status);
        }

        return view('admin.reviews.index', ['reviews' => $q->limit(500)->get(), 'status' => $status, 'counts' => Review::selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status')]);
    }

    public function create(Request $request): View
    {
        $this->guard();

        return view('admin.reviews.create', ['products' => Product::orderBy('name')->get(['id', 'name']), 'preProduct' => $request->query('product')]);
    }

    /** Staff can add a review themselves (a testimonial, or one received by phone / WhatsApp). It is shown as written by "name", never as a verified buyer. */
    public function store(Request $request): RedirectResponse
    {
        $this->guard();
        $data = $request->validate($this->contentRules() + ['product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')], 'status' => ['required', Rule::in(Review::STATUSES)], 'reply' => ['nullable', new NoMarkup, 'string', 'max:1000']]);

        $review = new Review($data + ['added_by' => $request->user()->id]);
        if (! empty($data['date'])) {
            $review->created_at = $data['date'];
        }
        $review->save();

        return redirect()->route('admin.reviews.index', ['status' => 'all'])->with('success', __('Review added'));
    }

    public function edit(Review $review): View
    {
        $this->guard();

        return view('admin.reviews.edit', ['review' => $review->load('product')]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $this->guard();
        $rules = ['status' => ['required', Rule::in(Review::STATUSES)], 'reply' => ['nullable', new NoMarkup, 'string', 'max:1000']];
        if ($review->added_by) {   // staff-written reviews can be edited fully; customers' words are never changed
            $rules += $this->contentRules();
        }
        $data = $request->validate($rules);
        $review->fill(['status' => $data['status'], 'reply' => $data['reply'] ?? null]);
        if ($review->added_by) {
            $review->fill(collect($data)->only(['name', 'rating', 'title', 'body'])->all());
            if (! empty($data['date'])) {
                $review->created_at = $data['date'];
            }
        }
        $review->save();

        return redirect()->route('admin.reviews.index', ['status' => 'all'])->with('success', __('Review updated'));
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->guard();
        $review->delete();

        return redirect()->route('admin.reviews.index', ['status' => 'all'])->with('success', __('Review deleted'));
    }

    private function contentRules(): array
    {
        return [
            'name' => ['required', new NoMarkup, 'string', 'max:80'], 'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', new NoMarkup, 'string', 'max:120'], 'body' => ['nullable', new NoMarkup, 'string', 'max:2000'],
            'date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    private function guard(): void
    {
        abort_unless(BusinessProfile::hasModule('reviews'), 404);
        $this->authorizeTo('reviews.manage');
    }
}
