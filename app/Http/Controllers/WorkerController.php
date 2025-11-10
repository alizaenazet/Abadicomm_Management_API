<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class WorkerController extends Controller
{
    public function index()
    {
        $workers = Worker::with('role')->get()->map(function ($worker) {
            return [
                'id' => (string)$worker->id,
                'name' => $worker->name,
                'password' => $worker->password,
                'role_id' => (string)$worker->role_id,
            ];
        });

        return response()->json($workers);
    }

    public function getKaryawan()
    {
        $karyawan = Worker::where('role_id', 2)->get()->map(function ($worker) {
            return [
                'id' => (string)$worker->id,
                'name' => $worker->name,
                'password' => $worker->password,
            ];
        });

        return response()->json($karyawan);
    }

    public function getSupervisors()
    {
        $supervisors = Worker::where('role_id', 1)->get()->map(function ($worker) {
            return [
                'id' => (string)$worker->id,
                'name' => $worker->name,
            ];
        });

        return response()->json($supervisors);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:workers,name',
            'role_id' => 'required|in:1,2,3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'error' => $validator->errors()->first()
            ], 400);
        }

        $defaultPassword = '12121212';
        $worker = Worker::create([
            'name' => $request->name,
            'password' => Hash::make($defaultPassword),
            'role_id' => $request->role_id,
        ]);

        return response()->json([
            'ok' => true,
            'id' => $worker->id,
            'name' => $worker->name,
        ]);
    }
}
