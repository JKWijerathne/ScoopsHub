<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ItemController extends Controller
{
    /**
     * Display a listing of items, with optional name search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Item::query();

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $items = $query->get();

        return response()->json([
            'data' => $items,
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $item = Item::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'],
        ]);

        return response()->json([
            'message' => 'Item created successfully',
            'data' => $item,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified item.
     */
    public function show(string $id): JsonResponse
    {
        $item = Item::find($id);

        if (! $item) {
            return response()->json([
                'message' => 'Item not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $item,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified item in storage (PUT for full replace, PATCH for partial).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $item = Item::find($id);

        if (! $item) {
            return response()->json([
                'message' => 'Item not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $isPatch = $request->isMethod('patch');

        if ($isPatch) {
            $validated = $request->validate([
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
                'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
                'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            ]);
        } else {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
                'price' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
                'stock_quantity' => ['required', 'integer', 'min:0'],
            ]);
        }

        $item->update($validated);

        return response()->json([
            'message' => 'Item updated successfully',
            'data' => $item->fresh(),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified item from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $item = Item::find($id);

        if (! $item) {
            return response()->json([
                'message' => 'Item not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $item->delete();

        return response()->json([
            'message' => 'Item deleted successfully',
        ], Response::HTTP_OK);
    }
}
