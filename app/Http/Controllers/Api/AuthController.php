<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DeviceToken;
use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:40',
            'lastname' => 'required|string|max:40',
            'email' => 'required|string|email|max:40|unique:users',
            'mobile' => 'required|string|max:40|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'country_code' => 'required|string',
            'country' => 'required|string',
            'agree' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'country_code' => $request->country_code,
                'country' => $request->country,
                'password' => Hash::make($request->password),
                'kv' => gs('ev') ? 0 : 1,
                'sv' => gs('sv') ? 0 : 1,
            ]);

            $token = $user->createToken('API Token')->plainTextToken;

            // Send verification email if required
            if (gs('ev')) {
                $this->sendVerificationEmail($user);
            }

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => $user->makeHidden(['password', 'remember_token']),
                    'token' => $token,
                    'email_verification_required' => gs('ev'),
                    'mobile_verification_required' => gs('sv'),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $username = $request->username;
        $fieldType = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        if (!Auth::attempt([$fieldType => $username, 'password' => $request->password])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();

        // Check if user is banned
        if ($user->status == 0) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended'
            ], 403);
        }

        // Update login information
        $user->last_login = now();
        $user->save();

        // Log user login
        $this->logUserLogin($user, $request);

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->makeHidden(['password', 'remember_token']),
                'token' => $token,
                'profile_complete' => $this->isProfileComplete($user),
                'email_verified' => $user->ev,
                'mobile_verified' => $user->sv,
                'kyc_verified' => $user->kv,
                'two_factor_enabled' => $user->tsc ? true : false,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->load(['basicInfo', 'careerInfo', 'educationInfo', 'familyInfo', 'physicalAttribute', 'religionInfo', 'partnerExpectation']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->makeHidden(['password', 'remember_token']),
                'profile_complete' => $this->isProfileComplete($user),
                'package_info' => $this->getCurrentPackageInfo($user),
            ]
        ]);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();
            $code = verificationCode(6);
            
            $user->verification_code = $code;
            $user->verification_code_expired_at = now()->addMinutes(15);
            $user->save();

            // Send password reset email
            $this->sendPasswordResetEmail($user, $code);

            return response()->json([
                'success' => true,
                'message' => 'Password reset code sent to your email'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reset code'
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->verification_code !== $request->code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 422);
        }

        if ($user->verification_code_expired_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired'
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->verification_code = null;
        $user->verification_code_expired_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if ($user->verification_code !== $request->code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 422);
        }

        if ($user->verification_code_expired_at < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired'
            ], 422);
        }

        $user->ev = 1;
        $user->verification_code = null;
        $user->verification_code_expired_at = null;
        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully'
        ]);
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->ev) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified'
            ], 422);
        }

        $this->sendVerificationEmail($user);

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent successfully'
        ]);
    }

    public function verifyMobile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if ($user->verification_code !== $request->code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 422);
        }

        $user->sv = 1;
        $user->verification_code = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Mobile verified successfully'
        ]);
    }

    public function enableTwoFactor(Request $request)
    {
        $google2fa = new Google2FA();
        $user = $request->user();

        if ($user->tsc) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is already enabled'
            ], 422);
        }

        $secretKey = $google2fa->generateSecretKey();
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            gs('site_name'),
            $user->email,
            $secretKey
        );

        $user->tsc = $secretKey;
        $user->ts = 0; // Not activated yet
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication setup initiated',
            'data' => [
                'secret_key' => $secretKey,
                'qr_code_url' => $qrCodeUrl,
            ]
        ]);
    }

    public function verifyTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $google2fa = new Google2FA();

        if (!$user->tsc) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is not set up'
            ], 422);
        }

        $valid = $google2fa->verifyKey($user->tsc, $request->code);

        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 422);
        }

        $user->ts = 1;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled successfully'
        ]);
    }

    public function disableTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password'
            ], 422);
        }

        $user->tsc = null;
        $user->ts = 0;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication disabled successfully'
        ]);
    }

    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:google,facebook',
            'provider_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if user exists with this provider ID
            $user = User::where('provider_id', $request->provider_id)
                        ->where('provider', $request->provider)
                        ->first();

            if (!$user) {
                // Check if user exists with this email
                $user = User::where('email', $request->email)->first();
                
                if (!$user) {
                    // Create new user
                    $nameParts = explode(' ', $request->name, 2);
                    $user = User::create([
                        'firstname' => $nameParts[0],
                        'lastname' => $nameParts[1] ?? '',
                        'email' => $request->email,
                        'provider' => $request->provider,
                        'provider_id' => $request->provider_id,
                        'ev' => 1, // Email verified through social provider
                        'password' => Hash::make(Str::random(24)),
                    ]);
                } else {
                    // Link existing user with social provider
                    $user->provider = $request->provider;
                    $user->provider_id = $request->provider_id;
                    $user->ev = 1;
                    $user->save();
                }
            }

            // Log user login
            $this->logUserLogin($user, $request);

            $token = $user->createToken('API Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Social login successful',
                'data' => [
                    'user' => $user->makeHidden(['password', 'remember_token']),
                    'token' => $token,
                    'profile_complete' => $this->isProfileComplete($user),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Social login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function notifications(Request $request)
    {
        $user = $request->user();
        $notifications = AdminNotification::where('user_id', $user->id)
                                        ->orWhere('user_id', 0)
                                        ->orderBy('created_at', 'desc')
                                        ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    public function markNotificationRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = AdminNotification::where('id', $id)
                                        ->where(function($query) use ($user) {
                                            $query->where('user_id', $user->id)
                                                  ->orWhere('user_id', 0);
                                        })
                                        ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->is_read = 1;
        $notification->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    public function markAllNotificationsRead(Request $request)
    {
        $user = $request->user();
        AdminNotification::where(function($query) use ($user) {
                            $query->where('user_id', $user->id)
                                  ->orWhere('user_id', 0);
                        })
                        ->update(['is_read' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    public function deleteNotification(Request $request, $id)
    {
        $user = $request->user();
        $notification = AdminNotification::where('id', $id)
                                        ->where('user_id', $user->id)
                                        ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted'
        ]);
    }

    public function registerDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'type' => 'required|string|in:android,ios,web',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        DeviceToken::updateOrCreate(
            ['user_id' => $user->id, 'token' => $request->token],
            ['type' => $request->type, 'is_active' => 1]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully'
        ]);
    }

    private function sendVerificationEmail($user)
    {
        $code = verificationCode(6);
        $user->verification_code = $code;
        $user->verification_code_expired_at = now()->addMinutes(15);
        $user->save();

        // Send email notification
        notify($user, 'EMAIL_VERIFICATION_CODE', [
            'code' => $code,
            'name' => $user->fullname,
        ]);
    }

    private function sendPasswordResetEmail($user, $code)
    {
        notify($user, 'PASSWORD_RESET_CODE', [
            'code' => $code,
            'name' => $user->fullname,
        ]);
    }

    private function logUserLogin($user, $request)
    {
        $userLogin = new \App\Models\UserLogin();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $request->ip();
        $userLogin->city = null;
        $userLogin->country = null;
        $userLogin->country_code = null;
        $userLogin->location = null;
        $userLogin->browser = $request->userAgent();
        $userLogin->os = null;
        $userLogin->longitude = null;
        $userLogin->latitude = null;
        $userLogin->save();
    }

    private function isProfileComplete($user)
    {
        return $user->basicInfo && 
               $user->physicalAttribute && 
               $user->religionInfo && 
               $user->careerInfo()->exists() && 
               $user->educationInfo()->exists();
    }

    private function getCurrentPackageInfo($user)
    {
        $purchaseHistory = $user->purchaseHistory()
                               ->where('expired_date', '>', now())
                               ->latest()
                               ->first();

        if ($purchaseHistory) {
            return [
                'package' => $purchaseHistory->package,
                'expired_date' => $purchaseHistory->expired_date,
                'remaining_days' => now()->diffInDays($purchaseHistory->expired_date),
                'interest_limit' => $purchaseHistory->package->interest_express_limit ?? 0,
                'contact_view_limit' => $purchaseHistory->package->contact_view_limit ?? 0,
            ];
        }

        return null;
    }
}