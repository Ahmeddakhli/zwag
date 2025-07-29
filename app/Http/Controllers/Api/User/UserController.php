<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\LookupResource;
use App\Http\Resources\MaritalStatusResource;
use App\Lib\FormProcessor;
use App\Lib\GoogleAuthenticator;
use App\Models\BasicInfo;
use App\Models\BloodGroup;
use App\Models\CareerInfo;
use App\Models\DeviceToken;
use App\Models\EducationInfo;
use App\Models\FamilyInfo;
use App\Models\Form;
use App\Models\Lookup;
use App\Models\MaritalStatus;
use App\Models\PartnerExpectation;
use App\Models\PhysicalAttribute;
use App\Models\PurchaseHistory;
use App\Models\ReligionInfo;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use ApiResponse;

    public function home()
    {
        $user = User::where('id', auth()->id())->with(['limitation.package', 'interests' => function ($query) {
            $query->orderBy('id', 'desc')->take(10);
        }, 'interests.profile.basicInfo', 'interests.conversation', 'interestRequests' => function ($query) {
            $query->orderBy('id', 'desc')->take(10);
        }, 'interestRequests.user.basicInfo', 'interestRequests.conversation'])
            ->withCount([
                'galleries as total_images', 'shortListedProfile as totalShortlisted',
                'interests as interestSent', 'interestRequests as totalInterestRequests'
            ])->first();

        return $this->successResponse('User dashboard data retrieved successfully', $user);
    }

    public function userData()
    {
        $user = auth()->user();
        if ($user->profile_complete == Status::YES) {
            return $this->successResponse('Profile already complete');
        }

        $totalStep = count($user->completed_step) + count($user->skipped_step);
        $data = [];
        
        if (!$totalStep) {
            $data['religions'] = ReligionInfo::get();
            $data['maritalStatuses'] = LookupResource::collection(Lookup::with('children')->where('slug', 'marital-status')->get()); // MaritalStatus::get();
            $data['countries'] = json_decode(file_get_contents(resource_path('views/partials/country.json')));
            $data['user'] = $user;
            $info = json_decode(json_encode(getIpInfo()), true);
            $data['mobileCode'] = @implode(',', $info['code']);
            $step = 'basicInfo';
        } elseif ($totalStep == 1) {
            $step = 'familyInfo';
        } elseif ($totalStep == 2) {
            $step = 'educationInfo';
        } elseif ($totalStep == 3) {
            $step = 'careerInfo';
        } elseif ($totalStep == 4) {
            $data['bloodGroups'] = BloodGroup::get();
            $step = 'physicalAttributeInfo';
        } elseif ($totalStep == 5) {
            $data['countries'] = json_decode(file_get_contents(resource_path('views/partials/country.json')));
            $data['maritalStatuses'] = MaritalStatus::get();
            $data['religions'] = ReligionInfo::get();
            $step = 'partnerExpectation';
        }

        return $this->successResponse('User data step retrieved', [
            'current_step' => $step,
            'data' => $data
        ]);
    }

    public function userDataSubmit(Request $request, $step)
    {
        $steps = array('basicInfo', 'familyInfo', 'educationInfo', 'careerInfo', 'physicalAttributeInfo', 'partnerExpectation');

        if (!in_array($step, $steps)) {
            return $this->notFoundResponse('Invalid step');
        }

        if ($request->has('back_to') && !in_array($request->back_to, $steps)) {
            return $this->errorResponse('Invalid back to field');
        }

        $user = auth()->user();
        if ($user->profile_complete == Status::YES) {
            return $this->successResponse('Profile already complete');
        }

        if ($request->has('back_to')) {
            $removedIndex = array_search($request->back_to, $steps) + 1;
            if (in_array($removedIndex, $user->skipped_step)) {
                $arrayValue = array_flip($user->skipped_step);
                unset($arrayValue[$removedIndex]);
                $arrayValue = array_flip($arrayValue);
                $user->skipped_step = $arrayValue;
                $user->save();
            }

            if (in_array($removedIndex, $user->completed_step)) {
                $arrayValue = array_flip($user->completed_step);
                unset($arrayValue[$removedIndex]);
                $arrayValue = array_flip($arrayValue);
                $user->completed_step = $arrayValue;
                $user->save();
            }

            return $this->successResponse('Step reverted successfully');
        }

        $response = $this->$step($request, $user);
        if ($response && !$response['success']) {
            return $this->errorResponse($response['message']);
        }

        return $this->successResponse('Step completed successfully');
    }


    public function storeInformation(Request $request)
    {
        $countryData = (array)json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes = implode(',', array_column($countryData, 'dial_code'));

        $user = auth()->user();

        $emailValidation = 'nullable';
        $mobileValidation = 'nullable';
        $mobileCodeValidation = 'nullable';
        $countryCodeValidation = 'nullable';

        if (!$user->mobile) {
            $mobileValidation = 'required|regex:/^([0-9]*)$/';
            $mobileCodeValidation = 'required|in:' . $mobileCodes;
            $countryCodeValidation = 'required|in:' . $countryCodes;
        }

        if (!$user->email) {
            $emailValidation = 'required|string|email|unique:users';
        }

        $validator = Validator::make($request->all(), [
            'email' => $emailValidation,
            'mobile' => $mobileValidation,
            'mobile_code' => $mobileCodeValidation,
            'country_code' => $countryCodeValidation
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!$user->mobile) {
            $exist = User::where('mobile', $request->mobile_code . $request->mobile)->first();
            if ($exist) {
                return $this->errorResponse('The mobile number already exists');
            }

            $user->country_code = $request->country_code;
            $user->mobile = $request->mobile_code . $request->mobile;
        }

        if (!$user->email) {
            $user->email = $request->email;
        }

        $user->save();

        return $this->successResponse('Information saved successfully');
    }

    protected function basicInfo($request, $user)
    {
        $countryData = (array)json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes = implode(',', array_column($countryData, 'dial_code'));
        $countries = implode(',', array_column($countryData, 'country'));

        $rules = [
            'birth_date' => 'required|date_format:Y-m-d|before:today',
            'religion' => 'required|exists:religion_infos,name',
            'gender' => 'required|in:m,f',
            'profession' => 'required|string',
            'financial_condition' => 'required|string',
            'smoking_status' => 'required|in:0,1',
            'drinking_status' => 'required|in:0,1',
            'marital_status' => 'required|exists:marital_statuses,title',
            'languages' => 'required|array',
            'languages.*' => 'string',
            'country_code' => 'required|in:' . $countryCodes,
            'country' => 'required|in:' . $countries,
            'mobile_code' => 'required|in:' . $mobileCodes,
            'username' => 'required|unique:users|min:6',
            'mobile' => ['required', 'regex:/^([0-9]*)$/', Rule::unique('users')->where('dial_code', $request->mobile_code)],
            'pre_state' => 'nullable',
            'pre_zip' => 'nullable',
            'pre_city' => 'required',
            'per_state' => 'nullable',
            'per_zip' => 'nullable',
            'per_city' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first()];
        }

        if (preg_match("/[^a-z0-9_]/", trim($request->username))) {
            return ['success' => false, 'message' => 'Username can contain only small letters, numbers and underscore.'];
        }

        $user->country_code = $request->country_code;
        $user->mobile = $request->mobile;
        $user->username = $request->username;
        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->country_name = @$request->country;
        $user->dial_code = $request->mobile_code;
        $user->save();

        $basicInfo = new BasicInfo();
        $basicInfo->user_id = $user->id;
        $basicInfo->gender = $request->gender;
        $basicInfo->profession = $request->profession;
        $basicInfo->financial_condition = $request->financial_condition;
        $basicInfo->religion = $request->religion;
        $basicInfo->smoking_status = $request->smoking_status;
        $basicInfo->drinking_status = $request->drinking_status;
        $basicInfo->birth_date = $request->birth_date;
        $basicInfo->language = $request->languages;
        $basicInfo->marital_status = $request->marital_status;
        $basicInfo->present_address = [
            'country' => $user->country_name,
            'state' => $request->pre_state,
            'zip' => $request->pre_zip,
            'city' => $request->pre_city,
        ];
        $basicInfo->permanent_address = [
            'country' => $request->per_country,
            'state' => $request->per_state,
            'zip' => $request->per_zip,
            'city' => $request->per_city,
        ];
        $basicInfo->save();

        $this->updateRegistrationStep($user, 1, 'completed_step');
        return ['success' => true];
    }

    protected function familyInfo($request, $user)
    {
        if (!$request->has('button_value')) {
            $this->updateRegistrationStep($user, 2, 'skipped_step');
            return ['success' => true];
        }

        $rules = [
            'father_name' => 'required',
            'father_contact' => 'required|numeric|gt:0',
            'mother_name' => 'required',
            'mother_contact' => 'required|numeric|gt:0',
            'total_brother' => 'nullable|min:0',
            'total_sister' => 'nullable|min:0',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first()];
        }

        $familyInfo = new FamilyInfo();
        $familyInfo->user_id = $user->id;
        $familyInfo->father_name = $request->father_name;
        $familyInfo->father_profession = $request->father_profession;
        $familyInfo->father_contact = $request->father_contact;
        $familyInfo->mother_name = $request->mother_name;
        $familyInfo->mother_profession = $request->mother_profession;
        $familyInfo->mother_contact = $request->mother_contact;
        $familyInfo->total_brother = $request->total_brother ?? 0;
        $familyInfo->total_sister = $request->total_sister ?? 0;
        $familyInfo->save();

        $this->updateRegistrationStep($user, 2, 'completed_step');
        return ['success' => true];
    }

    protected function educationInfo($request, $user)
    {
        if (!$request->has('button_value')) {
            $this->updateRegistrationStep($user, 3, 'skipped_step');
            return ['success' => true];
        }

        $rules = [
            'institute' => 'required|array',
            'institute.*' => 'required|string',
            'degree' => 'required|array',
            'degree.*' => 'required|string',
            'field_of_study' => 'required|array',
            'field_of_study.*' => 'required|string|max:255',
            'reg_no' => 'nullable|array',
            'reg_no.*' => 'nullable|integer|gt:0',
            'roll_no' => 'nullable|array',
            'roll_no.*' => 'nullable|integer|gt:0',
            'start' => 'required|array',
            'start.*' => 'required|integer|gt:0|digits:4|max:' . date('Y'),
            'end' => 'nullable|array',
            'end.*' => 'nullable|integer|gt:0|digits:4|max:' . date('Y'),
            'result' => 'nullable|array',
            'result.*' => 'nullable|numeric|gte:0',
            'out_of' => 'nullable|array',
            'out_of.*' => 'nullable|numeric|gte:0'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first()];
        }

        if ($request->end) {
            foreach ($request->end as $key => $end) {
                if ($end && $request->start[$key] > $end) {
                    return ['success' => false, 'message' => 'Ending year can\'t be less than starting year'];
                }
            }
        }

        foreach ($request->degree as $key => $degree) {
            $educationInfo = new EducationInfo();
            $educationInfo->user_id = $user->id;
            $educationInfo->degree = $degree;
            $educationInfo->field_of_study = $request->field_of_study[$key];
            $educationInfo->institute = $request->institute[$key];
            $educationInfo->reg_no = $request->reg_no[$key] ?? 0;
            $educationInfo->roll_no = $request->roll_no[$key] ?? 0;
            $educationInfo->start = $request->start[$key];
            $educationInfo->end = $request->end[$key];
            $educationInfo->result = $request->result[$key];
            $educationInfo->out_of = $request->out_of[$key];
            $educationInfo->save();
        }

        $this->updateRegistrationStep($user, 3, 'completed_step');
        return ['success' => true];
    }

    protected function careerInfo($request, $user)
    {
        if (!$request->has('button_value')) {
            $this->updateRegistrationStep($user, 4, 'skipped_step');
            return ['success' => true];
        }

        $rules = [
            'company' => 'required|array',
            'company.*' => 'required|string|max:255',
            'designation' => 'required|array',
            'designation.*' => 'required|string|max:40',
            'start' => 'required|array',
            'start.*' => 'required|integer|digits:4|gt:0|lte:' . date('Y'),
            'end' => 'nullable|array',
            'end.*' => 'nullable|integer|digits:4|lte:' . date('Y')
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first()];
        }

        if ($request->end) {
            foreach ($request->end as $key => $end) {
                if ($end && $request->start[$key] > $end) {
                    return ['success' => false, 'message' => 'Ending year can\'t be less than starting year'];
                }
            }
        }

        foreach ($request->company as $key => $company) {
            $careerInfo = new CareerInfo();
            $careerInfo->user_id = $user->id;
            $careerInfo->company = $company;
            $careerInfo->designation = $request->designation[$key];
            $careerInfo->start = $request->start[$key];
            $careerInfo->end = $request->end[$key];
            $careerInfo->save();
        }

        $this->updateRegistrationStep($user, 4, 'completed_step');
        return ['success' => true];
    }

    protected function physicalAttributeInfo($request, $user)
    {
        if (!$request->has('button_value')) {
            $this->updateRegistrationStep($user, 5, 'skipped_step');
            return ['success' => true];
        }

        $rules = [
            'height' => 'required|numeric|gt:0',
            'weight' => 'required|numeric|gt:0',
            'blood_group' => 'required|exists:blood_groups,name',
            'eye_color' => 'required|string|max:40',
            'hair_color' => 'required|string|max:40',
            'complexion' => 'required|string|max:255',
            'disability' => 'nullable|string|max:40'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first()];
        }

        $physicalAttribute = new PhysicalAttribute();
        $physicalAttribute->user_id = $user->id;
        $physicalAttribute->height = $request->height;
        $physicalAttribute->weight = $request->weight;
        $physicalAttribute->blood_group = $request->blood_group;
        $physicalAttribute->eye_color = $request->eye_color;
        $physicalAttribute->hair_color = $request->hair_color;
        $physicalAttribute->complexion = $request->complexion;
        $physicalAttribute->disability = $request->disability;
        $physicalAttribute->save();

        $this->updateRegistrationStep($user, 5, 'completed_step');
        return ['success' => true];
    }

    protected function partnerExpectation($request, $user)
    {
        if (!$request->has('button_value')) {
            $this->updateRegistrationStep($user, 6, 'skipped_step');
            return ['success' => true];
        }

        $rules = [
            'general_requirement' => 'nullable|string|max:255',
            'country' => 'nullable',
            'min_age' => 'nullable|integer|gt:0',
            'max_age' => 'nullable|integer|gt:0',
            'min_height' => 'nullable|numeric|gt:0',
            'max_height' => 'nullable|numeric|gt:0',
            'max_weight' => 'nullable|numeric|gt:0',
            'marital_status' => 'nullable',
            'religion' => 'nullable|exists:religion_infos,name',
            'complexion' => 'nullable|string|max:255',
            'smoking_status' => 'nullable|in:1,2',
            'drinking_status' => 'nullable|in:1,2',
            'language' => 'nullable|array',
            'language.*' => 'string',
            'min_degree' => 'nullable|string|max:40',
            'personality' => 'nullable|string|max:40',
            'profession' => 'nullable|string|max:40',
            'financial_condition' => 'nullable|string|max:40',
            'family_position' => 'nullable|string|max:40'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first()];
        }

        $partnerExpectation = new PartnerExpectation();
        $partnerExpectation->user_id = $user->id;
        $partnerExpectation->general_requirement = $request->general_requirement;
        $partnerExpectation->country = $request->country;
        $partnerExpectation->min_age = $request->min_age;
        $partnerExpectation->max_age = $request->max_age;
        $partnerExpectation->min_height = $request->min_height;
        $partnerExpectation->max_weight = $request->max_weight;
        $partnerExpectation->marital_status = $request->marital_status;
        $partnerExpectation->religion = $request->religion;
        $partnerExpectation->complexion = $request->complexion;
        $partnerExpectation->smoking_status = $request->smoking_status ?? 0;
        $partnerExpectation->drinking_status = $request->drinking_status ?? 0;
        $partnerExpectation->language = $request->language ?? [];
        $partnerExpectation->min_degree = $request->min_degree;
        $partnerExpectation->profession = $request->profession;
        $partnerExpectation->personality = $request->personality;
        $partnerExpectation->financial_condition = $request->financial_condition;
        $partnerExpectation->family_position = $request->family_position;
        $partnerExpectation->save();

        $this->updateRegistrationStep($user, 6, 'completed_step');
        return ['success' => true];
    }

    protected function updateRegistrationStep($user, $index, $column)
    {
        $array = $user->$column;

        if (!in_array($index, $array)) {
            array_push($array, $index);
        }

        $user->$column = $array;
        if ($index == 6) {
            $user->profile_complete = 1;
        }
        $user->save();
    }
}