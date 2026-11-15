<?php
namespace App\Http\Controllers;
use App\Models\{Property,Setting};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,Hash};
class AdminController extends Controller {
public function login() { return Auth::check() ? redirect()->route('admin.index') : view('admin.login'); }
public function authenticate(Request $r) {
$data=$r->validate(['username'=>'required|string','password'=>'required|string']);
if (!Auth::attempt($data)) return back()->withErrors(['username'=>'Username atau password tidak sesuai.'])->onlyInput('username');
$r->session()->regenerate(); return redirect()->intended(route('admin.index'));
}
public function logout(Request $r) { Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken(); return redirect()->route('login'); }
public function index() { return view('admin.index',['properties'=>Property::latest()->get(),'settings'=>Setting::pluck('value','key')]); }
public function edit(?Property $property=null) { return view('admin.property',['property'=>$property ?? new Property]); }
public function save(Request $r, ?Property $property=null) {
$data=$r->validate(['name'=>'required|string|max:200','location'=>'required|string|max:255','price'=>'nullable|integer|min:0|max:999999999999','land_area'=>'required|integer|min:1|max:1000000','building_area'=>'required|integer|min:1|max:1000000','bedrooms'=>'required|integer|min:0|max:50','bathrooms'=>'required|integer|min:0|max:50','description'=>'required|string|max:20000','image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:4096']);
$property ??= new Property; unset($data['image']);
if($r->hasFile('image')) $data['image']=$r->file('image')->store('properties','public');
$data['is_published']=$r->boolean('is_published'); $property->fill($data)->save();
return redirect()->route('admin.index')->with('success','Properti berhasil disimpan.');
}
public function destroy(Property $property) { $property->delete(); return back()->with('success','Properti berhasil dihapus.'); }
public function settings(Request $r) {
$data=$r->validate(['hero_title'=>'required|string|max:150','hero_description'=>'required|string|max:600','about'=>'required|string|max:5000','whatsapp'=>'nullable|regex:/^[0-9]{9,15}$/','address'=>'nullable|string|max:600','instagram'=>'nullable|url:http,https|max:255','logo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']);
unset($data['logo']); if($r->hasFile('logo')) $data['logo']=$r->file('logo')->store('branding','public');
foreach($data as $key=>$value) Setting::updateOrCreate(['key'=>$key],['value'=>$value]);
return back()->with('success','Konten website berhasil diperbarui.');
}
public function password(Request $r) { $data=$r->validate(['current_password'=>'required|current_password','password'=>'required|string|min:8|confirmed']); $r->user()->forceFill(['password'=>Hash::make($data['password'])])->save(); return back()->with('success','Password berhasil diganti.'); }
}
