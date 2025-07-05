<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserInterest;
use App\Models\ShortListedProfile;
use App\Models\IgnoredProfile;
use App\Models\ContactView;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'age_min' => 'nullable|integer|min:18|max:100',
            'age_max' => 'nullable|integer|min:18|max:100',
            'height_min' => 'nullable|numeric',
            'height_max' => 'nullable|numeric',
            'religion' => 'nullable|string',
            'marital_status' => 'nullable|string',
            'education' => 'nullable|string',
            'profession' => 'nullable|string',
            'location' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:50',
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
            $perPage = $request->get('per_page', 20);

            // Get users to exclude (ignored and already interested)
            $excludeIds = $this->getExcludedUserIds($user);

            $query = User::where('id', '!=', $user->id)
                        ->where('status', 1)
                        ->whereNotIn('id', $excludeIds)
                        ->with([
                            'basicInfo', 
                            'physicalAttribute', 
                            'religionInfo', 
                            'careerInfo', 
                            'educationInfo',
                            'galleries' => function($q) {
                                $q->where('status', 1)->limit(3);
                            }
                        ]);

            // Apply filters based on partner expectations
            if ($user->partnerExpectation) {
                $expectations = $user->partnerExpectation;
                
                if ($expectations->age_from && $expectations->age_to) {
                    $query->whereBetween('age', [$expectations->age_from, $expectations->age_to]);
                }
            }

            // Apply search filters
            if ($request->filled('age_min') && $request->filled('age_max')) {
                $query->whereBetween('age', [$request->age_min, $request->age_max]);
            }

            if ($request->filled('location')) {
                $query->where(function($q) use ($request) {
                    $q->where('city', 'like', '%' . $request->location . '%')
                      ->orWhere('state', 'like', '%' . $request->location . '%')
                      ->orWhere('country', 'like', '%' . $request->location . '%');
                });
            }

            if ($request->filled('religion')) {
                $query->whereHas('religionInfo', function($q) use ($request) {
                    $q->where('religion', 'like', '%' . $request->religion . '%');
                });
            }

            if ($request->filled('height_min') && $request->filled('height_max')) {
                $query->whereHas('physicalAttribute', function($q) use ($request) {
                    $q->whereBetween('height', [$request->height_min, $request->height_max]);
                });
            }

            if ($request->filled('profession')) {
                $query->whereHas('careerInfo', function($q) use ($request) {
                    $q->where('designation', 'like', '%' . $request->profession . '%')
                      ->orWhere('company', 'like', '%' . $request->profession . '%');
                });
            }

            if ($request->filled('education')) {
                $query->whereHas('educationInfo', function($q) use ($request) {
                    $q->where('degree', 'like', '%' . $request->education . '%')
                      ->orWhere('institution', 'like', '%' . $request->education . '%');
                });
            }

            $matches = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $matches,
                'message' => 'Matches retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search matches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function recommendations(Request $request)
    {
        try {
            $user = $request->user();
            $excludeIds = $this->getExcludedUserIds($user);

            $query = User::where('id', '!=', $user->id)
                        ->where('status', 1)
                        ->whereNotIn('id', $excludeIds)
                        ->with([
                            'basicInfo', 
                            'physicalAttribute', 
                            'religionInfo', 
                            'careerInfo', 
                            'educationInfo',
                            'galleries' => function($q) {
                                $q->where('status', 1)->limit(3);
                            }
                        ]);

            // Apply partner expectations if available
            if ($user->partnerExpectation) {
                $expectations = $user->partnerExpectation;
                
                if ($expectations->age_from && $expectations->age_to) {
                    $query->whereBetween('age', [$expectations->age_from, $expectations->age_to]);
                }

                if ($expectations->religion) {
                    $religions = json_decode($expectations->religion, true);
                    if ($religions) {
                        $query->whereHas('religionInfo', function($q) use ($religions) {
                            $q->whereIn('religion', $religions);
                        });
                    }
                }

                if ($expectations->height_from && $expectations->height_to) {
                    $query->whereHas('physicalAttribute', function($q) use ($expectations) {
                        $q->whereBetween('height', [$expectations->height_from, $expectations->height_to]);
                    });
                }

                if ($expectations->location_preference) {
                    $query->where(function($q) use ($expectations) {
                        $q->where('city', 'like', '%' . $expectations->location_preference . '%')
                          ->orWhere('state', 'like', '%' . $expectations->location_preference . '%');
                    });
                }
            }

            $recommendations = $query->inRandomOrder()->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $recommendations,
                'message' => 'Recommendations retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function profile(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $profile = User::where('id', $id)
                          ->where('status', 1)
                          ->with([
                              'basicInfo', 
                              'physicalAttribute', 
                              'religionInfo', 
                              'familyInfo',
                              'careerInfo', 
                              'educationInfo',
                              'partnerExpectation',
                              'galleries' => function($q) {
                                  $q->where('status', 1);
                              }
                          ])
                          ->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found'
                ], 404);
            }

            // Check if user has already expressed interest
            $hasInterest = UserInterest::where('user_id', $user->id)
                                     ->where('interested_user_id', $id)
                                     ->exists();

            // Check if user has shortlisted this profile
            $isShortlisted = ShortListedProfile::where('user_id', $user->id)
                                              ->where('shortlisted_user_id', $id)
                                              ->exists();

            // Check if user has ignored this profile
            $isIgnored = IgnoredProfile::where('user_id', $user->id)
                                      ->where('ignored_user_id', $id)
                                      ->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'profile' => $profile->makeHidden(['password', 'remember_token']),
                    'interactions' => [
                        'has_interest' => $hasInterest,
                        'is_shortlisted' => $isShortlisted,
                        'is_ignored' => $isIgnored,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewProfile(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            // Check if profile exists
            $profile = User::find($id);
            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found'
                ], 404);
            }

            // Record profile view (avoid duplicate views on same day)
            ContactView::firstOrCreate([
                'user_id' => $user->id,
                'viewed_user_id' => $id,
                'date' => now()->format('Y-m-d')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile view recorded'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record profile view',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function recentVisitors(Request $request)
    {
        try {
            $user = $request->user();
            
            $visitors = ContactView::where('viewed_user_id', $user->id)
                                  ->with('user:id,firstname,lastname,image,city,state')
                                  ->latest()
                                  ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $visitors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recent visitors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function interests(Request $request)
    {
        try {
            $user = $request->user();
            
            $interests = UserInterest::where('user_id', $user->id)
                                   ->orWhere('interested_user_id', $user->id)
                                   ->with(['user:id,firstname,lastname,image', 'interestedUser:id,firstname,lastname,image'])
                                   ->latest()
                                   ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $interests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get interests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sentInterests(Request $request)
    {
        try {
            $user = $request->user();
            
            $interests = UserInterest::where('user_id', $user->id)
                                   ->with('interestedUser:id,firstname,lastname,image,city,state')
                                   ->latest()
                                   ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $interests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sent interests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function receivedInterests(Request $request)
    {
        try {
            $user = $request->user();
            
            $interests = UserInterest::where('interested_user_id', $user->id)
                                   ->with('user:id,firstname,lastname,image,city,state')
                                   ->latest()
                                   ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $interests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get received interests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendInterest(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Check if target user exists
            $targetUser = User::find($id);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Check if already sent interest
            $existingInterest = UserInterest::where('user_id', $user->id)
                                          ->where('interested_user_id', $id)
                                          ->first();

            if ($existingInterest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Interest already sent to this user'
                ], 422);
            }

            // Check package limits
            $packageLimitCheck = $this->checkInterestLimit($user);
            if (!$packageLimitCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $packageLimitCheck['message']
                ], 422);
            }

            // Create interest
            UserInterest::create([
                'user_id' => $user->id,
                'interested_user_id' => $id,
                'status' => 0 // Pending
            ]);

            // Send notification to target user
            notify($targetUser, 'INTEREST_RECEIVED', [
                'user_name' => $user->fullname,
                'profile_link' => route('user.member.profile.public', $user->id)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Interest sent successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send interest',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function acceptInterest(Request $request, $id)
    {
        try {
            $user = $request->user();

            $interest = UserInterest::where('id', $id)
                                  ->where('interested_user_id', $user->id)
                                  ->where('status', 0)
                                  ->first();

            if (!$interest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Interest not found'
                ], 404);
            }

            $interest->status = 1; // Accepted
            $interest->save();

            // Send notification to the user who sent interest
            notify($interest->user, 'INTEREST_ACCEPTED', [
                'user_name' => $user->fullname,
                'profile_link' => route('user.member.profile.public', $user->id)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Interest accepted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept interest',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function declineInterest(Request $request, $id)
    {
        try {
            $user = $request->user();

            $interest = UserInterest::where('id', $id)
                                  ->where('interested_user_id', $user->id)
                                  ->where('status', 0)
                                  ->first();

            if (!$interest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Interest not found'
                ], 404);
            }

            $interest->status = 2; // Declined
            $interest->save();

            return response()->json([
                'success' => true,
                'message' => 'Interest declined'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to decline interest',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function removeInterest(Request $request, $id)
    {
        try {
            $user = $request->user();

            $interest = UserInterest::where('id', $id)
                                  ->where(function($query) use ($user) {
                                      $query->where('user_id', $user->id)
                                            ->orWhere('interested_user_id', $user->id);
                                  })
                                  ->first();

            if (!$interest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Interest not found'
                ], 404);
            }

            $interest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Interest removed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove interest',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function shortlistedProfiles(Request $request)
    {
        try {
            $user = $request->user();
            
            $shortlisted = ShortListedProfile::where('user_id', $user->id)
                                           ->with('shortlistedUser:id,firstname,lastname,image,city,state')
                                           ->latest()
                                           ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $shortlisted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get shortlisted profiles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addToShortlist(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Check if target user exists
            $targetUser = User::find($id);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Check if already shortlisted
            $existing = ShortListedProfile::where('user_id', $user->id)
                                        ->where('shortlisted_user_id', $id)
                                        ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile already shortlisted'
                ], 422);
            }

            ShortListedProfile::create([
                'user_id' => $user->id,
                'shortlisted_user_id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile added to shortlist'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add to shortlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function removeFromShortlist(Request $request, $id)
    {
        try {
            $user = $request->user();

            $shortlisted = ShortListedProfile::where('user_id', $user->id)
                                           ->where('shortlisted_user_id', $id)
                                           ->first();

            if (!$shortlisted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found in shortlist'
                ], 404);
            }

            $shortlisted->delete();

            return response()->json([
                'success' => true,
                'message' => 'Profile removed from shortlist'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove from shortlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function ignoredProfiles(Request $request)
    {
        try {
            $user = $request->user();
            
            $ignored = IgnoredProfile::where('user_id', $user->id)
                                   ->with('ignoredUser:id,firstname,lastname,image,city,state')
                                   ->latest()
                                   ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $ignored
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get ignored profiles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function ignoreProfile(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Check if target user exists
            $targetUser = User::find($id);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Check if already ignored
            $existing = IgnoredProfile::where('user_id', $user->id)
                                    ->where('ignored_user_id', $id)
                                    ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile already ignored'
                ], 422);
            }

            IgnoredProfile::create([
                'user_id' => $user->id,
                'ignored_user_id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile ignored'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to ignore profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unignoreProfile(Request $request, $id)
    {
        try {
            $user = $request->user();

            $ignored = IgnoredProfile::where('user_id', $user->id)
                                   ->where('ignored_user_id', $id)
                                   ->first();

            if (!$ignored) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile not found in ignored list'
                ], 404);
            }

            $ignored->delete();

            return response()->json([
                'success' => true,
                'message' => 'Profile removed from ignored list'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unignore profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function contactViews(Request $request)
    {
        try {
            $user = $request->user();
            
            $contacts = ContactView::where('user_id', $user->id)
                                  ->with('viewedUser:id,firstname,lastname,image,city,state,mobile,email')
                                  ->latest()
                                  ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $contacts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get contact views',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewContact(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Check package limits
            $packageLimitCheck = $this->checkContactViewLimit($user);
            if (!$packageLimitCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $packageLimitCheck['message']
                ], 422);
            }

            // Check if target user exists
            $targetUser = User::find($id);
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Check if already viewed contact
            $existing = ContactView::where('user_id', $user->id)
                                  ->where('viewed_user_id', $id)
                                  ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contact already viewed',
                    'data' => [
                        'mobile' => $targetUser->mobile,
                        'email' => $targetUser->email,
                    ]
                ]);
            }

            // Record contact view
            ContactView::create([
                'user_id' => $user->id,
                'viewed_user_id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contact details retrieved',
                'data' => [
                    'mobile' => $targetUser->mobile,
                    'email' => $targetUser->email,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to view contact',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function contactLimitStatus(Request $request)
    {
        try {
            $user = $request->user();
            $limitCheck = $this->checkContactViewLimit($user);

            return response()->json([
                'success' => true,
                'data' => $limitCheck
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get contact limit status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getExcludedUserIds($user)
    {
        $excludeIds = [];

        // Add ignored profiles
        $ignoredIds = IgnoredProfile::where('user_id', $user->id)
                                  ->pluck('ignored_user_id')
                                  ->toArray();
        $excludeIds = array_merge($excludeIds, $ignoredIds);

        // Add profiles that ignored this user
        $ignoredByIds = IgnoredProfile::where('ignored_user_id', $user->id)
                                    ->pluck('user_id')
                                    ->toArray();
        $excludeIds = array_merge($excludeIds, $ignoredByIds);

        return array_unique($excludeIds);
    }

    private function checkInterestLimit($user)
    {
        $package = $this->getCurrentPackage($user);
        
        if (!$package) {
            return [
                'allowed' => false,
                'message' => 'No active package found. Please purchase a package to send interests.'
            ];
        }

        $currentMonthInterests = UserInterest::where('user_id', $user->id)
                                           ->whereMonth('created_at', now()->month)
                                           ->count();

        $limit = $package->interest_express_limit ?? 0;

        if ($currentMonthInterests >= $limit) {
            return [
                'allowed' => false,
                'message' => 'Monthly interest limit reached. Please upgrade your package.'
            ];
        }

        return [
            'allowed' => true,
            'used' => $currentMonthInterests,
            'limit' => $limit,
            'remaining' => $limit - $currentMonthInterests
        ];
    }

    private function checkContactViewLimit($user)
    {
        $package = $this->getCurrentPackage($user);
        
        if (!$package) {
            return [
                'allowed' => false,
                'message' => 'No active package found. Please purchase a package to view contacts.'
            ];
        }

        $currentMonthViews = ContactView::where('user_id', $user->id)
                                       ->whereMonth('created_at', now()->month)
                                       ->count();

        $limit = $package->contact_view_limit ?? 0;

        if ($currentMonthViews >= $limit) {
            return [
                'allowed' => false,
                'message' => 'Monthly contact view limit reached. Please upgrade your package.'
            ];
        }

        return [
            'allowed' => true,
            'used' => $currentMonthViews,
            'limit' => $limit,
            'remaining' => $limit - $currentMonthViews
        ];
    }

    private function getCurrentPackage($user)
    {
        $purchaseHistory = $user->purchaseHistory()
                               ->where('expired_date', '>', now())
                               ->latest()
                               ->first();

        return $purchaseHistory ? $purchaseHistory->package : null;
    }
}