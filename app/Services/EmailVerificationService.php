<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailVerificationRequested;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class EmailVerificationService
{
    /**
     * Send verification code to user's email
     */
    public function sendVerificationCode(User $user)
    {
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

        return $verificationCode;
    }

    /**
     * Verify the email with the provided code
     */
    public function verifyEmail(User $user, $verificationCode)
    {
        // Check if user is already verified
        if ($user->email_verified_at !== null) {
            return ['success' => false, 'message' => 'Email is already verified'];
        }

        // Check if verification code has expired
        if (Carbon::now()->isAfter($user->verification_expires_at)) {
            return ['success' => false, 'message' => 'Verification code has expired. Please request a new one.'];
        }

        // Verify the code
        if (!Hash::check($verificationCode, $user->verification_code)) {
            return ['success' => false, 'message' => 'Invalid verification code'];
        }

        // Mark email as verified
        $user->update([
            'email_verified_at' => Carbon::now(),
            'verification_code' => null,
            'verification_expires_at' => null,
        ]);

        return ['success' => true, 'message' => 'Email verified successfully'];
    }
}