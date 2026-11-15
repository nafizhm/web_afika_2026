@extends('layouts.admin')
@section('title','Tampilan Website')
@section('content')
<div class="section-head"><div><h1>Tampilan website</h1><p>Atur teks, foto, navigasi, dan gaya website.</p></div><a class="button" href="{{ route('home') }}" target="_blank" rel="noopener">Lihat website ↗</a></div>
<nav class="appearance-tabs" aria-label="Bagian pengaturan">@foreach(config('website.groups') as $group=>$keys)<a href="#appearance-{{ $loop->index }}">{{ $group }}</a>@endforeach</nav>
<form method="post" action="{{ route('admin.appearance.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
@foreach(config('website.groups') as $group=>$keys)
<section class="panel" id="appearance-{{ $loop->index }}"><h2>{{ $group }}</h2><div class="form-grid">
@foreach($keys as $key)
@php($value=old($key,$settings->get($key)))
<label for="{{ $key }}">{{ config('website.labels.'.$key) }}
@if(str_starts_with($key,'show_'))
<select class="faq-type-select" id="{{ $key }}" name="{{ $key }}"><option value="1" @selected((bool)$value)>Tampilkan</option><option value="0" @selected(!(bool)$value)>Sembunyikan</option></select>
@elseif(in_array($key,['hero_image','gallery_image_1','gallery_image_2']))
<input id="{{ $key }}" name="{{ $key }}" type="url" value="{{ str_starts_with($value ?? '', 'storage/') ? '' : $value }}" placeholder="https://...">
<input type="file" name="{{ $key }}_upload" accept="image/png,image/jpeg,image/webp" aria-label="Upload {{ config('website.labels.'.$key) }}"><small>URL eksternal atau upload foto, maksimal 5 MB.</small>
@if($settings->get($key))<img class="website-image-preview" src="{{ preg_match('~^https?://~',$settings[$key]) ? $settings[$key] : asset($settings[$key]) }}" alt="Pratinjau {{ config('website.labels.'.$key) }}">@endif
@elseif($key==='theme_font')
<select class="faq-type-select" id="{{ $key }}" name="{{ $key }}">@foreach(['DM Sans','Manrope','Arial','Georgia'] as $font)<option @selected($value===$font)>{{ $font }}</option>@endforeach</select>
@elseif(str_starts_with($key,'theme_'))
<input id="{{ $key }}" name="{{ $key }}" type="color" value="{{ $value }}">
@elseif($key==='faq_limit')
<input id="{{ $key }}" name="{{ $key }}" type="number" min="0" max="100" value="{{ $value }}">
@elseif($key==='instagram')
<input id="{{ $key }}" name="{{ $key }}" type="url" value="{{ $value }}">
@else
<textarea id="{{ $key }}" name="{{ $key }}" rows="{{ in_array($key,['about','hero_description'])?4:2 }}" maxlength="5000">{{ $value }}</textarea>
@endif
</label>
@endforeach
</div></section>
@endforeach
<div class="appearance-save"><button class="button" type="submit">Simpan tampilan website</button></div>
</form>
@endsection
