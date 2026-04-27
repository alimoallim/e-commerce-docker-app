<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * List payments with relationships
     */
    public function index(): JsonResponse
    {
        $payments = Payment::with('order.customer')
            ->latest()
            ->paginate(10);

        return response()->json($payments);
    }

    /**
     * Store a payment (supports partial payments)
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'amount'   => ['required', 'numeric', 'min:0.01'],
            'method'   => ['required', 'string', 'max:50'],
            'status'   => ['nullable', 'string'] // optional now
        ]);

        try {
            $payment = DB::transaction(function () use ($data) {

                // 🔒 Lock order to prevent race conditions
                $order = Order::lockForUpdate()
                    ->with('payments')
                    ->findOrFail($data['order_id']);

                $total = $order->total;
                $paid  = $order->payments->sum('amount');
                $remaining = $total - $paid;

                // 🚫 Prevent overpayment
                if ($data['amount'] > $remaining) {
                    throw ValidationException::withMessages([
                        'amount' => ['Payment exceeds remaining balance']
                    ]);
                }

                // 🧾 Determine payment status
                $newPaidTotal = $paid + $data['amount'];

                $status = match (true) {
                    $newPaidTotal == 0 => 'unpaid',
                    $newPaidTotal < $total => 'partial',
                    $newPaidTotal == $total => 'paid',
                };

                // 💾 Create payment
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'amount'   => $data['amount'],
                    'method'   => $data['method'],
                    'status'   => $data['status'] ?? 'completed',
                    'paid_at'  => now()
                ]);

                // 📊 Update order payment status (optional but recommended)
                $order->update([
                    'payment_status' => $status
                ]);

                return $payment->load('order.customer');
            });

            return response()->json($payment, 201);

        } catch (ValidationException $e) {
            throw $e;

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Payment processing failed'
            ], 500);
        }
    }

    /**
     * Show payment
     */
    public function show(int $id): JsonResponse
    {
        $payment = Payment::with('order.customer')
            ->findOrFail($id);

        return response()->json($payment);
    }

    /**
     * Delete payment (with rollback logic)
     */
    public function destroy(int $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {

            $payment = Payment::with('order.payments')->findOrFail($id);
            $order = $payment->order;

            // Remove payment
            $payment->delete();

            // Recalculate totals
            $paid = $order->payments()->sum('amount');
            $total = $order->total;

            $status = match (true) {
                $paid == 0 => 'unpaid',
                $paid < $total => 'partial',
                $paid == $total => 'paid',
            };

            $order->update([
                'payment_status' => $status
            ]);

            return response()->json([
                'message' => 'Payment removed successfully'
            ]);
        });
    }
}