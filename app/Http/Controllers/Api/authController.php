<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Validator};
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use App\Notifications\{EmailVerificationRequest, PasswordResetRequested};
use Carbon\Carbon;

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
            'status' => true,
            'message' => 'User created successfully. Please check your email for verification code.', 
            'data' => $data,
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
          
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        if ($user->role =='user') {
            // Check if email is verified 
            if ($user->email_verified_at === null) {
                return response()->json([
                    'message' => 'Please verify your email before logging in.',
                ], 422);
            }
        }

        // Ban checks
        if ($user->ban_type === 'ban_permanently') {
            return response()->json([
                'message' => 'Your account has been permanently banned.',
            ], 403);
        }

        if ($user->banned_until && Carbon::now()->lt($user->banned_until)) {
            return response()->json([
                'message' => 'Your account is temporarily banned until '.$user->banned_until->format('jS F Y'),
            ], 403);
        }

        // Auto-unban if ban period has passed
        if ($user->banned_until && Carbon::now()->gte($user->banned_until)) {
            $user->ban_type = null;
            $user->banned_until = null;
            $user->ban_reason = null;
            $user->save();
        }
        

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'=> true,
            'message' => 'Logged in successfully.',
            'access_token' => $token,
            'role' => $user->role,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout(Request $request)
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken $personalAccessToken */
        $personalAccessToken = $request->user()->currentAccessToken();
        $personalAccessToken->delete();

        return response()->json([
            'status'=> true,
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
                'status' => false,
                'message' => $result['message']
            ], 422);
        }

        $token = $user->createToken("auth_token")->plainTextToken;
        return response()->json([
            "status"=> true,
            'message' => $result['message'],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
        
        return ['success' => true, 'message' => 'Email verified successfully'];
    }
 

    public function sendResetOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'=> false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // CHANGED: Generate 6-character token (Alphanumeric: a-z, A-Z, 0-9)
        // If you want ONLY uppercase and numbers (cleaner for users), use: strtoupper(Str::random(6))
        // $resetToken = Str::random(6);
        $resetToken = rand(100000, 999999);
        
        // Set expiration time (60 minutes from now)
        $expiresAt = Carbon::now()->addMinutes(10);
        
        // Save reset token and expiration time
        $user->update([
            'reset_token' => Hash::make($resetToken),
            'reset_token_expires_at' => $expiresAt,
        ]);

        // Send reset token to user's email
        // Make sure your Notification class is updated to display this short code!
        $user->notify(new PasswordResetRequested($resetToken));

        return response()->json([
            'status' => true,
            'message' => 'Password reset code sent to your email'
        ], 200);
    }

    

    public function verifyOtp(Request $request)
    {
        // 1. Validate Input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string', // The 6-digit code
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        // 2. Check Expiry
        if (!$user->reset_token_expires_at || Carbon::now()->isAfter($user->reset_token_expires_at)) {
            return response()->json([
                'status'  => false,
                'message' => 'OTP has expired. Please request a new one.'
            ], 422);
        }

        // 3. Check Token Match
        if (!Hash::check($request->otp, $user->reset_token)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid OTP code'
            ], 422);
        }

        // 4. Mark as Verified
        $user->update([
            'reset_token_verified_at' => Carbon::now(),
            'reset_token' => null, // Invalidate the token after verification
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'OTP verified successfully. You may now reset your password.'
        ], 200);
    }

    public function changePassword(Request $request)
    {
        // 1. Validate Input
        $validator = Validator::make($request->all(), [
            'email'           => 'required|email|exists:users,email',
            'old_password'    => 'required|string',
            'password'        => 'required|string|min:8|confirmed', // New password + confirmation
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }
        
        $user = User::where('email', $request->email)->first();

        // 2. Security Check: Does the Old Password match?
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'The old password provided is incorrect.'
            ], 401); // 401 Unauthorized
        }

        // 3. Update to New Password
        $user->update([
            'password' => Hash::make($request->password),
            // Optional: You might want to revoke existing tokens if you are using API tokens
            // $user->tokens()->delete(); 
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Password changed successfully.'
        ], 200);
    }

    /**
     * Step 2: Set the New Password
     */
    public function resetPassword(Request $request)
    {
        // 1. Validate Input
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        // 2. Security Check: Was the OTP verified recently?
        // We allow a 15-minute window between verifying OTP and setting the password.
        if (!$user->reset_token_verified_at) {
            return response()->json([
                'status'  => false,
                'message' => 'You must verify the OTP first.'
            ], 403);
        }

        if (Carbon::now()->diffInMinutes($user->reset_token_verified_at) > 15) {
             return response()->json([
                'status'  => false,
                'message' => 'Verification session expired. Please verify OTP again.'
            ], 408);
        }

        // 3. Update Password & Clear Tokens
        $user->update([
            'password'                => Hash::make($request->password),
            'reset_token'             => null,
            'reset_token_expires_at'  => null,
            'reset_token_verified_at' => null, // Reset the flag so it can't be reused
        ]);

        return response()->json([
            'status'  => true,
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
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Check if user is already verified
        if ($user->email_verified_at !== null) {
            return response()->json([
                'status'=> false,
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
        $user->notify(new EmailVerificationRequest($verificationCode));

        return response()->json([
            'status'=> true,
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
                'status'=> false,
                'message' => 'Email is already verified.'
            ], 422);
        }

        $this->emailVerificationService->sendVerificationCode($user);

        return response()->json([
            'status'=> true,
            'message' => 'Verification code resent successfully. Please check your email.'
        ], 200);
    }

   
}
