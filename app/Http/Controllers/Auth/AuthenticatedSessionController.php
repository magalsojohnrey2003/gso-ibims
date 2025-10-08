<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login-register');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Friendly message for returning users
        $firstName = Auth::user()->first_name ? trim(Auth::user()->first_name) : '';
        if ($firstName) {
            $loginMessage = "Hello again, {$firstName}! Good to see you.";
        } else {
            $loginMessage = "Hello again! Good to see you.";
        }

        // Redirect user based on role and set consistent flash keys used by your blades
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard')
                             ->with('status', 'login-success')
                             ->with('login_message', $loginMessage);
        }

        return redirect()->route('user.dashboard')
                         ->with('status', 'login-success')
                         ->with('login_message', $loginMessage);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
