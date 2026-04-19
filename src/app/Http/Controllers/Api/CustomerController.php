<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
    public function index()
    {
        return Customer::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'contact' => 'required',
            'address' => 'required',
        ]);

        return Customer::create($validated);
    }

    public function show($id)
    {
        return Customer::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update($request->all());

        return $customer;
    }

    public function destroy($id)
    {
        Customer::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}