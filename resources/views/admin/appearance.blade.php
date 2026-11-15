@extends('layouts.admin')
@section('title','Tampilan Website')
@section('content')
<div class="appearance-editor">
<link rel="stylesheet" href="{{ asset('css/appearance.css') }}?v={{ is_file(public_path('css/appearance.css')) ? filemtime(public_path('css/appearance.css')) : 1 }}">
<script defer src="{{ asset('js/appearance.js') }}?v={{ is_file(public_path('js/appearance.js')) ? filemtime(public_path('js/appearance.js')) : 1 }}"></script>
<div class="section-head appearance-heading"><div><h1>Tampilan website</h1><p>Atur teks, foto, navigasi, dan gaya website.</p></div><a class="button" href="{{ route('home') }}" target="_blank" rel="noopener">Lihat website ↗</a></div>
<div class="appearance-layout"><nav class="appearance-tabs" aria-label="Bagian pengaturan">@foreach(config('website.groups') as $group=>$keys)<a id="appearance-tab-{{ $loop->index }}" href="#appearance-{{ $loop->index }}"><span class="appearance-step">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><span>{{ $group }}</span><span class="appearance-nav-arrow" aria-hidden="true">?</span></a>@endforeach</nav>
<form class="appearance-form" method="post" action="{{ route('admin.appearance.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
@foreach(config('website.groups') as $group=>$keys)
<section class="panel" id="appearance-{{ $loop->index }}"><div class="appearance-panel-heading"><span class="eyebrow">PENGATURAN WEBSITE / {{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><h2>{{ $group }}</h2></div><div class="form-grid">
@foreach($keys as $key)
@php($value=old($key,$settings->get($key)))
<label class="appearance-field {{ in_array($key,['hero_image','gallery_image_1','gallery_image_2'])?'appearance-photo-field':'' }} {{ in_array($key,['about','meta_description','hero_description','gallery_note','homes_description','location_description','faq_description','contact_description'])?'appearance-wide':'' }}" for="{{ $key }}">{{ config('website.labels.'.$key) }}
@if(str_starts_with($key,'show_'))
<select class="faq-type-select" id="{{ $key }}" name="{{ $key }}"><option value="1" @selected((bool)$value)>Tampilkan</option><option value="0" @selected(!(bool)$value)>Sembunyikan</option></select>
@elseif(in_array($key,['hero_image','gallery_image_1','gallery_image_2']))
<input id="{{ $key }}" name="{{ $key }}" type="url" value="{{ str_starts_with($value ?? '', 'storage/') ? '' : $value }}" placeholder="https://...">
<span class="appearance-upload-label">Atau upload foto</span><input type="file" data-image-upload="{{ $key }}" name="{{ $key }}_upload" accept="image/png,image/jpeg,image/webp" aria-label="Upload {{ config('website.labels.'.$key) }}"><small>URL eksternal atau upload foto, maksimal 5 MB.</small>
@if($settings->get($key))<img data-preview="{{ $key }}" class="website-image-preview" src="{{ preg_match('~^https?://~',$settings[$key]) ? $settings[$key] : asset($settings[$key]) }}" alt="Pratinjau {{ config('website.labels.'.$key) }}">@endif
@elseif($key==='theme_font')
<select class="faq-type-select" id="{{ $key }}" name="{{ $key }}">@foreach(['DM Sans','Manrope','Arial','Georgia'] as $font)<option @selected($value===$font)>{{ $font }}</option>@endforeach</select>
@elseif(str_starts_with($key,'theme_'))
<span class="appearance-color"><input id="{{ $key }}" name="{{ $key }}" type="color" value="{{ $value }}"><span data-color-label="{{ $key }}">{{ $value }}</span></span>
@elseif($key==='faq_limit')
<input id="{{ $key }}" name="{{ $key }}" type="number" min="0" max="100" value="{{ $value }}">
@elseif($key==='instagram')
<input id="{{ $key }}" name="{{ $key }}" type="url" value="{{ $value }}">
@elseif(!in_array($key,['about','meta_description','hero_description','gallery_note','homes_description','location_description','faq_description','contact_description','wa_message','wa_survey_message','wa_property_message']) && !str_ends_with($key,'_title'))
<input id="{{ $key }}" name="{{ $key }}" type="text" value="{{ $value }}" maxlength="5000">
@else
<textarea id="{{ $key }}" name="{{ $key }}" rows="{{ in_array($key,['about','hero_description'])?4:2 }}" maxlength="5000">{{ $value }}</textarea>
@endif
@error($key)<small class="appearance-field-error" data-field-error="{{ $key }}">{{ $message }}</small>@enderror
@error($key.'_upload')<small class="appearance-field-error" data-field-error="{{ $key }}">{{ $message }}</small>@enderror
</label>
@endforeach
</div></section>
@endforeach
<div class="appearance-save"><span data-save-status aria-live="polite">Perubahan berlaku setelah disimpan.</span><button class="button" type="submit">Simpan tampilan website</button></div>
</form></div></div>
@endsection
