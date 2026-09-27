<?php

use App\Http\Controllers\Account\InterestController;
use App\Http\Controllers\Account\InvitationController;
use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Admin\PasswordPolicyController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\SecurityLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\VerificationController;
use App\Livewire\HostOnboarding;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/invitacion/{token}', InvitationAcceptController::class)->middleware('throttle:20,1')->name('invitacion.aceptar');
Route::get('/experiencias/{experience}', [ExperienceController::class, 'show'])->name('experiencias.show');

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [LoginController::class, 'create'])->name('login');
    Route::post('/ingresar', [LoginController::class, 'store']);
    Route::get('/registrarme', [RegisterController::class, 'create'])->name('register');
    Route::post('/registrarme', [RegisterController::class, 'store']);
    Route::get('/olvide-mi-contrasena', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/olvide-mi-contrasena', [ForgotPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/restablecer-contrasena', [ResetPasswordController::class, 'store'])->middleware('throttle:10,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/salir', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/cuenta', [ProfileController::class, 'edit'])->name('cuenta.perfil');
    Route::put('/cuenta', [ProfileController::class, 'update'])->name('cuenta.perfil.update');
    Route::get('/cuenta/seguridad', [PasswordController::class, 'edit'])->name('cuenta.seguridad');
    Route::put('/cuenta/seguridad', [PasswordController::class, 'update'])->name('cuenta.seguridad.update');
    Route::get('/cuenta/intereses', [InterestController::class, 'edit'])->name('cuenta.intereses');
    Route::put('/cuenta/intereses', [InterestController::class, 'update'])->name('cuenta.intereses.update');
    Route::middleware('verified.level:1')->group(function () {
        Route::get('/cuenta/invitaciones', [InvitationController::class, 'index'])->name('cuenta.invitaciones');
        Route::post('/cuenta/invitaciones/email', [InvitationController::class, 'sendEmail'])->middleware('throttle:10,1')->name('cuenta.invitaciones.email');
        Route::post('/cuenta/invitaciones/enlace', [InvitationController::class, 'createLink'])->middleware('throttle:10,1')->name('cuenta.invitaciones.enlace');
    });
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
        Route::get('/seguridad', [SecurityLogController::class, 'index'])->name('seguridad');
        Route::get('/configuracion', [PlatformSettingsController::class, 'edit'])->name('configuracion');
        Route::put('/configuracion', [PlatformSettingsController::class, 'update'])->name('configuracion.update');
        Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios');
        Route::post('/usuarios/validar', [UserController::class, 'validateLevel'])->name('usuarios.validar');
        Route::get('/usuarios/{user}', [UserController::class, 'show'])->name('usuarios.show');
        Route::post('/usuarios/{user}/suspension', [UserController::class, 'toggleSuspension'])->name('usuarios.suspension');
        Route::put('/contrasenas', [PasswordPolicyController::class, 'update'])->name('contrasenas.update');
    });
});
