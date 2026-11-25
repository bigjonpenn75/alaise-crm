<?php

namespace Tests;

use App\Enums\UserLevel;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** A user of the given level with access to the listed business codes. */
    protected function user(UserLevel $level = UserLevel::Management, array $businesses = ['flesh'], array $attributes = []): User
    {
        $user = User::factory()->level($level)->create($attributes);
        $user->businesses()->sync(Business::whereIn('code', $businesses)->pluck('id'));

        return $user;
    }
}
