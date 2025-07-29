<?php

namespace Database\Seeders;

use App\Models\Lookup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LookupSeeder extends Seeder
{
    public function run()
    {
        $lookups = [
            // Applicant Status
            [
                'slug' => 'applicant-status',
                'title' => [
                    'en' => 'Applicant Status',
                    'ar' => 'حالة مقدم الطلب'
                ],
                'options' => [
                    ['slug' => 'self', 'title' => ['en' => 'Self', 'ar' => 'نفسي']],
                    ['slug' => 'parent', 'title' => ['en' => 'Parent', 'ar' => 'والد']],
                    ['slug' => 'guardian', 'title' => ['en' => 'Guardian', 'ar' => 'وصي']],
                    ['slug' => 'other', 'title' => ['en' => 'Other', 'ar' => 'آخر']],
                ]
            ],

            // Gender
            [
                'slug' => 'gender',
                'title' => [
                    'en' => 'Gender',
                    'ar' => 'الجنس'
                ],
                'options' => [
                    ['slug' => 'male', 'title' => ['en' => 'Male', 'ar' => 'ذكر']],
                    ['slug' => 'female', 'title' => ['en' => 'Female', 'ar' => 'أنثى']],
                ]
            ],

            // Marital Status
            [
                'slug' => 'marital-status',
                'title' => [
                    'en' => 'Marital Status',
                    'ar' => 'الحالة الاجتماعية'
                ],
                'options' => [
                    ['slug' => 'single', 'title' => ['en' => 'Single', 'ar' => 'أعزب']],
                    ['slug' => 'divorced', 'title' => ['en' => 'Divorced', 'ar' => 'مطلق']],
                    ['slug' => 'widowed', 'title' => ['en' => 'Widowed', 'ar' => 'أرمل']],
                ]
            ],

            // Has Children
            [
                'slug' => 'has-children',
                'title' => [
                    'en' => 'Has Children',
                    'ar' => 'لديه أطفال'
                ],
                'options' => [
                    ['slug' => 'yes', 'title' => ['en' => 'Yes', 'ar' => 'نعم']],
                    ['slug' => 'no', 'title' => ['en' => 'No', 'ar' => 'لا']],
                ]
            ],
            // Children number
            [
                'slug' => 'children-number',
                'title' => [
                    'en' => 'Children Number',
                    'ar' => 'عدد الأطفال'
                ],
                'options' => [
                    ['slug' => 'none', 'title' => ['en' => 'None', 'ar' => 'بدون اطفال']],
                    ['slug' => 'one', 'title' => ['en' => 'One', 'ar' => 'واحد']],
                    ['slug' => 'two', 'title' => ['en' => 'Two', 'ar' => 'اثنين']],
                    ['slug' => 'three', 'title' => ['en' => 'Three', 'ar' => 'ثلاثة']],
                    ['slug' => 'four', 'title' => ['en' => 'Four', 'ar' => 'اربعة']],
                ]
            ],
            // Children Live With Me
            [
                'slug' => 'children-live-with-me',
                'title' => [
                    'en' => 'Children Live With Me',
                    'ar' => 'الأطفال يعيشون معي'
                ],
                'options' => [
                    ['slug' => 'yes', 'title' => ['en' => 'Yes', 'ar' => 'نعم']],
                    ['slug' => 'no', 'title' => ['en' => 'No', 'ar' => 'لا']],
                    ['slug' => 'partial', 'title' => ['en' => 'Partial', 'ar' => 'جزئي']],
                ]
            ],

            // Parents Alive Status
            [
                'slug' => 'parents-alive-status',
                'title' => [
                    'en' => 'Parents Alive Status',
                    'ar' => 'حالة الوالدين'
                ],
                'options' => [
                    ['slug' => 'both', 'title' => ['en' => 'Both', 'ar' => 'كلاهما']],
                    ['slug' => 'father', 'title' => ['en' => 'Father', 'ar' => 'الأب']],
                    ['slug' => 'mother', 'title' => ['en' => 'Mother', 'ar' => 'الأم']],
                    ['slug' => 'none', 'title' => ['en' => 'None', 'ar' => 'لا أحد']],
                ]
            ],

            // Parent Relationship Quality
            [
                'slug' => 'parent-relationship-quality',
                'title' => [
                    'en' => 'Parent Relationship Quality',
                    'ar' => 'جودة العلاقة مع الوالدين'
                ],
                'options' => [
                    ['slug' => 'excellent', 'title' => ['en' => 'Excellent', 'ar' => 'ممتاز']],
                    ['slug' => 'good', 'title' => ['en' => 'Good', 'ar' => 'جيد']],
                    ['slug' => 'average', 'title' => ['en' => 'Average', 'ar' => 'متوسط']],
                    ['slug' => 'poor', 'title' => ['en' => 'Poor', 'ar' => 'ضعيف']],
                ]
            ],

            // Appearance
            [
                'slug' => 'appearance',
                'title' => [
                    'en' => 'Appearance',
                    'ar' => 'المظهر'
                ],
                'options' => [
                    ['slug' => 'very-attractive', 'title' => ['en' => 'Very Attractive', 'ar' => 'جذاب جدًا']],
                    ['slug' => 'attractive', 'title' => ['en' => 'Attractive', 'ar' => 'جذاب']],
                    ['slug' => 'average', 'title' => ['en' => 'Average', 'ar' => 'متوسط']],
                    ['slug' => 'simple', 'title' => ['en' => 'Simple', 'ar' => 'عادي']],
                ]
            ],

            // Skin Color
            [
                'slug' => 'skin-color',
                'title' => [
                    'en' => 'Skin Color',
                    'ar' => 'لون البشرة'
                ],
                'options' => [
                    ['slug' => 'fair', 'title' => ['en' => 'Fair', 'ar' => 'فاتح']],
                    ['slug' => 'medium', 'title' => ['en' => 'Medium', 'ar' => 'متوسط']],
                    ['slug' => 'olive', 'title' => ['en' => 'Olive', 'ar' => 'زيتوني']],
                    ['slug' => 'dark', 'title' => ['en' => 'Dark', 'ar' => 'غامق']],
                ]
            ],

            // Body Type
            [
                'slug' => 'body-type',
                'title' => [
                    'en' => 'Body Type',
                    'ar' => 'نوع الجسم'
                ],
                'options' => [
                    ['slug' => 'slim', 'title' => ['en' => 'Slim', 'ar' => 'نحيف']],
                    ['slug' => 'average', 'title' => ['en' => 'Average', 'ar' => 'متوسط']],
                    ['slug' => 'athletic', 'title' => ['en' => 'Athletic', 'ar' => 'رياضي']],
                    ['slug' => 'heavy', 'title' => ['en' => 'Heavy', 'ar' => 'ثقيل']],
                ]
            ],

            // Personal Hygiene
            [
                'slug' => 'personal-hygiene',
                'title' => [
                    'en' => 'Personal Hygiene',
                    'ar' => 'النظافة الشخصية'
                ],
                'options' => [
                    ['slug' => 'excellent', 'title' => ['en' => 'Excellent', 'ar' => 'ممتاز']],
                    ['slug' => 'good', 'title' => ['en' => 'Good', 'ar' => 'جيد']],
                    ['slug' => 'average', 'title' => ['en' => 'Average', 'ar' => 'متوسط']],
                ]
            ],

            // Exercise Frequency
            [
                'slug' => 'exercise-frequency',
                'title' => [
                    'en' => 'Exercise Frequency',
                    'ar' => 'تكرار التمارين'
                ],
                'options' => [
                    ['slug' => 'daily', 'title' => ['en' => 'Daily', 'ar' => 'يوميًا']],
                    ['slug' => 'weekly', 'title' => ['en' => 'Weekly', 'ar' => 'أسبوعيًا']],
                    ['slug' => 'monthly', 'title' => ['en' => 'Monthly', 'ar' => 'شهريًا']],
                    ['slug' => 'rarely', 'title' => ['en' => 'Rarely', 'ar' => 'نادرًا']],
                    ['slug' => 'never', 'title' => ['en' => 'Never', 'ar' => 'أبدًا']],
                ]
            ],

            // Financial Status
            [
                'slug' => 'financial-status',
                'title' => [
                    'en' => 'Financial Status',
                    'ar' => 'الحالة المالية'
                ],
                'options' => [
                    ['slug' => 'excellent', 'title' => ['en' => 'Excellent', 'ar' => 'ممتاز']],
                    ['slug' => 'good', 'title' => ['en' => 'Good', 'ar' => 'جيد']],
                    ['slug' => 'average', 'title' => ['en' => 'Average', 'ar' => 'متوسط']],
                    ['slug' => 'struggling', 'title' => ['en' => 'Struggling', 'ar' => 'يعاني']],
                ]
            ],

            // Housing Status
            [
                'slug' => 'housing-status',
                'title' => [
                    'en' => 'Housing Status',
                    'ar' => 'حالة السكن'
                ],
                'options' => [
                    ['slug' => 'owned', 'title' => ['en' => 'Owned', 'ar' => 'ملك']],
                    ['slug' => 'rented', 'title' => ['en' => 'Rented', 'ar' => 'مؤجر']],
                    ['slug' => 'with-family', 'title' => ['en' => 'With Family', 'ar' => 'مع العائلة']],
                ]
            ],

            // Has Personal Car
            [
                'slug' => 'has-personal-car',
                'title' => [
                    'en' => 'Has Personal Car',
                    'ar' => 'يمتلك سيارة خاصة'
                ],
                'options' => [
                    ['slug' => 'yes', 'title' => ['en' => 'Yes', 'ar' => 'نعم']],
                    ['slug' => 'no', 'title' => ['en' => 'No', 'ar' => 'لا']],
                ]
            ],

            // Education Level
            [
                'slug' => 'education-level',
                'title' => [
                    'en' => 'Education Level',
                    'ar' => 'المستوى التعليمي'
                ],
                'options' => [
                    ['slug' => 'primary', 'title' => ['en' => 'Primary', 'ar' => 'ابتدائي']],
                    ['slug' => 'secondary', 'title' => ['en' => 'Secondary', 'ar' => 'ثانوي']],
                    ['slug' => 'high-school', 'title' => ['en' => 'High School', 'ar' => 'ثانوية عامة']],
                    ['slug' => 'diploma', 'title' => ['en' => 'Diploma', 'ar' => 'دبلوم']],
                    ['slug' => 'bachelor', 'title' => ['en' => 'Bachelor', 'ar' => 'بكالوريوس']],
                    ['slug' => 'master', 'title' => ['en' => 'Master', 'ar' => 'ماجستير']],
                    ['slug' => 'doctorate', 'title' => ['en' => 'Doctorate', 'ar' => 'دكتوراه']],
                    ['slug' => 'other', 'title' => ['en' => 'Other', 'ar' => 'آخر']],
                ]
            ],

            // Religion
            [
                'slug' => 'religion',
                'title' => [
                    'en' => 'Religion',
                    'ar' => 'الدين'
                ],
                'options' => [
                    ['slug' => 'islam', 'title' => ['en' => 'Islam', 'ar' => 'الإسلام']],
                ]
            ],

            // Sect
            [
                'slug' => 'sect',
                'title' => [
                    'en' => 'Sect',
                    'ar' => 'المذهب'
                ],
                'options' => [
                    ['slug' => 'sunni', 'title' => ['en' => 'Sunni', 'ar' => 'سني']],
                    ['slug' => 'other', 'title' => ['en' => 'Other', 'ar' => 'آخر']],
                ]
            ],

            // Religious Level
            [
                'slug' => 'religious-level',
                'title' => [
                    'en' => 'Religious Level',
                    'ar' => 'المستوى الديني'
                ],
                'options' => [
                    ['slug' => 'basic', 'title' => ['en' => 'Basic', 'ar' => 'أساسي']],
                    ['slug' => 'practicing', 'title' => ['en' => 'Practicing', 'ar' => 'ملتزم']],
                    ['slug' => 'very-religious', 'title' => ['en' => 'Very Religious', 'ar' => 'متشدد']],
                    ['slug' => 'moderate', 'title' => ['en' => 'Moderate', 'ar' => 'معتدل']],
                ]
            ],

            // Quran Memorization
            [
                'slug' => 'quran-memorization',
                'title' => [
                    'en' => 'Quran Memorization',
                    'ar' => 'حفظ القرآن'
                ],
                'options' => [
                    ['slug' => 'none', 'title' => ['en' => 'None', 'ar' => 'لا شيء']],
                    ['slug' => 'less-than-juz', 'title' => ['en' => 'Less Than Juz', 'ar' => 'أقل من جزء']],
                    ['slug' => 'one-juz', 'title' => ['en' => 'One Juz', 'ar' => 'جزء واحد']],
                    ['slug' => 'few-juz', 'title' => ['en' => 'Few Juz', 'ar' => 'أجزاء قليلة']],
                    ['slug' => 'less-than-quarter', 'title' => ['en' => 'Less Than Quarter', 'ar' => 'أقل من ربع']],
                    ['slug' => 'quarter', 'title' => ['en' => 'Quarter', 'ar' => 'ربع']],
                    ['slug' => 'half', 'title' => ['en' => 'Half', 'ar' => 'نصف']],
                    ['slug' => 'more-than-half', 'title' => ['en' => 'More Than Half', 'ar' => 'أكثر من النصف']],
                    ['slug' => 'full', 'title' => ['en' => 'Full', 'ar' => 'كامل']],
                ]
            ],

            // Quran Reading Frequency
            [
                'slug' => 'quran-reading-frequency',
                'title' => [
                    'en' => 'Quran Reading Frequency',
                    'ar' => 'تكرار قراءة القرآن'
                ],
                'options' => [
                    ['slug' => 'daily', 'title' => ['en' => 'Daily', 'ar' => 'يوميًا']],
                    ['slug' => 'weekly', 'title' => ['en' => 'Weekly', 'ar' => 'أسبوعيًا']],
                    ['slug' => 'monthly', 'title' => ['en' => 'Monthly', 'ar' => 'شهريًا']],
                    ['slug' => 'occasionally', 'title' => ['en' => 'Occasionally', 'ar' => 'أحيانًا']],
                    ['slug' => 'rarely', 'title' => ['en' => 'Rarely', 'ar' => 'نادرًا']],
                ]
            ],

            // Religious Knowledge
            [
                'slug' => 'religious-knowledge',
                'title' => [
                    'en' => 'Religious Knowledge',
                    'ar' => 'المعرفة الدينية'
                ],
                'options' => [
                    ['slug' => 'basic', 'title' => ['en' => 'Basic', 'ar' => 'أساسي']],
                    ['slug' => 'intermediate', 'title' => ['en' => 'Intermediate', 'ar' => 'متوسط']],
                    ['slug' => 'advanced', 'title' => ['en' => 'Advanced', 'ar' => 'متقدم']],
                    ['slug' => 'scholar', 'title' => ['en' => 'Scholar', 'ar' => 'عالم']],
                ]
            ],

            // Charity Work
            [
                'slug' => 'charity-work',
                'title' => [
                    'en' => 'Charity Work',
                    'ar' => 'العمل الخيري'
                ],
                'options' => [
                    ['slug' => 'regularly', 'title' => ['en' => 'Regularly', 'ar' => 'بانتظام']],
                    ['slug' => 'occasionally', 'title' => ['en' => 'Occasionally', 'ar' => 'أحيانًا']],
                    ['slug' => 'rarely', 'title' => ['en' => 'Rarely', 'ar' => 'نادرًا']],
                    ['slug' => 'never', 'title' => ['en' => 'Never', 'ar' => 'أبدًا']],
                ]
            ],

            // Family Importance
            [
                'slug' => 'family-importance',
                'title' => [
                    'en' => 'Family Importance',
                    'ar' => 'أهمية العائلة'
                ],
                'options' => [
                    ['slug' => 'very-important', 'title' => ['en' => 'Very Important', 'ar' => 'مهم جدًا']],
                    ['slug' => 'important', 'title' => ['en' => 'Important', 'ar' => 'مهم']],
                    ['slug' => 'moderate', 'title' => ['en' => 'Moderate', 'ar' => 'معتدل']],
                    ['slug' => 'not-important', 'title' => ['en' => 'Not Important', 'ar' => 'غير مهم']],
                ]
            ],

            // Music Listening
            [
                'slug' => 'music-listening',
                'title' => [
                    'en' => 'Music Listening',
                    'ar' => 'سماع الموسيقى'
                ],
                'options' => [
                    ['slug' => 'never', 'title' => ['en' => 'Never', 'ar' => 'أبدًا']],
                    ['slug' => 'rarely', 'title' => ['en' => 'Rarely', 'ar' => 'نادرًا']],
                    ['slug' => 'sometimes', 'title' => ['en' => 'Sometimes', 'ar' => 'أحيانًا']],
                    ['slug' => 'often', 'title' => ['en' => 'Often', 'ar' => 'غالبًا']],
                ]
            ],

            // Watching TV Shows
            [
                'slug' => 'watching-tv-shows',
                'title' => [
                    'en' => 'Watching TV Shows',
                    'ar' => 'مشاهدة البرامج التلفزيونية'
                ],
                'options' => [
                    ['slug' => 'never', 'title' => ['en' => 'Never', 'ar' => 'أبدًا']],
                    ['slug' => 'rarely', 'title' => ['en' => 'Rarely', 'ar' => 'نادرًا']],
                    ['slug' => 'sometimes', 'title' => ['en' => 'Sometimes', 'ar' => 'أحيانًا']],
                    ['slug' => 'often', 'title' => ['en' => 'Often', 'ar' => 'غالبًا']],
                ]
            ],

            // Smoking Status
            [
                'slug' => 'smoking-status',
                'title' => [
                    'en' => 'Smoking Status',
                    'ar' => 'حالة التدخين'
                ],
                'options' => [
                    ['slug' => 'never', 'title' => ['en' => 'Never', 'ar' => 'أبدًا']],
                    ['slug' => 'quit', 'title' => ['en' => 'Quit', 'ar' => 'أقلع']],
                    ['slug' => 'occasionally', 'title' => ['en' => 'Occasionally', 'ar' => 'أحيانًا']],
                    ['slug' => 'regularly', 'title' => ['en' => 'Regularly', 'ar' => 'بانتظام']],
                ]
            ],

            // Alcohol Consumption
            [
                'slug' => 'alcohol-consumption',
                'title' => [
                    'en' => 'Alcohol Consumption',
                    'ar' => 'استهلاك الكحول'
                ],
                'options' => [
                    ['slug' => 'never', 'title' => ['en' => 'Never', 'ar' => 'أبدًا']],
                    ['slug' => 'rarely', 'title' => ['en' => 'Rarely', 'ar' => 'نادرًا']],
                    ['slug' => 'occasionally', 'title' => ['en' => 'Occasionally', 'ar' => 'أحيانًا']],
                    ['slug' => 'regularly', 'title' => ['en' => 'Regularly', 'ar' => 'بانتظام']],
                ]
            ],

            // Drug Use
            [
                'slug' => 'drug-use',
                'title' => [
                    'en' => 'Drug Use',
                    'ar' => 'تعاطي المخدرات'
                ],
                'options' => [
                    ['slug' => 'never', 'title' => ['en' => 'Never', 'ar' => 'أبدًا']],
                    ['slug' => 'past', 'title' => ['en' => 'Past', 'ar' => 'في الماضي']],
                    ['slug' => 'current', 'title' => ['en' => 'Current', 'ar' => 'حاليًا']],
                ]
            ],

            // Wants Children
            [
                'slug' => 'wants-children',
                'title' => [
                    'en' => 'Wants Children',
                    'ar' => 'يرغب في الأطفال'
                ],
                'options' => [
                    ['slug' => 'yes', 'title' => ['en' => 'Yes', 'ar' => 'نعم']],
                    ['slug' => 'no', 'title' => ['en' => 'No', 'ar' => 'لا']],
                    ['slug' => 'maybe', 'title' => ['en' => 'Maybe', 'ar' => 'ربما']],
                ]
            ],

            // Female Specific: Guardian Relationship
            [
                'slug' => 'guardian-relationship',
                'title' => [
                    'en' => 'Guardian Relationship',
                    'ar' => 'علاقة الوصي'
                ],
                'options' => [
                    ['slug' => 'father', 'title' => ['en' => 'Father', 'ar' => 'الأب']],
                    ['slug' => 'brother', 'title' => ['en' => 'Brother', 'ar' => 'الأخ']],
                    ['slug' => 'uncle', 'title' => ['en' => 'Uncle', 'ar' => 'العم']],
                    ['slug' => 'other', 'title' => ['en' => 'Other', 'ar' => 'آخر']],
                ]
            ],

            // Female Specific: Praying Location
            [
                'slug' => 'female-praying-location',
                'title' => [
                    'en' => 'Female Praying Location',
                    'ar' => 'مكان صلاة الأنثى'
                ],
                'options' => [
                    ['slug' => 'home', 'title' => ['en' => 'Home', 'ar' => 'المنزل']],
                    ['slug' => 'mosque-when-possible', 'title' => ['en' => 'Mosque When Possible', 'ar' => 'المسجد عند الإمكان']],
                ]
            ],

            // Female Specific: Beauty Level
            [
                'slug' => 'beauty-level',
                'title' => [
                    'en' => 'Beauty Level',
                    'ar' => 'مستوى الجمال'
                ],
                'options' => [
                    ['slug' => 'very-beautiful', 'title' => ['en' => 'Very Beautiful', 'ar' => 'جميلة جدًا']],
                    ['slug' => 'beautiful', 'title' => ['en' => 'Beautiful', 'ar' => 'جميلة']],
                    ['slug' => 'average', 'title' => ['en' => 'Average', 'ar' => 'متوسطة']],
                    ['slug' => 'simple', 'title' => ['en' => 'Simple', 'ar' => 'عادية']],
                ]
            ],

            // Female Specific: Cooking Skills
            [
                'slug' => 'cooking-skills',
                'title' => [
                    'en' => 'Cooking Skills',
                    'ar' => 'مهارات الطبخ'
                ],
                'options' => [
                    ['slug' => 'excellent', 'title' => ['en' => 'Excellent', 'ar' => 'ممتاز']],
                    ['slug' => 'good', 'title' => ['en' => 'Good', 'ar' => 'جيد']],
                    ['slug' => 'basic', 'title' => ['en' => 'Basic', 'ar' => 'أساسي']],
                    ['slug' => 'learning', 'title' => ['en' => 'Learning', 'ar' => 'يتعلم']],
                ]
            ],

            // Female Specific: Household Management
            [
                'slug' => 'household-management',
                'title' => [
                    'en' => 'Household Management',
                    'ar' => 'إدارة المنزل'
                ],
                'options' => [
                    ['slug' => 'excellent', 'title' => ['en' => 'Excellent', 'ar' => 'ممتاز']],
                    ['slug' => 'good', 'title' => ['en' => 'Good', 'ar' => 'جيد']],
                    ['slug' => 'basic', 'title' => ['en' => 'Basic', 'ar' => 'أساسي']],
                    ['slug' => 'learning', 'title' => ['en' => 'Learning', 'ar' => 'يتعلم']],
                ]
            ],

            // Female Specific: Work After Marriage
            [
                'slug' => 'work-after-marriage',
                'title' => [
                    'en' => 'Work After Marriage',
                    'ar' => 'العمل بعد الزواج'
                ],
                'options' => [
                    ['slug' => 'yes', 'title' => ['en' => 'Yes', 'ar' => 'نعم']],
                    ['slug' => 'no', 'title' => ['en' => 'No', 'ar' => 'لا']],
                    ['slug' => 'undecided', 'title' => ['en' => 'Undecided', 'ar' => 'غير محدد']],
                ]
            ],
            // marriage Type
            [
                'slug' => 'marriage-type',
                'title' => [
                    'en' => 'Marriage Type',
                    'ar' => 'نوع الزواج'
                ],
                //زوجة اولى , زوجة ثانية, زوجة ثالثة, زوجة رابعة
                'options' => [
                    ['slug' => 'first', 'title' => ['en' => 'First', 'ar' => 'الزوجة الاولى']],
                    ['slug' => 'second', 'title' => ['en' => 'Second', 'ar' => 'الزوجة الثانية']],
                    ['slug' => 'third', 'title' => ['en' => 'Third', 'ar' => 'الزوجة الثالثة']],
                    ['slug' => 'fourth', 'title' => ['en' => 'Fourth', 'ar' => 'الزوجة الرابعة']],
                ]

            ],

            // Male Specific: Praying Location
            [
                'slug' => 'male-praying-location',
                'title' => [
                    'en' => 'Male Praying Location',
                    'ar' => 'مكان صلاة الذكر'
                ],
                'options' => [
                    ['slug' => 'mosque', 'title' => ['en' => 'Mosque', 'ar' => 'المسجد']],
                    ['slug' => 'home', 'title' => ['en' => 'Home', 'ar' => 'المنزل']],
                    ['slug' => 'both', 'title' => ['en' => 'Both', 'ar' => 'كلاهما']],
                ]
            ],

            // Male Specific: Congregational Prayer
            [
                'slug' => 'congregational-prayer',
                'title' => [
                    'en' => 'Congregational Prayer',
                    'ar' => 'صلاة الجماعة'
                ],
                'options' => [
                    ['slug' => 'always', 'title' => ['en' => 'Always', 'ar' => 'دائمًا']],
                    ['slug' => 'usually', 'title' => ['en' => 'Usually', 'ar' => 'عادة']],
                    ['slug' => 'sometimes', 'title' => ['en' => 'Sometimes', 'ar' => 'أحيانًا']],
                    ['slug' => 'rarely', 'title' => ['en' => 'Rarely', 'ar' => 'نادرًا']],
                    ['slug' => 'never', 'title' => ['en' => 'Never', 'ar' => 'أبدًا']],
                ]
            ],

            // Male Specific: Attractiveness Level
            [
                'slug' => 'male-attractiveness-level',
                'title' => [
                    'en' => 'Male Attractiveness Level',
                    'ar' => 'مستوى جاذبية الذكر'
                ],
                'options' => [
                    ['slug' => 'very-handsome', 'title' => ['en' => 'Very Handsome', 'ar' => 'وسيم جدًا']],
                    ['slug' => 'handsome', 'title' => ['en' => 'Handsome', 'ar' => 'وسيم']],
                    ['slug' => 'average', 'title' => ['en' => 'Average', 'ar' => 'متوسط']],
                    ['slug' => 'simple', 'title' => ['en' => 'Simple', 'ar' => 'عادي']],
                ]
            ],

            // Male Specific: Financial Situation
            [
                'slug' => 'male-financial-situation',
                'title' => [
                    'en' => 'Male Financial Situation',
                    'ar' => 'الوضع المالي للذكر'
                ],
                'options' => [
                    ['slug' => 'excellent', 'title' => ['en' => 'Excellent', 'ar' => 'ممتاز']],
                    ['slug' => 'good', 'title' => ['en' => 'Good', 'ar' => 'جيد']],
                    ['slug' => 'average', 'title' => ['en' => 'Average', 'ar' => 'متوسط']],
                    ['slug' => 'struggling', 'title' => ['en' => 'Struggling', 'ar' => 'يعاني']],
                ]
            ],

            // Male Specific: Housing Ownership
            [
                'slug' => 'male-housing-ownership',
                'title' => [
                    'en' => 'Male Housing Ownership',
                    'ar' => 'ملكية السكن للذكر'
                ],
                'options' => [
                    ['slug' => 'owned', 'title' => ['en' => 'Owned', 'ar' => 'ملك']],
                    ['slug' => 'rented', 'title' => ['en' => 'Rented', 'ar' => 'مؤجر']],
                    ['slug' => 'family', 'title' => ['en' => 'Family', 'ar' => 'عائلي']],
                ]
            ],

            // Male Specific: Current Living Arrangement
            [
                'slug' => 'male-current-living-arrangement',
                'title' => [
                    'en' => 'Male Current Living Arrangement',
                    'ar' => 'ترتيبات المعيشة الحالية للذكر'
                ],
                'options' => [
                    ['slug' => 'independent', 'title' => ['en' => 'Independent', 'ar' => 'مستقل']],
                    ['slug' => 'with-family', 'title' => ['en' => 'With Family', 'ar' => 'مع العائلة']],
                    ['slug' => 'shared', 'title' => ['en' => 'Shared', 'ar' => 'مشترك']],
                ]
            ],
        ];

        foreach ($lookups as $lookupData) {
            $lookup = Lookup::create([
                'slug' => $lookupData['slug'],
                'title' => $lookupData['title'],
            ]);

            foreach ($lookupData['options'] as $option) {
                Lookup::create([
                    // make slug unique by appending the lookup slug
                    'slug' => $lookup->slug . '-' . $option['slug'],
                    'title' => $option['title'],
                    'parent_id' => $lookup->id,
                ]);
            }
        }
    }
}
