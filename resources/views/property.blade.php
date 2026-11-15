@extends('layouts.public')
@section('title', $property->name.(isset($selectedType) ? ' Tipe '.$selectedType['type'] : '').' | '.$settings['brand_name'])
@section('content')
@php
$photo = $property->image ? $property->imageUrl() : (preg_match('~^https?://~', $settings['hero_image'] ?? '') ? $settings['hero_image'] : asset($settings['hero_image'] ?: 'images/residence.svg'));
$money = fn($value) => 'Rp '.number_format($value, 0, ',', '.');
$number = $settings->get('whatsapp') ?: config('whatsapp.number');
@endphp
<section class="container section housing-detail">
<a class="estate-link" href="{{ route('home') }}#hunian">&larr; {{ $settings['detail_back'] }}</a>
<div class="housing-heading"><span class="estate-kicker">HUNIAN AFIKA / KENDARI</span><h1>{{ $property->name }}@isset($selectedType) <span>Tipe {{ $selectedType['type'] }}</span>@endisset</h1><p>{{ $property->location }}</p></div>
<figure class="housing-cover"><img src="{{ $photo }}" alt="{{ $property->image ? $property->name : 'Foto sementara sebagai inspirasi hunian' }}">@unless($property->image)<figcaption>Foto sementara sebagai inspirasi hunian. Dokumentasi perumahan akan diperbarui.</figcaption>@endunless</figure>
<p class="preserve">{{ $property->description }}</p>
@if($property->house_types)
<nav class="housing-type-tags" aria-label="Pilihan tipe {{ $property->name }}">@foreach($property->house_types as $item)<a href="{{ route('property.type',['property'=>$property,'type'=>$item['type']]) }}" @if(isset($selectedType) && $selectedType['type']==$item['type']) aria-current="page" @endif>Tipe {{ $item['type'] }}</a>@endforeach</nav>
@auth<a class="estate-link" href="{{ route('admin.property.edit',$property) }}#{{ isset($selectedType) ? 'tipe-'.$selectedType['type'] : 'tipe-rumah' }}">Edit detail {{ isset($selectedType) ? 'tipe '.$selectedType['type'] : 'tipe rumah' }}</a>@endauth
<div class="housing-heading"><span class="estate-kicker">PILIHAN TIPE & PEMBAYARAN</span><h2>Rumah yang sesuai<br>untuk rencana Anda.</h2></div>
<div class="housing-types {{ isset($selectedType) ? 'housing-types-single' : '' }}">
@foreach(isset($selectedType) ? [$selectedType] : $property->house_types as $type)
<article class="housing-type-card">
<div class="housing-type-top"><h3>Tipe {{ $type['type'] }}</h3><span>{{ $type['available'] ? 'Pilihan hunian' : 'Tidak tersedia' }}</span></div>
@if(!empty($type['description']))<p class="preserve">{{ $type['description'] }}</p>@endif
@if($type['available'])
<div class="housing-cash"><small>HARGA CASH</small><strong>{{ $money($type['cash_price']) }}</strong></div>
@if(isset($type['normal_down_payment']))<p class="housing-normal">Kredit normal: DP {{ $money($type['normal_down_payment']) }} · Biaya akad {{ $money($type['normal_contract_fee']) }}</p>@endif
<div class="housing-offer"><span class="estate-kicker">{{ $type['type'] == 36 ? 'PROMO SAAT INI' : 'PEMBAYARAN KREDIT' }}</span><div><span>DP <strong>{{ $money($type['down_payment']) }}</strong></span><span>Biaya akad <strong>{{ $type['contract_fee'] == 0 ? 'Gratis' : $money($type['contract_fee']) }}</strong></span></div></div>
<table class="housing-installments"><caption>Angsuran per bulan</caption><thead><tr><th scope="col">Tenor</th><th scope="col">Angsuran / bulan</th></tr></thead><tbody>@foreach($type['installments'] as $years => $amount)<tr><th scope="row">{{ $years }} tahun</th><td>{{ $money($amount) }}</td></tr>@endforeach</tbody></table>
<a class="estate-button" href="https://wa.me/{{ $number }}?text={{ rawurlencode('Halo, saya tertarik '.$property->name.' tipe '.$type['type'].'. Mohon informasi lebih lanjut.') }}" target="_blank" rel="noopener">Tanyakan tipe {{ $type['type'] }} <span>&nearr;</span></a>
@else
<p>Tipe {{ $type['type'] }} tidak tersedia di {{ $property->name }}. Silakan lihat pilihan tipe yang tersedia di halaman ini.</p>
@endif
</article>
@endforeach
</div>
<p class="estate-photo-note">Nominal sesuai informasi perumahan. Hubungi tim kami untuk masa berlaku promo, syarat kredit, ketersediaan unit, dan spesifikasi lengkap.</p>
@else
<h2>{{ $property->price ? $money($property->price) : $settings['price_empty'] }}</h2>
<div class="estate-specs">@foreach(['building_area'=>'building_label','land_area'=>'land_label','bedrooms'=>'bedroom_label','bathrooms'=>'bathroom_label'] as $field => $label)@if($property->$field)<div><small>{{ $settings[$label] }}</small><strong>{{ $property->$field }}</strong></div>@endif @endforeach</div>
@endif
</section>
@endsection
