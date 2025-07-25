<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    /**
     * Impersonate a user (admin only)
     */
    public function impersonate(Request $request, User $user)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        if (!$user->isClient()) {
            abort(400, 'Can only impersonate client users');
        }

        $adminUser = Auth::user();

        Log::info('Admin impersonation started', [
            'admin_id' => $adminUser->id,
            'admin_email' => $adminUser->email,
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'target_client_id' => $user->client_id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->session()->flush();
        $request->session()->regenerate();
        Auth::guard('web')->loginUsingId($user->id, true);
        $request->session()->put('is_impersonating', true);
        $request->session()->put('impersonator_id', $adminUser->id);
        $request->session()->save();

        return redirect()->to('/client');
    }

    /**
     * Handle impersonation landing page
     */
    public function landing(Request $request)
    {
        // If we have impersonation parameters, set them up
        if ($request->has('impersonating') && $request->has('token')) {
            $userId = $request->get('impersonating');
            $token = $request->get('token');

            // Verify this is a valid impersonation
            if (session('impersonator_id')) {
                session(['is_impersonating' => true]);
                return redirect()->to('/client');
            }
        }

        return redirect()->to('/admin');
    }

    /**
     * Show impersonation form
     */
    public function showForm(User $user)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        if (!$user->isClient()) {
            abort(400, 'Can only impersonate client users');
        }

        return view('impersonate-form', compact('user'));
    }

    /**
     * Stop impersonating and return to original user
     */
    public function stopImpersonating(Request $request)
    {
        if (!Session::has('is_impersonating')) {
            return redirect()->to('/admin');
        }

        $originalUserId = Session::get('impersonator_id');
        $originalUser = User::find($originalUserId);
        $currentUser = Auth::user();

        if (!$originalUser) {
            Auth::logout();
            return redirect()->to('/admin/login');
        }

        Log::info('Admin impersonation ended', [
            'admin_id' => $originalUser->id,
            'admin_email' => $originalUser->email,
            'impersonated_user_id' => $currentUser->id,
            'impersonated_user_email' => $currentUser->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Session::forget(['is_impersonating', 'impersonator_id']);
        Auth::login($originalUser, true);

        return redirect()->to('/admin');
    }
}
