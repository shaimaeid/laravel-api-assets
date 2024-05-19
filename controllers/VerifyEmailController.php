<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class VerifyEmailController extends Controller
{
    public function verifyEmail(Request $request): RedirectResponse
    {
        $user = User::find($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            return redirect(config('laravel-app.front_url') . '/email/verify/already-success');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect(config('laravel-app.front_url') . '/email/verify/success');
    }

    public function resendVerifyEmail(Request $request)
    {
        if (!isset($request['user_id'])) {
            $request->user()->sendEmailVerificationNotification();
        } else {
            $user = User::findOrFail($request['user_id']);
            $user->sendEmailVerificationNotification();
        }

        return Response::json(['message' => 'Verification link sent!']);
    }
}
