@extends('layouts.admin')
@section('title','Pengaturan Akun')
@section('content')
<div class="section-head"><div><span class="eyebrow">AKUN ADMIN</span><h1>Pengaturan akun</h1><p>Kelola keamanan akun Anda.</p></div></div>
<section class="panel"><h2>Ganti password</h2><form action="{{ route('admin.password') }}" method="post">@csrf @method('PUT')<div class="form-grid"><label>Password saat ini<input type="password" name="current_password" autocomplete="current-password" required></label><label>Password baru<input type="password" name="password" autocomplete="new-password" minlength="8" required></label><label>Ulangi password baru<input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></label></div><button class="button" type="submit">Perbarui Password</button></form></section>

@endsection
