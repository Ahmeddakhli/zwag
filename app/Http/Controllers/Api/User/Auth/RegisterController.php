<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Package;
use App\Models\User;
use App\Models\UserLimitation;
use App\Models\UserLogin;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        parent::__construct();
    }
    public function register(Request $request)
    {

        if (!gs('registration')) {
            return $this->unauthorizedResponse(__('Registration is currently disabled.'));
        }

        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            // and a 422 Unprocessable Entity status code
            return  $this->errorResponse(__('Validation failed'), $validator->errors());
        }

        if (!verifyCaptcha()) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid captcha provided.'),
            ], 400);
        }

        event(new Registered($user = $this->create($request->all())));

        auth()->login($user);

        return $this->successResponse(__('Registration successful'), [
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    protected function validator(array $data)
    {
        $passwordValidation = Password::min(6);

        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        return Validator::make($data, [
            'firstname' => 'required|string',
            'lastname'  => 'required|string',
            'email'     => 'required|email|unique:users,email',
            'password'  => ['required', 'confirmed', $passwordValidation],
            'captcha'   => 'sometimes|required',
            'agree'     => gs('agree') ? 'required' : 'nullable',
            'zip'       => 'nullable|numeric|digits:6',
            'state'     => 'nullable|string',
            'city'      => 'nullable|string',
            'address'   => 'nullable|string',
        ]);
    }

    protected function create(array $data)
    {
        $user = new User();
        $user->profile_id   = getNumber(8);
        $user->email        = strtolower($data['email']);
        $user->password     = Hash::make($data['password']);
        $user->firstname    = $data['firstname'];
        $user->lastname     = $data['lastname'];
        $user->gender       = $data['gender'];
        $user->kv = gs('kv') ? Status::NO : Status::YES;
        $user->ev = gs('ev') ? Status::NO : Status::YES;
        $user->sv = gs('sv') ? Status::NO : Status::YES;
        $user->save();

        $this->userLimitation($user);
        $this->createAdminNotification($user);
        $this->logUserLogin($user);

        return $user;
    }

    protected function userLimitation(User $user)
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
        $limitation->expire_date = now()->addDays($package->validity_period ?? 0);
        $limitation->save();
    }

    protected function createAdminNotification(User $user)
    {
        $notification = new AdminNotification();
        $notification->user_id = $user->id;
        $notification->title = 'New member registered';
        $notification->click_url = urlPath('admin.users.detail', $user->id);
        $notification->save();
    }

    protected function logUserLogin(User $user)
    {
        $ip = getRealIP();
        $userAgent = osBrowser();

        $userLogin = new UserLogin();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $ip;
        $userLogin->browser = $userAgent['browser'] ?? null;
        $userLogin->os = $userAgent['os_platform'] ?? null;

        $location = UserLogin::where('user_ip', $ip)->first();
        if ($location) {
            $userLogin->fill($location->only(['longitude', 'latitude', 'city', 'country_code', 'country']));
        } else {

            $info                    = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude    = @implode(',', $info['long']);
            $userLogin->latitude     = @implode(',', $info['lat']);
            $userLogin->city         = @implode(',', $info['city']);
            $userLogin->country_code = @implode(',', $info['code']);
            $userLogin->country      = @implode(',', $info['country']);
        }

        $userAgent          = osBrowser();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $ip;

        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os      = @$userAgent['os_platform'];

        $userLogin->save();
    }

    public function checkUser(Request $request)
    {
        $exist['data'] = false;
        $exist['type'] = null;
        $exist['field'] = null;

        if ($request->email) {
            $exist['data'] = User::where('email', $request->email)->exists();
            $exist['type'] = 'email';
            $exist['field'] = 'Email';
        } elseif ($request->mobile) {
            $exist['data'] = User::where('mobile', $request->mobile)
                ->where('dial_code', $request->mobile_code)
                ->exists();
            $exist['type'] = 'mobile';
            $exist['field'] = 'Mobile';
        } elseif ($request->username) {
            $exist['data'] = User::where('username', $request->username)->exists();
            $exist['type'] = 'username';
            $exist['field'] = 'Username';
        }

        return $this->successResponse(__('User check completed'), $exist);
    }
}
