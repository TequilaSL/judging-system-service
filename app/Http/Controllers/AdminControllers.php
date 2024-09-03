<?php

namespace App\Http\Controllers;
// require_once vender\;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Validator;

class AdminControllers extends Controller
{
    // User login
    public function login(Request $request)
    {
        $fields = $request->validate([
            'user_name' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('user_name', $fields['user_name'])->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response(['message' => 'Bad credentials'], 401);
        }

        $token = $user->createToken('token')->plainTextToken;
        return response(['user' => $user, 'token' => $token], 200);
    }

    // User registration
    public function register(Request $request)
    {
        $request->validate([
            'user_name' => 'required|unique:users,user_name',
            'contact' => 'required|unique:users,contact',
            'password' => 'required'
        ]);

        User::create([
            'user_name' => $request->user_name,
            'contact' => $request->contact,
            'password' => bcrypt($request->password),
        ]);


        return response()->json(['message' => 'Registration successful'], 201);
    }

    // Update user details
    public function userUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'user_name' => 'sometimes|required|unique:users,user_name,' . $id,
            'contact' => 'sometimes|required|unique:users,contact,' . $id,
            'password' => 'sometimes|nullable|min:8',
        ]);

        $user->update([
            'user_name' => $request->user_name,
            'contact' => $request->contact,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
        ]);

        return response()->json(['message' => 'Update successful'], 200);
    }

    // User logout
    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Logout successful'], 200);
    }

    // Get user details
    public function userDetails()
    {
        $user = Auth::user(['id', 'user_name']);
        return response()->json($user, 200);
    }

    // Get all users
    public function getAllUser()
    {
        $users = User::select('id', 'user_name', 'contact')->get();

        return response()->json($users, 200);
    }


    // Send OTP function
    public function sendOTP(Request $request)
    {
        $request->validate(['contact' => 'required']);
        $contact = $request->contact;

        $user = User::where('user_name', $request['user_name'] && 'contact', $request['contact'])->first();
        if (!$user) {
            dd($user);
        }else{
            dd($user);
        }

        $twilio = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
        print(env('TWILIO_SID'));
        $verification = $twilio->verify->v2->services(env('SERVICE_TOKEN'))
            ->verifications
            ->create($contact, "sms");
        print($verification->sid);

        return response()->json(['message' => 'OTP sent successfully'], 200);
    }


    // Verify OTP function
    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric',
            'contact' => 'required|exists:otps,contact',
        ]);

        // $otpRecord = Otp::where('contact', $request->contact)
        //     ->where('otp', $request->otp)
        //     ->where('expires_at', '>', now())
        //     ->first();

        $twilio = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
        $verification_check = $twilio->verify->v2->services(env('SERVICE_TOKEN'))
            ->verificationChecks
            ->create(
                [
                    "to" => $request->contact,
                    "code" => $request->otp
                ]
            );
        print($verification_check->sid);

        // if (!$otpRecord) {
        //     return response()->json(['error' => 'Invalid or expired OTP'], 400);
        // }
        // $otpRecord->delete();
        return response()->json(['message' => 'OTP verified successfully'], 200);
    }


    // Change password
    public function changePassword(Request $request)
    {
        $request->validate([
            'contact' => 'required|exists:users,contact',
            'password' => 'required|min:8|string',
        ]);

        $user = User::where('user_name', $request['user_name'])
                ->where('contact', $request['contact'])
                ->first();
        if (!$user) {
            //innavanam
        }else{
//nattan
        }

        $user = User::where('contact', $request->contact)->first();
        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password changed successfully'], 200);
    }
}
