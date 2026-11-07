<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in');
    }

    public function test_user_can_log_in_and_is_audited(): void
    {
        $user = User::factory()->doctor()->create(['password' => 'secret123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login', 'user_id' => $user->id]);
    }

    public function test_wrong_password_is_rejected_and_logged(): void
    {
        $user = User::factory()->doctor()->create(['password' => 'secret123']);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'nope'])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'login_failed']);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->doctor()->inactive()->create(['password' => 'secret123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_is_signed_out_on_next_request(): void
    {
        $user = $this->doctor();
        $this->actingAs($user)->get('/')->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user->fresh())->get('/')->assertRedirect(route('login'));
    }

    public function test_user_can_log_out(): void
    {
        $user = $this->doctor();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->doctor()->create(['password' => 'secret123']);
        foreach (range(1, 5) as $_) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'secret123'])->assertStatus(429);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/', '/patients', '/prescriptions', '/laboratory/reports', '/settings'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_audit_log_never_contains_passwords(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Doctor', 'email' => 'new@clinic.test', 'roles' => ['doctor'],
            'password' => 'Password123', 'password_confirmation' => 'Password123', 'is_active' => 1,
        ])->assertRedirect(route('users.index'));

        $logs = ActivityLog::query()->get()->toJson();
        $this->assertStringNotContainsString('Password123', $logs);
        $this->assertStringNotContainsString('"password"', $logs);
    }
}
