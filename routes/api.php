<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\FieldNoteController;
use App\Http\Controllers\Api\V1\ForumController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\JournalController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\ModuleController;
use App\Http\Controllers\Api\V1\ParticipantController;
use App\Http\Controllers\Api\V1\PlanningBoardController;
use App\Http\Controllers\Api\V1\PlanningColumnController;
use App\Http\Controllers\Api\V1\PlanningTaskController;
use App\Http\Controllers\Api\V1\ProgramController;
use App\Http\Controllers\Api\V1\SubmissionController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::get('health', HealthController::class)->name('health');

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('auth/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:auth');
    Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::post('auth/accept-invitation', [AuthController::class, 'acceptInvitation'])->middleware('throttle:password-reset');
    Route::get('auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');

    Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

    Route::middleware('auth:api')->group(function () {
        Route::post('broadcasting/auth', [BroadcastController::class, 'authenticate']);

        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])->middleware('throttle:password-reset');

        Route::get('tenants', [TenantController::class, 'index'])->middleware('verified');
        Route::post('tenants', [TenantController::class, 'store'])->middleware('verified');

        Route::middleware(['tenant'])->group(function () {
            Route::middleware(SubstituteBindings::class)->group(function () {
                Route::get('tenants/{tenant}', [TenantController::class, 'show']);

                Route::get('tenants/{tenant}/members', [MemberController::class, 'index']);
                Route::post('tenants/{tenant}/members', [MemberController::class, 'invite']);
                Route::patch('tenants/{tenant}/members/{user}', [MemberController::class, 'updateRole']);
                Route::delete('tenants/{tenant}/members/{user}', [MemberController::class, 'remove']);

                Route::get('my/participants', [ParticipantController::class, 'myParticipants']);
                Route::apiResource('participants', ParticipantController::class);
                Route::post('participants/{participant}/guardians', [ParticipantController::class, 'addGuardian']);
                Route::delete('participants/{participant}/guardians/{user}', [ParticipantController::class, 'removeGuardian']);
                Route::get('participants/{participant}/field-notes', [ParticipantController::class, 'fieldNotes']);

                Route::apiResource('groups', GroupController::class);
                Route::post('groups/{group}/members', [GroupController::class, 'addMember']);
                Route::delete('groups/{group}/members/{participant}', [GroupController::class, 'removeMember']);

                Route::post('programs/thumbnail', [ProgramController::class, 'uploadThumbnail']);
                Route::apiResource('programs', ProgramController::class)->except(['edit', 'create']);
                Route::post('programs/{program}/publish', [ProgramController::class, 'publish']);
                Route::post('programs/{program}/unpublish', [ProgramController::class, 'unpublish']);
                Route::post('programs/{program}/modules', [ModuleController::class, 'store']);
                Route::patch('programs/{program}/modules/{module}', [ModuleController::class, 'update']);
                Route::delete('programs/{program}/modules/{module}', [ModuleController::class, 'destroy']);
                Route::post('modules/{module}/activities', [ActivityController::class, 'store']);
                Route::patch('modules/{module}/activities/{activity}', [ActivityController::class, 'update']);
                Route::delete('modules/{module}/activities/{activity}', [ActivityController::class, 'destroy']);

                Route::apiResource('enrollments', EnrollmentController::class)->except(['edit', 'create', 'update']);
                Route::get('enrollments/{enrollment}/progress', [EnrollmentController::class, 'progress']);

                Route::get('submissions', [SubmissionController::class, 'index']);
                Route::get('submissions/{submission}', [SubmissionController::class, 'show']);
                Route::post('activities/{activity}/submit', [SubmissionController::class, 'submit']);
                Route::patch('submissions/{submission}/review', [SubmissionController::class, 'review']);

                Route::get('field-notes', [FieldNoteController::class, 'index']);
                Route::post('field-notes', [FieldNoteController::class, 'store']);
                Route::get('field-notes/{fieldNote}', [FieldNoteController::class, 'show']);
                Route::patch('field-notes/{fieldNote}', [FieldNoteController::class, 'update']);
                Route::delete('field-notes/{fieldNote}', [FieldNoteController::class, 'destroy']);

                Route::get('journal', [JournalController::class, 'index']);
                Route::post('journal', [JournalController::class, 'store']);
                Route::get('journal/{entry}', [JournalController::class, 'show']);
                Route::patch('journal/{entry}', [JournalController::class, 'update']);
                Route::delete('journal/{entry}', [JournalController::class, 'destroy']);

                Route::apiResource('planning/boards', PlanningBoardController::class);
                Route::post('planning/boards/{board}/columns', [PlanningColumnController::class, 'store']);
                Route::patch('planning/columns/{column}', [PlanningColumnController::class, 'update']);
                Route::delete('planning/columns/{column}', [PlanningColumnController::class, 'destroy']);
                Route::post('planning/columns/{column}/tasks', [PlanningTaskController::class, 'store']);
                Route::patch('planning/tasks/{task}', [PlanningTaskController::class, 'update']);
                Route::delete('planning/tasks/{task}', [PlanningTaskController::class, 'destroy']);
                Route::patch('planning/tasks/{task}/move', [PlanningTaskController::class, 'move']);

                Route::get('billing/plans', [BillingController::class, 'plans']);
                Route::get('billing/current', [BillingController::class, 'current']);
                Route::post('billing/subscriptions', [BillingController::class, 'store']);
                Route::post('billing/subscriptions/swap', [BillingController::class, 'swap']);
                Route::post('billing/subscriptions/cancel', [BillingController::class, 'cancel']);
                Route::get('billing/subscriptions/portal', [BillingController::class, 'portal']);
                Route::get('billing/invoices', [BillingController::class, 'invoices']);

                Route::middleware('throttle:ai')->group(function () {
                    Route::post('ai/summarize-progress', [AiController::class, 'summarizeProgress']);
                    Route::post('ai/suggest-activities', [AiController::class, 'suggestActivities']);
                    Route::post('ai/explain-activity', [AiController::class, 'explainActivity']);
                    Route::post('ai/generate-draft', [AiController::class, 'generateDraft']);
                });

                Route::get('conversations', [MessageController::class, 'index']);
                Route::post('conversations', [MessageController::class, 'store']);
                Route::get('conversations/{conversation}', [MessageController::class, 'show']);
                Route::post('conversations/{conversation}/messages', [MessageController::class, 'sendMessage']);
                Route::get('conversations/{conversation}/messages', [MessageController::class, 'messages']);
                Route::patch('conversations/{conversation}/read', [MessageController::class, 'markAsRead']);
                Route::post('conversations/{conversation}/typing', [MessageController::class, 'typing']);

                Route::get('forum/posts', [ForumController::class, 'index']);
                Route::post('forum/posts', [ForumController::class, 'store']);
                Route::get('forum/posts/{post}', [ForumController::class, 'show']);
                Route::patch('forum/posts/{post}', [ForumController::class, 'update']);
                Route::delete('forum/posts/{post}', [ForumController::class, 'destroy']);
                Route::post('forum/posts/{post}/comments', [ForumController::class, 'addComment']);
                Route::delete('forum/comments/{comment}', [ForumController::class, 'deleteComment']);
                Route::post('forum/posts/{post}/reactions', [ForumController::class, 'togglePostReaction']);
                Route::post('forum/comments/{comment}/reactions', [ForumController::class, 'toggleCommentReaction']);
            });
        });
    });
});
