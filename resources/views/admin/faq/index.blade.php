@extends('layouts.admin')
@section('title', 'FAQ')
@section('content')
<div class="section-head"><div><span class="eyebrow">KNOWLEDGE BASE</span><h1>Pertanyaan & jawaban</h1><p>Kelola jawaban Fika untuk pelanggan.</p></div><a class="button" href="{{ route('admin.faq.create') }}">+ Tambah FAQ</a></div>
<section class="panel"><div class="panel-heading"><div><h2>Daftar FAQ</h2><p>{{ $faqs->total() }} FAQ tersimpan.</p></div></div>
<div class="table-wrap"><table class="admin-table" data-table-kind="faqs" data-source="{{ route('admin.tables','faqs') }}" data-initial-search="{{ $search }}"><thead><tr><th>Urutan</th><th>Pertanyaan</th><th>Jawaban</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@foreach($faqs as $faq)<tr><td>{{ $faq->sort_order }}</td><td>{{ $faq->question }}</td><td><details class="table-detail"><summary>{{ \Illuminate\Support\Str::limit($faq->answer,110) }}</summary><p class="preserve">{{ $faq->answer }}</p></details></td><td>{{ $faq->is_active?'Aktif':'Nonaktif' }} ? {{ $faq->is_public && $faq->faq_type==='general'?'Website & AI':'Khusus AI' }}</td><td>@include('admin.partials.table-actions',['edit'=>route('admin.faq.edit',$faq),'delete'=>route('admin.faq.destroy',$faq),'label'=>'FAQ'])</td></tr>@endforeach
</tbody></table></div><noscript>{{ $faqs->links() }}</noscript></section>
@endsection