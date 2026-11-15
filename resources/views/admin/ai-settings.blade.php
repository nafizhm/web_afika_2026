@extends('layouts.admin')
@section('title','Pengaturan AI')
@section('content')
<div class="section-head"><div><span class="eyebrow">WHATSAPP ASSISTANT</span><h1>Pengaturan balasan AI</h1><p>Atur kapan Fika membalas pesan pelanggan.</p></div></div>
<section class="panel">
<div class="panel-heading"><div><h2>Balasan otomatis WhatsApp</h2></div><span class="table-badge {{ $enabled===true?'positive':'neutral' }}">{{ $enabled===null?'Belum terkonfirmasi':($enabled?'AI aktif':'AI nonaktif') }}</span></div>
@if($enabled===null)<div class="alert error" role="alert">Status AI belum tersedia. Coba muat ulang halaman.</div>@endif
<p class="ai-mode-description">{{ $enabled===true?'Fika membalas pesan baru secara otomatis.':($enabled===false?'Pesan ditangani langsung oleh admin.':'Status belum terkonfirmasi.') }}</p>
<div class="actions ai-control-actions">
@foreach([1=>'Aktifkan AI',0=>'Nonaktifkan AI'] as $value=>$label)
@if($enabled===null || (bool)$value!==$enabled)
<form method="post" action="{{ route('admin.ai-settings.update') }}" onsubmit="return confirm('{{ $value?'Aktifkan Fika untuk membalas pesan baru?':'Nonaktifkan Fika dan batalkan antrean balasan yang belum dikirim?' }}')">@csrf @method('PUT')<input type="hidden" name="enabled" value="{{ $value }}"><button type="submit" class="button {{ $value?'':'ai-off-button' }}">{{ $label }}</button></form>
@endif
@endforeach
</div>

</section>
@endsection
