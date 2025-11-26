<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use App\Notifications\RequestNotification;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create()
    {
        return view('auth.login-register');
    }

    /**
     * Handle an incoming registration request.
     *
     * Validation failures are redirected back with the 'register' error bag.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name'   => ['required', 'string', 'max:50', 'regex:/^[A-Za-z\s\-]+$/'],
            'middle_name'  => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z\s\-]*$/'],
            'last_name'    => ['required', 'string', 'max:50', 'regex:/^[A-Za-z\s\-]+$/'],
            'email'        => [
                'required',
                'string',
                'email',
                'max:50',
                'unique:'.User::class.',email',
                'not_regex:/[<>"\s]/',
            ],
            'password'     => [
                'required',
                'confirmed',
                Rules\Password::min(8)->letters()->numbers()->mixedCase()->symbols(),
            ],
            'phone'        => ['nullable', 'string', 'regex:/^\d{7,11}$/'],
            'address'      => ['nullable', 'string', 'max:150', 'regex:/^[A-Za-z0-9\s,\.\-]+$/'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator, 'register')
                ->withInput();
        }

        $user = User::create([
            'first_name'  => $request->input('first_name'),
            'middle_name' => $request->input('middle_name'),
            'last_name'   => $request->input('last_name'),
            'phone'       => preg_replace('/\D/', '', (string) $request->input('phone')),
            'address'     => $request->input('address'),
            'email'       => $request->input('email'),
            'password'    => Hash::make($request->input('password')),
            'role'        => 'user', // default role
            'creation_source' => 'Borrower-Registered',
        ]);

        event(new Registered($user));

        $admins = User::where('role', 'admin')->get();
        if ($admins->isNotEmpty()) {
            $displayName = $user->full_name;
            if (! $displayName) {
                $displayName = trim((string) (($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
            }
            if ($displayName === '') {
                $displayName = $user->email;
            }

            $payload = [
                'type' => 'user_registered',
                'message' => sprintf('%s registered an account.', $displayName),
                'user_id' => $user->id,
                'user_name' => $displayName,
                'email' => $user->email,
                'creation_source' => $user->creation_source,
                'registered_at' => optional($user->created_at)->toDateTimeString(),
            ];

            Notification::send($admins, new RequestNotification($payload));
        }

        // Redirect to the combined auth page (login-register) and show the register success banner.
        // 'login_message' intentionally does NOT include "Please log in." — client-side will auto-switch.
        return redirect()->route('login')
                    ->with('status', 'register-success')
                    ->with('login_message', 'Registration successful!');
    }
}
