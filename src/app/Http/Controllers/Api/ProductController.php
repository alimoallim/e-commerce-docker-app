<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Display a paginated list of products.
     */
    public function index(): JsonResponse
    {
        $products = Product::latest()->paginate(10);

        return response()->json($products);
    }

    /**
     * Store a new product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $product = Product::create($validated);

            return response()->json($product, 201);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to create product.'
            ], 500);
        }
    }

    /**
     * Display a single product.
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        return response()->json($product);
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $product->update($validated);

            return response()->json($product);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to update product.'
            ], 500);
        }
    }

    /**
     * Delete a product.
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        try {
            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully.'
            ]);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to delete product.'
            ], 500);
        }
    }
}