<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\PincodeController;
use App\Http\Controllers\Admin\ZoneController;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('admin')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('admin.orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->name('admin.orders.store');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('admin.orders.show');
    Route::post('/orders/{id}', [OrderController::class, 'update'])->name('admin.orders.update');
    Route::post('/orders/{id}/message', [OrderController::class, 'message'])->name('admin.orders.message');

    // customers resource
    Route::get('/customers', [CustomerController::class, 'index'])->name('admin.customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('admin.customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('admin.customers.store');
    Route::get('/customers/{id}/edit', [CustomerController::class, 'edit'])->name('admin.customers.edit');
    Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('admin.customers.update');

    // soft delete / restore / force delete
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('admin.customers.destroy');
    Route::post('/customers/{id}/restore', [CustomerController::class, 'restore'])->name('admin.customers.restore');
    Route::delete('/customers/{id}/force', [CustomerController::class, 'forceDelete'])->name('admin.customers.force_delete');


    Route::resource('countries', CountryController::class)
        ->only(['index', 'edit', 'update'])
        ->names([
            'index' => 'admin.countries.index',
            'edit' => 'admin.countries.edit',
            'update' => 'admin.countries.update',
        ]);
    Route::post('countries/sync', [CountryController::class, 'sync'])->name('admin.countries.sync');

    Route::resource('pincodes', PincodeController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])->names([
        'index' => 'admin.pincodes.index',
        'create' => 'admin.pincodes.create',
        'store' => 'admin.pincodes.store',
        'edit' => 'admin.pincodes.edit',
        'update' => 'admin.pincodes.update',
        'destroy' => 'admin.pincodes.destroy'
    ]);

    Route::post('zones/{id}/attach-pincode', [ZoneController::class, 'attachPincode'])->name('admin.zones.attach_pincode');
});




Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
