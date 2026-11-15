@extends('layouts.admin')
@section('title','Pesan WhatsApp')
@section('content')
<div class="section-head"><div><span class="eyebrow">CUSTOMER INBOX</span><h1>Pesan WhatsApp</h1><p>Lihat pesan masuk pelanggan dan cari riwayat percakapan yang tersimpan.</p></div><span class="page-label"><i></i> Waktu Indonesia Barat</span></div>
<section class="panel"><div class="panel-heading"><div><h2>Daftar pesan masuk</h2><p>Pesan teks dan caption ditampilkan. Lampiran media tersimpan sebagai metadata.</p></div></div>
<div class="table-wrap"><table class="admin-table" data-table-kind="messages" data-source="{{ route('admin.tables','messages') }}"><thead><tr><th>Pengirim</th><th>Nomor / JID</th><th>Pesan</th><th>Jenis</th><th>Waktu masuk</th></tr></thead><tbody>
@foreach($messages as $message)<tr><td>{{ $message->sender_name?:'Tanpa nama' }}</td><td>{{ $message->sender_phone?:$message->sender_jid }}</td><td><details class="table-detail"><summary>{{ \Illuminate\Support\Str::limit($message->body??'[Pesan nonteks]',110) }}</summary><p class="preserve">{{ $message->body??'[Pesan nonteks]' }}</p></details></td><td>{{ $message->message_type }}</td><td>{{ ($message->sent_at??$message->created_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB</td></tr>@endforeach
</tbody></table></div><noscript>{{ $messages->links() }}</noscript></section>
@endsection