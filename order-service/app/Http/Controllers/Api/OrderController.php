<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateProductStock;
use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 5]);
    }

    public function index()
    {
        return response()->json(['status' => 'Success', 'message' => 'Orders retrieved', 'data' => Order::all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|string',
            'user_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'status' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'Failed', 'message' => 'Validation errors', 'data' => $validator->errors()], 422);
        }

        $order = Order::create([
            'id' => Str::uuid()->toString(),
            'code' => 'OR-' . Str::random(8),
            'product_id' => $request->product_id,
            'user_id' => $request->user_id,
            'status' => $request->status ?? 'pending',
            'total_price' => $request->total_price,
            'quantity' => $request->quantity,
        ]);

        // Dispatch job asinkron ke RabbitMQ → dikonsumsi Product Service
        UpdateProductStock::dispatch($request->product_id, $request->quantity);

        return response()->json(['status' => 'Success', 'message' => 'Order created (stock update dispatched async)', 'data' => $order], 201);
    }

    public function show($id)
    {
        $order = Order::find($id);
        if (!$order)
            return response()->json(['status' => 'Failed', 'message' => 'Order not found', 'data' => null], 404);

        // Komunikasi sinkron ke service lain
        $productData = null;
        $userData = null;

        try {
            $productRes = $this->http->get(env('PRODUCT_SERVICE_URL') . "/api/products/{$order->product_id}");
            $productData = json_decode($productRes->getBody(), true)['data'];
        } catch (\Exception $e) {
        }

        try {
            $userRes = $this->http->get(env('USER_SERVICE_URL') . "/api/users/{$order->user_id}");
            $userData = json_decode($userRes->getBody(), true)['data'];
        } catch (\Exception $e) {
        }

        return response()->json([
            'status' => 'Success',
            'message' => 'Order found',
            'data' => array_merge($order->toArray(), [
                'product' => $productData,
                'user' => $userData,
            ]),
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order)
            return response()->json(['status' => 'Failed', 'message' => 'Order not found', 'data' => null], 404);

        $order->update($request->only(['status', 'total_price', 'quantity']));
        return response()->json(['status' => 'Success', 'message' => 'Order updated', 'data' => $order]);
    }

    public function destroy($id)
    {
        $order = Order::find($id);
        if (!$order)
            return response()->json(['status' => 'Failed', 'message' => 'Order not found', 'data' => null], 404);
        $order->delete();
        return response()->json(['status' => 'Success', 'message' => 'Order deleted', 'data' => null]);
    }

    public function getByUser($userId)
    {
        $orders = Order::where('user_id', $userId)->get();
        return response()->json(['status' => 'Success', 'message' => 'Orders by user', 'data' => $orders]);
    }
}