<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSync;
use App\Services\ProductSheetSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Upload the weekly product sheet: new SKUs are added, known SKUs updated, identical rows skipped. */
class ProductSyncController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('products.view');

        return view('admin.product-sync.index', [
            'runs' => ProductSync::with('user:id,name')->latest('id')->limit(30)->get(),
            'total' => Product::where('source', 'sheet')->count(),
            'last' => ProductSync::latest('id')->first(),
        ]);
    }

    public function show(ProductSync $sync): View
    {
        $this->authorizeTo('products.view');

        return view('admin.product-sync.show', ['run' => $sync->load('user:id,name')]);
    }

    public function store(Request $request, ProductSheetSync $service): RedirectResponse
    {
        $this->authorizeTo('products.create');
        $request->validate(['file' => ['required', 'file', 'max:20480', 'extensions:xlsx,csv']]);

        $file = $request->file('file');
        $run = $service->run($file->getRealPath(), strtolower($file->getClientOriginalExtension()), $file->getClientOriginalName(), [
            'update_existing' => $request->boolean('update_existing'),
            'use_active' => $request->boolean('use_active'),
            'deactivate_missing' => $request->boolean('deactivate_missing'),
        ], $request->user());

        return redirect()->route('admin.product-sync.show', $run)->with('status', __('Sync finished.'));
    }

    public function export(ProductSheetSync $service)
    {
        $this->authorizeTo('products.view');
        $path = tempnam(sys_get_temp_dir(), 'ps').'.xlsx';
        $service->export($path);

        return response()->download($path, 'products-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend(true);
    }
}
