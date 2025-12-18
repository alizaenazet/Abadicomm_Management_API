<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Services\BrevoMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function redirectHome()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }
        return redirect('/login');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    // 🔹 Proses login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['login' => 'Nama atau password salah'])->withInput();
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['worker_id', 'worker_role']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:workers,name',
            'email' => 'required|email|unique:workers,email',
            'role' => 'required|in:1,2,3',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $worker = Worker::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('dashboard')->with('success', 'Registrasi berhasil!');
    }

    public function showChangePassword()
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai'])->withInput();
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->route('dashboard')->with('success', 'Password berhasil diubah!');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:workers,email',
        ], [
            'email.exists' => 'Email tidak ditemukan dalam sistem.',
        ]);

        $worker = Worker::where('email', $request->email)->first();

        // Delete old tokens for this email
        DB::table('password_resets')->where('email', $request->email)->delete();

        // Generate new token
        $token = Str::random(64);

        // Store token
        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now()
        ]);

        // Send email via Brevo
        $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($request->email));
        $mailService = new BrevoMailService();
        $emailSent = $mailService->sendPasswordResetEmail($request->email, $worker->name, $resetUrl);

        if ($emailSent) {
            return back()->with('success', 'Link reset password telah dikirim ke email Anda.');
        }

        return back()->withErrors(['email' => 'Gagal mengirim email. Silakan coba lagi.']);
    }

    public function showResetPassword(Request $request, $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:workers,email',
            'password' => 'required|string|min:8|confirmed',
            'token' => 'required'
        ]);

        // Check if token exists and is not expired (60 minutes)
        $passwordReset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset) {
            return back()->withErrors(['email' => 'Token reset password tidak valid.']);
        }

        // Check if token is expired
        if (Carbon::parse($passwordReset->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'Token reset password telah kadaluarsa.']);
        }

        // Verify token
        if (!Hash::check($request->token, $passwordReset->token)) {
            return back()->withErrors(['email' => 'Token reset password tidak valid.']);
        }

        // Update password
        $worker = Worker::where('email', $request->email)->first();
        $worker->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete token
        DB::table('password_resets')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('success', 'Password berhasil direset! Silakan login dengan password baru.');
    }
}
