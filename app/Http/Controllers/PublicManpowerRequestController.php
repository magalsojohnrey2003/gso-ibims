<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ManpowerRequest;

class PublicManpowerRequestController extends Controller
{
    public function show(string $token)
    {
        $request = ManpowerRequest::with(['roleType', 'user'])
            ->where('public_token', $token)
            ->firstOrFail();

        return view('public.manpower-status', [
            'request' => $request,
        ]);
    }
}
