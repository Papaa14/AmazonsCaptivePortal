{{-- resources/views/portal/offers.blade.php --}}
@extends('amazons.layouts.app')

@section('title', 'AMAZONS - Offers')

@push('styles')
<style>
    /* Additional styles specific to this page to match the screenshot */
    .portal-card { box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e9ecef; }
    .balance-section { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; text-align: center; padding: 1rem 0; border-top: 1px solid #f1f1f1; border-bottom: 1px solid #f1f1f1; }
    #refreshBtn { cursor: pointer; transition: transform 0.5s ease; color: #888; }
    #refreshBtn.rotating { transform: rotate(360deg); }
    .owl-carousel .item img { border-radius: var(--border-radius); }
    .active-sub-card { background-color: #f0f5f9; border: 1px solid #dee2e6; border-radius: var(--border-radius); }
    .active-sub-card strong { word-break: break-word; }
    .reconnect-btn { background-color: #28a745; color: white; border-radius: 50px; font-weight: 600; padding: 0.4rem 1rem; font-size: 0.9rem; border: none; flex-shrink: 0; }
    .reconnect-btn:hover { background-color: #218838; color: white; }
    #packagesBox h4 { font-weight: 600; }
    .plan-card { transition: all 0.2s ease; }
    .plan-card:hover { border-color: var(--primary-color); box-shadow: 0 2px 5px rgba(0,0,0,0.08); }
    .modal-body .form-control { border: 1px solid #ced4da; padding: 0.75rem; }
    .paybill-instructions { background-color: #f8f9fa; padding: 1rem; border-radius: var(--border-radius); }
    .bg-light-success { background-color: #d1e7dd !important; }
    .border-success { border-color: #198754 !important; }
</style>
@endpush


@section('content')
<div class="container py-3">
    {{-- This div holds all the data passed from the controller for JS to access --}}
    <div id="page-data"
         data-user-credit="{{ $user['credit'] }}"
         data-user-phone="{{ $user['phone'] }}"
         data-plans='@json($plans)'
         data-buy-url="{{ route('portal.offers.buy') }}"
         data-voucher-url="{{ route('portal.offers.voucher') }}">
    </div>

    <div class="logo-container">
        <img src="{{ asset('assets/amazons/assets/logo.png') }}" alt="Amazons Network Logo">
    </div>

    <div class="portal-card mx-auto p-0">
        <div class="p-3">
            <p class="lead mb-0">Welcome back, <span id="userName">{{ $user['name'] }}</span> 👋</p>
        </div>

        <div class="balance-section">
            <div> Credit(Ksh)<br> <strong id="creditBalanceText" class="fs-4 text-success">{{ $user['credit'] }}</strong></div>
            <div id="refreshBtn" title="Refresh">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/></svg>
            </div>
            <div> Net Points<br> <strong class="fs-4">{{ $user['points'] }}</strong></div>
        </div>

        <div class="row g-0">
            <!-- Left Column -->
            <div class="col-lg-6 p-3 border-end-lg">
                <!-- Slider -->
                <div class="owl-carousel owl-theme" id="imageSlider">
                    @foreach($sliderImages as $img)
                        <div class="item"><img src="{{ $img }}" alt="Offer"></div>
                    @endforeach
                </div>
                <!-- Active Subscriptions -->
                <div class="mt-4" id="activeSubscriptionsBox">
                     <h6>Your active subscriptions</h6>
                     @forelse($activeSubscriptions as $sub)
                     <div class="card active-sub-card my-2">
                         <div class="card-body p-3">
                             <div><strong class="d-block">{{ $sub['voucher'] }} • {{ $sub['name'] }}</strong><small class="text-muted d-block">{{ $sub['usage'] }}</small></div>
                             <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">Expires: {{ $sub['expires'] }}</small>
                                <button class="btn reconnect-btn">RECONNECT</button>
                             </div>
                         </div>
                     </div>
                     @empty
                        <p class="text-muted text-center mt-3">No active subscriptions found.</p>
                     @endforelse
                </div>
            </div>
            <!-- Right Column -->
            <div class="col-lg-6 p-3">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="Enter Voucher Code">
                    <button class="btn btn-outline-danger" id="voucherConnectBtn">CONNECT</button>
                </div>
                <div id="packagesBox">
                    <h4>Offers <span class="badge bg-danger rounded-pill">New!</span></h4>
                    <!-- Free Offer -->
                    <!-- <div class="card plan-card my-2 border-success">
                        <div class="card-body p-3 bg-light-success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="m-0 text-success"><strong>🎁 Daily FREE 20 Minutes UnlimiNET</strong></h6>
                                    <small class="text-success">FREE (Available from 6AM to 8AM daily)</small>
                                </div>
                                <span class="badge bg-success rounded-pill">FREE!</span>
                            </div>
                        </div>
                   </div> -->
                    <!-- Paid Offers -->
                     @foreach($plans as $plan)
                         <div class="card plan-card my-2">
                             <div class="card-body p-3">
                                 <div class="d-flex justify-content-between align-items-center">
                                     <div>
                                         <h6 class="m-0"><b>{{ $plan['name'] }}</b></h6>
                                         <small>{{ $plan['devices'] }} Device(s)</small>
                                     </div>
                                     <button data-package-id="{{ $plan['id'] }}" class="btn btn-sm btn-outline-danger subscribePlanBtn rounded-pill px-3">Connect</button>
                                 </div>
                             </div>
                         </div>
                     @endforeach
                </div>
            </div>
        </div>

        <div class="py-3 border-top text-center">
            <p class="lead mb-1">Customer Service</p>
            <p class="lead fw-bold">0702026544</p>
            <a href="#" class="btn btn-success btn-sm rounded-pill px-3 py-2">Join our WhatsApp Group</a>
        </div>
    </div>
</div>

<!-- Credit Purchase Confirmation Modal -->
<div class="modal fade" id="confirmPurchaseModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header border-0"><h5 class="modal-title">Confirm</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="confirmPurchaseModalBody"></div><div class="modal-footer border-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="confirmPurchaseBtn">Continue</button></div></div></div></div>
<!-- M-PESA Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header border-0"><h5 class="modal-title">Pay via <img src="{{ asset('assets/mpesa-logo.png') }}" height="60" alt="M-PESA"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body pt-0" id="paymentModalBody"></div></div></div></div>

<!-- Welcome Back Modal -->
<div class="modal fade" id="welcomeBackModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 text-center">
                <h4 class="modal-title w-100 text-success">🎉 Welcome Back!</h4>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-wifi text-success" viewBox="0 0 16 16">
                        <path d="M15.384 6.115a.485.485 0 0 0-.047-.736A12.444 12.444 0 0 0 8 3C5.259 3 2.723 3.882.663 5.379a.485.485 0 0 0-.048.736.518.518 0 0 0 .668.05A11.448 11.448 0 0 1 8 4c2.507 0 4.827.802 6.716 2.164.205.148.49.13.668-.049z"/>
                        <path d="M13.229 8.271a.482.482 0 0 0-.063-.745A9.455 9.455 0 0 0 8 6c-1.905 0-3.68.56-5.166 1.526a.48.48 0 0 0-.063.745.525.525 0 0 0 .652.065A8.46 8.46 0 0 1 8 7a8.46 8.46 0 0 1 4.576 1.336c.206.132.48.108.653-.065zm-2.183 2.183c.226-.226.185-.605-.1-.75A6.473 6.473 0 0 0 8 9c-1.06 0-2.062.254-2.946.704-.285.145-.326.524-.1.75l.015.015c.16.16.407.19.611.09A5.478 5.478 0 0 1 8 10c.868 0 1.69.201 2.42.56.203.1.45.07.61-.091l.016-.015zM9.06 12.44c.196-.196.198-.52-.04-.66A1.99 1.99 0 0 0 8 11.5a1.99 1.99 0 0 0-1.02.28c-.238.14-.236.464-.04.66l.706.706a.5.5 0 0 0 .708 0l.707-.707z"/>
                    </svg>
                </div>
                <p class="lead mb-3">We had a brief service disruption, but we're back and better than ever!</p>
                <div class="alert alert-info">
                    <h6 class="alert-heading">🎁 Special Welcome Gift</h6>
                    <p class="mb-0">Enjoy our <strong>FREE 24 Hours UnlimiNET</strong> package to feel at home again!</p>
                </div>
                <!-- <p class="text-muted small">Available Today</p> -->
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">Awesome, Let's Go! 🚀</button>
            </div>
        </div>
    </div>
</div>

@endsection