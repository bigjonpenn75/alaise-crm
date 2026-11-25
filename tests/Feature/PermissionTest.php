<?php

namespace Tests\Feature;

use App\Enums\UserLevel;
use App\Models\Technician;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reception_sees_the_dashboard_but_no_management_pages(): void
    {
        $this->actingAs($this->user(UserLevel::Reception));

        $this->get('/')->assertOk();
        $this->get('/leads/create')->assertOk();
        foreach (['/users', '/flesh/tracker', '/flesh/customers', '/settings', '/flesh/marketing/spend'] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_managers_only_reach_businesses_ticked_on_their_account(): void
    {
        $this->actingAs($this->user(UserLevel::Management, ['flesh']));

        $this->get('/flesh/tracker')->assertOk();
        $this->get('/flesh/marketing/performance')->assertOk();
        $this->get('/mts/tracker')->assertForbidden();
        $this->get('/truk/customers')->assertForbidden();
        $this->get('/settings/truk')->assertForbidden();
    }

    public function test_superusers_reach_every_business_and_month_on_month(): void
    {
        $this->actingAs($this->user(UserLevel::Superuser, []));

        foreach (['/truk/customers', '/mts/tracker', '/truk/month-on-month', '/truk/new-bookings', '/technicians/performance', '/calls', '/settings/salford'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_month_on_month_is_superuser_only(): void
    {
        $this->actingAs($this->user(UserLevel::Management, ['truk']))->get('/truk/month-on-month')->assertForbidden();
    }

    public function test_a_technician_can_open_their_own_diary_only(): void
    {
        $mine = Technician::create(['business_id' => 1, 'name' => 'Nicola']);
        $other = Technician::create(['business_id' => 1, 'name' => 'Matt']);
        $this->actingAs($this->user(UserLevel::Standard, ['truk'], ['technician_id' => $mine->id]));

        $this->get("/technicians/{$mine->id}/diary")->assertOk();
        $this->get("/technicians/{$other->id}/diary")->assertForbidden();
    }

    public function test_managers_cannot_touch_superuser_accounts_or_create_superusers(): void
    {
        $super = $this->user(UserLevel::Superuser);
        $this->actingAs($this->user(UserLevel::Management));

        $this->patch("/users/{$super->id}/active")->assertForbidden();
        $this->put("/users/{$super->id}/password", ['password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])->assertForbidden();
        $this->post('/users', ['username' => 'x', 'first_name' => 'X', 'last_name' => 'Y', 'level' => 5,
            'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])->assertForbidden();
        $this->assertTrue($super->fresh()->active);
    }
}
