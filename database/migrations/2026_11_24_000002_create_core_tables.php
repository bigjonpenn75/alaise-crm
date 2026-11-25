<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('email', 150)->nullable();
            $table->string('tel', 20)->nullable();
            $table->foreignId('staff_member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('deposit', 8, 2)->nullable();
            $table->decimal('full_cost', 8, 2)->default(0);
            $table->unsignedTinyInteger('booking_type')->default(1); // App\Enums\BookingType
            $table->date('date_added')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['business_id', 'date_added']);
        });

        Schema::create('marketing_spends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->date('spend_date');
            $table->decimal('instagram', 10, 2)->default(0);
            $table->decimal('google', 10, 2)->default(0);
            $table->decimal('facebook', 10, 2)->default(0);
            $table->decimal('tiktok', 10, 2)->default(0);
            $table->decimal('staff', 10, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['business_id', 'spend_date']);
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->string('first_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->string('tel', 20)->nullable()->index();
            $table->string('email', 150)->nullable()->index();
            $table->foreignId('staff_member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('created_date')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            // Contact details as they were when the appointment was booked.
            $table->string('client_name', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('tel', 20)->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('appointment_date')->nullable();
            $table->time('appointment_time')->nullable();
            $table->foreignId('technician_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('booking_type')->nullable(); // 1 treatment, 2 consultation
            $table->foreignId('disposition_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('status_updated_at')->nullable();
            $table->foreignId('marketing_source_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('fee', 8, 2)->nullable();
            $table->date('created_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['technician_id', 'appointment_date']);
            $table->index(['disposition_id', 'status_updated_at']);
            $table->index('appointment_date');
        });

        // Bookings pushed in from Timely, waiting to be matched to a client.
        Schema::create('timely_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('tel', 20)->nullable();
            $table->string('location', 150)->nullable();
            $table->dateTime('next_appointment')->nullable();
            $table->boolean('is_new')->default(true)->index();
            $table->timestamps();
            $table->index(['tel', 'next_appointment']);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('notable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('display_name');
            $table->timestamps();
        });

        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('action');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['histories', 'documents', 'notes', 'timely_bookings', 'appointments', 'clients', 'marketing_spends', 'leads'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
