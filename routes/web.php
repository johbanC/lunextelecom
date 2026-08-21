<?php

use App\Http\Controllers\Admin\AgreementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicAgreementController;
use App\Models\Attachment;
use App\Models\Ticket;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::redirect('/', '/admin/agreements');

Route::get('lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'es', 'hi'], true)) {
        session(['locale' => $locale]);
    }

    return back();
})->name('locale.switch');

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('agreements', [AgreementController::class, 'index'])->name('agreements.index');
        Route::get('agreements/create', [AgreementController::class, 'create'])->name('agreements.create');
        Route::post('agreements', [AgreementController::class, 'store'])->name('agreements.store');
        Route::get('agreements/{agreement}', [AgreementController::class, 'show'])->name('agreements.show');
        Route::post('agreements/{agreement}/extend', [AgreementController::class, 'extend'])->name('agreements.extend');
        Route::get('agreements/{agreement}/pdf', [AgreementController::class, 'pdf'])->name('agreements.pdf');

        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', function () {
                return view('admin.tickets.index');
            })->name('index')->middleware('can:viewAny,'.Ticket::class);
            Route::get('create', function () {
                return view('admin.tickets.create');
            })->name('create')->middleware('can:create,'.Ticket::class);
            Route::get('catalog', function () {
                return view('admin.tickets.catalog');
            })->name('catalog')->middleware('can:catalog.manage');
            Route::get('notification-rules', function () {
                return view('admin.tickets.notification-rules');
            })->name('notification-rules')->middleware('can:notification_rules.manage');
            Route::get('{ticket}', function (Ticket $ticket) {
                return view('admin.tickets.show', ['ticket' => $ticket]);
            })->name('show')->middleware('can:view,ticket');

            Route::get('{ticket}/attachments/{attachment}', function (Ticket $ticket, Attachment $attachment) {
                abort_unless($attachment->ticket_id === $ticket->id, 404);

                return Storage::disk('local')->download($attachment->path, $attachment->original_name);
            })->name('attachments.download')->middleware('can:view,ticket');
        });

        Route::get('users', function () {
            return view('admin.users.index');
        })->name('users.index')->middleware('can:users.manage');

        Route::get('groups', function () {
            return view('admin.groups.index');
        })->name('groups.index')->middleware('can:groups.manage');

        Route::get('reports', function () {
            return view('admin.reports.index');
        })->name('reports.index');
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
