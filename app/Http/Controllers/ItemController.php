<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Services\DynamicFieldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = $request->user()->workspaces()->firstOrFail();
        $items = $workspace->items()->with('category')->latest()->get();

        return view('items.index', compact('workspace', 'items'));
    }

    public function create(Request $request): View
    {
        $workspace = $request->user()->workspaces()->firstOrFail();
        $categories = Category::query()->where('is_active', true)->orderBy('sort_order')->get();
        $selectedCategory = null;

        if ($request->filled('category')) {
            $selectedCategory = Category::query()
                ->whereKey($request->integer('category'))
                ->where('is_active', true)
                ->with('fields')
                ->firstOrFail();
        }

        return view('items.create', compact('workspace', 'categories', 'selectedCategory'));
    }

    public function store(StoreItemRequest $request, DynamicFieldService $dynamicFields): RedirectResponse
    {
        $workspace = $request->user()->workspaces()->firstOrFail();
        $category = Category::query()->whereKey($request->integer('category_id'))->with('fields')->firstOrFail();
        $validated = $request->validated();

        $item = DB::transaction(function () use ($workspace, $category, $validated, $dynamicFields): Item {
            $item = $workspace->items()->create([
                'category_id' => $category->id,
                'name' => $validated['name'],
                'brand' => $validated['brand'] ?? null,
                'model' => $validated['model'] ?? null,
                'serial_number' => $validated['serial_number'] ?? null,
                'purchase_date' => $validated['purchase_date'] ?? null,
                'purchase_price_minor' => isset($validated['purchase_price'])
                    ? (int) round((float) $validated['purchase_price'] * 100)
                    : null,
                'currency' => $workspace->currency,
                'seller_name' => $validated['seller_name'] ?? null,
                'return_days' => $validated['return_days'] ?? null,
                'warranty_months' => $validated['warranty_months'] ?? null,
                'status' => 'active',
            ]);

            $dynamicFields->persist($item, $category, $validated['dynamic'] ?? []);

            return $item;
        });

        return redirect()->route('items.show', $item)->with('status', 'Product added to your cabinet.');
    }

    public function show(Item $item): View
    {
        Gate::authorize('view', $item);
        $item->load(['category', 'values.categoryField', 'documents', 'resolutionCases']);

        return view('items.show', compact('item'));
    }
}
