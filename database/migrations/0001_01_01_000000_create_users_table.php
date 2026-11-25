<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();           // url slug: truk, flesh, salford, mts
            $table->string('short_name', 30);
            $table->boolean('is_clinic')->default(false);   // clinic = customers + appointments (TRUK)
            $table->boolean('uses_reception_split')->default(false); // studios split reception / artist bookings
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->string('name', 100);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->unsignedTinyInteger('level');           // App\Enums\UserLevel
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email', 150)->nullable();
            $table->string('tel', 20)->nullable();
            $table->foreignId('technician_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_reception')->default(false);
            $table->boolean('active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('business_user', function (Blueprint $table) {
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['business_id', 'user_id']);
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('business_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('technicians');
        Schema::dropIfExists('businesses');
    }
};
