<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
  public function index()
{
    return Customer::latest()->paginate(5);
}

public function store(Request $request)
{
    $data = $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:customers',
        'phone' => 'nullable',
        'address' => 'nullable'
    ]);

    return Customer::create($data);
}

public function show($id)
{
    return Customer::findOrFail($id);
}

public function update(Request $request, $id)
{
    $customer = Customer::findOrFail($id);

    $data = $request->validate([
        'name' => 'required',
        'email' => "required|email|unique:customers,email,$id",
        'phone' => 'nullable',
        'address' => 'nullable'
    ]);

    $customer->update($data);

    return $customer;
}

public function destroy($id)
{
    Customer::destroy($id);
    return response()->json(['message' => 'Deleted']);
}
}