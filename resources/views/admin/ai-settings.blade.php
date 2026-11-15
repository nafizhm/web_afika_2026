@extends('layouts.admin')
@section('title','Pengaturan AI')
@section('content')
<div class="section-head"><div><span class="eyebrow">WHATSAPP ASSISTANT</span><h1>Pengaturan balasan AI</h1><p>Aktifkan Fika pada malam hari atau hari libur. Matikan saat admin melayani pelanggan.</p></div></div>
<section class="panel">
<div class="panel-heading"><div><h2>Balasan otomatis WhatsApp</h2><p>Status dibaca langsung dari hosting webhook.</p></div><span class="table-badge {{ $enabled===true?'positive':'neutral' }}">{{ $enabled===null?'Belum terkonfirmasi':($enabled?'AI aktif':'AI nonaktif') }}</span></div>
@if($enabled===null)<div class="alert error" role="alert">Status hosting belum dapat diperiksa. Pastikan file webhook terbaru terpasang dan konfigurasi token benar. Status AI tidak berubah hanya karena halaman ini dibuka.</div>@endif
<p class="ai-mode-description">{{ $enabled===true?'Fika sedang aktif dan dapat membalas pesan pelanggan.':($enabled===false?'Admin mengambil alih percakapan. Fika tidak membuat balasan otomatis untuk pesan baru.':'Muat ulang untuk memeriksa status, atau kirim pengaturan di bawah agar dikonfirmasi hosting.') }}</p>
<div class="actions ai-control-actions">
@foreach([1=>'Aktifkan AI',0=>'Nonaktifkan AI'] as $value=>$label)
@if($enabled===null || (bool)$value!==$enabled)
<form method="post" action="{{ route('admin.ai-settings.update') }}" onsubmit="return confirm('{{ $value?'Aktifkan Fika untuk membalas pesan baru?':'Nonaktifkan Fika dan batalkan antrean balasan yang belum dikirim?' }}')">@csrf @method('PUT')<input type="hidden" name="enabled" value="{{ $value }}"><button type="submit" class="button {{ $value?'':'ai-off-button' }}">{{ $label }}</button></form>
@endif
@endforeach
</div>
<div class="ai-settings-notes"><h3>Cara menggunakan</h3><ul><li>Aktifkan secara manual ketika kantor tutup, malam hari, atau hari libur.</li><li>Nonaktifkan ketika admin kembali melayani. Pesan masuk tetap disimpan.</li><li>Pesan yang masuk saat AI nonaktif tidak dibalas ulang ketika AI dinyalakan.</li><li>Balasan yang sudah diserahkan ke gateway sebelum AI dimatikan mungkin tetap terkirim.</li></ul><p>Pengaturan ini bersifat manual, belum menggunakan jadwal otomatis.</p></div>
</section>
@endsection
