<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ManpowerRole;
use Illuminate\Http\Request;

class ManpowerRoleController extends Controller
{
    public function index()
    {
        $roles = ManpowerRole::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120|unique:manpower_roles,name',
        ]);

        $role = ManpowerRole::create($data);

        return response()->json([
            'message' => 'Role created.',
            'role' => $role,
        ], 201);
    }

    public function destroy(ManpowerRole $manpowerRole)
    {
        $manpowerRole->delete();

        return response()->json(['message' => 'Role deleted.']);
    }
}
