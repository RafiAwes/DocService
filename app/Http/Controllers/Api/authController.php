<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\EmailVerificationService;
use App\Notifications\EmailVerificationRequest;

class authController extends Controller
{
    protected $emailVerificationService;
    public function __construct()
    {
        $this->emailVerificationService = new EmailVerificationService();
    }
    public function userRegister(Request $request)
    {
        $data = $request->validate([
            'name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|confirmed',
            'password_confirmation'=>'required'
        ]);
        
        
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->role = "user";
        $user->created_at = Carbon::now();
        $user->save();
        
        $this->emailVerificationService->sendVerificationCode($user);
        

        $token = $user->createToken("auth_token")->plainTextToken;
       
        return response()->json([
            'message' => 'User created successfully. Please check your email for verification code.', 
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
           throw ValidationException::withMessages([
               'email' => 'The provided credentials are incorrect.',
           ]);
        }

        if ($user->role =='user') {
            // Check if email is verified 
            if ($user->email_verified_at === null) {
                return response()->json([
                    'message' => 'Please verify your email before logging in.',
                ], 422);
            }
        }
        

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout(Request $request)
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken $personalAccessToken */
        $personalAccessToken = $request->user()->currentAccessToken();
        $personalAccessToken->delete();

        return response()->json([
            'message' => 'Logged out Successfully.',
        ], 200);
    }

    public function verifyRegistration(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
            'verification_code' => 'required|numeric|digits:6',
        ]);

        $user = User::where('email', $data['email'])->first();

        $result = $this->emailVerificationService->verifyEmail($user, $data['verification_code']);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 422);
        }

        $token = $user->createToken("auth_token")->plainTextToken;
        return response()->json([
            'message' => $result['message'],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
        
        return ['success' => true, 'message' => 'Email verified successfully'];
    }

     public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Generate reset token
        $resetToken = Str::random(60);
        
        // Set expiration time (60 minutes from now)
        $expiresAt = Carbon::now()->addMinutes(60);
        
        // Save reset token and expiration time
        $user->update([
            'reset_token' => Hash::make($resetToken),
            'reset_token_expires_at' => $expiresAt,
        ]);

        // Send reset token to user's email
        $user->notify(new PasswordResetRequested($resetToken));

        return response()->json([
            'message' => 'Password reset link sent to your email'
        ], 200);
    }

    /**
     * Reset the user's password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Check if reset token has expired
        if (Carbon::now()->isAfter($user->reset_token_expires_at)) {
            return response()->json([
                'message' => 'Password reset token has expired. Please request a new one.'
            ], 422);
        }

        // Verify the token
        if (!Hash::check($request->token, $user->reset_token)) {
            return response()->json([
                'message' => 'Invalid password reset token'
            ], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
            'reset_token' => null,
            'reset_token_expires_at' => null,
        ]);

        return response()->json([
            'message' => 'Password reset successfully'
        ], 200);
    }

    // sending verification code
    public function sendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Check if user is already verified
        if ($user->email_verified_at !== null) {
            return response()->json([
                'message' => 'Email is already verified'
            ], 422);
        }

        // Generate 6-digit verification code
        $verificationCode = rand(100000, 999999);
        
        // Set expiration time (30 minutes from now)
        $expiresAt = Carbon::now()->addMinutes(30);
        
        // Save verification code and expiration time
        $user->update([
            'verification_code' => Hash::make($verificationCode),
            'verification_expires_at' => $expiresAt,
        ]);

        // Send verification code to user's email
        $user->notify(new EmailVerificationRequested($verificationCode));

        return response()->json([
            'message' => 'Verification code sent to your email'
        ], 200);
    }

    /**
     * Verify the email with the provided code
     */
    

    //resending verification code
    public function resendVerificationCode(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user->email_verified_at !== null) {
            return response()->json([
                'message' => 'Email is already verified.'
            ], 422);
        }

        $this->emailVerificationService->sendVerificationCode($user);

        return response()->json([
            'message' => 'Verification code resent successfully. Please check your email.'
        ], 200);
    }
}
