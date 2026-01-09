<?php

use Illuminate\Support\Facades\Route;
use Manuelballmer\EmailTemplates\Http\Controllers\EmailTemplatePreviewController;

// Route für alle Domains/Subdomains verfügbar
Route::get('email-templates/{emailTemplate}/preview', [EmailTemplatePreviewController::class, 'show'])
    ->name('email-templates.preview')
    ->middleware(['web'])
    ->withoutMiddleware(['auth']);
