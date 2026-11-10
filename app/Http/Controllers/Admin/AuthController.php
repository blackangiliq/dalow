<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function form()
    {
        return Auth::check() ? redirect()->route('admin.dashboard') : view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'البريد أو كلمة المرور غير صحيحة']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
