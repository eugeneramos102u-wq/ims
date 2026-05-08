<?php

namespace App\Http\Controllers;

use App\Models\DocumentSequence;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Uom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $query = Item::with(['category', 'uom']);

        if ($s = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($s) {
                $q->where('item_code', 'like', "%{$s}%")
                  ->orWhere('item_name', 'like', "%{$s}%");
            });
        }
        if ($cat = $request->integer('category_id')) {
            $query->where('category_id', $cat);
        }
        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }
        $stockLevel = $request->string('stock_level')->trim()->toString();
        if ($stockLevel === 'zero') {
            $query->where('qty_on_hand', '<=', 0);
        } elseif ($stockLevel === 'low') {
            $query->whereColumn('qty_on_hand', '<=', 'reorder_level')->where('qty_on_hand', '>', 0);
        } elseif ($stockLevel === 'in_stock') {
            $query->whereColumn('qty_on_hand', '>', 'reorder_level');
        } elseif ($request->boolean('low_stock')) {
            // legacy: backwards compat with the old "low stock only" checkbox
            $query->whereColumn('qty_on_hand', '<=', 'reorder_level');
        }

        $items = $query->orderBy('item_code')->paginate(20)->withQueryString();
        $categories = ItemCategory::orderBy('category_name')->get();

        return view('items.index', compact('items', 'categories'));
    }

    public function create(): View
    {
        return view('items.form', [
            'item' => new Item(['tax_rate_pct' => 12]),
            'categories' => ItemCategory::orderBy('category_name')->get(),
            'uoms' => Uom::where('status', 'active')->orderBy('uom_code')->get(),
            'nextCode' => DocumentSequence::where('seq_key', 'item')->value('prefix') . str_pad(
                (string) DocumentSequence::where('seq_key', 'item')->value('next_number'),
                DocumentSequence::where('seq_key', 'item')->value('padding'), '0', STR_PAD_LEFT),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['item_code'] = DocumentSequence::next('item');
        Item::create($data);

        return redirect()->route('items.index')->with('flash', 'Item created.');
    }

    public function edit(Item $item): View
    {
        return view('items.form', [
            'item' => $item,
            'categories' => ItemCategory::orderBy('category_name')->get(),
            'uoms' => Uom::where('status', 'active')->orderBy('uom_code')->get(),
            'nextCode' => null,
        ]);
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $item->update($this->validateData($request));

        return redirect()->route('items.index')->with('flash', 'Item updated.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'item_name'       => ['required', 'string', 'max:200'],
            'category_id'     => ['required', 'exists:item_categories,id'],
            'uom_id'          => ['required', 'exists:units_of_measurement,id'],
            'description'     => ['nullable', 'string'],
            'barcode'         => ['nullable', 'string', 'max:60'],
            'unit_cost'       => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'retail_price'    => ['required', 'numeric', 'min:0', 'gte:wholesale_price'],
            'tax_rate_pct'    => ['required', 'numeric', 'min:0', 'max:100'],
            'reorder_level'   => ['required', 'numeric', 'min:0'],
            'status'          => ['required', 'in:active,inactive'],
        ], [
            'retail_price.gte' => 'Retail price must be ≥ wholesale price.',
        ]);
    }
}
