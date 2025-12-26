<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str; // Import File facade for public folder operations

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        // 1. Validation (All nullable so partial updates work)
        $rules = [
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ];

        // Only add email validation if the user is actually trying to change it
        if ($user->role === 'user' && $request->filled('email')) {
            $rules['email'] = [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ];
        }

        $request->validate($rules);

        try {
            // 2. Smart Update: Only update fields present in the request
            // $request->filled('key') returns true only if key is present AND not empty/null

            if ($request->filled('name')) {
                $user->name = $request->name;
            }

            if ($request->filled('phone_number')) {
                $user->phone_number = $request->phone_number;
            }

            if ($request->filled('address')) {
                $user->address = $request->address;
            }

            if ($user->role === 'user' && $request->filled('email')) {
                $user->email = $request->email;
            }

            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Profile info updated successfully',
                'data' => $user,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update profile info',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Profile Picture Only
     * POST /api/profile/update-picture
     */
    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_pic' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $user = Auth::user();

        try {
            if ($request->hasFile('profile_pic')) {

                $destinationPath = public_path('images/profile');

                // Create directory if missing
                if (! File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }

                // Delete Old Image (Garbage Collection)
                if ($user->profile_pic) {
                    // Check if it's a local file path (not a full URL like Google's)
                    $oldPath = public_path($user->getRawOriginal('profile_pic')); // getRawOriginal gets value without Accessor

                    // If your Accessor is already modifying it, we need to be careful.
                    // Best way: Check if file exists at the stored path.
                    // Assuming you store 'images/profile/abc.jpg' in DB:
                    $storedPath = $user->getAttributes()['profile_pic'] ?? null;

                    if ($storedPath && file_exists(public_path($storedPath))) {
                        File::delete(public_path($storedPath));
                    }
                }

                // Upload New Image
                $file = $request->file('profile_pic');
                $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();
                $file->move($destinationPath, $filename);

                // Save Relative Path
                $user->profile_pic = 'images/profile/'.$filename;
                $user->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Profile picture updated successfully',
                    'data' => $user, // Returns full URL via Accessor
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => 'No image file uploaded',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update profile picture',
                'error' => $e->getMessage(),
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
                'status' => false,
                'message' => 'Failed to fetch profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
