@extends('layouts.admin')
@section('title','Login Admin')
@section('content')
<div class="login-card"><span class="eyebrow">ADMINISTRATOR</span><h1>Selamat datang kembali.</h1><p>Masuk untuk mengelola konten Afika Residence.</p><form action="{{ route('login.submit') }}" method="post">@csrf<label>Username<input name="username" value="{{ old('username') }}" autocomplete="username" required autofocus></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="button full" type="submit">Masuk ke Dashboard →</button></form><a class="text-link" href="{{ route('home') }}">← Kembali ke website</a></div>
@endsection
