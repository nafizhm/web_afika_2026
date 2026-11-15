@extends('layouts.admin')
@section('content')
<a class="text-link" href="{{ route('admin.index') }}">← Kembali ke dashboard</a><section class="panel"><span class="eyebrow">PROPERTI</span><h1>{{ $property->exists ? 'Edit properti' : 'Tambah properti' }}</h1><form method="post" action="{{ $property->exists ? route('admin.property.update',$property) : route('admin.property.store') }}" enctype="multipart/form-data">@csrf @if($property->exists) @method('PUT') @endif<div class="form-grid"><label>Nama properti<input name="name" value="{{ old('name',$property->name) }}" required maxlength="200"></label><label>Lokasi<input name="location" value="{{ old('location',$property->location) }}" required maxlength="255"></label><label>Harga (Rp)<input type="number" name="price" value="{{ old('price',$property->price) }}" min="0"><small>Kosongkan untuk menampilkan “Hubungi untuk informasi harga”.</small></label><label>Foto hunian<input type="file" name="image" accept="image/png,image/jpeg,image/webp"><small>PNG, JPG, WebP. Maksimal 4 MB.</small></label>@foreach(['land_area'=>'Luas tanah (m²)','building_area'=>'Luas bangunan (m²)','bedrooms'=>'Kamar tidur','bathrooms'=>'Kamar mandi'] as $field=>$label)<label>{{ $label }}<input type="number" name="{{ $field }}" value="{{ old($field,$property->$field) }}" min="{{ $field === 'building_area' ? 1 : 0 }}" required></label>@endforeach<label class="full">Deskripsi<textarea name="description" required rows="6">{{ old('description',$property->description) }}</textarea></label></div>@if($property->house_types)
<h2 id="tipe-rumah">Tipe rumah &amp; pembayaran</h2>
<nav class="admin-type-menu" aria-label="Edit tipe rumah">@foreach($property->house_types as $item)<a class="button small" href="#tipe-{{ $item['type'] }}">Edit tipe {{ $item['type'] }}</a>@endforeach</nav><p>Angsuran diisi per bulan dalam rupiah. Kosongkan harga normal jika belum ada informasi.</p>
@foreach(old('house_types',$property->house_types) as $index => $type)
<fieldset class="panel admin-type-editor" id="tipe-{{ $type['type'] }}"><legend>Tipe {{ $type['type'] }}</legend><div class="form-grid">
<label>Tipe<input type="number" name="house_types[{{ $index }}][type]" value="{{ $type['type'] }}" min="1" required></label>
<label>Status<select name="house_types[{{ $index }}][available]"><option value="1" @selected($type['available'])>Tersedia</option><option value="0" @selected(!$type['available'])>Tidak tersedia</option></select></label>
@foreach(['cash_price'=>'Harga cash','down_payment'=>'DP / DP promo','contract_fee'=>'Biaya akad (0 = gratis)','normal_down_payment'=>'DP normal (opsional)','normal_contract_fee'=>'Biaya akad normal (opsional)'] as $key => $label)
<label>{{ $label }} (Rp)<input type="number" min="0" name="house_types[{{ $index }}][{{ $key }}]" value="{{ $type[$key] ?? '' }}"></label>
@endforeach
<label class="full">Detail tipe rumah<textarea name="house_types[{{ $index }}][description]" rows="4" maxlength="20000" placeholder="Deskripsi dan spesifikasi resmi tipe rumah ini">{{ $type['description'] ?? '' }}</textarea></label>
@foreach([5,10,15,20] as $years)<label>Angsuran {{ $years }} tahun / bulan (Rp)<input type="number" min="0" name="house_types[{{ $index }}][installments][{{ $years }}]" value="{{ $type['installments'][$years] ?? '' }}"></label>@endforeach
</div></fieldset>
@endforeach
@endif
@if($property->image)<img class="property-preview" src="{{ $property->imageUrl() }}" alt="Foto saat ini">@endif<input type="hidden" name="is_published" value="0"><label class="check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published',$property->is_published))> Tampilkan properti di website</label><button class="button" type="submit">Simpan Properti ↗</button></form></section>
@endsection
