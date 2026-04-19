<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        return Order::with(['customer', 'items.product'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array'
        ]);

        DB::beginTransaction();

        try {
            $total = 0;

            $order = Order::create([
                'customer_id' => $request->customer_id,
                'total' => 0
            ]);

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                $price = $product->price;
                $quantity = $item['quantity'];

                $total += $price * $quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price
                ]);
            }

            $order->update(['total' => $total]);

            DB::commit();

            return $order->load('items.product');

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        return Order::with(['customer', 'items.product'])->findOrFail($id);
    }

    public function destroy($id)
    {
        Order::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}