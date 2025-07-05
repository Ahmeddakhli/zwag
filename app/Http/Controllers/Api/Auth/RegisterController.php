<?php

namespace App\Http\Controllers\Api\Auth;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Package;
use App\Models\User;
use App\Models\UserLimitation;
use App\Models\UserLogin;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest:sanctum');
    }

    /**
     * Handle user registration via API
     */
    public function register(Request $request): JsonResponse
    {
        try {
            // Check if registration is allowed
            if (!gs('registration')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration is currently not allowed',
                    'errors' => []
                ], 403);
            }

            // Validate request data
            $validator = $this->validator($request->all());
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify captcha if required
            if ($request->has('captcha') && !verifyCaptcha()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid captcha provided',
                    'errors' => ['captcha' => ['Invalid captcha provided']]
                ], 422);
            }

            // Create the user
            $user = $this->create($request->all());

            // Fire registered event
            event(new Registered($user));

            // Create authentication token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Load user relationships for response
            $user->load(['basicInfo', 'religionInfo', 'userLimitation', 'userLimitation.package']);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'profile_id' => $user->profile_id,
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'email' => $user->email,
                        'mobile' => $user->mobile,
                        'dial_code' => $user->dial_code,
                        'username' => $user->username,
                        'image' => $user->image_src,
                        'status' => $user->status,
                        'created_at' => $user->created_at,
                        'basic_info' => $user->basicInfo,
                        'religion_info' => $user->religionInfo,
                        'package_info' => $user->userLimitation,
                        'verification_status' => [
                            'email_verified' => $user->ev,
                            'mobile_verified' => $user->sv,
                            'kyc_verified' => $user->kv
                        ]
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'errors' => ['general' => [$e->getMessage()]]
            ], 500);
        }
    }

    /**
     * Check if user exists (email, mobile, username)
     */
    public function checkUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'mobile' => 'nullable|string',
            'mobile_code' => 'nullable|string',
            'username' => 'nullable|string|min:3'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input data',
                'errors' => $validator->errors()
            ], 422);
        }

        $exists = false;
        $type = null;
        $field = null;
        $message = null;

        // Check email
        if ($request->email) {
            $exists = User::where('email', $request->email)->exists();
            $type = 'email';
            $field = 'Email';
            $message = $exists ? 'Email is already registered' : 'Email is available';
        }

        // Check mobile
        if ($request->mobile && $request->mobile_code) {
            $exists = User::where('mobile', $request->mobile)
                         ->where('dial_code', $request->mobile_code)
                         ->exists();
            $type = 'mobile';
            $field = 'Mobile';
            $message = $exists ? 'Mobile number is already registered' : 'Mobile number is available';
        }

        // Check username
        if ($request->username) {
            $exists = User::where('username', $request->username)->exists();
            $type = 'username';
            $field = 'Username';
            $message = $exists ? 'Username is already taken' : 'Username is available';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'exists' => $exists,
                'type' => $type,
                'field' => $field,
                'message' => $message
            ]
        ]);
    }

    /**
     * Get registration requirements and configuration
     */
    public function getRegistrationConfig(): JsonResponse
    {
        $general = gs();
        
        return response()->json([
            'success' => true,
            'data' => [
                'registration_enabled' => (bool) $general->registration,
                'email_verification_required' => (bool) $general->ev,
                'mobile_verification_required' => (bool) $general->sv,
                'kyc_verification_required' => (bool) $general->kv,
                'agreement_required' => (bool) $general->agree,
                'captcha_required' => function_exists('verifyCaptcha'),
                'secure_password_required' => (bool) $general->secure_password,
                'default_package' => Package::find($general->default_package_id),
                'password_requirements' => [
                    'min_length' => 6,
                    'mixed_case' => (bool) $general->secure_password,
                    'numbers' => (bool) $general->secure_password,
                    'symbols' => (bool) $general->secure_password,
                    'uncompromised' => (bool) $general->secure_password
                ]
            ]
        ]);
    }

    /**
     * Validate registration data
     */
    protected function validator(array $data)
    {
        $passwordValidation = Password::min(6);

        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $agree = 'nullable';
        if (gs('agree')) {
            $agree = 'required';
        }

        $rules = [
            'firstname' => 'required|string|max:40',
            'lastname' => 'required|string|max:40',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', $passwordValidation],
            'mobile' => 'nullable|string|max:15',
            'dial_code' => 'nullable|string|max:10',
            'username' => 'nullable|string|min:3|max:40|unique:users',
            'captcha' => 'sometimes|required',
            'agree' => $agree,
            'zip' => 'nullable|numeric|digits:6',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'country_code' => 'nullable|string|max:10'
        ];

        $messages = [
            'firstname.required' => 'The first name field is required',
            'lastname.required' => 'The last name field is required',
            'email.required' => 'The email field is required',
            'email.unique' => 'This email is already registered',
            'password.required' => 'The password field is required',
            'password.confirmed' => 'Password confirmation does not match',
            'username.unique' => 'This username is already taken',
            'agree.required' => 'You must agree to the terms and conditions'
        ];

        return Validator::make($data, $rules, $messages);
    }

    /**
     * Create a new user instance
     */
    protected function create(array $data): User
    {
        // Create user
        $user = new User();
        $user->profile_id = getNumber(8);
        $user->email = strtolower($data['email']);
        $user->password = Hash::make($data['password']);
        $user->firstname = $data['firstname'];
        $user->lastname = $data['lastname'];
        
        // Optional fields
        $user->mobile = $data['mobile'] ?? null;
        $user->dial_code = $data['dial_code'] ?? null;
        $user->username = $data['username'] ?? null;
        $user->zip = $data['zip'] ?? null;
        $user->state = $data['state'] ?? null;
        $user->city = $data['city'] ?? null;
        $user->address = $data['address'] ?? null;
        $user->country = $data['country'] ?? null;
        $user->country_code = $data['country_code'] ?? null;

        // Set verification status based on system settings
        $user->kv = gs('kv') ? Status::NO : Status::YES;
        $user->ev = gs('ev') ? Status::NO : Status::YES;
        $user->sv = gs('sv') ? Status::NO : Status::YES;
        
        $user->save();

        // Set up user limitations/package
        $this->userLimitation($user);

        // Create admin notification
        $this->createAdminNotification($user);

        // Log user registration
        $this->createUserLogin($user);

        return $user;
    }

    /**
     * Set up user package limitations
     */
    protected function userLimitation(User $user): void
    {
        $general = gs();
        $package = Package::find($general->default_package_id);

        $limitation = new UserLimitation();
        $limitation->user_id = $user->id;
        $limitation->package_id = $package->id ?? 0;
        $limitation->interest_express_limit = $package->interest_express_limit ?? 0;
        $limitation->contact_view_limit = $package->contact_view_limit ?? 0;
        $limitation->image_upload_limit = $package->image_upload_limit ?? 0;
        $limitation->validity_period = $package->validity_period ?? 0;
        $limitation->expire_date = $package ? now()->addDays($package->validity_period) : now();
        $limitation->save();
    }

    /**
     * Create admin notification for new registration
     */
    protected function createAdminNotification(User $user): void
    {
        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'New member registered';
        $adminNotification->click_url = urlPath('admin.users.detail', $user->id);
        $adminNotification->save();
    }

    /**
     * Create user login log
     */
    protected function createUserLogin(User $user): void
    {
        $ip = getRealIP();
        $existingLogin = UserLogin::where('user_ip', $ip)->first();
        
        $userLogin = new UserLogin();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $ip;

        if ($existingLogin) {
            // Use existing location data
            $userLogin->longitude = $existingLogin->longitude;
            $userLogin->latitude = $existingLogin->latitude;
            $userLogin->city = $existingLogin->city;
            $userLogin->country_code = $existingLogin->country_code;
            $userLogin->country = $existingLogin->country;
        } else {
            // Get new location data
            $ipInfo = getIpInfo();
            if ($ipInfo) {
                $info = json_decode(json_encode($ipInfo), true);
                $userLogin->longitude = @implode(',', $info['long']);
                $userLogin->latitude = @implode(',', $info['lat']);
                $userLogin->city = @implode(',', $info['city']);
                $userLogin->country_code = @implode(',', $info['code']);
                $userLogin->country = @implode(',', $info['country']);
            }
        }

        // Get browser and OS information
        $userAgent = osBrowser();
        $userLogin->browser = $userAgent['browser'] ?? null;
        $userLogin->os = $userAgent['os_platform'] ?? null;
        
        $userLogin->save();
    }

    /**
     * Check password strength
     */
    public function checkPasswordStrength(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Password is required',
                'errors' => $validator->errors()
            ], 422);
        }

        $password = $request->password;
        $strength = [
            'score' => 0,
            'feedback' => [],
            'requirements' => []
        ];

        // Check length
        if (strlen($password) >= 6) {
            $strength['score'] += 20;
            $strength['requirements']['min_length'] = true;
        } else {
            $strength['requirements']['min_length'] = false;
            $strength['feedback'][] = 'Password must be at least 6 characters long';
        }

        // Check for lowercase
        if (preg_match('/[a-z]/', $password)) {
            $strength['score'] += 20;
            $strength['requirements']['lowercase'] = true;
        } else {
            $strength['requirements']['lowercase'] = false;
            if (gs('secure_password')) {
                $strength['feedback'][] = 'Password must contain lowercase letters';
            }
        }

        // Check for uppercase
        if (preg_match('/[A-Z]/', $password)) {
            $strength['score'] += 20;
            $strength['requirements']['uppercase'] = true;
        } else {
            $strength['requirements']['uppercase'] = false;
            if (gs('secure_password')) {
                $strength['feedback'][] = 'Password must contain uppercase letters';
            }
        }

        // Check for numbers
        if (preg_match('/\d/', $password)) {
            $strength['score'] += 20;
            $strength['requirements']['numbers'] = true;
        } else {
            $strength['requirements']['numbers'] = false;
            if (gs('secure_password')) {
                $strength['feedback'][] = 'Password must contain numbers';
            }
        }

        // Check for symbols
        if (preg_match('/[^a-zA-Z\d]/', $password)) {
            $strength['score'] += 20;
            $strength['requirements']['symbols'] = true;
        } else {
            $strength['requirements']['symbols'] = false;
            if (gs('secure_password')) {
                $strength['feedback'][] = 'Password must contain special characters';
            }
        }

        $strength['level'] = match(true) {
            $strength['score'] >= 80 => 'strong',
            $strength['score'] >= 60 => 'good',
            $strength['score'] >= 40 => 'fair',
            default => 'weak'
        };

        return response()->json([
            'success' => true,
            'data' => $strength
        ]);
    }
}