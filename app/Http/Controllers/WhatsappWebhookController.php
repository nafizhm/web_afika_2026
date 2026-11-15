<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsappWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        require_once base_path('hosting/whatsapp-webhook/Receiver.php');
        [$status, $body] = \Afika\WhatsApp\Receiver::handle(
            DB::connection()->getPdo(),
            $request->getContent(),
            $request->header('Authorization', ''),
            (string) config('whatsapp.webhook_secret'),
            (string) config('whatsapp.number'),
        );
        return response()->json($body, $status);
    }
}

