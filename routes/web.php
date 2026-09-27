<?php

use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Admin\PasswordPolicyController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\VerificationController;
use App\Livewire\HostOnboarding;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/experiencias/{experience}', [ExperienceController::class, 'show'])->name('experiencias.show');

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [LoginController::class, 'create'])->name('login');
    Route::post('/ingresar', [LoginController::class, 'store']);
    Route::get('/registrarme', [RegisterController::class, 'create'])->name('register');
    Route::post('/registrarme', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/salir', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/cuenta/contrasena', [PasswordController::class, 'edit'])->name('cuenta.contrasena');
    Route::put('/cuenta/contrasena', [PasswordController::class, 'update'])->name('cuenta.contrasena.update');
    Route::get('/verificacion', [VerificationController::class, 'index'])->name('verificacion');
    Route::post('/verificacion', [VerificationController::class, 'store'])->name('verificacion.store');
    Route::post('/verificacion/codigo', [VerificationController::class, 'confirm'])->name('verificacion.confirmar');

    // Publicar exige documento validado (nivel 2). Cobrar sin restricciones exige nivel 3.
    Route::get('/anfitrion/registro', HostOnboarding::class)->middleware('verified.level:2')->name('anfitrion.registro');

    Route::prefix('admin')->middleware('admin')->name('admin.')->group(function () {
        Route::get('/verificaciones', [AdminVerificationController::class, 'index'])->name('verificaciones');
        Route::post('/verificaciones/{verification}/aprobar', [AdminVerificationController::class, 'approve'])->name('verificaciones.aprobar');
        Route::post('/verificaciones/{verification}/rechazar', [AdminVerificationController::class, 'reject'])->name('verificaciones.rechazar');
        Route::get('/contrasenas', [PasswordPolicyController::class, 'edit'])->name('contrasenas');
        Route::put('/contrasenas', [PasswordPolicyController::class, 'update'])->name('contrasenas.update');
    });
});
