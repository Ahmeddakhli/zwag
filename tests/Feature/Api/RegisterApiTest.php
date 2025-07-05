<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class RegisterApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a default package for testing
        Package::factory()->create([
            'id' => 1,
            'name' => 'Free Package',
            'price' => 0,
            'validity_period' => 30,
            'interest_express_limit' => 10,
            'contact_view_limit' => 5,
            'image_upload_limit' => 3,
            'status' => 1
        ]);
    }

    /** @test */
    public function it_can_get_registration_configuration()
    {
        $response = $this->getJson('/api/v1/auth/registration-config');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'registration_enabled',
                        'email_verification_required',
                        'mobile_verification_required',
                        'kyc_verification_required',
                        'agreement_required',
                        'captcha_required',
                        'secure_password_required',
                        'default_package',
                        'password_requirements' => [
                            'min_length',
                            'mixed_case',
                            'numbers',
                            'symbols',
                            'uncompromised'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_check_if_user_exists_by_email()
    {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        // Check existing email
        $response = $this->postJson('/api/v1/auth/check-user', [
            'email' => 'existing@example.com'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'exists' => true,
                        'type' => 'email',
                        'field' => 'Email',
                        'message' => 'Email is already registered'
                    ]
                ]);

        // Check new email
        $response = $this->postJson('/api/v1/auth/check-user', [
            'email' => 'new@example.com'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'exists' => false,
                        'type' => 'email',
                        'field' => 'Email',
                        'message' => 'Email is available'
                    ]
                ]);
    }

    /** @test */
    public function it_can_check_if_user_exists_by_mobile()
    {
        // Create existing user
        User::factory()->create([
            'mobile' => '1234567890',
            'dial_code' => '+1'
        ]);

        // Check existing mobile
        $response = $this->postJson('/api/v1/auth/check-user', [
            'mobile' => '1234567890',
            'mobile_code' => '+1'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'exists' => true,
                        'type' => 'mobile',
                        'field' => 'Mobile',
                        'message' => 'Mobile number is already registered'
                    ]
                ]);
    }

    /** @test */
    public function it_can_check_password_strength()
    {
        // Test weak password
        $response = $this->postJson('/api/v1/auth/check-password-strength', [
            'password' => '123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'score',
                        'feedback',
                        'requirements',
                        'level'
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'level' => 'weak'
                    ]
                ]);

        // Test strong password
        $response = $this->postJson('/api/v1/auth/check-password-strength', [
            'password' => 'StrongP@ssw0rd123!'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'level' => 'strong'
                    ]
                ]);
    }

    /** @test */
    public function it_can_register_user_successfully()
    {
        $userData = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'mobile' => '1234567890',
            'dial_code' => '+1',
            'country' => 'United States',
            'country_code' => 'US',
            'state' => 'California',
            'city' => 'Los Angeles',
            'zip' => '90210',
            'agree' => true
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'profile_id',
                            'firstname',
                            'lastname',
                            'email',
                            'mobile',
                            'dial_code',
                            'username',
                            'image',
                            'status',
                            'created_at',
                            'basic_info',
                            'religion_info',
                            'package_info',
                            'verification_status' => [
                                'email_verified',
                                'mobile_verified',
                                'kyc_verified'
                            ]
                        ],
                        'token',
                        'token_type'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Registration successful',
                    'data' => [
                        'token_type' => 'Bearer'
                    ]
                ]);

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@example.com',
            'mobile' => '1234567890',
            'dial_code' => '+1'
        ]);

        // Verify user limitation was created
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertDatabaseHas('user_limitations', [
            'user_id' => $user->id,
            'package_id' => 1
        ]);

        // Verify admin notification was created
        $this->assertDatabaseHas('admin_notifications', [
            'user_id' => $user->id,
            'title' => 'New member registered'
        ]);

        // Verify login log was created
        $this->assertDatabaseHas('user_logins', [
            'user_id' => $user->id
        ]);
    }

    /** @test */
    public function it_fails_registration_with_validation_errors()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'firstname' => '', // Required field empty
            'email' => 'invalid-email', // Invalid email format
            'password' => '123', // Too short password
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'firstname',
                        'lastname',
                        'email',
                        'password'
                    ]
                ])
                ->assertJson([
                    'success' => false,
                    'message' => 'Validation failed'
                ]);
    }

    /** @test */
    public function it_fails_registration_with_duplicate_email()
    {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'existing@example.com', // Duplicate email
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'agree' => true
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'email'
                    ]
                ]);
    }

    /** @test */
    public function it_fails_registration_when_registration_disabled()
    {
        // Mock gs() function to return registration disabled
        config(['app.registration' => false]);

        $userData = [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'agree' => true
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Registration is currently not allowed'
                ]);
    }

    /** @test */
    public function it_can_register_with_minimal_required_fields()
    {
        $userData = [
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => 'jane.smith@example.com',
            'password' => 'SimplePass123',
            'password_confirmation' => 'SimplePass123'
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'Registration successful'
                ]);

        // Verify user was created with minimal data
        $this->assertDatabaseHas('users', [
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => 'jane.smith@example.com',
            'mobile' => null,
            'username' => null
        ]);
    }

    /** @test */
    public function it_generates_unique_profile_id()
    {
        $userData1 = [
            'firstname' => 'User',
            'lastname' => 'One',
            'email' => 'user1@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ];

        $userData2 = [
            'firstname' => 'User',
            'lastname' => 'Two',
            'email' => 'user2@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ];

        $response1 = $this->postJson('/api/v1/auth/register', $userData1);
        $response2 = $this->postJson('/api/v1/auth/register', $userData2);

        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $profileId1 = $response1->json('data.user.profile_id');
        $profileId2 = $response2->json('data.user.profile_id');

        $this->assertNotEquals($profileId1, $profileId2);
        $this->assertEquals(8, strlen($profileId1));
        $this->assertEquals(8, strlen($profileId2));
    }

    /** @test */
    public function it_creates_authentication_token_on_registration()
    {
        $userData = [
            'firstname' => 'Token',
            'lastname' => 'User',
            'email' => 'token.user@example.com',
            'password' => 'TokenPass123!',
            'password_confirmation' => 'TokenPass123!'
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        // Verify token can be used for authenticated requests
        $authResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/v1/auth/user');

        $authResponse->assertStatus(200);
    }
}