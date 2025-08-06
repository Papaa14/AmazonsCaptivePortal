{{-- resources/views/portal/otp.blade.php --}}
@extends('layouts.app')

@section('title', 'Amazons Network - Verify')

@section('content')
<div class="main-container">
    <div class="auth-card">
        <div class="logo-container">
            <img src="{{ asset('assets/logo.png') }}" alt="Amazons Network Logo">
        </div>
        <p class="text-center mb-4">Enter verification code sent via SMS to <br><b>{{ $phoneNumber }}</b></p>
        
        <form id="verifyForm" method="POST" action="{{ route('portal.otp.verify') }}">
            @csrf
            <div class="form-group">
                <input class="form-control @error('code') is-invalid @enderror" id="otp-input" name="code" required
                       maxlength="4" pattern="\d{4}" autocomplete="off" autofocus>
                @error('code')
                    <div class="invalid-feedback d-block text-center mt-2">{{ $message }}</div>
                @enderror
            </div>
            <button id="verifyButton" type="submit" class="btn btn-primary btn-lg btn-block mt-4">Verify</button>
        </form>

        <div id="countdown-container" class="mt-4 text-center">
            {{-- JS will handle this part --}}
        </div>
        
        <hr class="my-4">
        <div class="text-center">
            <p class="lead mb-1">Customer Service</p>
            <p class="lead">0702026544 | 0790882866</p>
        </div>
    </div>
</div>
@endsection

@section('footer-text', '© 2025 Lence Amazons LLC. All rights reserved.')

@push('scripts')
<script>
    // Countdown Timer logic from original file
    const countdownEl = document.getElementById('countdown-container');
    let timeLeft = 59;
    const updateTimer = () => {
        if (timeLeft <= 0) {
            clearInterval(timer);
            countdownEl.innerHTML = `<button id="resendCodeBtn">Resend code</button>`;
        } else {
            countdownEl.innerHTML = `<span>Resend code in: ${timeLeft} seconds</span>`;
        }
        timeLeft--;
    };
    const timer = setInterval(updateTimer, 1000);
    updateTimer(); // Initial call
</script>
@endpush