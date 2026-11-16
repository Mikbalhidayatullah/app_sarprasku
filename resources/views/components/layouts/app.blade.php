@props(['title'=>'Sarprasku'])
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $title }} · Sarprasku</title>@vite(['resources/css/app.css','resources/js/app.js']) @livewireStyles</head>
<body>
<a class="skip-link" href="#main">Lewati ke konten</a>
<div class="app-shell" x-data="{ menu: false }">
    <div class="mobile-shade" x-show="menu" x-cloak @click="menu=false"></div>
    <aside class="sidebar" :class="{ 'is-open': menu }" aria-label="Navigasi utama">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">s<span>•</span></span><span>sarprasku<small>INVENTARIS SEKOLAH</small></span></a>
        <div class="school-chip"><span class="school-icon">⌂</span><div><strong>{{ \App\Models\School::query()->value('name') }}</strong><small>Satu sekolah, satu catatan</small></div></div>
        <span class="nav-caption">RUANG KERJA</span>
        <nav>
            @php($links=[['dashboard.view','dashboard','▦','Dashboard','dashboard'],['assets.view','assets.index','▤','Data Barang','assets.*'],['procurements.manage','procurements.index','＋','Pengadaan','procurements.*'],['assets.view','transfers.index','⇄','Perpindahan','transfers.*'],['maintenance.manage','maintenances.index','⚒','Pemeliharaan','maintenances.*'],['reports.view','reports.index','▥','Laporan','reports.*']])
            @foreach($links as [$permission,$route,$icon,$label,$pattern]) @can($permission)<a href="{{ route($route) }}" class="nav-item {{ request()->routeIs($pattern)?'active':'' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif><span class="nav-icon" aria-hidden="true">{{ $icon }}</span>{{ $label }}</a>@endcan @endforeach
            @can('masters.manage')<span class="nav-caption">ADMINISTRASI</span><a class="nav-item {{ request()->routeIs('masters.*')?'active':'' }}" href="{{ route('masters.index') }}"><span class="nav-icon" aria-hidden="true">▧</span>Master Data</a>@endcan
            @can('users.manage')<a class="nav-item {{ request()->routeIs('users.*','permissions.*')?'active':'' }}" href="{{ route('users.index') }}"><span class="nav-icon" aria-hidden="true">♧</span>Pengguna</a>@endcan
            @can('audit.view')<a class="nav-item {{ request()->routeIs('audit')?'active':'' }}" href="{{ route('audit') }}"><span class="nav-icon" aria-hidden="true">◷</span>Audit Aktivitas</a>@endcan
        </nav>
        <div class="sidebar-note"><span class="status-dot"></span> Pencatatan internal sekolah<small>Bukan pengganti sistem resmi BMN.</small></div>
        <div class="account"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role?->name }}</small></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" aria-label="Keluar dari aplikasi" title="Keluar">↗</button></form></div>
    </aside>
    <div class="workspace"><header class="topbar"><button type="button" class="menu-toggle" @click="menu=!menu" :aria-expanded="menu" aria-label="Buka navigasi">☰</button><div class="breadcrumb">Ruang kerja <span>/</span> <strong>{{ $title }}</strong></div><span class="today">{{ \App\Support\Display::date(now()) }}</span></header>
        <main id="main" class="main-content">
            @if(session('success'))<div class="alert success" role="status"><strong>✓</strong> {{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert error" role="alert" tabindex="-1" data-error-summary><strong>Data belum dapat disimpan.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            {{ $slot }}
        </main>
        <footer class="page-footer">Sarprasku <span>•</span> Inventaris yang tercatat, tanggung jawab yang jelas.<small>Zona waktu: {{ \App\Models\School::timezone() }}</small></footer>
    </div>
</div>@livewireScripts
</body></html>
