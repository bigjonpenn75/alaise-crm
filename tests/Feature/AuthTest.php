<?php

namespace Tests\Feature;

use App\Enums\UserLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_a_user_can_sign_in_with_username_and_password(): void
    {
        $this->user(attributes: ['username' => 'jon']);

        $this->post('/login', ['username' => 'jon', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_a_bcrypt_hash_from_the_old_system_still_works(): void
    {
        // What password_hash($p, PASSWORD_BCRYPT) produced in the old add-user.php.
        $legacyHash = password_hash('old-system-pass', PASSWORD_BCRYPT);
        $user = $this->user(attributes: ['username' => 'legacy']);
        User::whereKey($user->id)->toBase()->update(['password' => $legacyHash]);

        $this->post('/login', ['username' => 'legacy', 'password' => 'old-system-pass'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_and_deactivated_accounts_are_refused(): void
    {
        $this->user(attributes: ['username' => 'jon']);
        $this->user(attributes: ['username' => 'gone', 'active' => false]);

        $this->post('/login', ['username' => 'jon', 'password' => 'nope'])->assertSessionHasErrors('username');
        $this->post('/login', ['username' => 'gone', 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_deactivating_a_user_ends_their_session(): void
    {
        $user = $this->user();
        $this->actingAs($user)->get('/')->assertOk();

        $user->update(['active' => false]);
        $this->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_form_is_not_open_to_sql_injection(): void
    {
        $this->user(attributes: ['username' => 'jon']);

        $this->post('/login', ['username' => "jon' OR '1'='1", 'password' => 'x'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_standard_users_land_on_add_booking(): void
    {
        $this->actingAs($this->user(UserLevel::Standard))->get('/')->assertRedirect('/leads/create');
    }
}
