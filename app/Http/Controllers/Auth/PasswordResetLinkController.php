<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetEmail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Find the user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('If the email exists, a reset link will be sent.')]);
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Generate the reset URL for custom email
        $resetUrl = URL()->temporarySignedRoute(
            'password.reset',
            now()->addMinutes(60),
            ['token' => $request->email] // Using email as token reference
        );

        // Send custom password reset email
        Mail::to($user->email)->send(new PasswordResetEmail($user, $resetUrl));

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __('A password reset link has been sent to your email address.'))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __('Unable to send reset link.')]);
    }
}
