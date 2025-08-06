{{-- resources/views/portal/connected.blade.php --}}
@extends('amazons.layouts.app')

@section('title', 'AMAZONS - Connected')

@section('content')
<div class="container py-3">
    <div class="logo-container">
     <img src="{{ asset('assets/amazons/assets/logo.png') }}" alt="Amazons Network Logo">

    </div>
    <div class="portal-card mx-auto">
        <div class="card-body py-4 connected-container">
            {{-- We will load the SVG via JS to keep the Blade file clean --}}
            <div id="connected-svg-container" style="max-width: 250px; margin: 0 auto 2rem auto;"></div>
            <h1 class="text-success mt-3">You're now Connected!</h1>
            <h3 class="mb-5 mt-4 text-muted">Thanks for choosing Amazons Network</h3>
        </div>
        <div class="pt-3 border-top text-center">
            <p class="lead mb-1">Customer Service: 0702026544</p>
            <a href="#" class="btn btn-success btn-sm">Join our WhatsApp Group</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Redirect back to offers page after a few seconds
    setTimeout(() => {
        window.location.href = '{{ route("amazons.portal.offers") }}';
    }, 8000);
</script>
@endpush