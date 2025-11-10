<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $worker = Worker::where('name', $request->name)->first();

            if (!$worker || !Hash::check($request->password, $worker->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid name or password.'
                ], 401);
            }

            // buat token menggunakan Sanctum
            $token = $worker->createToken('api-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful!',
                'role' => (string)$worker->role_id,
                'worker' => $worker,
                'token' => $token,
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:workers,name',
            'role' => 'required|in:1,2,3',
            'email' => 'required|email|unique:workers,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $worker = Worker::create([
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'role_id' => $request->role,
            'email' => $request->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful!',
            'worker' => $worker
        ]);
    }

}
