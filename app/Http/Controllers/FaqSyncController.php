<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Support\Facades\Http;

class FaqSyncController extends Controller
{
    public function __invoke()
    {
        $token = (string) config('faq.sync_token');
        $url = (string) config('faq.sync_url');
        if (strlen($token) < 32 || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return back()->withErrors(['faq_sync' => 'Isi FAQ_SYNC_URL (HTTPS) dan FAQ_SYNC_TOKEN pada .env. Token harus sama dengan workerToken di hosting.']);
        }
        $faqs = Faq::orderBy('sort_order')->orderBy('id')->limit(101)->get(['id','question','answer','sort_order','is_active']);
        if ($faqs->count() > 100) return back()->withErrors(['faq_sync' => 'Sinkronisasi trial dibatasi 100 FAQ.']);
        try {
            $response = Http::withToken($token)->acceptJson()->timeout(20)->post($url.(str_contains($url, '?') ? '&' : '?').'worker=1', [
                'action' => 'sync_faqs', 'faqs' => $faqs->toArray(),
            ]);
            if (!$response->successful() || $response->json('ok') !== true) {
                return back()->withErrors(['faq_sync' => 'Sinkronisasi gagal (HTTP '.$response->status().'). Pastikan webhook terbaru sudah di-upload dan token sesuai.']);
            }
            return back()->with('success', 'FAQ berhasil disinkronkan ke hosting WhatsApp. Pesan baru akan menggunakan FAQ aktif.');
        } catch (\Throwable) {
            return back()->withErrors(['faq_sync' => 'Hosting WhatsApp belum bisa dihubungi. Data FAQ lokal tetap tersimpan; coba sinkronkan kembali.']);
        }
    }
}
