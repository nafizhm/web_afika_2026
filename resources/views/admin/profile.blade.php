@extends('layouts.admin')
@section('title','Profil Perusahaan')
@section('content')
<div class="section-head"><div><span class="eyebrow">COMPANY PROFILE</span><h1>Profil perusahaan</h1><p>Kelola identitas, kontak, dan lokasi perusahaan yang tampil di website.</p></div></div>
<form method="post" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
@csrf @method('PUT')
<section class="panel"><div class="panel-heading"><div><h2>Informasi perusahaan</h2><p>Nama dan kontak digunakan pada identitas serta bagian kontak website.</p></div></div>
<div class="form-grid">
<label class="full" for="company_name">Nama perusahaan<input id="company_name" name="company_name" required maxlength="200" value="{{ old('company_name',$settings->get('company_name','PT. Sahir Propertindo Niaga')) }}" placeholder="PT. Sahir Propertindo Niaga"></label>
<label for="phone">Nomor telepon<input id="phone" name="phone" type="tel" maxlength="40" value="{{ old('phone',$settings->get('phone')) }}" placeholder="082221992911"><small>Nomor kontak perusahaan. Nomor WhatsApp tetap di menu Konten Website.</small></label>
<label for="email">Email perusahaan<input id="email" name="email" type="email" maxlength="255" value="{{ old('email',$settings->get('email')) }}" placeholder="info@perusahaan.com"></label>
<label class="full" for="address">Alamat perusahaan<textarea id="address" name="address" rows="3" maxlength="2000" placeholder="Alamat lengkap kantor pemasaran">{{ old('address',$settings->get('address')) }}</textarea></label>
</div></section>
<section class="panel"><div class="panel-heading"><div><h2>Logo & favicon</h2><p>Upload identitas visual perusahaan. Gambar lama tetap dipakai jika tidak diganti.</p></div></div>
<div class="form-grid"><label for="logo">Logo perusahaan<input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp"><small>PNG, JPG, atau WebP. Maksimal 2 MB.</small>@if($settings->get('logo'))<div class="branding-preview"><img src="{{ asset('storage/'.$settings['logo']) }}" alt="Logo perusahaan saat ini"></div>@endif</label>
<label for="favicon">Favicon<input id="favicon" type="file" name="favicon" accept="image/png,image/x-icon,image/vnd.microsoft.icon,.ico"><small>PNG atau ICO. Maksimal 512 KB. Disarankan gambar persegi 32 × 32 atau 64 × 64 piksel.</small>@if($settings->get('favicon'))<div class="branding-preview"><img class="favicon-preview" src="{{ asset('storage/'.$settings['favicon']) }}" alt="Favicon saat ini"></div>@endif</label></div></section>
<section class="panel"><div class="panel-heading"><div><h2>Lokasi Google Maps</h2><p>Di Google Maps pilih Bagikan → Sematkan peta → Salin HTML, lalu paste di bawah.</p></div></div>
<label for="google_maps_embed">Kode embed Google Maps<textarea id="google_maps_embed" name="google_maps_embed" rows="5" maxlength="15000" placeholder="Tempel kode &lt;iframe src=...&gt;&lt;/iframe&gt; dari Google Maps">{{ old('google_maps_embed',$settings->get('google_maps_embed')) }}</textarea><small>Bisa juga diisi URL embed Google Maps. Kosongkan untuk menyembunyikan peta.</small></label>
@if($settings->get('google_maps_url'))<iframe class="company-map" src="{{ $settings['google_maps_url'] }}" title="Lokasi perusahaan" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>@endif
</section><button class="button" type="submit">Simpan Profil Perusahaan</button>
</form>
@endsection
