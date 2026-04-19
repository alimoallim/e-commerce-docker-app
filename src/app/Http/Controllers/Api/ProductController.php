<?php 
use App\Models\Product;
use Illuminate\Http\Request;

public function index()
{
    return Product::all();
}

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required',
        'quantity' => 'required|integer',
        'price' => 'required|numeric',
        'description' => 'nullable'
    ]);

    return Product::create($validated);
}

public function show($id)
{
    return Product::findOrFail($id);
}

public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);
    $product->update($request->all());

    return $product;
}

public function destroy($id)
{
    Product::destroy($id);
    return response()->json(['message' => 'Deleted']);
}