<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(Request $request)
    {
        try {
            // Determine the user type based on the route
            $route = $request->route()->getName();
            $userType = 'user'; // default to user
            
            
            // Store the user type in the session
            session()->put('google_auth_user_type', $userType);
            session()->save();
            
            Log::info('Storing user type in session', [
                'user_type' => $userType,
                'session_id' => session()->getId()
            ]);
            
            return Socialite::driver('google')->redirect();
        } catch (Exception $e) {
            Log::error('Google Redirect Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Google redirect failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    public function handleGoogleCallback(Request $request)
    {
        try {
            Log::info('Google callback reached', [
                'session_id' => session()->getId(),
                'session_data' => session()->all()
            ]);
            
            // Retrieve the user type from session
            $userType = session()->get('google_auth_user_type', 'customer');
            Log::info('Retrieved user type from session', ['user_type' => $userType]);
            
            // Remove the user type from session
            session()->forget('google_auth_user_type');
            session()->save();
            
            // Get user data from Google
            $googleUser = Socialite::driver('google')->user();
            Log::info('Google user data received', ['user_id' => $googleUser->id, 'email' => $googleUser->email]);
            
            // Check if user already exists
            $finduser = User::where('google_id', $googleUser->id)->first();
            if($finduser){
                // Check if the existing user has the correct role
                if ($finduser->role !== $userType) {
                    return response()->json([
                        'message' => "User exists but with a different role ({$finduser->role}). Please use the appropriate login method.",
                    ], 400);
                }
                
                Auth::login($finduser);
                return response()->json([
                    'message' => 'User found and logged in',
                    'user' => $finduser,
                    'token' => $finduser->createToken('google-auth-token')->plainTextToken
                ]);
            }
            
            // Check if user exists with same email
            $existingUser = User::where('email', $googleUser->email)->first();

            if($existingUser){
                // If user exists with email but no google_id, update the google_id
                if (is_null($existingUser->google_id)) {
                    $existingUser->update(['google_id' => $googleUser->id]);
                }
                
                // Check if the existing user has the correct role
                if ($existingUser->role !== $userType) {
                    return response()->json([
                        'message' => "User exists but with a different role ({$existingUser->role}). Please use the appropriate login method.",
                    ], 400);
                }
                
                Auth::login($existingUser);
                return response()->json([
                    'message' => 'Existing user updated with Google ID',
                    'user' => $existingUser,
                    'token' => $existingUser->createToken('google-auth-token')->plainTextToken
                ]);
            } else {
                // Create new user with appropriate role
                $newUser = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'role' => $userType, // Assign the role based on user type
                    'password' => encrypt('123456dummy')
                ]);
                
                // If this is a vendor, create a vendor profile with pending approval
                if ($userType === 'vendor') {
                    $vendor = new Vendor();
                    $vendor->user_id = $newUser->id;
                    // Note: Address will need to be added later by the vendor
                    $vendor->approval_status = 'pending';
                    $vendor->save();
                }
                
                Auth::login($newUser);
                return response()->json([
                    'message' => 'New user created successfully',
                    'user' => $newUser,
                    'token' => $newUser->createToken('google-auth-token')->plainTextToken
                ]);
            }
        } catch (InvalidStateException $e) {
            Log::error('Google Callback InvalidStateException: ' . $e->getMessage());
            Log::error('Google Callback Trace: ' . $e->getTraceAsString());
            
            // Try to get more information about the session
            Log::info('Session data: ', [
                'session_id' => session()->getId(),
                'all_session_data' => session()->all(),
                'has_previous_url' => session()->has('_previous'),
                'has_state' => session()->has('state'),
                'has_google_auth_identifier' => session()->has('google_auth_identifier')
            ]);
            
            return response()->json([
                'message' => 'Invalid state during Google authentication. This usually happens due to session issues.',
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'solution' => 'Please clear your browser cache and cookies for this site and try again. Also make sure you\'re using the same browser tab/window for the entire authentication process.'
            ], 400);
        } catch (Exception $e) {
            Log::error('Google Callback Error: ' . $e->getMessage());
            Log::error('Google Callback Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'message' => 'Error occurred during Google authentication',
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ], 400);
        }
    }

    public function googleLogin(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'google_id' => 'required|string',
                'email' => 'required|email',
                'name' => 'required|string',
            ]);

            // Check if user exists
            $user = User::where('google_id', $request->google_id)->first();
            
            if ($user) {
                // User found, return token
                return response()->json([
                    'message' => 'User authenticated successfully',
                    'user' => $user,
                    'token' => $user->createToken('google-auth-token')->plainTextToken
                ], 200);
            }

            // Check if user exists by email
            $user = User::where('email', $request->email)->first();
            
            if ($user && is_null($user->google_id)) {
                // Update the user with google_id
                $user->update(['google_id' => $request->google_id]);
                
                return response()->json([
                    'message' => 'User authenticated successfully',
                    'user' => $user,
                    'token' => $user->createToken('google-auth-token')->plainTextToken
                ], 200);
            }

            // Create a new user
            $newUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'google_id' => $request->google_id,
                'role' => 'user',
                'password' => encrypt('google-auth-password')
            ]);

            return response()->json([
                'message' => 'User created and authenticated successfully',
                'user' => $newUser,
                'token' => $newUser->createToken('google-auth-token')->plainTextToken
            ], 201);
        } catch (Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error during authentication',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}