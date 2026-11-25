<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::enforceMorphMap(['lead' => Lead::class, 'client' => Client::class]);

        // Reports, customer lists, users and settings (Management, Marketing, Superuser).
        Gate::define('manage', fn (User $user) => $user->canManage());
        Gate::define('superuser', fn (User $user) => $user->isSuperuser());
        Gate::define('access-business', fn (User $user, Business $business) => $user->canAccessBusiness($business));
        Gate::define('manage-business', fn (User $user, Business $business) => $user->canManage() && $user->canAccessBusiness($business));

        // A technician's own diary, or anyone's for managers.
        Gate::define('view-diary', fn (User $user, Technician $technician) => $user->technician_id === $technician->id
            || ($user->canManage() && $user->canAccessBusiness($technician->business_id)));
    }
}
