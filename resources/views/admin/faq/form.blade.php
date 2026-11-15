@extends('layouts.admin')
@section('title', $faq->exists ? 'Edit FAQ' : 'Tambah FAQ')
@section('content')
<a class="text-link" href="{{ route('admin.faq.index') }}">← Kembali ke daftar FAQ</a>
<section class="panel faq-editor">
    <span class="eyebrow">PERTANYAAN & JAWABAN</span>
    <h1>{{ $faq->exists ? 'Edit FAQ' : 'Tambah FAQ' }}</h1>
    <p class="faq-form-intro">Atur jawaban dan pilih tempat FAQ digunakan.</p>
    <form class="faq-edit-form" method="post" action="{{ $faq->exists ? route('admin.faq.update', $faq) : route('admin.faq.store') }}">
        @csrf
        @if($faq->exists) @method('PUT') @endif
        <div class="faq-form-options">
        <label for="faq_type">Jenis FAQ<select id="faq_type" class="faq-type-select" name="faq_type">@foreach(['general'=>'Informasi umum','identity'=>'Nama asisten (jawaban: nama saja)','handoff'=>'Pengalihan ke admin'] as $key=>$label)<option value="{{ $key }}" @selected(old('faq_type',$faq->faq_type ?? 'general')===$key)>{{ $label }}</option>@endforeach</select></label>
        <label for="faq_audience">Penggunaan FAQ<select id="faq_audience" class="faq-type-select" name="audience">@foreach(['ai'=>'Khusus AI','web'=>'Khusus Web','both'=>'Web & AI'] as $key=>$label)<option value="{{ $key }}" @selected(old('audience',($faq->faq_type !== 'general' || !$faq->is_public) ? 'ai' : (($faq->is_ai ?? true) ? 'both' : 'web'))===$key)>{{ $label }}</option>@endforeach</select></label>
        </div>
        <p class="faq-field-help">Khusus Web tampil di website. Khusus AI hanya digunakan asisten. Web &amp; AI digunakan keduanya. Nama asisten dan pengalihan ke admin selalu khusus AI.</p>
        <label for="question">Pertanyaan<textarea id="question" name="question" rows="2" maxlength="500" required placeholder="Contoh: Bagaimana cara menjadwalkan kunjungan?" autofocus>{{ old('question', $faq->question) }}</textarea></label>
        <label for="answer">Jawaban<textarea id="answer" name="answer" rows="8" maxlength="20000" required placeholder="Tuliskan jawaban resmi perusahaan...">{{ old('answer', $faq->answer) }}</textarea></label>
        <div class="faq-form-bottom">
        <label class="faq-order" for="sort_order">Urutan<input id="sort_order" type="number" name="sort_order" min="0" max="999999" value="{{ old('sort_order', $faq->sort_order) }}" required></label>
        <input type="hidden" name="is_active" value="0">
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $faq->is_active))> FAQ aktif</label>
        </div>
        <div class="actions faq-form-actions"><button class="button" type="submit">Simpan FAQ</button><a class="text-link" href="{{ route('admin.faq.index') }}">Batal</a></div>
    </form>
</section>
@endsection

