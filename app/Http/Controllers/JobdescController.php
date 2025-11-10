<?php

namespace App\Http\Controllers;

use App\Models\Jobdesc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobdescController extends Controller
{
    public function index()
    {
        $jobdescs = Jobdesc::all()->map(function ($jobdesc) {
            return [
                'id' => (string)$jobdesc->id,
                'name' => $jobdesc->name,
            ];
        });

        return response()->json($jobdescs);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:jobdescs,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'error' => $validator->errors()->first()
            ], 400);
        }

        $jobdesc = Jobdesc::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'ok' => true,
            'id' => $jobdesc->id,
            'name' => $jobdesc->name,
        ]);
    }
}
