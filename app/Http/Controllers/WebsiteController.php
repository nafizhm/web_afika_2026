<?php
namespace App\Http\Controllers;
use App\Models\{Property,Setting,Faq};
class WebsiteController extends Controller {
 private function settings() {return collect(config('website.defaults'))->merge(Setting::pluck('value','key'));}
 public function index() {
  $settings=$this->settings();
  $faqs=Faq::where('is_active',true)->where('is_public',true)->where('faq_type','general')->orderBy('sort_order')->orderBy('id');
  $limit=max(0,min(100,(int)$settings->get('faq_limit',8)));
  if($limit>0)$faqs->limit($limit);
  return view('home',['settings'=>$settings,'properties'=>Property::where('is_published',true)->latest()->get(),'faqs'=>$faqs->get()]);
 }
 public function show(Property $property) {abort_unless($property->is_published,404);return view('property',['property'=>$property,'settings'=>$this->settings()]);}
}
