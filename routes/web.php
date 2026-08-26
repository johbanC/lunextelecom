<?php

use App\Http\Controllers\Admin\AgreementController;
use App\Http\Controllers\Admin\EmailLogController;
use App\Http\Controllers\EmailTrackingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicAgreementController;
use App\Models\Attachment;
use App\Models\Ticket;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::redirect('/', '/admin/agreements');

Route::get('email-tracking/{token}', [EmailTrackingController::class, 'pixel'])
    ->where('token', '[0-9a-fA-F\-]{36}')
    ->name('email.tracking');

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
        Route::post('agreements/{agreement}/manage', [AgreementController::class, 'manage'])->name('agreements.manage');
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
            Route::get('drafts', function () {
                return view('admin.tickets.drafts');
            })->name('drafts.index')->middleware('can:create,'.Ticket::class);
            Route::get('demo', function () {
                abort_unless(\App\Livewire\Admin\Tickets\DemoDataGenerator::allowed(), 404);

                return view('admin.tickets.demo');
            })->name('demo')->middleware('can:catalog.manage');
            Route::get('{ticket}/draft', function (Ticket $ticket) {
                abort_unless($ticket->is_draft, 404);

                return view('admin.tickets.edit-draft', ['ticket' => $ticket]);
            })->name('drafts.edit')->middleware('can:view,ticket');
            Route::get('{ticket}', function (Ticket $ticket) {
                if ($ticket->is_draft) {
                    return redirect()->route('admin.tickets.drafts.edit', $ticket);
                }

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

        Route::get('email-log', function () {
            return view('admin.email-log.index');
        })->name('email-log.index')->middleware('can:email_log.view');
        Route::get('email-log/{emailLog}', [EmailLogController::class, 'show'])->name('email-log.show')->middleware('can:email_log.view');
        Route::post('email-log/{emailLog}/resend', [EmailLogController::class, 'resend'])->name('email-log.resend')->middleware('can:email_log.view');

        Route::get('help', function () {
            return view('admin.help.index');
        })->name('help.index')->middleware('can:tickets.view.own');
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
