<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Notifications\EmailVerificationRequest;


class EmailVerificationService
{
    /**
     * Send verification code to user's email
     */
    public function sendVerificationCode(User $user)
    {
        // generate 6-digit verification code
        $verificationCode = rand(100000, 999999);

        // redis key
        $key = "email_verification:" . $user->email;
        // store in redis for 30 minutes
        Cache::put($key, $verificationCode, now()->addMinutes(30));

        $user->notify(new EmailVerificationRequest($verificationCode));
        return $verificationCode;



        // // Generate 6-digit verification code
        // $verificationCode = rand(100000, 999999);
        
        // // Set expiration time (30 minutes from now)
        // $expiresAt = Carbon::now()->addMinutes(30);
        
        // // Save verification code and expiration time
        // $user->update([
        //     'verification_code' => Hash::make($verificationCode),
        //     'verification_expires_at' => $expiresAt,
        // ]);

        // // Send verification code to user's email
        // $user->notify(new EmailVerificationRequest($verificationCode));

        // return $verificationCode;
    }

    /**
     * Verify the email with the provided code
     */

    public function verifyEmail(User $user, $verificationCode)
    {

        $key = "email_verification:" . $user->email;

        //Redis key to get stored code
        $storedCode = Cache::get($key);

        if (!$storedCode) {
            return ['success' => false, 'message' => 'No verification code found or it has expired. Please request a new one.'];
        }

        if ($storedCode != $verificationCode) {
            return ['success' => false, 'message' => 'Invalid verification code'];
        }

        $user->email_verified_at = Carbon::now();
        $user->save();

        //Remove the code from cache after successful verification
        Cache::forget($key); 

        return ['success'=> true,'message'=> 'email verified successfully'];







        // // Check if user is already verified
        // if ($user->email_verified_at !== null) {
        //     return ['success' => false, 'message' => 'Email is already verified'];
        // }

        // if(empty($user->verification_code) || empty($user->verification_expires_at)) {
        //     return ['success' => false, 'message' => 'No verification code found. Please request a new one.'];
        // }

        // // Check if verification code has expired
        // if (Carbon::now()->isAfter($user->verification_expires_at)) {
        //     return ['success' => false, 'message' => 'Verification code has expired. Please request a new one.'];
        // }

        // // Verify the code
        // if (!Hash::check($verificationCode, $user->verification_code)) {
        //     return ['success' => false, 'message' => 'Invalid verification code'];
        // }

        // // Mark email as verified
        // $user->update([
        //     'email_verified_at' => Carbon::now(),
        //     'verification_code' => null,
        //     'verification_expires_at' => null,
        // ]);

        // return ['success' => true, 'message' => 'Email verified successfully'];
    }
   
}