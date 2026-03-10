<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\StepController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskCommentAttachmentController;
use App\Http\Controllers\TaskCollaborationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDueDateReminderController;
use App\Http\Controllers\TaskImageController;
use App\Http\Controllers\TaskMentionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tasks');
Route::redirect('/ideas', '/tasks');

Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'locale' => ['required', 'in:nl,en'],
    ]);

    session(['locale' => $validated['locale']]);

    return back();
})->name('locale.update');

Route::get('/support', [SupportController::class, 'index'])->name('support');
Route::post('/support', [SupportController::class, 'store'])->name('support.store')->middleware('throttle:support-submissions');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/voorwaarden', 'pages.terms')->name('terms');

Route::get('/register', [RegisteredUserController::class, 'create'])->middleware('guest');
Route::post('/register', [RegisteredUserController::class, 'store'])->middleware(['guest', 'throttle:register']);

Route::get('/login', [SessionsController::class, 'create'])->name('login')->middleware('guest');
Route::post('/login', [SessionsController::class, 'store'])->middleware(['guest', 'throttle:login']);

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [SessionsController::class, 'destroy']);
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/reset', [OnboardingController::class, 'reset'])->name('onboarding.reset');

    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index')->middleware('admin');
    Route::get('/admin/users/{user}/tasks', [AdminController::class, 'userTasks'])->name('admin.users.tasks')->middleware('admin');
    Route::patch('/admin/support/{supportMessage}/resolve', [AdminController::class, 'resolve'])->name('admin.support.resolve')->middleware(['admin', 'throttle:admin-actions']);
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy')->middleware(['admin', 'throttle:admin-actions']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::get('/tasks/invites/{token}', [TaskCollaborationController::class, 'acceptInvite'])->name('task.invites.accept')->middleware('throttle:collaboration-actions');

    Route::get('/tasks', [TaskController::class, 'index'])->name('task.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('task.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('task.show');

    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('task.update');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('task.status.update');
    Route::post('/tasks/{task}/collaborators/email', [TaskCollaborationController::class, 'inviteByEmail'])->name('task.collaborators.email')->middleware('throttle:collaboration-actions');
    Route::delete('/tasks/{task}/collaborators/{user}', [TaskCollaborationController::class, 'removeCollaborator'])->name('task.collaborators.destroy')->middleware('throttle:collaboration-actions');
    Route::post('/tasks/{task}/leave', [TaskCollaborationController::class, 'leave'])->name('task.leave')->middleware('throttle:collaboration-actions');
    Route::post('/tasks/{task}/invites/link', [TaskCollaborationController::class, 'createInviteLink'])->name('task.invites.link')->middleware('throttle:collaboration-actions');
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('task.comments.store');
    Route::delete('/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('task.comments.destroy');
    Route::get('/tasks/{task}/comments/{comment}/attachments/{attachment}', [TaskCommentAttachmentController::class, 'download'])->name('task.comments.attachments.download');
    Route::post('/collaboration-requests/{collaborationRequest}/accept', [TaskCollaborationController::class, 'acceptRequest'])->name('task.collab-requests.accept')->middleware('throttle:collaboration-actions');
    Route::post('/collaboration-requests/{collaborationRequest}/reject', [TaskCollaborationController::class, 'rejectRequest'])->name('task.collab-requests.reject')->middleware('throttle:collaboration-actions');

    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('task.destroy');
    Route::delete('/tasks/{task}/image', [TaskImageController::class, 'destroy'])->name('task.image.destroy');
    Route::patch('/steps/{step}', [StepController::class, 'update'])->name('step.update');

    Route::get('/inbox/mentions/{mention}', [TaskMentionController::class, 'open'])->name('inbox.mentions.open');
    Route::get('/inbox/reminders/{reminder}', [TaskDueDateReminderController::class, 'open'])->name('inbox.reminders.open');
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::post('/inbox/mentions/read-all', [InboxController::class, 'markAllMentionsRead'])->name('inbox.mentions.read-all');
    Route::post('/inbox/reminders/read-all', [InboxController::class, 'markAllRemindersRead'])->name('inbox.reminders.read-all');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update')->middleware('throttle:profile-update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy')->middleware('throttle:profile-update');
    Route::delete('profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy')->middleware('throttle:profile-update');
});
