<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(['status' => 'Success', 'message' => 'Products retrieved', 'data' => Product::all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'        => 'required|unique:products,code',
            'name'        => 'required|string',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'Failed', 'message' => 'Validation errors', 'data' => $validator->errors()], 422);
        }

        $product = Product::create($request->all());
        return response()->json(['status' => 'Success', 'message' => 'Product created', 'data' => $product], 201);
    }

    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['status' => 'Failed', 'message' => 'Product not found', 'data' => null], 404);
        return response()->json(['status' => 'Success', 'message' => 'Product found', 'data' => $product]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['status' => 'Failed', 'message' => 'Product not found', 'data' => null], 404);

        $product->update($request->only(['code','name','description','price','stock']));
        return response()->json(['status' => 'Success', 'message' => 'Product updated', 'data' => $product]);
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['status' => 'Failed', 'message' => 'Product not found', 'data' => null], 404);
        $product->delete();
        return response()->json(['status' => 'Success', 'message' => 'Product deleted', 'data' => null]);
    }

    // Endpoint update stok — dipanggil sinkron dari Order Service
    public function updateStock(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['status' => 'Failed', 'message' => 'Product not found', 'data' => null], 404);

        $validator = Validator::make($request->all(), [
            'product_quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'Failed', 'message' => 'Validation errors', 'data' => $validator->errors()], 422);
        }

        // Simulasi proses lambat (untuk demo perbandingan sinkron vs asinkron)
        sleep(5);

        $product->decrement('stock', $request->product_quantity);
        return response()->json(['status' => 'Success', 'message' => 'Stock updated (sync)', 'data' => $product]);
    }
}