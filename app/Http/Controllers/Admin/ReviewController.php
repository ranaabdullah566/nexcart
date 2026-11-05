<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Support\BusinessProfile;
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

    public function edit(Review $review): View
    {
        $this->guard();

        return view('admin.reviews.edit', ['review' => $review->load('product')]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $this->guard();
        $data = $request->validate(['status' => ['required', Rule::in(Review::STATUSES)], 'reply' => ['nullable', 'string', 'max:1000']]);
        $review->update(['status' => $data['status'], 'reply' => $data['reply'] ?? null]);

        return redirect()->route('admin.reviews.index', ['status' => 'all'])->with('success', __('Review updated'));
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->guard();
        $review->delete();

        return redirect()->route('admin.reviews.index', ['status' => 'all'])->with('success', __('Review deleted'));
    }

    private function guard(): void
    {
        abort_unless(BusinessProfile::hasModule('reviews'), 404);
        $this->authorizeTo('reviews.manage');
    }
}
