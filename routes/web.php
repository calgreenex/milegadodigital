<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MensajePostumoController;
use App\Http\Controllers\DocumentoImportanteController;
use App\Http\Controllers\ContactoConfianzaController;
use App\Http\Controllers\RecuerdoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Plan;
use Illuminate\Http\Request; // Add this line

Route::get('/', function () {
    $plans = Plan::with('features')->orderBy('price')->get();
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'plans' => $plans,
    ]);
});

use App\Models\MensajePostumo;
use App\Models\DocumentoImportante;
use App\Models\Recuerdo;
use Illuminate\Support\Facades\Auth;

Route::get('/payment-redirect', function (Request $request) {
    $planId = $request->query('plan_id');
    $billingCycle = $request->query('billing_cycle', 'monthly'); // Default to monthly if not provided
    return Inertia::render('PaymentRedirect', [
        'plan_id' => $planId,
        'billing_cycle' => $billingCycle,
        'csrf_token' => csrf_token(),
    ]);
})->name('payment.redirect');

Route::post('/webpay/initiate', [App\Http\Controllers\TransbankController::class, 'initiateWebpayTransaction'])->name('webpay.initiate');
Route::any('/webpay/return', [App\Http\Controllers\TransbankController::class, 'returnFromWebpay'])->name('webpay.return');

Route::post('/oneclick/initiate-inscription', [App\Http\Controllers\TransbankController::class, 'initiateOneclickInscription'])->name('oneclick.initiateInscription');
Route::any('/oneclick/return-inscription', [App\Http\Controllers\TransbankController::class, 'returnFromOneclickInscription'])->name('oneclick.return');
Route::post('/oneclick/payment', [App\Http\Controllers\TransbankController::class, 'initiateOneclickPayment'])->name('oneclick.payment');
Route::delete('/oneclick/inscriptions/{oneclickInscription}', [App\Http\Controllers\TransbankController::class, 'deleteOneclickInscription'])->name('oneclick.destroy');

