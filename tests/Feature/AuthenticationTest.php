<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\PassportClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Setup the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPassport();
    }

    /**
     * Setup Passport for tests
     */
    protected function setUpPassport(): void
    {
        // Generate encryption keys
        $this->artisan('passport:keys', ['--force' => true]);

        // Create personal access client if it doesn't exist
        if (! PassportClient::where('personal_access_client', true)->exists()) {
            PassportClient::create([
                'id' => Str::uuid(),
                'user_id' => null,
                'name' => 'Test Personal Access Client',
                'secret' => Str::random(40),
                'provider' => 'users',
                'redirect' => 'http://localhost',
                'redirect_uris' => [],
                'grant_types' => ['personal_access'],
                'personal_access_client' => true,
                'password_client' => false,
                'revoked' => false,
            ]);
        }
    }

    /**
     * Test user registration
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePass@2024',
            'password_confirmation' => 'SecurePass@2024',
            'phone_number' => '+254712345678',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'full_name', 'email'],
                    'token',
                    'expires_at',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'user',
        ]);
    }

    /**
     * Test organizer registration
     */
    public function test_organizer_can_register(): void
    {
        $response = $this->postJson('/api/auth/register-organizer', [
            'full_name' => 'Test Organizer',
            'email' => 'organizer@example.com',
            'password' => 'SecurePass@2024',
            'password_confirmation' => 'SecurePass@2024',
            'phone_number' => '+254712345678',
            'business_name' => 'Test Events Ltd',
            'business_country' => 'KE',
            'default_currency' => 'KES',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'full_name', 'email', 'role'],
                    'organizer' => ['id', 'business_name', 'business_country'],
                    'token',
                    'expires_at',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'organizer@example.com',
            'role' => 'organizer',
        ]);

        $this->assertDatabaseHas('organizers', [
            'business_name' => 'Test Events Ltd',
        ]);
    }

    /**
     * Test user login with valid credentials
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('SecurePass@2024'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'SecurePass@2024',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user',
                    'access_token',
                    'token_type',
                    'expires_at',
                    'refresh_token',
                    'refresh_expires_at',
                ],
            ]);
    }

    /**
     * Test login fails with invalid credentials
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('SecurePass@2024'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test account lockout after multiple failed attempts
     */
    public function test_account_locks_after_multiple_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('SecurePass@2024'),
            'is_active' => true,
        ]);

        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'user@example.com',
                'password' => 'WrongPassword',
            ]);
        }

        // 6th attempt should be locked
        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'SecurePass@2024',
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment([
                'status' => 'error',
            ]);

        $this->assertStringContainsString('Account is locked', $response->json('message'));
    }

    /**
     * Test token refresh
     */
    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('test-app');
        $accessToken = $tokenResult->accessToken;

        // Generate refresh token manually for test
        $refreshToken = \Str::random(80);
        cache()->put("refresh_token_{$refreshToken}", [
            'user_id' => $user->id,
            'created_at' => now()->toIso8601String(),
        ], now()->addDays(30));

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_at',
                    'refresh_token',
                    'refresh_expires_at',
                ],
            ]);

        // Old refresh token should be invalidated
        $this->assertNull(cache()->get("refresh_token_{$refreshToken}"));
    }

    /**
     * Test authenticated user can get profile
     */
    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('test-app');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenResult->accessToken,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'full_name', 'email'],
                ],
            ]);
    }

    /**
     * Test unauthenticated user cannot access protected routes
     */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Test user can logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('test-app');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenResult->accessToken,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Successfully logged out',
            ]);

        // Token should be revoked
        $this->assertDatabaseMissing('oauth_access_tokens', [
            'id' => $tokenResult->token->id,
            'revoked' => false,
        ]);
    }

    /**
     * Test password reset request
     */
    public function test_user_can_request_password_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/auth/password/request-reset', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Password reset code sent to your email',
            ]);

        // Check if reset code is cached
        $this->assertNotNull(cache()->get('reset_code_user@example.com'));
    }

    /**
     * Test password reset with valid code
     */
    public function test_user_can_reset_password_with_valid_code(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        // Set reset code in cache
        $code = '123456';
        cache()->put('reset_code_user@example.com', $code, 900);

        $response = $this->postJson('/api/auth/password/reset', [
            'email' => 'user@example.com',
            'code' => $code,
            'password' => 'NewSecurePass@2024',
            'password_confirmation' => 'NewSecurePass@2024',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Password reset successful',
            ]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePass@2024', $user->password));

        // Reset code should be cleared
        $this->assertNull(cache()->get('reset_code_user@example.com'));
    }

    /**
     * Test email verification
     */
    public function test_user_can_verify_email(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $tokenResult = $user->createToken('test-app');

        // Set verification code
        $code = '123456';
        cache()->put("verify_code_{$user->id}", $code, 600);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$tokenResult->accessToken,
        ])->postJson('/api/auth/verify-email', [
            'code' => $code,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Email verified successfully',
            ]);

        // Check email is verified
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    /**
     * Test registration validation
     */
    public function test_registration_requires_valid_data(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name' => '',
            'email' => 'invalid-email',
            'password' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'email', 'password']);
    }

    /**
     * Test duplicate email registration
     */
    public function test_cannot_register_with_existing_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'SecurePass@2024',
            'password_confirmation' => 'SecurePass@2024',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test inactive account cannot login
     */
    public function test_inactive_account_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('SecurePass@2024'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'SecurePass@2024',
            'password_confirmation' => 'SecurePass@2024',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Account deactivated',
            ]);
    }
}
