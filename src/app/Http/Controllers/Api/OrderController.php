<?php
namespace App\Http\Controllers\Api;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
class OrderController extends Controller
{
    /**
     * Display a paginated listing of orders.
     */
 public function index(): JsonResponse
{
    $orders = Order::with([
            'customer',
            'items.product',
            'payments' => function ($q) {
                $q->latest();
            }
        ])
        ->latest()
        ->paginate(10);

    return response()->json($orders);
}

    /**
     * Store a newly created order.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $order = DB::transaction(function () use ($validated) {

                $total = 0;
                $seenProducts = [];

                $order = Order::create([
                    'customer_id' => $validated['customer_id'],
                    'total' => 0,
                ]);

                foreach ($validated['items'] as $item) {

                    // Prevent duplicate product entries
                    if (in_array($item['product_id'], $seenProducts)) {
                        throw ValidationException::withMessages([
                            'items' => ['Duplicate product detected in order.']
                        ]);
                    }

                    $seenProducts[] = $item['product_id'];

                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    // Stock validation
                    if ($product->quantity < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'items' => ["Insufficient stock for {$product->name}."]
                        ]);
                    }

                    $price = $product->price;
                    $quantity = $item['quantity'];
                    $subtotal = $price * $quantity;

                    $total += $subtotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $price,
                    ]);

                    // Deduct stock safely
                    $product->decrement('quantity', $quantity);
                }

                $order->update(['total' => $total]);

                return $order->load(['customer', 'items.product']);
            });

            return response()->json($order, 201);

        } catch (ValidationException $e) {
            throw $e; // Laravel will format it automatically

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Order creation failed.'
            ], 500);
        }
    }

    /**
     * Display a specific order.
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with(['customer', 'items.product'])
            ->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Remove an order.
     */
    public function destroy(int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        DB::transaction(function () use ($order) {

            // Restore stock before deleting
            foreach ($order->items as $item) {
                $item->product->increment('quantity', $item->quantity);
            }

            $order->delete();
        });

        return response()->json([
            'message' => 'Order deleted successfully.'
        ]);
    }
}