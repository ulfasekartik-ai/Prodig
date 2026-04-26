<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        $ref = $this->resolveRefCode($request);

        $refMemberName = null;
        if ($ref) {
            $refMember = User::where('referral_code', $ref)->first();
            if ($refMember) {
                $refMemberName = $refMember->name;
                session(['ref_code' => $ref]);
            }
        }

        return view('auth.register', compact('ref', 'refMemberName'));
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $uplineId = null;
        $refCode = $this->resolveRefCode($request);
        if ($refCode) {
            $upline = User::where('referral_code', $refCode)->first();
            if ($upline) {
                $uplineId = $upline->id;
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp_number' => $request->whatsapp_number,
            'password' => Hash::make($request->password),
            'upline_id' => $uplineId,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $intendedSlug = session('intended_product_slug');
        if ($intendedSlug) {
            $redirectUrl = route('checkout', $intendedSlug);
            return redirect($redirectUrl);
        }

        return redirect(route('dashboard', absolute: false));
    }

    private function resolveRefCode(Request $request): ?string
    {
        $candidates = [
            $request->input('ref'),
            $request->cookie('ref'),
            session('ref_code'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }
}
