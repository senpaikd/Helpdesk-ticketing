<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyOtherSessionsRequest;
use App\Services\UserSessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileSessionController extends Controller
{
    public function destroyOthers(DestroyOtherSessionsRequest $request, UserSessionManager $sessions): RedirectResponse
    {
        $user = $request->user();

        $revoked = DB::transaction(function () use ($request, $sessions, $user): int {
            $count = $sessions->revokeOthers($user, $request->session()->getId());
            $user->forceFill(['remember_token' => Str::random(60)])->save();

            return $count;
        });

        $message = $revoked === 1
            ? 'One other session was signed out.'
            : "{$revoked} other sessions were signed out.";

        return redirect()->route('profile.edit')->with('success', $message);
    }
}
