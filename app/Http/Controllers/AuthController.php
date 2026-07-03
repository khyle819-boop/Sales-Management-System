<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Handle login attempt
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'role' => 'nullable|in:admin,engineer'
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();
            // Optional role guard: if role selected, ensure it matches the user's role
            $selectedRole = $request->input('role');
            if ($selectedRole && $user->role !== ($selectedRole === 'admin' ? 'sales' : 'engineer')) {
                Auth::logout();
                return back()->withErrors(['email' => 'Selected role does not match your account.'])->withInput($request->only('email','role'));
            }

            if ($user->role === 'engineer') {
                return redirect()->intended(route('engineer.installations.index'));
            }
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email','role'));
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')->with('success', 'You have been successfully logged out.');
    }

    /**
     * Create a default admin user if none exists
     */
    public function createDefaultUser()
    {
        if (User::count() === 0) {
            User::create([
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
            ]);
            
            return true;
        }
        return false;
    }
}
