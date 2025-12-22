<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File; // Import File facade for public folder operations

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // 1. Dynamic Validation
        $rules = [
            'name'          => 'required|string|max:255',
            'phone_number'  => 'nullable|string|max:20',
            'address'       => 'nullable|string|max:500',
            'profile_pic'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        if ($user->role === 'user') {
            $rules['email'] = [
                'required',
                'email',
            ];
        }

        $request->validate($rules);

        try {
            // 2. Handle Custom Public Path Image Upload
            if ($request->hasFile('profile_pic')) {

                // Define the destination path
                $destinationPath = public_path('images/profile');

                // Create directory if it doesn't exist
                if (! File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }

                // A. Delete Old Image (if exists and is local)
                // We check if the user already has a pic and it's not an external URL (like Google)
                if ($user->profile_pic && file_exists(public_path($user->profile_pic))) {
                    File::delete(public_path($user->profile_pic));
                }

                // B. Upload New Image
                $file = $request->file('profile_pic');
                // Create unique filename: time_random.extension
                $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();

                // Move the file directly to public/images/profile
                $file->move($destinationPath, $filename);

                // Save relative path to DB (so Accessor can append domain later)
                $user->profile_pic = 'images/profile/'.$filename;
            }

            // 3. Update Basic Fields
            $user->name             = $request->name;
            $user->phone_number     = $request->phone_number;
            $user->address          = $request->address;

            if ($user->role === 'user' && $request->has('email')) {
                $user->email = $request->email;
            }

            $user->save();

            return response()->json([
                'status'    => true,
                'message'   => 'Profile updated successfully',
                'data'      => $user,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'    => false,
                'message'   => 'Failed to update profile',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View Profile
     * GET /api/profile
     */
    public function viewProfile()
    {
        try {
            $user = Auth::user();

            return UserResource::make($user);

        } catch (\Exception $e) {
            return response()->json([
                'status'    => false,
                'message'   => 'Failed to fetch profile',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }
}
