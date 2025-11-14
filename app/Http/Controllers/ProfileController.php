<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    /**
     * Update the user's profile information.
     */
public function update(ProfileUpdateRequest $request): RedirectResponse
{
    $user = $request->user();

    // Update basic fields
    $user->fill($request->validated());

    if ($user->isDirty('email')) {
        $user->email_verified_at = null;
    }

    // Handle profile photo upload + delete old file
    if ($request->hasFile('profile_photo')) {
        $newPath = $request->file('profile_photo')->store('profile_photos', 'public');

        // delete old if exists
        if ($user->profile_photo && \Storage::disk('public')->exists($user->profile_photo)) {
            \Storage::disk('public')->delete($user->profile_photo);
        }

        $user->profile_photo = $newPath;
    }

    $user->save();

    return Redirect::to('/profile')->with('status', 'profile-updated');
}

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function editInfo(Request $request): View
{
    return view('profile.info', [
        'user' => $request->user(),
    ]);
}

public function editPassword(Request $request): View
{
    return view('profile.password', [
        'user' => $request->user(),
    ]);
}

public function editDelete(Request $request): View
{
    return view('profile.delete', [
        'user' => $request->user(),
    ]);
}

}
