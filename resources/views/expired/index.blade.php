<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">
        <title>Account Expired</title>
        <script src="{{ asset('assets/js/tailwind.min.js') }}"></script>
        
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <style>
            body {
                font-family: 'Inter', sans-serif;
                background-color: #f9fafb;
            }
            .gradient-header {
                background: linear-gradient(90deg, #4338ca, #6366f1);
            }
            .expired-badge {
                animation: pulse 2s infinite;
            }
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.7; }
                100% { opacity: 1; }
            }
            .card-hover {
                transition: all 0.3s ease;
            }
            .card-hover:hover {
                transform: translateY(-5px);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            }
            .fade-in {
                animation: fadeIn 0.5s ease-in-out;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>
    </head>
    <body class="bg-gray-50 min-h-screen">
        <!-- Header -->
        <header class="gradient-header text-white shadow-lg">
            <div class="container mx-auto px-4 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold">{{ strtoupper($company?->name) }}</h1>
                    </div>
                </div>
            </div>
        </header>

        @if(!$customer)
        <!-- No Customer Information Found -->
        <section class="w-full max-w-3xl mx-auto px-4 py-12">
            <div class="mb-12 rounded-lg bg-white p-8 shadow-lg max-w-7xl mx-auto text-center fade-in">
                <div class="mb-6">
                    <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Account Information Not Found</h2>
                <p class="mb-6 text-gray-600">We couldn't find your account information. Please contact our support team for assistance.</p>
                
                <div class="mt-12 text-center" id="contact">
                    <p class="mb-4 text-gray-600">Need help? We've got you covered.</p>
                    <a href="tel:{{ $company->phone ?? '#' }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-6 py-3 font-medium text-white transition duration-300 hover:bg-indigo-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        Call Support
                    </a>
                </div>
            </div>
        </section>
        @else
        <!-- Expired Alert Banner -->
        <div class="bg-gradient-to-r from-red-600 to-red-500 text-white shadow-lg border-b border-red-700 fade-in">
            <div class="container mx-auto px-4 py-4">
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <div class="flex items-center mb-3 md:mb-0">
                        <div class="flex-shrink-0 bg-red-700 p-1.5 rounded-full mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold">Your Internet Plan Has Expired</h2>
                            <p class="text-sm text-white/80">Access to the internet is currently restricted</p>
                        </div>
                    </div>
                    <button class="renew-btn w-full md:w-auto rounded-md bg-white px-6 py-2.5 font-bold text-red-600 transition duration-300 hover:bg-red-50 shadow-md"
                        data-package-id="{{ $cpackage->id }}"
                        data-package-price="{{ $current_package_price }}"
                        data-account="{{ $customer->account }}"
                        data-amount="{{ $current_package_price - $customer->balance }}"
                        data-nas-ip="{{ $nas->nasname }}">
                        <span class="flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Renew Subscription
                        </span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Customer Information Section -->
        <section class="container mx-auto px-4 py-6 fade-in" style="animation-delay: 0.1s">
            <div class="max-w-3xl mx-auto mb-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Account Information
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Customer Details -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Customer Details
                                </h3>
                                
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-600">Full Name</span>
                                        <span class="font-medium text-gray-900">{{ $customer->fullname }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-600">Account Number</span>
                                        <span class="font-medium text-gray-900">{{ $customer->account }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-600">Package</span>
                                        <span class="font-medium text-indigo-600">{{ $customer->package }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-600">Account Status</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 expired-badge">
                                            Expired
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-600">Wallet Balance</span>
                                        <span class="font-medium text-gray-900">Ksh. {{ $customer->balance }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-600">Expiry Date</span>
                                        <span class="font-medium text-red-600">{{ $customer->expiry }}</span>
                                    </div>
                                    @if(isset($dataUsage))
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-600">Data Usage</span>
                                        <span class="font-medium text-gray-900">
                                            @php
                                                $download = $dataUsage->download;
                                                $upload = $dataUsage->upload;
                                            @endphp
                                            @if($upload >= 1073741824 || $download >= 1073741824)
                                                {{ number_format($download / 1073741824, 2) }}GB / {{ number_format($upload / 1073741824, 2) }}GB
                                            @else
                                                {{ number_format($download / 1048576, 2) }}MB / {{ number_format($upload / 1048576, 2) }}MB
                                            @endif
                                        </span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Current Plan -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    Current Plan
                                </h3>
                                
                                <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-lg p-5">
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-lg font-bold text-red-800">{{ $customer->package }} Plan</h4>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-200 text-red-800">
                                            EXPIRED
                                        </span>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <span class="text-3xl font-bold text-gray-800">Ksh. {{ $current_package_price }}</span>
                                        <span class="text-gray-600 text-sm">/{{ $cpackage->validity }} {{ $cpackage->validity_unit }}</span>
                                    </div>
                                    
                                    <div class="mb-4 bg-white bg-opacity-60 p-3 rounded-lg">
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600">Plan Cost</span>
                                            <span class="font-medium">Ksh. {{ $current_package_price }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-600">Wallet Balance</span>
                                            <span class="font-medium">- Ksh. {{ $customer->balance }}</span>
                                        </div>
                                        <div class="border-t border-gray-200 my-2"></div>
                                        <div class="flex justify-between font-medium">
                                            <span class="text-gray-800">Amount Due Today</span>
                                            <span class="text-red-600">Ksh. {{ $current_package_price - $customer->balance }}</span>
                                        </div>
                                    </div>
                                    
                                    <button class="package-btn w-full rounded-lg bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 px-4 py-3 text-sm font-medium text-white transition duration-300 shadow-md flex items-center justify-center"
                                        data-package-id="{{ $cpackage->id }}"
                                        data-package-price="{{ $current_package_price }}"
                                        data-account="{{ $customer->account }}"
                                        data-amount="{{ $current_package_price - $customer->balance }}"
                                        data-nas-ip="{{ $nas->nasname }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                        Pay Now & Restore Connection
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="container mx-auto px-4 py-6 fade-in" style="animation-delay: 0.2s">
            <div class="max-w-3xl mx-auto mb-8">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Upgrade Packages
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">Consider upgrading to a higher package for better features</p>
                    </div>
                    
                    <div class="p-6">
                        @if(count($packages->filter(function($item) use ($current_package_price) { return $item->price > $current_package_price; })) > 0)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                @foreach($packages as $package)
                                    @if($package->price > $current_package_price)
                                        <div class="card-hover bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                                            <div class="p-5 bg-gradient-to-r from-indigo-50 to-blue-50">
                                                <h3 class="text-lg font-bold text-gray-800">{{ $package->name_plan }}</h3>
                                                <div class="mt-2">
                                                    <span class="text-2xl font-bold text-indigo-600">Ksh. {{ $package->price }}</span>
                                                    <span class="text-gray-600 text-sm">/{{ $package->validity }} {{ $package->validity_unit }}</span>
                                                </div>
                                            </div>
                                            
                                            <div class="p-5">
                                                <ul class="space-y-3 mb-5">
                                                    <li class="flex items-start">
                                                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                        <div>
                                                            <span class="text-sm font-medium text-gray-700">Speed:</span>
                                                            <p class="text-sm text-gray-600">{{ $package->bandwidth->rate_down }}{{ $package->bandwidth->rate_down_unit }}/{{ $package->bandwidth->rate_up }}{{ $package->bandwidth->rate_up_unit }}</p>
                                                        </div>
                                                    </li>
                                                    <li class="flex items-start">
                                                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                        <div>
                                                            <span class="text-sm font-medium text-gray-700">Data:</span>
                                                            <p class="text-sm text-gray-600">
                                                                @if($package->fup_limit_status)
                                                                    {{ $package->fup_limit }} {{ $package->fup_unit }} / Daily
                                                                @else
                                                                    Unlimited Usage
                                                                @endif
                                                            </p>
                                                        </div>
                                                    </li>
                                                </ul>
                                                
                                                <button type="button" 
                                                    class="upgrade-btn w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition"
                                                    data-package-id="{{ $package->id }}"
                                                    data-package-price="{{ $package->price }}"
                                                    data-account="{{ $customer->account }}"
                                                    data-amount="{{ $package->price - $customer->balance }}"
                                                    data-nas-ip="{{ $nas->nasname }}">
                                                    Upgrade Now
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No Upgrade Packages Available</h3>
                                <p class="text-gray-500">You're already on our highest package tier. Contact support for custom options.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Contact Support Section -->
        <section class="container mx-auto px-4 py-6 mb-20 fade-in" style="animation-delay: 0.3s" id="contact">
            <div class="max-w-3xl mx-auto">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg overflow-hidden text-white">
                    <div class="p-8 md:p-10 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-5 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <h2 class="text-2xl font-bold mb-3">Need Help or Custom Package?</h2>
                        <p class="text-white/80 mb-6 max-w-lg mx-auto">
                            Our team is ready to assist you with any questions about your account, 
                            package options, or technical issues. Contact us today!
                        </p>
                        <a href="tel:{{ $company->phone ?? '#' }}" class="inline-flex items-center rounded-lg bg-white px-6 py-3 font-medium text-indigo-700 transition duration-300 hover:bg-gray-100 shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            Call Support
                        </a>
                    </div>
                </div>
            </div>
        </section>
        @endif

        <!-- Footer -->
        <footer class="bg-gray-800 text-white py-4 w-full fixed bottom-0">
            <div class="container mx-auto px-4">
                <div class="text-center text-gray-400 text-sm">
                    <p>&copy; {{ date('Y') }} {{ $company?->name }}. All rights reserved.</p>
                </div>
            </div>
        </footer>
        <script src="{{ asset('js/jquery.min.js') }}"></script>
        <script src="{{ asset('assets/js/plugins/sweetalert2.all.min.js') }}"></script>
        <script>
            $(document).ready(function() {
                // Smooth scroll for anchor links
                $('a[href^="#"]').on('click', function(e) {
                    e.preventDefault();
                    const target = $(this.getAttribute('href'));
                    if(target.length) {
                        $('html, body').stop().animate({
                            scrollTop: target.offset().top - 100
                        }, 500);
                    }
                });
                
                // Package button click handler
                $('.package-btn, .upgrade-btn, .renew-btn').on('click', function() {
                    const packageId = $(this).data('package-id');
                    const accountNumber = $(this).data('account');
                    const amount = $(this).data('amount');
                    const nasIp = $(this).data('nas-ip');
                    
                    Swal.fire({
                        title: 'Renew Subscription',
                        html: `
                            <div class="mb-4">
                                <p class="mb-2 text-gray-600">Amount Due: <strong>Ksh ${amount}</strong></p>
                                <p class="text-sm text-gray-500">Enter your phone number to proceed with payment</p>
                            </div>
                        `,
                        input: 'tel',
                        inputPlaceholder: 'Enter Phone Number',
                        confirmButtonText: 'Pay Now',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel',
                        showLoaderOnConfirm: true,
                        inputValidator: (value) => {
                            if (!value) {
                                return 'Please enter your phone number';
                            }
                            value = value.replace(/[^0-9]/g, '');
                            if (value.startsWith('07')) {
                                value = '254' + value.substring(1);
                            } else if (value.startsWith('01')) {
                                value = '254' + value.substring(1);
                            }
                            
                            if (!/^(254)[0-9]{9}$/.test(value)) {
                                return 'Please enter a valid phone number starting with 254, 07, or 01';
                            }
                            // Update the input value with the standardized format
                            Swal.getInput().value = value;
                        },
                        preConfirm: (phoneNumber) => {
                            return $.ajax({
                                url: "{{ route('renewPackage') }}",
                                method: "POST",
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    phone_number: phoneNumber,
                                    amount: amount,
                                    package_id: packageId,
                                    account: accountNumber,
                                    nas_ip: nasIp
                                }
                            })
                            .then(response => {
                                if (response.success) {
                                    return response;
                                } else {
                                    throw new Error(response.message || 'Payment initiation failed');
                                }
                            })
                            .catch(error => {
                                Swal.showValidationMessage(`Request failed: ${error.message || 'Unknown error'}`);
                            });
                        },
                        allowOutsideClick: () => !Swal.isLoading()
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Payment Initiated',
                                html: `
                                    <p class="mb-4">Please check your phone and enter your M-PESA PIN to complete the transaction.</p>
                                    <p class="text-sm text-gray-600">Your connection will be restored automatically once payment is confirmed.</p>
                                `,
                                showConfirmButton: false,
                                timer: 5000,
                                timerProgressBar: true
                            }).then(() => {
                                Swal.fire({
                                    title: 'Checking Payment Status',
                                    text: 'Please wait while we verify your payment...',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                        // Create a loop to check payment status
                                        let checkCount = 0;
                                        const maxChecks = 10; 
                                        function checkStatus() {
                                            $.ajax({
                                                url: "{{ route('QueryMpesa') }}",
                                                method: "POST",
                                                data: {
                                                    _token: "{{ csrf_token() }}",
                                                    ref: result.value.checkoutRequestID,
                                                    nas_ip: nasIp,
                                                    package_id: packageId,
                                                    phone_number: result.value.phoneNumber,
                                                    account: accountNumber,
                                                    cID: result.value.cID,
                                                    amount: amount
                                                }
                                            })
                                            .done(function(response) {
                                                console.log("QueryMpesa response:", response); 
                                                if (response.success && response.status === "confirmed") {
                                                    // clearInterval(pollingInterval);
                                                    Swal.fire({
                                                        title: "Payment Successful!",
                                                        text: "Your subscription has been updated.",
                                                        icon: "success",
                                                        showConfirmButton: false,
                                                        allowOutsideClick: false,
                                                        timer: 2000
                                                    }).then(() => {
                                                        window.location.href = 'googlechrome://navigate?url=' + encodeURIComponent(window.location.href);
                                                    });
                                                } else if (response.status === "failed") {
                                                    // clearInterval(pollingInterval);
                                                    Swal.fire("Payment Failed", "error");
                                                } else if (response.status === "cancelled") {
                                                    // clearInterval(pollingInterval);
                                                    Swal.fire("Payment Canceled", "error");
                                                } else if (response.status === "insufficient_funds") {
                                                    // clearInterval(pollingInterval);
                                                    Swal.fire("Insufficient Funds", "error");
                                                }

                                                // if (response.ResultCode === "0") {
                                                //     Swal.fire({
                                                //         icon: 'success',
                                                //         title: 'Payment Successful!',
                                                //         text: 'Your account has been renewed. Internet access will be restored shortly.',
                                                //         confirmButtonText: 'Great!'
                                                //     }).then(() => {
                                                //         window.location.href = 'googlechrome://navigate?url=' + encodeURIComponent(window.location.href);
                                                //     });
                                                // } else if (response.ResultCode === "1") {
                                                //     Swal.fire("Payment Failed", response.ResultDesc, "error");
                                                // } else if (response.ResultCode === "1032") {
                                                //     Swal.fire("Payment Canceled", response.ResultDesc, "error");
                                                // } else {
                                                //     checkCount++;
                                                //     if (checkCount < maxChecks) {
                                                //         setTimeout(checkStatus, 5000); // Retry in 5 seconds
                                                //     } else {
                                                //         Swal.fire({
                                                //             icon: 'info',
                                                //             title: 'Payment Pending',
                                                //             text: 'We haven\'t received your payment confirmation yet. If you completed the payment, your account will be updated shortly.',
                                                //             confirmButtonText: 'OK'
                                                //         });
                                                //     }
                                                // }
                                            })
                                            .fail(function() {
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Error',
                                                    text: 'There was a problem checking your payment status. Please contact support if you already paid.',
                                                    confirmButtonText: 'OK'
                                                });
                                            });
                                        }
                                        setTimeout(checkStatus, 10000); // First check after 10 seconds
                                    }
                                });
                            });
                        }
                    });
                });
            });
        </script>
    </body>
</html>
 