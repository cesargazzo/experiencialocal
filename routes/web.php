<?php

use App\Http\Controllers\Account\BookingController as AccountBookingController;
use App\Http\Controllers\Account\InterestController;
use App\Http\Controllers\Account\InvitationController;
use App\Http\Controllers\Account\NotificationController;
use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ExperienceController as AdminExperienceController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\PasswordPolicyController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\SecurityLogController;
use App\Http\Controllers\Admin\TermsController as AdminTermsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HostBookingController;
use App\Http\Controllers\HostDashboardController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\VerificationController;
use App\Livewire\HostOnboarding;
use App\Livewire\ManageExperience;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/invitacion/{token}', InvitationAcceptController::class)->middleware('throttle:20,1')->name('invitacion.aceptar');
Route::get('/experiencias/{experience}', [ExperienceController::class, 'show'])->name('experiencias.show');
Route::get('/terminos', [TermsController::class, 'show'])->name('terminos');
Route::get('/terminos/version/{terms:version}', [TermsController::class, 'version'])->name('terminos.version');

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
    Route::get('/terminos/aceptar', [TermsController::class, 'acceptForm'])->name('terminos.aceptar');
    Route::post('/terminos/aceptar', [TermsController::class, 'accept'])->name('terminos.aceptar.store');
    Route::get('/cuenta', [ProfileController::class, 'edit'])->name('cuenta.perfil');
    Route::put('/cuenta', [ProfileController::class, 'update'])->name('cuenta.perfil.update');
    Route::put('/cuenta/alimentacion', [ProfileController::class, 'updateDiet'])->name('cuenta.alimentacion.update');
    Route::get('/cuenta/seguridad', [PasswordController::class, 'edit'])->name('cuenta.seguridad');
    Route::put('/cuenta/seguridad', [PasswordController::class, 'update'])->name('cuenta.seguridad.update');
    Route::get('/cuenta/avisos', [NotificationController::class, 'index'])->name('cuenta.avisos');
    Route::get('/cuenta/reservas', [AccountBookingController::class, 'index'])->name('cuenta.reservas');
    Route::post('/cuenta/reservas/{booking}/cancelar', [AccountBookingController::class, 'cancel'])->name('cuenta.reservas.cancelar');
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
    Route::get('/anfitrion', [HostDashboardController::class, 'index'])->name('anfitrion.panel');
    Route::post('/anfitrion/reservas/{booking}/confirmar', [HostBookingController::class, 'confirm'])->name('anfitrion.reservas.confirmar');
    Route::post('/anfitrion/reservas/{booking}/rechazar', [HostBookingController::class, 'decline'])->name('anfitrion.reservas.rechazar');
    Route::get('/anfitrion/experiencias/{experience}', ManageExperience::class)->name('anfitrion.experiencias.editar');
    Route::get('/anfitrion/registro', HostOnboarding::class)->middleware('verified.level:2')->name('anfitrion.registro');

    Route::prefix('admin')->middleware('admin')->name('admin.')->group(function () {
        Route::get('/verificaciones', [AdminVerificationController::class, 'index'])->name('verificaciones');
        Route::post('/verificaciones/{verification}/aprobar', [AdminVerificationController::class, 'approve'])->name('verificaciones.aprobar');
        Route::post('/verificaciones/{verification}/rechazar', [AdminVerificationController::class, 'reject'])->name('verificaciones.rechazar');
        Route::get('/experiencias', [AdminExperienceController::class, 'index'])->name('experiencias');
        Route::get('/experiencias/{experience}', [AdminExperienceController::class, 'show'])->name('experiencias.show');
        Route::post('/experiencias/{experience}/pausar', [AdminExperienceController::class, 'pause'])->name('experiencias.pausar');
        Route::post('/experiencias/{experience}/reactivar', [AdminExperienceController::class, 'resume'])->name('experiencias.reactivar');
        Route::post('/experiencias/{experience}/aprobar', [AdminExperienceController::class, 'approve'])->name('experiencias.aprobar');
        Route::post('/experiencias/{experience}/rechazar', [AdminExperienceController::class, 'reject'])->name('experiencias.rechazar');
        Route::get('/terminos', [AdminTermsController::class, 'index'])->name('terminos');
        Route::get('/terminos/nueva', [AdminTermsController::class, 'create'])->name('terminos.create');
        Route::post('/terminos', [AdminTermsController::class, 'store'])->name('terminos.store');
        Route::get('/terminos/{terms}/editar', [AdminTermsController::class, 'edit'])->name('terminos.edit');
        Route::put('/terminos/{terms}', [AdminTermsController::class, 'update'])->name('terminos.update');
        Route::post('/terminos/{terms}/publicar', [AdminTermsController::class, 'publish'])->name('terminos.publicar');
        Route::get('/terminos/{terms}/aceptaciones', [AdminTermsController::class, 'acceptances'])->name('terminos.aceptaciones');
        Route::get('/contrasenas', [PasswordPolicyController::class, 'edit'])->name('contrasenas');
        Route::get('/seguridad', [SecurityLogController::class, 'index'])->name('seguridad');
        Route::get('/registro', [LogController::class, 'index'])->name('registro');
        Route::get('/registro/{file}/descargar', [LogController::class, 'download'])->where('file', '[\w.-]+\.log')->name('registro.descargar');
        Route::get('/auditoria', [AuditLogController::class, 'index'])->name('auditoria');
        Route::get('/configuracion', [PlatformSettingsController::class, 'edit'])->name('configuracion');
        Route::put('/configuracion', [PlatformSettingsController::class, 'update'])->name('configuracion.update');
        Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios');
        Route::post('/usuarios/validar', [UserController::class, 'validateLevel'])->name('usuarios.validar');
        Route::get('/usuarios/{user}', [UserController::class, 'show'])->name('usuarios.show');
        Route::post('/usuarios/{user}/verificaciones/{verification}/revocar', [UserController::class, 'revokeVerification'])->name('usuarios.verificaciones.revocar');
        Route::post('/usuarios/{user}/suspension', [UserController::class, 'toggleSuspension'])->name('usuarios.suspension');
        Route::put('/contrasenas', [PasswordPolicyController::class, 'update'])->name('contrasenas.update');
    });
});
