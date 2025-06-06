<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Storage;

class SettingsController extends Controller
{
    public function index(Request $request, User $user)
    {
        $updated_fields = []; 
        if ($request->hasFile('profile_picture')) {
            Storage::disk('public')->delete($user->profile_picture);

            $file = $request->file('profile_picture');
            $path = $file->store('profile_pictures', 'public');
            $user->profile_picture = $path;
            $updatedFields['profile_picture'] = Storage::url($path);
        }

        if ($request->has('name')) {
            $user->name = $request->input('name');
            $updatedFields['name'] = $user->name;
        }

        if ($request->has('email')) {
            $user->email = $request->input('email');
            $updatedFields['email'] = $user->email;
        }

        if (!empty($updatedFields)) {
            $user->save();
            return response()->json([
                'message' => 'Profile updated successfully.',
                'updated' => $updatedFields
            ]);
        }

        // return response()->json(['message' => 'No file uploaded.'], 400);
    
    }
}
