<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'status'  => 'Success',
            'message' => 'Users retrieved successfully',
            'data'    => User::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Validation errors',
                'data'    => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status'  => 'Success',
            'message' => 'User created successfully',
            'data'    => $user,
        ], 201);
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'User not found',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'Success',
            'message' => 'User found',
            'data'    => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'User not found',
                'data'    => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'Validation errors',
                'data'    => $validator->errors(),
            ], 422);
        }

        if ($request->has('name'))     $user->name     = $request->name;
        if ($request->has('password')) $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status'  => 'Success',
            'message' => 'User updated successfully',
            'data'    => $user,
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status'  => 'Failed',
                'message' => 'User not found',
                'data'    => null,
            ], 404);
        }

        $user->delete();

        return response()->json([
            'status'  => 'Success',
            'message' => 'User deleted successfully',
            'data'    => null,
        ]);
    }
}