@extends('amazons.layouts.app')

@section('title', 'Amazons Network - Login')

@section('content')
<div class="main-container">
    <div class="auth-card">
        <div class="logo-container">
           <img src="{{ asset('assets/amazons/assets/logo.png') }}" alt="Amazons Network Logo">
            <p class="tagline">Fast • Reliable • Affordable</p>
        </div>
        <h4 class="text-center mb-4">Sign in here</h4>
        <form id="loginForm" method="POST" action="{{ route('portal.login.handle') }}">
            @csrf
            <div class="mb-3">
                <input type="tel" class="form-control form-control-lg @error('username') is-invalid @enderror"
                       placeholder="Enter your phone number" id="phoneNumber" name="username" value="{{ old('username') }}" required>
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block mt-4">Connect</button>
        </form>
        <hr class="my-4">
        <div class="text-center">
            <p class="lead mb-1">Customer Service</p>
            <p class="lead">0702026544 | 0790882866</p>
        </div>
    </div>
</div>
@endsection

@section('footer-text', '© 2025 Lence Amazons LLC. All rights reserved.')