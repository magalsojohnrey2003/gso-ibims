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

        $user = Auth::user();
        $firstName = $user->first_name ? trim($user->first_name) : '';
        
        // Determine if user is new or returning based on last_login_at
        $isNewUser = is_null($user->last_login_at);
        
        // Generate personalized welcome message
        if ($isNewUser) {
            // First time login - warm welcome
            $loginMessage = $firstName 
                ? "Welcome, {$firstName}! We're excited to have you here." 
                : "Welcome! We're excited to have you here.";
            $greetingType = 'new';
        } else {
            // Returning user - welcome back with time-based greeting
            $hour = now()->hour;
            $timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
            
            $loginMessage = $firstName 
                ? "{$timeGreeting}, {$firstName}! Welcome back." 
                : "{$timeGreeting}! Welcome back.";
            $greetingType = 'returning';
        }

        // Update last login timestamp
        $user->last_login_at = now();
        $user->save();

        // Redirect user based on role and set consistent flash keys used by your blades
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard')
                             ->with('status', 'login-success')
                             ->with('login_message', $loginMessage)
                             ->with('greeting_type', $greetingType)
                             ->with('user_name', $firstName ?: $user->full_name);
        }

        return redirect()->route('user.dashboard')
                         ->with('status', 'login-success')
                         ->with('login_message', $loginMessage)
                         ->with('greeting_type', $greetingType)
                         ->with('user_name', $firstName ?: $user->full_name);
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
