<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{WebsiteController,AdminController};
Route::get('/',[WebsiteController::class,'index'])->name('home');
Route::get('/properti/{property}',[WebsiteController::class,'show'])->name('property.show');
Route::get('/properti/{property}/tipe/{type}',[WebsiteController::class,'showType'])->whereNumber('type')->name('property.type');
Route::get('/admin/login',[AdminController::class,'login'])->name('login');
Route::post('/admin/login',[AdminController::class,'authenticate'])->middleware('throttle:5,1')->name('login.submit');
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
Route::get('/tampilan-website',[\App\Http\Controllers\WebsiteAppearanceController::class,'edit'])->name('appearance');
Route::put('/tampilan-website',[\App\Http\Controllers\WebsiteAppearanceController::class,'update'])->name('appearance.update');
Route::get('/pengaturan-ai',[\App\Http\Controllers\AiSettingsController::class,'edit'])->name('ai-settings');
Route::put('/pengaturan-ai',[\App\Http\Controllers\AiSettingsController::class,'update'])->middleware('throttle:10,1')->name('ai-settings.update');
Route::get('/profil', [\App\Http\Controllers\CompanyProfileController::class,'edit'])->name('profile');
Route::put('/profil', [\App\Http\Controllers\CompanyProfileController::class,'update'])->name('profile.update');
Route::get('/tables/{type}', \App\Http\Controllers\AdminTableController::class)->name('tables');
Route::get('/properti', fn()=>view('admin.properties',['properties'=>\App\Models\Property::latest()->paginate(10)]))->name('properties');
Route::get('/konten', fn()=>view('admin.settings',['settings'=>\App\Models\Setting::pluck('value','key')]))->name('content');
Route::get('/akun', fn()=>view('admin.account'))->name('account');
Route::post('/faq/sync', \App\Http\Controllers\FaqSyncController::class)->middleware('throttle:5,1')->name('faq.sync');
Route::resource('faq', \App\Http\Controllers\FaqController::class)->except(['show']);
Route::get('/pesan-whatsapp', function () {
    return view('admin.messages', ['messages' => \App\Models\WhatsappMessage::latest('id')->paginate(25)]);
})->name('messages');
Route::get('/',[AdminController::class,'index'])->name('index');
Route::post('/logout',[AdminController::class,'logout'])->name('logout');
Route::get('/properti/tambah',[AdminController::class,'edit'])->name('property.create');
Route::get('/properti/{property}/edit',[AdminController::class,'edit'])->name('property.edit');
Route::post('/properti',[AdminController::class,'save'])->name('property.store');
Route::put('/properti/{property}',[AdminController::class,'save'])->name('property.update');
Route::delete('/properti/{property}',[AdminController::class,'destroy'])->name('property.destroy');
Route::put('/konten',[AdminController::class,'settings'])->name('settings');
Route::put('/password',[AdminController::class,'password'])->name('password');
});
