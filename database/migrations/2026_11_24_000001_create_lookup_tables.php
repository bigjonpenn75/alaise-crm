<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // People bookings are credited to (old lead_users). Not login accounts.
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->string('name', 100);
            $table->boolean('is_reception')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->string('name', 100);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->string('name', 100);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            // Walk-ins count towards reception totals whoever logged them.
            $table->boolean('counts_as_reception')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Which lead sources roll up into each paid channel, per business.
        Schema::create('channel_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20);                  // App\Enums\Channel
            $table->foreignId('lead_source_id')->constrained()->cascadeOnDelete();
            $table->unique(['business_id', 'channel', 'lead_source_id']);
        });

        Schema::create('dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->string('name', 100);
            $table->string('category', 20)->default('other'); // App\Enums\DispositionCategory
            $table->string('slug', 40)->nullable()->index();  // stable handle for automated rules
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('marketing_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->string('name', 100);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['marketing_sources', 'dispositions', 'channel_sources', 'lead_sources', 'services', 'locations', 'staff_members'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
