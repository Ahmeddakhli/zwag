<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReligionInfo;
use App\Models\BloodGroup;
use App\Models\MaritalStatus;
use App\Models\Language;
use App\Models\Package;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;

class DataController extends Controller
{
    public function religions()
    {
        try {
            $religions = ReligionInfo::where('status', 1)->get();

            return response()->json([
                'success' => true,
                'data' => $religions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch religions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bloodGroups()
    {
        try {
            $bloodGroups = BloodGroup::where('status', 1)->get();

            return response()->json([
                'success' => true,
                'data' => $bloodGroups
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch blood groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function maritalStatuses()
    {
        try {
            $maritalStatuses = MaritalStatus::where('status', 1)->get();

            return response()->json([
                'success' => true,
                'data' => $maritalStatuses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch marital statuses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function languages()
    {
        try {
            $languages = Language::where('status', 1)->get();

            return response()->json([
                'success' => true,
                'data' => $languages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch languages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function packages()
    {
        try {
            $packages = Package::where('status', 1)->get();

            return response()->json([
                'success' => true,
                'data' => $packages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch packages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generalSettings()
    {
        try {
            $settings = [
                'site_name' => gs('site_name'),
                'site_currency' => gs('cur_text'),
                'currency_symbol' => gs('cur_sym'),
                'email_verification' => gs('ev'),
                'mobile_verification' => gs('sv'),
                'social_login_enabled' => [
                    'google' => gs('socialite_credentials.google.status') ?? 0,
                    'facebook' => gs('socialite_credentials.facebook.status') ?? 0,
                ],
                'kyc_required' => gs('kv'),
                'registration_enabled' => gs('registration'),
                'contact_info' => [
                    'email' => gs('email_from'),
                    'phone' => gs('contact_number'),
                    'address' => gs('contact_address'),
                ],
                'social_links' => [
                    'facebook' => gs('facebook'),
                    'twitter' => gs('twitter'),
                    'linkedin' => gs('linkedin'),
                    'instagram' => gs('instagram'),
                    'youtube' => gs('youtube'),
                ],
                'app_info' => [
                    'timezone' => gs('timezone'),
                    'date_format' => gs('date_format'),
                    'time_format' => gs('time_format'),
                ],
                'features' => [
                    'multi_language' => gs('multi_language'),
                    'force_ssl' => gs('force_ssl'),
                    'maintenance_mode' => gs('maintenance_mode'),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch general settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function countries()
    {
        try {
            $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')), true);

            return response()->json([
                'success' => true,
                'data' => $countries
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch countries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function genderOptions()
    {
        try {
            $genderOptions = [
                ['id' => 1, 'name' => 'Male'],
                ['id' => 2, 'name' => 'Female'],
            ];

            return response()->json([
                'success' => true,
                'data' => $genderOptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch gender options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function complexionOptions()
    {
        try {
            $complexionOptions = [
                ['id' => 'fair', 'name' => 'Fair'],
                ['id' => 'wheatish', 'name' => 'Wheatish'],
                ['id' => 'medium', 'name' => 'Medium'],
                ['id' => 'olive', 'name' => 'Olive'],
                ['id' => 'dark', 'name' => 'Dark'],
            ];

            return response()->json([
                'success' => true,
                'data' => $complexionOptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch complexion options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bodyTypeOptions()
    {
        try {
            $bodyTypeOptions = [
                ['id' => 'slim', 'name' => 'Slim'],
                ['id' => 'average', 'name' => 'Average'],
                ['id' => 'athletic', 'name' => 'Athletic'],
                ['id' => 'heavyset', 'name' => 'Heavyset'],
            ];

            return response()->json([
                'success' => true,
                'data' => $bodyTypeOptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch body type options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function familyTypeOptions()
    {
        try {
            $familyTypeOptions = [
                ['id' => 'nuclear', 'name' => 'Nuclear'],
                ['id' => 'joint', 'name' => 'Joint'],
                ['id' => 'extended', 'name' => 'Extended'],
            ];

            return response()->json([
                'success' => true,
                'data' => $familyTypeOptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch family type options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function familyStatusOptions()
    {
        try {
            $familyStatusOptions = [
                ['id' => 'upper_middle', 'name' => 'Upper Middle Class'],
                ['id' => 'middle', 'name' => 'Middle Class'],
                ['id' => 'lower_middle', 'name' => 'Lower Middle Class'],
                ['id' => 'upper', 'name' => 'Upper Class'],
                ['id' => 'affluent', 'name' => 'Affluent'],
            ];

            return response()->json([
                'success' => true,
                'data' => $familyStatusOptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch family status options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function educationLevels()
    {
        try {
            $educationLevels = [
                ['id' => 'high_school', 'name' => 'High School'],
                ['id' => 'diploma', 'name' => 'Diploma'],
                ['id' => 'bachelor', 'name' => 'Bachelor\'s Degree'],
                ['id' => 'master', 'name' => 'Master\'s Degree'],
                ['id' => 'doctorate', 'name' => 'Doctorate'],
                ['id' => 'professional', 'name' => 'Professional Degree'],
            ];

            return response()->json([
                'success' => true,
                'data' => $educationLevels
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch education levels',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function professionCategories()
    {
        try {
            $professionCategories = [
                ['id' => 'business', 'name' => 'Business'],
                ['id' => 'service', 'name' => 'Service'],
                ['id' => 'professional', 'name' => 'Professional'],
                ['id' => 'self_employed', 'name' => 'Self Employed'],
                ['id' => 'retired', 'name' => 'Retired'],
                ['id' => 'not_working', 'name' => 'Not Working'],
                ['id' => 'student', 'name' => 'Student'],
            ];

            return response()->json([
                'success' => true,
                'data' => $professionCategories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profession categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function smokingDrinkingOptions()
    {
        try {
            $options = [
                ['id' => 'never', 'name' => 'Never'],
                ['id' => 'occasionally', 'name' => 'Occasionally'],
                ['id' => 'regularly', 'name' => 'Regularly'],
                ['id' => 'prefer_not_to_say', 'name' => 'Prefer not to say'],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'smoking' => $options,
                    'drinking' => $options,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch smoking/drinking options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function heightOptions()
    {
        try {
            $heights = [];
            for ($feet = 4; $feet <= 7; $feet++) {
                for ($inches = 0; $inches <= 11; $inches++) {
                    $totalInches = ($feet * 12) + $inches;
                    $cm = round($totalInches * 2.54);
                    $heights[] = [
                        'feet' => $feet,
                        'inches' => $inches,
                        'total_inches' => $totalInches,
                        'cm' => $cm,
                        'display' => $feet . "'" . $inches . '"',
                        'display_metric' => $cm . ' cm'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $heights
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch height options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function weightOptions()
    {
        try {
            $weights = [];
            for ($kg = 40; $kg <= 150; $kg++) {
                $pounds = round($kg * 2.205);
                $weights[] = [
                    'kg' => $kg,
                    'pounds' => $pounds,
                    'display' => $kg . ' kg',
                    'display_imperial' => $pounds . ' lbs'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $weights
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch weight options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function ageOptions()
    {
        try {
            $ages = [];
            for ($age = 18; $age <= 80; $age++) {
                $ages[] = [
                    'value' => $age,
                    'display' => $age . ' years'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $ages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch age options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function eyeColorOptions()
    {
        try {
            $eyeColors = [
                ['id' => 'black', 'name' => 'Black'],
                ['id' => 'brown', 'name' => 'Brown'],
                ['id' => 'hazel', 'name' => 'Hazel'],
                ['id' => 'green', 'name' => 'Green'],
                ['id' => 'blue', 'name' => 'Blue'],
                ['id' => 'gray', 'name' => 'Gray'],
                ['id' => 'amber', 'name' => 'Amber'],
            ];

            return response()->json([
                'success' => true,
                'data' => $eyeColors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch eye color options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function hairColorOptions()
    {
        try {
            $hairColors = [
                ['id' => 'black', 'name' => 'Black'],
                ['id' => 'brown', 'name' => 'Brown'],
                ['id' => 'blonde', 'name' => 'Blonde'],
                ['id' => 'red', 'name' => 'Red'],
                ['id' => 'gray', 'name' => 'Gray'],
                ['id' => 'white', 'name' => 'White'],
                ['id' => 'bald', 'name' => 'Bald'],
            ];

            return response()->json([
                'success' => true,
                'data' => $hairColors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch hair color options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function allFormData()
    {
        try {
            $data = [
                'religions' => ReligionInfo::where('status', 1)->get(),
                'blood_groups' => BloodGroup::where('status', 1)->get(),
                'marital_statuses' => MaritalStatus::where('status', 1)->get(),
                'languages' => Language::where('status', 1)->get(),
                'packages' => Package::where('status', 1)->get(),
                'genders' => [
                    ['id' => 1, 'name' => 'Male'],
                    ['id' => 2, 'name' => 'Female'],
                ],
                'complexions' => [
                    ['id' => 'fair', 'name' => 'Fair'],
                    ['id' => 'wheatish', 'name' => 'Wheatish'],
                    ['id' => 'medium', 'name' => 'Medium'],
                    ['id' => 'olive', 'name' => 'Olive'],
                    ['id' => 'dark', 'name' => 'Dark'],
                ],
                'body_types' => [
                    ['id' => 'slim', 'name' => 'Slim'],
                    ['id' => 'average', 'name' => 'Average'],
                    ['id' => 'athletic', 'name' => 'Athletic'],
                    ['id' => 'heavyset', 'name' => 'Heavyset'],
                ],
                'family_types' => [
                    ['id' => 'nuclear', 'name' => 'Nuclear'],
                    ['id' => 'joint', 'name' => 'Joint'],
                    ['id' => 'extended', 'name' => 'Extended'],
                ],
                'family_statuses' => [
                    ['id' => 'upper_middle', 'name' => 'Upper Middle Class'],
                    ['id' => 'middle', 'name' => 'Middle Class'],
                    ['id' => 'lower_middle', 'name' => 'Lower Middle Class'],
                    ['id' => 'upper', 'name' => 'Upper Class'],
                    ['id' => 'affluent', 'name' => 'Affluent'],
                ],
                'education_levels' => [
                    ['id' => 'high_school', 'name' => 'High School'],
                    ['id' => 'diploma', 'name' => 'Diploma'],
                    ['id' => 'bachelor', 'name' => 'Bachelor\'s Degree'],
                    ['id' => 'master', 'name' => 'Master\'s Degree'],
                    ['id' => 'doctorate', 'name' => 'Doctorate'],
                    ['id' => 'professional', 'name' => 'Professional Degree'],
                ],
                'smoking_drinking_options' => [
                    ['id' => 'never', 'name' => 'Never'],
                    ['id' => 'occasionally', 'name' => 'Occasionally'],
                    ['id' => 'regularly', 'name' => 'Regularly'],
                    ['id' => 'prefer_not_to_say', 'name' => 'Prefer not to say'],
                ],
                'eye_colors' => [
                    ['id' => 'black', 'name' => 'Black'],
                    ['id' => 'brown', 'name' => 'Brown'],
                    ['id' => 'hazel', 'name' => 'Hazel'],
                    ['id' => 'green', 'name' => 'Green'],
                    ['id' => 'blue', 'name' => 'Blue'],
                    ['id' => 'gray', 'name' => 'Gray'],
                    ['id' => 'amber', 'name' => 'Amber'],
                ],
                'hair_colors' => [
                    ['id' => 'black', 'name' => 'Black'],
                    ['id' => 'brown', 'name' => 'Brown'],
                    ['id' => 'blonde', 'name' => 'Blonde'],
                    ['id' => 'red', 'name' => 'Red'],
                    ['id' => 'gray', 'name' => 'Gray'],
                    ['id' => 'white', 'name' => 'White'],
                    ['id' => 'bald', 'name' => 'Bald'],
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch form data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}