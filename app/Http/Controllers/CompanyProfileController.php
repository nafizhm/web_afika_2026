<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile', ['settings' => Setting::pluck('value','key')]);
    }

    public function update(Request $request)
    {
        $data=$request->validate([
            'company_name'=>'required|string|max:200',
            'address'=>'nullable|string|max:2000',
            'phone'=>'nullable|string|max:40|regex:/^[0-9+().\s-]+$/',
            'email'=>'nullable|email|max:255',
            'google_maps_embed'=>'nullable|string|max:15000',
            'logo'=>'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'favicon'=>'nullable|file|mimes:png,ico|max:512',
        ]);
        $embed=trim($data['google_maps_embed']??'');
        $url=null;
        if ($embed!=='') {
            $url=$embed;
            if (str_starts_with($embed,'<')) {
                $previous=libxml_use_internal_errors(true);
                try {
                    $document=new \DOMDocument();
                    $document->loadHTML($embed,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
                    $frames=$document->getElementsByTagName('iframe');
                    $url=$frames->length===1 ? $frames->item(0)->getAttribute('src') : '';
                } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
            }
            $parts=parse_url($url);
            $host=strtolower($parts['host']??'');
            $path=$parts['path']??'';
            parse_str($parts['query']??'', $query);
            $validHost=in_array($host,['google.com','www.google.com','maps.google.com','www.google.co.id','google.co.id'],true);
            $validPath=$path==='/maps/embed' || str_starts_with($path,'/maps/embed/')
                || ($path==='/maps' && ($query['output']??'')==='embed');
            if (($parts['scheme']??'')!=='https' || !$validHost || !$validPath || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
                throw ValidationException::withMessages(['google_maps_embed'=>'Tempel kode iframe dari Google Maps → Bagikan → Sematkan peta, atau URL embed HTTPS Google Maps yang valid.']);
            }
        }
        unset($data['logo'],$data['favicon']);
        $data['google_maps_embed']=$embed?:null;
        $data['google_maps_url']=$url;
        foreach(['logo','favicon'] as $field) {
            if($request->hasFile($field)) $data[$field]=$request->file($field)->store('branding','public');
        }
        DB::transaction(function () use ($data) {
            foreach($data as $key=>$value) Setting::updateOrCreate(['key'=>$key],['value'=>$value]);
        });
        return redirect()->route('admin.profile')->with('success','Profil perusahaan berhasil diperbarui. Identitas website sudah mengikuti data terbaru.');
    }
}
