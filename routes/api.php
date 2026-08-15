<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\FieldNoteController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\ModuleController;
use App\Http\Controllers\Api\V1\ParticipantController;
use App\Http\Controllers\Api\V1\ProgramController;
use App\Http\Controllers\Api\V1\SubmissionController;
use App\Http\Controllers\Api\V1\TenantController;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');

    Route::middleware('auth:api')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('tenants', [TenantController::class, 'index']);
        Route::post('tenants', [TenantController::class, 'store']);

        Route::middleware('tenant')->group(function () {
            Route::middleware(SubstituteBindings::class)->group(function () {
                Route::get('tenants/{tenant}', [TenantController::class, 'show']);

                Route::get('tenants/{tenant}/members', [MemberController::class, 'index']);
                Route::post('tenants/{tenant}/members', [MemberController::class, 'invite']);
                Route::patch('tenants/{tenant}/members/{user}', [MemberController::class, 'updateRole']);
                Route::delete('tenants/{tenant}/members/{user}', [MemberController::class, 'remove']);

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
            });
        });
    });
});
