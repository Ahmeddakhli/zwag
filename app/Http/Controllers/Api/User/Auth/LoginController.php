<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserLogin;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    use ApiResponse;

    // public function __construct()
    // {
    //     parent::__construct();
    // }

    public function login(Request $request)
    {
        $fieldType = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = [
            $fieldType => $request->username,
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = Auth::user();

        // Save user login info
        $ip = getRealIP();
        $exist = UserLogin::where('user_ip', $ip)->first();
        $userLogin = new UserLogin();
        if ($exist) {
            $userLogin->longitude = $exist->longitude;
            $userLogin->latitude = $exist->latitude;
            $userLogin->city = $exist->city;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country = $exist->country;
        } else {
            $info = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude = @implode(',', $info['long']);
            $userLogin->latitude = @implode(',', $info['lat']);
            $userLogin->city = @implode(',', $info['city']);
            $userLogin->country_code = @implode(',', $info['code']);
            $userLogin->country = @implode(',', $info['country']);
        }

        $userAgent = osBrowser();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $ip;
        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os = @$userAgent['os_platform'];
        $userLogin->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse('Login successful', [
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        // Check if the token belongs to the authenticated user
        if (!$request->user() || !$request->user()->currentAccessToken()) {
            return $this->unauthorizedResponse('Unauthorized');
        }

        // Revoke the token
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(__('logout successful'), null, 200);
    }
}
