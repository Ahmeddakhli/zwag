<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BasicInfo;
use App\Models\CareerInfo;
use App\Models\EducationInfo;
use App\Models\FamilyInfo;
use App\Models\PhysicalAttribute;
use App\Models\ReligionInfo;
use App\Models\PartnerExpectation;
use App\Models\ContactView;
use App\Models\UserInterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load([
            'basicInfo', 
            'careerInfo', 
            'educationInfo', 
            'familyInfo', 
            'physicalAttribute', 
            'religionInfo', 
            'partnerExpectation',
            'galleries'
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->makeHidden(['password', 'remember_token']),
                'profile_completion' => $this->calculateProfileCompletion($user),
                'verification_status' => [
                    'email' => $user->ev,
                    'mobile' => $user->sv,
                    'kyc' => $user->kv,
                ],
                'package_info' => $this->getCurrentPackageInfo($user),
            ]
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'sometimes|string|max:40',
            'lastname' => 'sometimes|string|max:40',
            'mobile' => 'sometimes|string|max:40|unique:users,mobile,' . $request->user()->id,
            'address' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:80',
            'zip' => 'sometimes|string|max:40',
            'city' => 'sometimes|string|max:80',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $user->update($request->only([
                'firstname', 'lastname', 'mobile', 'address', 'state', 'zip', 'city'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user->makeHidden(['password', 'remember_token'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            // Delete old avatar if exists
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            // Process and save new avatar
            $image = $request->file('avatar');
            $filename = time() . '_' . $user->id . '.' . $image->getClientOriginalExtension();
            $path = 'users/avatars/' . $filename;

            // Resize image
            $manager = new ImageManager(new Driver());
            $img = $manager->read($image);
            $img->resize(300, 300);
            
            Storage::disk('public')->put($path, $img->encode());

            $user->image = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Avatar updated successfully',
                'data' => [
                    'avatar_url' => Storage::url($path)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update avatar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteAvatar(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            $user->image = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Avatar deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete avatar'
            ], 500);
        }
    }

    public function updateBasicInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gender' => 'required|in:1,2',
            'profession' => 'sometimes|string|max:255',
            'financial_condition' => 'sometimes|string|max:255',
            'my_information' => 'sometimes|string',
            'partner_age_min' => 'sometimes|integer|min:18|max:100',
            'partner_age_max' => 'sometimes|integer|min:18|max:100',
            'present_address' => 'sometimes|string',
            'permanent_address' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $basicInfo = BasicInfo::updateOrCreate(
                ['user_id' => $user->id],
                $request->only([
                    'gender', 'profession', 'financial_condition', 'my_information',
                    'partner_age_min', 'partner_age_max', 'present_address', 'permanent_address'
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'Basic information updated successfully',
                'data' => $basicInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update basic information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePhysicalAttributes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'height' => 'sometimes|numeric',
            'weight' => 'sometimes|numeric',
            'eye_color' => 'sometimes|string|max:50',
            'hair_color' => 'sometimes|string|max:50',
            'complexion' => 'sometimes|string|max:50',
            'body_type' => 'sometimes|string|max:50',
            'disability' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $physicalAttribute = PhysicalAttribute::updateOrCreate(
                ['user_id' => $user->id],
                $request->only([
                    'height', 'weight', 'eye_color', 'hair_color', 
                    'complexion', 'body_type', 'disability'
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'Physical attributes updated successfully',
                'data' => $physicalAttribute
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update physical attributes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateReligionInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'religion_id' => 'required|exists:religion_infos,id',
            'caste' => 'sometimes|string|max:100',
            'sub_caste' => 'sometimes|string|max:100',
            'community' => 'sometimes|string|max:100',
            'mother_tongue' => 'sometimes|string|max:100',
            'family_values' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $religionInfo = ReligionInfo::updateOrCreate(
                ['user_id' => $user->id],
                $request->only([
                    'religion_id', 'caste', 'sub_caste', 'community', 
                    'mother_tongue', 'family_values'
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'Religion information updated successfully',
                'data' => $religionInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update religion information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateFamilyInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'father_name' => 'sometimes|string|max:100',
            'father_occupation' => 'sometimes|string|max:100',
            'mother_name' => 'sometimes|string|max:100',
            'mother_occupation' => 'sometimes|string|max:100',
            'siblings' => 'sometimes|integer|min:0',
            'family_type' => 'sometimes|string|max:50',
            'family_status' => 'sometimes|string|max:50',
            'family_location' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $familyInfo = FamilyInfo::updateOrCreate(
                ['user_id' => $user->id],
                $request->only([
                    'father_name', 'father_occupation', 'mother_name', 'mother_occupation',
                    'siblings', 'family_type', 'family_status', 'family_location'
                ])
            );

            return response()->json([
                'success' => true,
                'message' => 'Family information updated successfully',
                'data' => $familyInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update family information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePartnerExpectations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'age_from' => 'required|integer|min:18|max:100',
            'age_to' => 'required|integer|min:18|max:100',
            'height_from' => 'sometimes|numeric',
            'height_to' => 'sometimes|numeric',
            'marital_status' => 'sometimes|array',
            'religion' => 'sometimes|array',
            'smoking_status' => 'sometimes|string',
            'drinking_status' => 'sometimes|string',
            'partner_expectations' => 'sometimes|string',
            'location_preference' => 'sometimes|string',
            'education_preference' => 'sometimes|string',
            'occupation_preference' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $data = $request->only([
                'age_from', 'age_to', 'height_from', 'height_to',
                'smoking_status', 'drinking_status', 'partner_expectations',
                'location_preference', 'education_preference', 'occupation_preference'
            ]);

            // Handle array fields
            if ($request->has('marital_status')) {
                $data['marital_status'] = json_encode($request->marital_status);
            }
            if ($request->has('religion')) {
                $data['religion'] = json_encode($request->religion);
            }

            $partnerExpectation = PartnerExpectation::updateOrCreate(
                ['user_id' => $user->id],
                $data
            );

            return response()->json([
                'success' => true,
                'message' => 'Partner expectations updated successfully',
                'data' => $partnerExpectation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update partner expectations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCareerInfo(Request $request)
    {
        $user = $request->user();
        $careerInfo = CareerInfo::where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $careerInfo
        ]);
    }

    public function addCareerInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'designation' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'present' => 'boolean',
            'details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $careerInfo = CareerInfo::create([
                'user_id' => $user->id,
                'designation' => $request->designation,
                'company' => $request->company,
                'start_date' => $request->start_date,
                'end_date' => $request->present ? null : $request->end_date,
                'present' => $request->present ?? false,
                'details' => $request->details,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Career information added successfully',
                'data' => $careerInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add career information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateCareerInfo(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'designation' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'present' => 'boolean',
            'details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $careerInfo = CareerInfo::where('id', $id)->where('user_id', $user->id)->first();

            if (!$careerInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Career information not found'
                ], 404);
            }

            $careerInfo->update([
                'designation' => $request->designation,
                'company' => $request->company,
                'start_date' => $request->start_date,
                'end_date' => $request->present ? null : $request->end_date,
                'present' => $request->present ?? false,
                'details' => $request->details,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Career information updated successfully',
                'data' => $careerInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update career information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteCareerInfo(Request $request, $id)
    {
        try {
            $user = $request->user();
            $careerInfo = CareerInfo::where('id', $id)->where('user_id', $user->id)->first();

            if (!$careerInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Career information not found'
                ], 404);
            }

            $careerInfo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Career information deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete career information'
            ], 500);
        }
    }

    public function getEducationInfo(Request $request)
    {
        $user = $request->user();
        $educationInfo = EducationInfo::where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $educationInfo
        ]);
    }

    public function addEducationInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'degree' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'present' => 'boolean',
            'details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $educationInfo = EducationInfo::create([
                'user_id' => $user->id,
                'degree' => $request->degree,
                'institution' => $request->institution,
                'start_date' => $request->start_date,
                'end_date' => $request->present ? null : $request->end_date,
                'present' => $request->present ?? false,
                'details' => $request->details,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Education information added successfully',
                'data' => $educationInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add education information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateEducationInfo(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'degree' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'present' => 'boolean',
            'details' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $educationInfo = EducationInfo::where('id', $id)->where('user_id', $user->id)->first();

            if (!$educationInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Education information not found'
                ], 404);
            }

            $educationInfo->update([
                'degree' => $request->degree,
                'institution' => $request->institution,
                'start_date' => $request->start_date,
                'end_date' => $request->present ? null : $request->end_date,
                'present' => $request->present ?? false,
                'details' => $request->details,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Education information updated successfully',
                'data' => $educationInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update education information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteEducationInfo(Request $request, $id)
    {
        try {
            $user = $request->user();
            $educationInfo = EducationInfo::where('id', $id)->where('user_id', $user->id)->first();

            if (!$educationInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Education information not found'
                ], 404);
            }

            $educationInfo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Education information deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete education information'
            ], 500);
        }
    }

    public function completionStatus(Request $request)
    {
        $user = $request->user();
        $completion = $this->calculateProfileCompletion($user);

        return response()->json([
            'success' => true,
            'data' => $completion
        ]);
    }

    public function dashboardStats(Request $request)
    {
        $user = $request->user();

        $stats = [
            'profile_views' => ContactView::where('viewed_user_id', $user->id)->count(),
            'interests_received' => UserInterest::where('interested_user_id', $user->id)->count(),
            'interests_sent' => UserInterest::where('user_id', $user->id)->count(),
            'shortlisted_profiles' => $user->shortListedProfiles()->count(),
            'profile_completion' => $this->calculateProfileCompletion($user)['percentage'],
            'package_info' => $this->getCurrentPackageInfo($user),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function recentActivities(Request $request)
    {
        $user = $request->user();

        $activities = collect([]);

        // Recent profile views
        $profileViews = ContactView::where('viewed_user_id', $user->id)
                                 ->with('user:id,firstname,lastname,image')
                                 ->latest()
                                 ->take(5)
                                 ->get()
                                 ->map(function($view) {
                                     return [
                                         'type' => 'profile_view',
                                         'user' => $view->user,
                                         'date' => $view->created_at,
                                         'message' => $view->user->fullname . ' viewed your profile'
                                     ];
                                 });

        // Recent interests received
        $interests = UserInterest::where('interested_user_id', $user->id)
                                ->with('user:id,firstname,lastname,image')
                                ->latest()
                                ->take(5)
                                ->get()
                                ->map(function($interest) {
                                    return [
                                        'type' => 'interest_received',
                                        'user' => $interest->user,
                                        'date' => $interest->created_at,
                                        'message' => $interest->user->fullname . ' expressed interest in you'
                                    ];
                                });

        $activities = $activities->merge($profileViews)->merge($interests)
                                ->sortByDesc('date')
                                ->take(10)
                                ->values();

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    public function profileViews(Request $request)
    {
        $user = $request->user();
        
        $views = ContactView::where('viewed_user_id', $user->id)
                           ->with('user:id,firstname,lastname,image,city,state')
                           ->latest()
                           ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $views
        ]);
    }

    public function kycStatus(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'status' => $user->kv,
                'kyc_data' => $user->kyc_data,
                'kyc_rejection_reason' => $user->kyc_rejection_reason,
            ]
        ]);
    }

    public function submitKyc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kyc_data' => 'required|array',
            'documents' => 'sometimes|array',
            'documents.*' => 'file|mimes:jpeg,png,jpg,pdf|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            // If user already verified, reject
            if ($user->kv == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'KYC already verified'
                ], 422);
            }

            $kycData = $request->kyc_data;

            // Handle file uploads
            if ($request->hasFile('documents')) {
                $documents = [];
                foreach ($request->file('documents') as $key => $file) {
                    $filename = time() . '_' . $key . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('kyc-documents', $filename, 'public');
                    $documents[$key] = $path;
                }
                $kycData['documents'] = $documents;
            }

            $user->kyc_data = $kycData;
            $user->kv = 2; // Pending verification
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'KYC submitted successfully. Please wait for verification.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit KYC',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function kycForm(Request $request)
    {
        // Return KYC form structure from settings
        $kycForm = \App\Models\Form::where('act', 'kyc')->first();
        
        return response()->json([
            'success' => true,
            'data' => [
                'form_data' => $kycForm ? $kycForm->form_data : [],
            ]
        ]);
    }

    private function calculateProfileCompletion($user)
    {
        $sections = [
            'basic_info' => $user->basicInfo ? 20 : 0,
            'physical_attributes' => $user->physicalAttribute ? 15 : 0,
            'religion_info' => $user->religionInfo ? 15 : 0,
            'family_info' => $user->familyInfo ? 15 : 0,
            'career_info' => $user->careerInfo()->exists() ? 15 : 0,
            'education_info' => $user->educationInfo()->exists() ? 10 : 0,
            'partner_expectations' => $user->partnerExpectation ? 10 : 0,
        ];

        $totalPercentage = array_sum($sections);

        return [
            'percentage' => $totalPercentage,
            'sections' => $sections,
            'missing_sections' => array_keys(array_filter($sections, function($value) {
                return $value === 0;
            }))
        ];
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
                'used_interests' => UserInterest::where('user_id', $user->id)
                                                ->whereMonth('created_at', now()->month)
                                                ->count(),
                'used_contact_views' => ContactView::where('user_id', $user->id)
                                                  ->whereMonth('created_at', now()->month)
                                                  ->count(),
            ];
        }

        return null;
    }
}