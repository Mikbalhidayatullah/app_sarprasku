@extends('auth.layout')
@section('title','Reset password')
@section('content')<h2>Lupa password?</h2><p class="muted">Kami akan mengirim tautan reset melalui email akun Anda.</p><form method="POST" action="{{ route('password.email') }}" class="stack">@csrf<x-field name="email" label="Email" type="email" required autocomplete="email"/><button class="btn btn-primary">Kirim tautan reset</button></form><a href="{{ route('login') }}">← Kembali masuk</a>@endsection
