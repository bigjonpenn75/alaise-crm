<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\TimelyBookingController;
use App\Http\Controllers\TrackerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('search', SearchController::class)->name('search');

    // Bookings ("Add Appointment" in the old system) - open to every level.
    Route::get('leads/create', [LeadController::class, 'create'])->name('leads.create');

    // A technician's own diary; managers can open anyone's.
    Route::get('technicians/{technician}/diary', [TechnicianController::class, 'diary'])
        ->can('view-diary', 'technician')->name('technicians.diary');

    Route::middleware('can:manage')->group(function () {
        Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::get('leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
        Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

        Route::get('documents/{document}', DocumentController::class)->name('documents.show');

        Route::get('marketing/spend/create', [MarketingController::class, 'create'])->name('spend.create');
        Route::post('marketing/spend', [MarketingController::class, 'store'])->name('spend.store');
        Route::get('marketing/spend/{spend}/edit', [MarketingController::class, 'edit'])->name('spend.edit');
        Route::put('marketing/spend/{spend}', [MarketingController::class, 'update'])->name('spend.update');
        Route::delete('marketing/spend/{spend}', [MarketingController::class, 'destroy'])->name('spend.destroy');

        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

        Route::get('appointments/unassigned', [AppointmentController::class, 'unassigned'])->name('appointments.unassigned');
        Route::post('appointments/unassigned', [AppointmentController::class, 'assign'])->name('appointments.assign');
        Route::post('clients/{client}/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::get('appointments/{appointment}/edit', [AppointmentController::class, 'edit'])->name('appointments.edit');
        Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
        Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');

        Route::get('bookings/{booking}', [TimelyBookingController::class, 'show'])->name('bookings.show');
        Route::post('bookings/{booking}', [TimelyBookingController::class, 'store'])->name('bookings.store');
        Route::delete('bookings/{booking}', [TimelyBookingController::class, 'destroy'])->name('bookings.destroy');

        Route::get('technicians/performance', [TechnicianController::class, 'performance'])->name('technicians.performance');
        Route::get('technicians/{technician}/appointments', [TechnicianController::class, 'appointments'])->name('technicians.appointments');
        Route::get('calls', [TechnicianController::class, 'calls'])->name('calls');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::put('users/{user}/password', [UserController::class, 'password'])->name('users.password');
        Route::patch('users/{user}/active', [UserController::class, 'toggle'])->name('users.toggle');

        Route::get('settings', [SettingsController::class, 'system'])->name('settings.system');
        Route::get('settings/{business}', [SettingsController::class, 'business'])
            ->can('access-business', 'business')->name('settings.business');

        // Per-business pages. Registered last so the fixed paths above win.
        Route::prefix('{business}')->middleware('can:access-business,business')->group(function () {
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
            Route::get('tracker', TrackerController::class)->name('tracker');
            Route::get('marketing/spend', [MarketingController::class, 'index'])->name('spend.index');
            Route::get('marketing/performance', [MarketingController::class, 'performance'])->name('performance');
            Route::get('month-on-month', [MarketingController::class, 'monthOnMonth'])->can('superuser')->name('month-on-month');
            Route::get('new-bookings', [TimelyBookingController::class, 'index'])->name('bookings.index');
        });
    });
});
