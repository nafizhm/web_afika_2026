<?php
namespace App\Http\Controllers;
use App\Models\{Property,Setting,Faq};
class WebsiteController extends Controller {
public function index() { return view('home',['settings'=>Setting::pluck('value','key'),'properties'=>Property::where('is_published',true)->latest()->get(),'faqs'=>Faq::where('is_active',true)->orderBy('sort_order')->orderBy('id')->limit(8)->get()]); }
public function show(Property $property) { abort_unless($property->is_published,404); return view('property',['property'=>$property,'settings'=>Setting::pluck('value','key')]); }
}
