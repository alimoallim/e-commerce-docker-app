<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // -----------------------------
        // 💰 PAYMENTS SUMMARY
        // -----------------------------
        $totalRevenue = Payment::sum('amount');

        $paymentsByMethod = Payment::select('method', DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->get();

        $recentPayments = Payment::with('order.customer')
            ->latest()
            ->take(5)
            ->get();

        // -----------------------------
        // 📦 ORDERS SUMMARY
        // -----------------------------
        $totalOrders = Order::count();
        $totalOrderValue = Order::sum('total');

        $ordersPaid = Order::where('payment_status', 'paid')->count();
        $ordersPartial = Order::where('payment_status', 'partial')->count();
        $ordersUnpaid = Order::where('payment_status', 'unpaid')->count();

        $recentOrders = Order::with('customer')
            ->latest()
            ->take(5)
            ->get();

        // Outstanding balance
        $totalPaid = Payment::sum('amount');
        $outstanding = $totalOrderValue - $totalPaid;

        // -----------------------------
        // 👥 CUSTOMERS SUMMARY
        // -----------------------------
        $totalCustomers = Customer::count();

        $topCustomers = Customer::withSum('orders', 'total')
            ->orderByDesc('orders_sum_total')
            ->take(5)
            ->get();

        // -----------------------------
        // 🛍️ PRODUCTS SUMMARY
        // -----------------------------
        $totalProducts = Product::count();

        $lowStockProducts = Product::where('quantity', '<', 10)
            ->take(5)
            ->get();

        $inventoryValue = Product::select(DB::raw('SUM(price * quantity) as total'))
            ->value('total');

        // -----------------------------
        // 📊 FINAL RESPONSE
        // -----------------------------
        return response()->json([
            'metrics' => [
                'revenue' => (float) $totalRevenue,
                'orders' => $totalOrders,
                'customers' => $totalCustomers,
                'products' => $totalProducts,
                'outstanding' => (float) $outstanding,
            ],

            'orders' => [
                'total_value' => (float) $totalOrderValue,
                'paid' => $ordersPaid,
                'partial' => $ordersPartial,
                'unpaid' => $ordersUnpaid,
                'recent' => $recentOrders,
            ],

            'payments' => [
                'total' => (float) $totalRevenue,
                'by_method' => $paymentsByMethod,
                'recent' => $recentPayments,
            ],

            'customers' => [
                'total' => $totalCustomers,
                'top' => $topCustomers,
            ],

            'products' => [
                'total' => $totalProducts,
                'low_stock' => $lowStockProducts,
                'inventory_value' => (float) $inventoryValue,
            ],
        ]);
    }
}