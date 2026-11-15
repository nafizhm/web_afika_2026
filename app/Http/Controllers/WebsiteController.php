<?php
namespace App\Http\Controllers;
use App\Models\{Property,Setting,Faq};


class WebsiteController extends Controller {
 private function settings() {return collect(config('website.defaults'))->merge(Setting::pluck('value','key'));}
 public function index() {
  $settings=$this->settings();
  $faqs=Faq::forWebsite()->orderBy('sort_order')->orderBy('id');
  $limit=max(0,min(100,(int)$settings->get('faq_limit',8)));
  if($limit>0)$faqs->limit($limit);
  return view('home',['settings'=>$settings,'properties'=>Property::where('is_published',true)->latest()->get(),'faqs'=>$faqs->get()]);
 }
 public function show(Property $property) {abort_unless($property->is_published,404);return view('property',['property'=>$property,'settings'=>$this->settings()]);}
 public function showType(Property $property, string $type) {
  abort_unless($property->is_published,404);
  $selectedType=collect($property->house_types ?? [])->first(fn($row)=>(string)$row['type']===$type);
  abort_unless($selectedType,404);
  return view('property',['property'=>$property,'selectedType'=>$selectedType,'settings'=>$this->settings()]);
 }
}
