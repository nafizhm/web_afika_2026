<?php

use App\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

// Registered separately: no browser session or CSRF; Bearer secret is required.
Route::post('/webhooks/whatsapp', WhatsappWebhookController::class)->name('whatsapp.webhook');

