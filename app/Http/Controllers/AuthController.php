<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Validator;
class AuthController extends Controller
{
    /**
     * API Login using username_machine + password_machine
     */
    use ApiResponse;
    public function viewLogin()
    {
        return response()->json([
            'success' => false,
            'message' => 'view login json'
        ], 404);

    }
    public function login(Request $request)
    {
        $request->validate([
            'username_machine' => 'required|string',
            'password_machine' => 'required|string',
        ]);

        // Find user by username_machine
        $user = User::with('getLocation')->where('username_machine', $request->username_machine)->first();

        // Check password_machine (must be hashed with bcrypt)
        if (!$user || !Hash::check($request->password_machine, $user->password_machine)) {
            return response()->json([
                'success' => false,
                'message' => 'Username Machine atau Password Machine salah!'
            ], 401);
        }

        // if ($user->is_login_device == 1) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'sudah login harap logout dulu'
        //     ], 400);
        // }

        // Create Sanctum token (for Android app)
        $token = $user->createToken('android-device-login')->plainTextToken;

        // Mark device as logged in
        $user->update(['is_login_device' => 1]);


        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'role' => $user->role,
                'username_machine' => $user->username_machine,
                'lokasi' => $user->getLocation,
                // 'last_url_image' => $this->getLastImageByUser($request)
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * API Logout
     * Protected with auth:sanctum
     */
    public function logout(Request $request)
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Reset device login flag
        $request->user()->update(['is_login_device' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }


    public function getMe(Request $request)
    {
        $data = User::find($request->data_user->id);
        return $this->autoResponse($data);
    }
    public function changeNewPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'new_password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 404);
        }

        $user = User::find($request->data_user->id);

        if (!Hash::check($request->old_password, $user->password_machine)) {
            return $this->error("password lama salah", 401);
        }
        $user->password_machine = Hash::make($request->new_password);
        $user->save();

        return $this->autoResponse("password berhasil diubah");
    }
}