<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    public function index()
    {
        return Payment::with(['order', 'customer'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric',
            'method' => 'required'
        ]);

        return Payment::create($validated);
    }

    public function show($id)
    {
        return Payment::findOrFail($id);
    }

    public function destroy($id)
    {
        Payment::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}