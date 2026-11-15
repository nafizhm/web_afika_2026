<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>@yield('title','Dashboard') &mdash; Afika Residence</title>
<script>try{document.documentElement.dataset.theme=localStorage.getItem('afika.theme')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.dataset.sidebarHidden=localStorage.getItem('afika.sidebar-hidden')||'false'}catch(e){document.documentElement.dataset.theme='light'}</script>
@php($companyProfile = \App\Models\Setting::pluck('value','key'))
@if($companyProfile->get('favicon'))<link rel="icon" href="{{ asset('storage/'.$companyProfile['favicon']) }}">@endif
<link rel="stylesheet" href="{{ asset('css/site.css') }}"><link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.min.css') }}"><link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}?v={{ filemtime(public_path('css/admin-theme.css')) }}">
<script defer src="{{ asset('vendor/datatables/jquery.min.js') }}"></script><script defer src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script><script defer src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
</head>
<body class="admin-body @auth admin-app @else admin-login @endauth">
@auth
<div class="sidebar-backdrop" data-sidebar-close hidden></div>
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Menu utama">
<a class="sidebar-brand" href="{{ route('admin.index') }}"><span class="sidebar-logo">A<span>.</span></span><span><strong>AFIKA</strong><small>RESIDENCE</small></span></a>
<button class="sidebar-close" data-sidebar-close type="button" aria-label="Tutup menu"><x-admin-icon name="close" /></button>
<div class="sidebar-label">WORKSPACE</div>
<nav class="sidebar-menu">
@foreach([
['admin.index','Dashboard','grid'],
['admin.properties','Properti','home'],
['admin.faq.index','FAQ','faq'],
['admin.messages','Pesan WhatsApp','message'],
['admin.profile','Profil Perusahaan','company'],
['admin.content','Konten Website','content'],
['admin.ai-settings','Pengaturan AI','bot'],
['admin.account','Pengaturan Akun','settings']
] as [$name,$label,$icon])
@php($active = $name==='admin.faq.index' ? request()->routeIs('admin.faq.*') : ($name==='admin.properties' ? request()->routeIs('admin.properties','admin.property.*') : request()->routeIs($name)))
<a href="{{ route($name) }}" @if($active) aria-current="page" @endif><x-admin-icon :name="$icon" class="nav-icon" /><span>{{ $label }}</span>@if($active)<span class="nav-active-dot"></span>@endif</a>
@endforeach
</nav>
</aside>
<div class="admin-workspace">
<header class="admin-topbar">
<div class="topbar-left"><button class="sidebar-toggle topbar-icon-button" type="button" aria-controls="admin-sidebar" aria-expanded="true" aria-label="Sembunyikan menu sidebar" title="Tampilkan/sembunyikan sidebar"><x-admin-icon name="menu" /></button><span class="breadcrumb-home">Workspace</span><span class="breadcrumb-slash">/</span><strong>@yield('title','Dashboard')</strong></div>
<div class="topbar-right">
<button class="theme-toggle topbar-icon-button" type="button" aria-label="Aktifkan mode gelap" title="Ganti mode terang/gelap"><x-admin-icon name="moon" class="theme-moon" /><x-admin-icon name="sun" class="theme-sun" /></button>
<details class="profile-menu"><summary aria-label="Buka menu profil"><span class="user-avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><span class="topbar-user-name">{{ auth()->user()->name }}</span><x-admin-icon name="chevron" /></summary>
<div class="profile-dropdown"><div class="profile-dropdown-heading"><strong>{{ auth()->user()->name }}</strong><small>Administrator</small></div><a href="{{ route('admin.account') }}"><x-admin-icon name="user" />Pengaturan akun</a><a href="{{ route('home') }}" target="_blank" rel="noopener"><x-admin-icon name="external" />Buka website</a><form method="post" action="{{ route('admin.logout') }}" data-logout-form onsubmit="return confirm('Yakin ingin keluar dari akun?')">@csrf<button type="submit" class="profile-logout"><x-admin-icon name="logout" />Logout</button></form></div>
</details></div></header>
<dialog id="logout-confirm" class="logout-dialog" aria-labelledby="logout-title"><div class="logout-dialog-icon"><x-admin-icon name="logout" /></div><h2 id="logout-title">Keluar dari akun?</h2><p>Anda perlu login kembali untuk mengakses halaman admin.</p><div class="logout-dialog-actions"><button type="button" class="dialog-cancel" data-dialog-close>Batal</button><form method="post" action="{{ route('admin.logout') }}">@csrf<button type="submit" class="button">Ya, keluar</button></form></div></dialog>
<main class="container admin-main">
@else
<header class="login-header"><a class="brand" href="{{ route('home') }}"><span class="sidebar-logo">A.</span><strong>AFIKA RESIDENCE</strong></a><a class="text-link" href="{{ route('home') }}">Kembali ke website &rarr;</a></header>
<main class="container admin-main">
@endauth
@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert"><strong>Periksa kembali isian Anda.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
@auth<footer class="admin-footer"><span>&copy; {{ date('Y') }} Afika Residence</span><span>PT. Sahir Propertindo Niaga</span></footer></div>@endauth
</body></html>