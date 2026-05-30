<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Google\GoogleDriveOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class GoogleDriveOAuthController extends Controller
{
    /**
     * Redirect the admin to Google's OAuth consent screen.
     */
    public function connect(GoogleDriveOAuthService $oauth): RedirectResponse
    {
        return redirect()->away($oauth->authorizationUrl());
    }

    /**
     * Handle the OAuth callback and store the Drive token.
     */
    public function callback(Request $request, GoogleDriveOAuthService $oauth): RedirectResponse
    {
        if ($request->string('state')->toString() !== (string) session('google_drive_oauth_state')) {
            return redirect()
                ->route('offorest.admin.users')
                ->with('google_drive_error', 'Google Drive OAuth state khong hop le. Hay connect lai.');
        }

        session()->forget('google_drive_oauth_state');

        if ($request->filled('error')) {
            return redirect()
                ->route('offorest.admin.users')
                ->with('google_drive_error', 'Google Drive OAuth bi tu choi: '.$request->string('error')->toString());
        }

        try {
            $oauth->connect($request->user(), $request->string('code')->toString());
        } catch (Throwable $exception) {
            return redirect()
                ->route('offorest.admin.users')
                ->with('google_drive_error', $exception->getMessage());
        }

        return redirect()
            ->route('offorest.admin.users')
            ->with('google_drive_status', 'Da connect Google Drive OAuth thanh cong.');
    }
}
