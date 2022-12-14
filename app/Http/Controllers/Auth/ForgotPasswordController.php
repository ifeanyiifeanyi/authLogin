<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    public function showForgottenPasswordForm(){
        return view('auth.forgetPassword');
    }

    public function submitForgottenPasswordForm(Request $request){
        // @exists: checks if value exists
        $this->validate($request, [
            'email' => 'required|email|exists:users'
        ]);

        $token = Str::random(64);
        DB::table('password_resets')->insert([
            'email'         => $request->email,
            'token'         => $token,
            'created_at'    => Carbon::now()
        ]);

        Mail::send('email.forgetPassword', ['token' => $token], function($message) use($request){
            $message->to($request->email);
            $message->subject("Reset Password");
        });
        return back()->with('message', 'You have been mailed your password reset link');
    }

    public function showResetPasswordForm($token){
        return view('auth.forgetPasswordLink', ['token' => $token]);
    }


    public function submitResetPasswordForm(Request $request){
        $this->validate($request, [
            'email'         => 'required|email|exists:users',
            'password'      => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required'
        ]);
        $updatedPassword = DB::table('password_resets')->where([
            'email' => $request->email,
            'token' => $request->token
        ])->first();

        if(!$updatedPassword){
            return back()->withInput()->with('error', 'Invalid token');
        }

        $user = User::where('email', $request->email)->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')->where(['email' => $request->email])->delete();

        return redirect()->route('login')->with('message', 'Your password has been changed');
    }
}
