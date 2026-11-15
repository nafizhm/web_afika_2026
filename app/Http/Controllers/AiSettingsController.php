<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiSettingsController extends Controller
{
    private function remote(array $payload): array
    {
        $token=(string)config('faq.sync_token');
        $url=(string)config('faq.sync_url');
        if(strlen($token)<32 || parse_url($url,PHP_URL_SCHEME)!=='https') throw new \RuntimeException('Missing configuration.');
        $response=Http::withToken($token)->acceptJson()->timeout(8)
            ->post($url.(str_contains($url,'?')?'&':'?').'worker=1',$payload);
        if(!$response->successful() || $response->json('ok')!==true || !is_bool($response->json('ai_enabled'))) {
            throw new \RuntimeException('Remote control unavailable.');
        }
        return $response->json();
    }

    public function edit()
    {
        $enabled=null;
        try { $enabled=$this->remote(['action'=>'get_ai_status'])['ai_enabled']; } catch(\Throwable) {}
        return view('admin.ai-settings',compact('enabled'));
    }

    public function update(Request $request)
    {
        $request->validate(['enabled'=>'required|boolean']);
        $enabled=$request->boolean('enabled');
        try {
            $result=$this->remote(['action'=>'set_ai_enabled','enabled'=>$enabled]);
            if($result['ai_enabled']!==$enabled) throw new \RuntimeException('Unconfirmed state.');
            Setting::updateOrCreate(['key'=>'wa_ai_enabled'],['value'=>$enabled?'1':'0']);
        } catch(\Throwable) {
            return back()->withErrors(['ai_control'=>'Status belum dapat dikonfirmasi oleh hosting. Pastikan webhook terbaru sudah di-upload dan token sesuai. Muat ulang halaman untuk memeriksa status sebenarnya.']);
        }
        return redirect()->route('admin.ai-settings')->with('success',$enabled
            ?'Balasan AI aktif. Fika akan menangani pesan baru.'
            :'Balasan AI nonaktif. Pesan baru ditangani admin; antrean yang belum dikirim dibatalkan.');
    }
}
