<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Services\UserSessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfilePasswordController extends Controller
{
    public function update(UpdatePasswordRequest $request, UserSessionManager $sessions): RedirectResponse
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        DB::transaction(function () use ($request, $sessions, $user, $currentSessionId): void {
            $user->forceFill([
                'password' => $request->validated('password'),
                'remember_token' => Str::random(60),
            ])->save();

            $sessions->revokeOthers($user, $currentSessionId);
        });

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('profile.edit')->with('success', 'Your password has been updated and other sessions were signed out.');
    }
}