Route::get('/dashboard', function () {
    $user = Auth::user();
    $mensajesPendientes = MensajePostumo::where('user_id', $user->id)->where('estado', 'pendiente')->count();
    $ultimosDocumentos = DocumentoImportante::where('user_id', $user->id)->latest()->take(3)->get();
    $ultimosRecuerdos = Recuerdo::where('user_id', $user->id)->latest()->take(3)->get();
    $planFeatures = $user->plan->features->pluck('value', 'feature_code')->toArray();

    return Inertia::render('Dashboard', [
        'mensajesPendientes' => $mensajesPendientes,
        'ultimosDocumentos' => $ultimosDocumentos,
        'ultimosRecuerdos' => $ultimosRecuerdos,
        'planFeatures' => $planFeatures,
        'proofOfLifeFrequencyDays' => $user->proof_of_life_frequency_days,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas para actualizar el plan del usuario
    Route::patch('/profile/plan', [ProfileController::class, 'updatePlan'])->name('profile.updatePlan');
    Route::get('/profile/upgrade-plan', [ProfileController::class, 'showUpgradePlanForm'])->name('profile.upgradePlanForm');
    Route::get('/profile/oneclick', [ProfileController::class, 'showOneclickManagement'])->name('profile.oneclick');

    // Rutas para Historial de Pagos
    Route::get('/payments', [App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');

    // Rutas para Configuración de Seguridad
    Route::get('/seguridad', [ProfileController::class, 'security'])->name('profile.security');
    Route::patch('/seguridad', [ProfileController::class, 'updateSecurity'])->name('profile.updateSecurity');

    // Rutas para Mensajes Póstumos
    Route::get('/mensajes-postumos', [MensajePostumoController::class, 'index'])->name('mensajes-postumos.index');
    Route::get('/mensajes-postumos/crear', function (Request $request) {
        return Inertia::render('MensajesPostumos/Create', [
            'temp_video_path' => $request->query('temp_video_path'),
        ]);
    })->name('mensajes-postumos.create');
    Route::post('/mensajes-postumos', [MensajePostumoController::class, 'store'])->name('mensajes-postumos.store');
    Route::get('/mensajes-postumos/{mensajePostumo}/editar', [MensajePostumoController::class, 'edit'])->name('mensajes-postumos.edit');
    Route::put('/mensajes-postumos/{mensajePostumo}', [MensajePostumoController::class, 'update'])->name('mensajes-postumos.update');
    Route::delete('/mensajes-postumos/{mensajePostumo}', [MensajePostumoController::class, 'destroy'])->name('mensajes-postumos.destroy');
    Route::post('/mensajes-postumos/upload-video-temp', [MensajePostumoController::class, 'uploadVideoTemp'])->name('mensajes-postumos.uploadVideoTemp');

    // Rutas para Documentos Importantes
    Route::get('/documentos-importantes', [DocumentoImportanteController::class, 'index'])->name('documentos-importantes.index');
    Route::get('/documentos-importantes/crear', [DocumentoImportanteController::class, 'create'])->name('documentos-importantes.create');
    Route::post('/documentos-importantes', [DocumentoImportanteController::class, 'store'])->name('documentos-importantes.store');
    Route::get('/documentos-importantes/{documentoImportante}/editar', [DocumentoImportanteController::class, 'edit'])->name('documentos-importantes.edit');
    Route::put('/documentos-importantes/{documentoImportante}', [DocumentoImportanteController::class, 'update'])->name('documentos-importantes.update');
    Route::delete('/documentos-importantes/{documentoImportante}', [DocumentoImportanteController::class, 'destroy'])->name('documentos-importantes.destroy');
    Route::get('/documentos-importantes/{documentoImportante}/descargar', [DocumentoImportanteController::class, 'download'])->name('documentos-importantes.download');

    // Rutas para Contactos de Confianza
    Route::get('/contactos-confianza', [ContactoConfianzaController::class, 'index'])->name('contactos-confianza.index');
    Route::get('/contactos-confianza/crear', [ContactoConfianzaController::class, 'create'])->name('contactos-confianza.create');
    Route::post('/contactos-confianza', [ContactoConfianzaController::class, 'store'])->name('contactos-confianza.store');
    Route::get('/contactos-confianza/{contactoConfianza}/editar', [ContactoConfianzaController::class, 'edit'])->name('contactos-confianza.edit');
    Route::put('/contactos-confianza/{contactoConfianza}', [ContactoConfianzaController::class, 'update'])->name('contactos-confianza.update');
    Route::delete('/contactos-confianza/{contactoConfianza}', [ContactoConfianzaController::class, 'destroy'])->name('contactos-confianza.destroy');

    // Rutas para Recuerdos
    Route::get('/recuerdos', [RecuerdoController::class, 'index'])->name('recuerdos.index');
    Route::get('/recuerdos/crear', [RecuerdoController::class, 'create'])->name('recuerdos.create');
    Route::post('/recuerdos', [RecuerdoController::class, 'store'])->name('recuerdos.store');
    Route::get('/recuerdos/{recuerdo}/editar', [RecuerdoController::class, 'edit'])->name('recuerdos.edit');
    Route::put('/recuerdos/{recuerdo}', [RecuerdoController::class, 'update'])->name('recuerdos.update');
    Route::delete('/recuerdos/{recuerdo}', [RecuerdoController::class, 'destroy'])->name('recuerdos.destroy');

    // Rutas para Lista de Deseos
    Route::get('/lista-deseos', [App\Http\Controllers\WishListItemController::class, 'index'])->name('wishlist.index');
    Route::post('/lista-deseos', [App\Http\Controllers\WishListItemController::class, 'store'])->name('wishlist.store');
    Route::patch('/lista-deseos/{wishListItem}', [App\Http\Controllers\WishListItemController::class, 'update'])->name('wishlist.update');
    Route::delete('/lista-deseos/{wishListItem}', [App\Http\Controllers\WishListItemController::class, 'destroy'])->name('wishlist.destroy');

    // Ruta para Grabar Video
    Route::get('/grabar-video', function () {
        $user = auth()->user();
        $canRecordVideo = false;
        $videoDurationLimit = 0;

        if ($user->is_admin) {
            $canRecordVideo = true;
            $videoDurationLimit = 999999; // Effectively unlimited for admin
        } else {
            $plan = $user->plan->load('features');
            $canRecordVideo = $plan->features->where('feature_code', 'video_recording')->first()->value === 'true';
            $videoDurationLimit = $plan->features->where('feature_code', 'video_duration')->first()->value ?? 0;
        }

        return Inertia::render('VideoRecorder', [
            'canRecordVideo' => $canRecordVideo,
            'videoDurationLimit' => (int)$videoDurationLimit,
        ]);
    })->name('video.recorder');

    // Rutas para Prueba de Vida
    Route::get('/prueba-de-vida/verificar', [App\Http\Controllers\ProofOfLifeController::class, 'showForm'])->name('proof-of-life.verify.form');
    Route::post('/prueba-de-vida/verificar', [App\Http\Controllers\ProofOfLifeController::class, 'verifyCode'])->name('proof-of-life.verify.code');
    Route::get('/prueba-de-vida/configuracion', [App\Http\Controllers\ProofOfLifeController::class, 'showSettings'])->name('proof-of-life.settings.show');
    Route::patch('/prueba-de-vida/configuracion', [App\Http\Controllers\ProofOfLifeController::class, 'updateSettings'])->name('proof-of-life.settings.update');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified', \App\Http\Middleware\AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/plans', function () {
        $plans = \App\Models\Plan::with('features')->get();
        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    })->name('plans.index');

    Route::get('/plans/create', function () {
        return Inertia::render('Admin/Plans/Create');
    })->name('plans.create');

    Route::post('/plans', [\App\Http\Controllers\PlanController::class, 'store'])->name('plans.store');

    Route::get('/plans/{plan}/edit', function (\App\Models\Plan $plan) {
        $plan->load('features');
        return Inertia::render('Admin/Plans/Edit', [
            'plan' => $plan,
        ]);
    })->name('plans.edit');

    Route::put('/plans/{plan}', [\App\Http\Controllers\PlanController::class, 'update'])->name('plans.update');

    Route::delete('/plans/{plan}', [\App\Http\Controllers\PlanController::class, 'destroy'])->name('plans.destroy');
});

