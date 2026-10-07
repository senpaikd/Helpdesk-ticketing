<?php

use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePasswordController;
use App\Http\Controllers\ProfileSessionController;
use App\Http\Controllers\TicketAssignmentController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketPriorityController;
use App\Http\Controllers\TicketStatusController;
use App\Http\Controllers\WorkspaceDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', CustomerDashboardController::class)->name('portal');
    Route::get('/dashboard', WorkspaceDashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfilePasswordController::class, 'update'])->name('profile.password.update');
    Route::delete('/profile/sessions', [ProfileSessionController::class, 'destroyOthers'])->name('profile.sessions.destroy-others');
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/tickets/{ticket}/assignment', [TicketAssignmentController::class, 'update'])->name('tickets.assignment.update');
    Route::patch('/tickets/{ticket}/status', [TicketStatusController::class, 'update'])->name('tickets.status.update');
    Route::patch('/tickets/{ticket}/priority', [TicketPriorityController::class, 'update'])->name('tickets.priority.update');
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
    Route::post('/tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store'])->name('tickets.attachments.store');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'download'])->name('tickets.attachments.download');
});

require __DIR__.'/auth.php';
