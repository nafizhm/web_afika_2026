@extends('layouts.public')
@section('content')
@php
$photos = array_map(fn($key) => preg_match('~^https?://~', $settings[$key] ?? '') ? $settings[$key] : asset($settings[$key] ?: 'images/residence.svg'), ['hero_image','gallery_image_1','gallery_image_2']);
$wa = 'https://wa.me/'.($settings->get('whatsapp') ?: config('whatsapp.number')).'?text='.rawurlencode($settings['wa_survey_message']);
@endphp
@if($settings['show_hero'])
<section class="estate-hero">
    <img class="estate-hero-image" src="{{ $photos[0] }}" alt="{{ $settings['hero_alt'] }}" fetchpriority="high">
    <div class="estate-hero-shade"></div>
    <div class="container estate-hero-content"><span class="estate-kicker">{{ $settings['hero_kicker'] }}</span><h1>{{ $settings->get('hero_title','Ruang untuk hidup. Tempat untuk pulang.') }}</h1><p>{{ $settings->get('hero_description','Temukan hunian yang sesuai dengan cerita keluarga Anda.') }}</p><a class="estate-button" href="#hunian">{{ $settings['hero_button'] }} <span aria-hidden="true">↗</span></a></div>
    <div class="estate-hero-bottom"><span>{{ $settings['hero_tagline'] }}</span><a href="#tentang">{{ $settings['hero_more'] }} ↓</a></div>
</section>
@endif
@if($settings['show_about'])
<section class="container estate-intro" id="tentang"><div><span class="estate-kicker">{{ $settings['about_kicker'] }}</span><h2>{{ $settings['about_title'] }}</h2></div><div><p class="preserve">{{ $settings->get('about') }}</p><a class="estate-link" href="#hunian">{{ $settings['about_button'] }} <span>↗</span></a></div></section>
@endif
@if($settings['show_gallery'])
<section class="container estate-gallery" aria-label="Inspirasi hunian"><figure><img src="{{ $photos[1] }}" alt="{{ $settings['gallery_alt_1'] }}" loading="lazy"><figcaption>{{ $settings['gallery_caption_1'] }}</figcaption></figure><figure><img src="{{ $photos[2] }}" alt="{{ $settings['gallery_alt_2'] }}" loading="lazy"><figcaption>{{ $settings['gallery_caption_2'] }}</figcaption></figure><p class="estate-photo-note">{{ $settings['gallery_note'] }}</p></section>
@endif
@if($settings['show_homes'])
<section class="estate-homes" id="hunian"><div class="container"><div class="estate-section-title"><span class="estate-kicker">{{ $settings['homes_kicker'] }}</span><h2>{{ $settings['homes_title'] }}</h2><p>{{ $settings['homes_description'] }}</p></div>
@forelse($properties as $property)
<article class="estate-property"><a class="estate-property-photo" href="{{ route('property.show',$property) }}"><img src="{{ $property->image ? $property->imageUrl() : $photos[$loop->index % 3] }}" alt="{{ $property->image ? $property->name : $settings['location_note'] }}" loading="lazy"><span>{{ $property->image ? $settings['brand_name'] : $settings['inspiration_label'] }}</span></a><div class="estate-property-info"><span class="estate-kicker">{{ $property->location }}</span><h3>{{ $property->name }}</h3><p class="preserve">{{ \Illuminate\Support\Str::limit($property->description,350) }}</p>@if($property->house_types)
<div class="housing-type-tags">@foreach($property->house_types as $type)<a class="{{ $type['available'] ? '' : 'unavailable' }}" href="{{ route('property.type', ['property'=>$property, 'type'=>$type['type']]) }}" aria-label="Lihat {{ $property->name }} tipe {{ $type['type'] }}">Tipe {{ $type['type'] }}{{ $type['available'] ? '' : ' ? Tidak tersedia' }} <span aria-hidden="true">&nearr;</span></a>@endforeach</div>
@else
<div class="estate-specs"><div><small>{{ $settings['building_label'] }}</small><strong>{{ $property->building_area }} <em>m²</em></strong></div><div><small>{{ $settings['land_label'] }}</small><strong>{{ $property->land_area }} <em>m²</em></strong></div><div><small>{{ $settings['bedroom_label'] }}</small><strong>{{ $property->bedrooms }}</strong></div><div><small>{{ $settings['bathroom_label'] }}</small><strong>{{ $property->bathrooms }}</strong></div></div>@endif
<div class="estate-price"><span>{{ $settings['price_label'] }}</span><strong>{{ $property->price ? 'Rp '.number_format($property->price,0,',','.') : $settings['price_empty'] }}</strong></div><a class="estate-button" href="{{ route('property.show',$property) }}">{{ $settings['property_button'] }} <span>↗</span></a></div></article>
@empty<div class="estate-empty"><h3>{{ $settings['homes_empty_title'] }}</h3><p>{{ $settings['homes_empty_description'] }}</p><a class="estate-button" href="{{ $wa }}" target="_blank" rel="noopener">{{ $settings['homes_empty_button'] }} ↗</a></div>@endforelse
</div></section>
@endif
@if($settings['show_location'])
<section class="container estate-location" id="lokasi"><div><span class="estate-kicker">{{ $settings['location_kicker'] }}</span><h2>{{ $settings['location_title'] }}</h2><p>{{ $settings['location_description'] }}</p>@if($settings->get('address'))<div class="estate-address"><span>{{ $settings['address_label'] }}</span><p class="preserve">{{ $settings['address'] }}</p></div>@endif<a class="estate-link" href="{{ $wa }}" target="_blank" rel="noopener">{{ $settings['location_button'] }} <span>↗</span></a></div><div class="estate-location-visual">@if($settings->get('google_maps_url'))<iframe src="{{ $settings['google_maps_url'] }}" title="Lokasi {{ $settings->get('company_name','Afika Residence') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>@else<img src="{{ $photos[0] }}" alt="{{ $settings['location_alt'] }}" loading="lazy"><span class="estate-photo-note">{{ $settings['location_note'] }}</span>@endif</div></section>
@endif
@if($settings['show_faq'] && $faqs->isNotEmpty())<section class="estate-faq" id="faq"><div class="container estate-faq-grid"><div><span class="estate-kicker">{{ $settings['faq_kicker'] }}</span><h2>{{ $settings['faq_title'] }}</h2><p>{{ $settings['faq_description'] }}</p></div><div>@foreach($faqs as $faq)<details class="estate-faq-item"><summary>{{ $faq->question }}<span aria-hidden="true">+</span></summary><p class="preserve">{{ $faq->answer }}</p></details>@endforeach</div></div></section>@endif
@if($settings['show_contact'])
<section class="estate-contact" id="kontak"><div class="container"><span class="estate-kicker">{{ $settings['contact_kicker'] }}</span><h2>{{ $settings['contact_title'] }}</h2><p>{{ $settings['contact_description'] }}</p><a class="estate-button" href="{{ $wa }}" target="_blank" rel="noopener">{{ $settings['contact_button'] }} <span>↗</span></a><div class="estate-contact-links">@if($settings->get('phone'))<a href="tel:{{ preg_replace('/[^0-9+]/','',$settings['phone']) }}">{{ $settings['phone'] }}</a>@endif @if($settings->get('instagram'))<a href="{{ $settings['instagram'] }}" target="_blank" rel="noopener noreferrer">{{ $settings['instagram_label'] }}</a>@endif @if($settings->get('email'))<a href="mailto:{{ $settings['email'] }}">{{ $settings['email'] }}</a>@endif</div></div></section>
@endif
@endsection
