<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Only list regular users (exclude admin/super-admin). Support both legacy `role` column and spatie roles.
        $query->where(function ($q) {
            $q->where('role', 'user')
              ->orWhereHas('roles', function ($q2) {
                  $q2->where('name', 'user');
              });
        });

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
            ]);

            $data['password'] = Hash::make($data['password']);
            $data['creation_source'] = 'Admin-Created';

            $user = User::create($data);

            // Ensure the basic borrower role exists before assignment.
            $userRole = $this->ensureUserRoleExists();
            $user->assignRole($userRole);

            if ($request->ajax()) {
                $row = view('admin.users._row', compact('user'))->render();
                return response()->json([
                    'success' => true, 
                    'html' => $row, 
                    'id' => $user->id,
                    'message' => 'User created successfully'
                ]);
            }

            return redirect()->route('admin.users.index')->with('success', 'User created.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'message' => 'Please check the form for errors'
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred'
                ], 500);
            }
            throw $e;
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        // If requested via AJAX, return only the form partial so the index modal can load it
        if (request()->ajax()) {
            return view('admin.users._form', ['user' => $user, 'action' => route('admin.users.update', $user), 'method' => 'PATCH']);
        }

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // update user fields (role is intentionally not editable here)
        $user->update($data);
        // Ensure the borrower role exists and is attached when missing.
        if ($user->roles()->count() === 0) {
            $userRole = $this->ensureUserRoleExists();
            $user->assignRole($userRole);
        }

        if ($request->ajax()) {
            $row = view('admin.users._row', compact('user'))->render();
            return response()->json([
                'success' => true, 
                'html' => $row, 
                'id' => $user->id,
                'message' => 'User updated successfully'
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // prevent deleting self
        if (auth()->id() === $user->id) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 403);
            }

            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true, 'id' => $user->id]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    /**
     * Guarantee the default borrower role exists for assignment.
     */
    protected function ensureUserRoleExists(): Role
    {
        $guard = config('auth.defaults.guard', 'web');

        return Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => $guard,
        ]);
    }
}
