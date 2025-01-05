<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Enums\UserType;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ManagerController;
use App\Http\Controllers\Api\EmployeeController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login'])->name('login');
});

Route::middleware(['auth:api', 'role:'. UserType::SuperAdmin->value ])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:api')->group(function () {

    // Route super admin
    Route::prefix('admin')->middleware('role:' . UserType::SuperAdmin->value)->group(function () {
        Route::post('/register/company', [AdminController::class, 'storeCompany'])->name('create.company');
    });

    //  Route Manager
    Route::prefix('manager')->middleware('role:' . UserType::Manager->value)->group(function () {
        Route::prefix('profile')->group(function () {
            Route::get('/', [ManagerController::class, 'index'])->name('profile.manager');
            Route::put('/update', [ManagerController::class, 'updateProfile'])->name('update.manager');
            Route::put('/update/password', [ManagerController::class, 'changePassword'])->name('update.password');
        });

        Route::prefix('company')->group(function () {
            Route::get('/employees/{id}', [ManagerController::class, 'show'])->name('get.employee.detail');
            Route::get('/employees', [ManagerController::class, 'showAll'])->name('get.employees');
            Route::post('/employees', [ManagerController::class, 'store'])->name('create.employee');
            Route::put('/employees/{id}', [ManagerController::class, 'update'])->name('update.employee');
            Route::delete('/employees/{id}', [ManagerController::class, 'destroy'])->name('delete.employee');
        });
    });

    // Route Employee
    Route::prefix('employee')->middleware('role:' . UserType::Employee->value)->group(function () {
        Route::prefix('profile')->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('profile.employee');
        });

        Route::prefix('company')->group(function () {
            Route::get('/employees', [EmployeeController::class, 'showAll'])->name('get.fellow.employees');
        });
    });
});

