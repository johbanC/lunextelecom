<?php

use App\Http\Controllers\Admin\AgreementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicAgreementController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/agreements');

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('agreements', [AgreementController::class, 'index'])->name('agreements.index');
        Route::get('agreements/create', [AgreementController::class, 'create'])->name('agreements.create');
        Route::post('agreements', [AgreementController::class, 'store'])->name('agreements.store');
        Route::get('agreements/{agreement}', [AgreementController::class, 'show'])->name('agreements.show');
        Route::post('agreements/{agreement}/extend', [AgreementController::class, 'extend'])->name('agreements.extend');
        Route::get('agreements/{agreement}/pdf', [AgreementController::class, 'pdf'])->name('agreements.pdf');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('f')->name('public.')->group(function () {
    Route::get('{uuid}', [PublicAgreementController::class, 'show'])->name('agreements.show');
    Route::post('{uuid}', [PublicAgreementController::class, 'store'])->name('agreements.store');
    Route::get('{uuid}/gracias', [PublicAgreementController::class, 'thanks'])->name('agreements.thanks');
});

require __DIR__.'/auth.php';
