@extends('layouts.admin')
@section('title','Dashboard')
@section('content')
<div class="section-head"><div><span class="eyebrow">OVERVIEW</span><h1>Selamat datang, {{ auth()->user()->name }} ??</h1><p>Kelola hunian, konten website, dan layanan pelanggan dari satu tempat.</p></div><a class="button" href="{{ route('admin.property.create') }}">+ Tambah Properti</a></div>
<div class="dashboard-stats">
<div class="metric"><div><span>Total properti</span><strong>{{ $properties->count() }}</strong><small>Seluruh katalog hunian</small></div><span class="metric-icon">?</span></div>
<div class="metric"><div><span>Properti tayang</span><strong>{{ $properties->where('is_published',true)->count() }}</strong><small>Ditampilkan di website</small></div><span class="metric-icon lime">?</span></div>
<div class="metric"><div><span>FAQ aktif</span><strong>{{ \App\Models\Faq::where('is_active',true)->count() }}</strong><small>Pengetahuan pelanggan</small></div><span class="metric-icon gold">?</span></div>
<div class="metric"><div><span>Pesan masuk</span><strong>{{ \App\Models\WhatsappMessage::count() }}</strong><small>Pesan WhatsApp tersimpan</small></div><span class="metric-icon blue">?</span></div>
</div>
<div class="dashboard-banner"><div class="banner-symbol">?</div><div><strong>Konten yang lengkap, pengalaman yang lebih baik.</strong><p>Perbarui FAQ dan sinkronkan ke WhatsApp agar Fika menjawab dengan informasi terbaru.</p></div><a href="{{ route('admin.faq.index') }}">Kelola FAQ <span>?</span></a></div>
<section class="panel"><div class="panel-heading"><div><h2>Daftar properti</h2><p>Kelola hunian yang tersedia di website Afika Residence.</p></div><a class="text-link" href="{{ route('admin.properties') }}">Semua properti ?</a></div>@include('admin.partials.properties-table')</section>
@endsection