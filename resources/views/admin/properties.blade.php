@extends('layouts.admin')
@section('title','Properti')
@section('content')
<div class="section-head"><div><span class="eyebrow">KATALOG HUNIAN</span><h1>Data properti</h1><p>Kelola informasi rumah yang ditampilkan di website.</p></div><a class="button" href="{{ route('admin.property.create') }}">+ Tambah Properti</a></div>
<section class="panel"><div class="panel-heading"><div><h2>Daftar properti</h2><p>Perbarui harga, spesifikasi, foto, dan status publikasi.</p></div></div><nav class="admin-type-menu" aria-label="Edit detail tipe rumah">@foreach($properties as $item)@if($item->house_types)<a class="button small" href="{{ route('admin.property.edit',$item) }}#tipe-rumah">Edit tipe {{ $item->name }}</a>@endif @endforeach</nav>@include('admin.partials.properties-table')<noscript>{{ $properties->links() }}</noscript></section>
@endsection
