<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function index()
    {
        $offices = Office::orderBy('code')->get(['code', 'name']);
        return response()->json(['data' => $offices]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|digits:4|unique:offices,code',
            'name' => 'nullable|string|max:255',
        ], [
            'code.digits' => 'Office code must be exactly 4 digits.',
        ]);
        $office = Office::create([
            'code' => $data['code'],
            'name' => $data['name'] ?? null,
        ]);
        return response()->json(['data' => $office], 201);
    }

    public function destroy($code)
    {
        $decoded = urldecode($code);
        $office = Office::where('code', strtoupper($decoded))->first();
        if (! $office) {
            return response()->json(['message' => 'Office not found'], 404);
        }

        // safeguard: if instances reference this office, prevent deletion
        if (\App\Models\ItemInstance::where('office_code', $office->code)->exists()) {
            return response()->json(['message' => 'Office code in use by item instances'], 409);
        }

        $office->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
