<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhilSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $roleFilter = function ($q) {
            $q->where('role', 'user')
              ->orWhereHas('roles', function ($q2) {
                  $q2->where('name', 'user');
              });
        };

        $query = User::query();

        // Only list regular users (exclude admin/super-admin). Support both legacy `role` column and spatie roles.
        $query->where($roleFilter);

        if ($search = $request->get('q')) {
            $digitsOnlySearch = preg_replace('/\D+/', '', (string) $search);

            $query->where(function ($q) use ($search, $digitsOnlySearch) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                if ($digitsOnlySearch !== '') {
                    $q->orWhere('phone', 'like', "%{$digitsOnlySearch}%");
                }
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $archivedUsers = User::onlyTrashed()
            ->where($roleFilter)
            ->orderByDesc('deleted_at')
            ->get();

        return view('admin.users.index', compact('users', 'archivedUsers'));
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
    public function store(Request $request, PhilSmsService $philSms)
    {
        try {
            $data = $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => ['required', 'string', 'regex:/^\d{7,11}$/'],
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
            ]);

            $data['phone'] = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
            $plainPassword = $data['password'];
            $data['password'] = Hash::make($plainPassword);
            $data['creation_source'] = 'Admin-Created';

            $user = User::create($data);

            // Ensure the basic borrower role exists before assignment.
            $userRole = $this->ensureUserRoleExists();
            $user->assignRole($userRole);

            try {
                $philSms->notifyNewUserAccount($user, $plainPassword);
            } catch (\Throwable $smsException) {
                Log::warning('Failed to dispatch account creation SMS.', [
                    'user_id' => $user->id,
                    'error' => $smsException->getMessage(),
                ]);
            }

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
            return view('admin.users._form', [
                'user' => $user,
                'action' => route('admin.users.update', $user),
                'method' => 'PATCH',
                'ajax' => true,
            ]);
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
            'phone' => ['required', 'string', 'regex:/^\d{7,11}$/'],
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ]);

        $data['phone'] = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));

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
            $archivedRow = view('admin.users._archived_row', ['user' => $user])->render();

            return response()->json([
                'success' => true,
                'id' => $user->id,
                'archivedHtml' => $archivedRow,
                'message' => 'User archived successfully',
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User archived.');
    }

    /**
     * Restore a previously archived user.
     */
    public function restore(Request $request, string $userId)
    {
        $user = User::withTrashed()->findOrFail($userId);

        if (! $user->trashed()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not archived.',
                ], 422);
            }

            return redirect()->route('admin.users.index')->with('error', 'User is not archived.');
        }

        $user->restore();
        $user->refresh();

        if ($user->roles()->count() === 0) {
            $userRole = $this->ensureUserRoleExists();
            $user->assignRole($userRole);
        }

        if ($request->ajax()) {
            $row = view('admin.users._row', ['user' => $user])->render();

            return response()->json([
                'success' => true,
                'id' => $user->id,
                'html' => $row,
                'message' => 'User restored successfully',
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User restored.');
    }

    /**
     * Permanently remove an archived user from storage.
     */
    public function forceDestroy(Request $request, string $userId)
    {
        $user = User::withTrashed()->findOrFail($userId);

        if (auth()->id() === $user->id) {
            $message = 'You cannot delete your own account.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            return redirect()->route('admin.users.index')->with('error', $message);
        }

        if (! $user->trashed()) {
            $message = 'User must be archived before it can be permanently deleted.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->route('admin.users.index')->with('error', $message);
        }

        $user->forceDelete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'id' => $userId,
                'message' => 'User deleted permanently',
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User deleted permanently.');
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
