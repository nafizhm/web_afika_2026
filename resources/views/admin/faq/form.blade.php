@extends('layouts.admin')
@section('title', $faq->exists ? 'Edit FAQ' : 'Tambah FAQ')
@section('content')
<a class="text-link" href="{{ route('admin.faq.index') }}">← Kembali ke daftar FAQ</a>
<section class="panel">
    <span class="eyebrow">PERTANYAAN & JAWABAN</span>
    <h1>{{ $faq->exists ? 'Edit FAQ' : 'Tambah FAQ' }}</h1>
    <form method="post" action="{{ $faq->exists ? route('admin.faq.update', $faq) : route('admin.faq.store') }}">
        @csrf
        @if($faq->exists) @method('PUT') @endif
        <label for="question">Pertanyaan<textarea id="question" name="question" rows="2" maxlength="500" required placeholder="Contoh: Bagaimana cara menjadwalkan kunjungan?" autofocus>{{ old('question', $faq->question) }}</textarea></label>
        <label for="answer">Jawaban<textarea id="answer" name="answer" rows="8" maxlength="20000" required placeholder="Tuliskan jawaban resmi perusahaan...">{{ old('answer', $faq->answer) }}</textarea></label>
        <label class="faq-order" for="sort_order">Urutan<input id="sort_order" type="number" name="sort_order" min="0" max="999999" value="{{ old('sort_order', $faq->sort_order) }}" required></label>
        <input type="hidden" name="is_active" value="0">
        <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $faq->is_active))> FAQ aktif</label>
        <div class="actions"><button class="button" type="submit">Simpan FAQ</button><a class="text-link" href="{{ route('admin.faq.index') }}">Batal</a></div>
    </form>
</section>
@endsection

