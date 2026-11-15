<?php
namespace App\Http\Controllers;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class WebsiteAppearanceController extends Controller {
 public function edit(){ return view('admin.appearance',['settings'=>collect(config('website.defaults'))->merge(Setting::pluck('value','key'))]); }
 public function update(Request $request){
  $rules=[];
  $images=['hero_image','gallery_image_1','gallery_image_2'];
  foreach(config('website.defaults') as $key=>$default){
   $rules[$key]='sometimes|nullable|string|max:5000';
   if(str_starts_with($key,'show_'))$rules[$key]='sometimes|required|boolean';
   if(in_array($key,$images,true)){$rules[$key]='sometimes|nullable|url:http,https|max:2000';$rules[$key.'_upload']='nullable|image|mimes:jpg,jpeg,png,webp|max:5120';}
   if(str_starts_with($key,'theme_'))$rules[$key]=$key==='theme_font'?'sometimes|required|in:DM Sans,Manrope,Arial,Georgia':'sometimes|required|regex:/^#[0-9a-fA-F]{6}$/';
  }
  $rules['instagram']='sometimes|nullable|url:http,https|max:255';
  $rules['faq_limit']='sometimes|required|integer|min:0|max:100';
  $data=$request->validate($rules);
  $stored=[];
  try {
   foreach($images as $key){unset($data[$key.'_upload']);if(empty($data[$key]) && str_starts_with((string)Setting::where('key',$key)->value('value'),'storage/'))unset($data[$key]);if($request->hasFile($key.'_upload')){$path=$request->file($key.'_upload')->store('website','public');$stored[]=$path;$data[$key]='storage/'.$path;}}
   DB::transaction(function()use($data){foreach($data as $key=>$value)Setting::updateOrCreate(['key'=>$key],['value'=>$value]);});
  } catch(\Throwable $error){foreach($stored as $path)\Illuminate\Support\Facades\Storage::disk('public')->delete($path);throw $error;}
  return back()->with('success','Tampilan website berhasil diperbarui.');
 }
}
