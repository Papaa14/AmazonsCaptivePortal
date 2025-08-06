<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
        <meta name="theme-color" content="#4F46E5"/>
        <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Wi-Fi Login Portal</title>
        
        <!-- Load Tailwind CSS -->
        <script src="{{ asset('assets/js/tailwind.min.js') }}"></script>
        
        <!-- Fonts --> 
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <style> 
            body {
                font-family: 'Inter', sans-serif;
                background-image: linear-gradient(135deg, #4F46E5 0%, #7c3aed 100%);
                background-attachment: fixed;
            }
            .glass-card {
                backdrop-filter: blur(16px) saturate(180%);
                -webkit-backdrop-filter: blur(16px) saturate(180%);
                background-color: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 12px;
                box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
                transition: all 0.3s ease;
            }
            .glass-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.45);
            }
            .package-btn {
                transition: all 0.3s ease;
                transform: scale(1);
            }
            .package-btn:hover {
                transform: scale(1.05);
            }
            .package-btn:active {
                transform: scale(0.98);
            }
            .pulse-animation {
                animation: pulse 2s infinite;
            }
            @keyframes pulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.7);
                }
                70% {
                    box-shadow: 0 0 0 10px rgba(79, 70, 229, 0);
                }
                100% {
                    box-shadow: 0 0 0 0 rgba(79, 70, 229, 0);
                }
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
    <body>
        <div class="min-h-screen py-8 px-4">
            <!-- Header Logo Area -->
            <div class="container mx-auto max-w-md mb-6 fade-in" style="animation-delay: 0.3s">
                <div class="glass-card p-4 text-center mb-4">
                    <h1 class="text-2xl font-bold text-white">{{ strtoupper($company?->name) }}</h1>
                    {{-- <p class="text-white/80 text-sm">Wi-Fi Hotspot Portal</p> --}}
                {{-- </div>
                <div class="mt-4 flex justify-center"> --}}
                    <a href="tel:{{ $CustomerCare ?? '#' }}" class="pulse-animation w-full flex items-center justify-center rounded-lg 
                            bg-gradient-to-r from-purple-600 to-indigo-600 p-3 text-white font-bold 
                            hover:from-purple-500 hover:to-indigo-500 transition-all border border-white/10 shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        CALL CUSTOMER CARE
                    </a>
                </div>
            </div>
            
            <!-- Instructions Card -->
            {{-- <div class="container mx-auto max-w-md mb-6 fade-in" style="animation-delay: 0.1s">
                <div class="glass-card p-4">
                    <h2 class="text-xl font-semibold text-white mb-3">How To Connect</h2>
                    <div class="space-y-2 text-white/90">
                        <div class="flex items-start">
                            <div class="bg-indigo-600 rounded-full h-6 w-6 flex items-center justify-center mr-3 flex-shrink-0">
                                <span class="text-white text-sm font-bold">1</span>
                            </div>
                            <p>Choose your preferred package below</p>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-indigo-600 rounded-full h-6 w-6 flex items-center justify-center mr-3 flex-shrink-0">
                                <span class="text-white text-sm font-bold">2</span>
                            </div>
                            <p>Enter your phone number when prompted</p>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-indigo-600 rounded-full h-6 w-6 flex items-center justify-center mr-3 flex-shrink-0">
                                <span class="text-white text-sm font-bold">3</span>
                            </div>
                            <p>Click "PAY NOW" and follow the payment instructions</p>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-indigo-600 rounded-full h-6 w-6 flex items-center justify-center mr-3 flex-shrink-0">
                                <span class="text-white text-sm font-bold">4</span>
                            </div>
                            <p>Enter your M-Pesa PIN and wait for confirmation</p>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-center">
                        <a href="{{ $company->phone ?? '#' }}" class="inline-flex items-center rounded-lg bg-indigo-700 px-4 py-2 text-center text-sm font-medium text-white hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            CUSTOMER CARE
                        </a>
                    </div>
                </div>
            </div> --}}
            <!-- Voucher Section -->
            <div class="container mx-auto max-w-md mb-6 fade-in" style="animation-delay: 0.3s">
                <div class="glass-card p-4">
                    <div class="text-center mb-4">
                        <h2 class="text-xl font-semibold text-white">Have a Voucher?</h2>
                        <p class="text-white/80 text-sm">Redeem your voucher code here</p>
                    </div>
                    <div class="flex flex-col space-y-3">
                        <button id="redeem-voucher-btn" 
                                class="pulse-animation w-full flex items-center justify-center rounded-lg 
                                bg-gradient-to-r from-purple-600 to-indigo-600 p-3 text-white font-bold 
                                hover:from-purple-500 hover:to-indigo-500 transition-all border border-white/10 shadow-lg"
                                data-mac-address="{{ session('hotspot_login.mac') }}"
                                data-nas-ip="{{ $nas_ip }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                            Redeem Voucher
                        </button>

                        <button id="add-device-btn" 
                                class="pulse-animation w-full flex items-center justify-center rounded-lg 
                                bg-gradient-to-r from-purple-600 to-indigo-600 p-3 text-white font-bold 
                                hover:from-purple-500 hover:to-indigo-500 transition-all border border-white/10 shadow-lg"
                                data-mac-address="{{ session('hotspot_login.mac') }}"
                                data-nas-ip="{{ $nas_ip }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Device
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Packages Section -->
            <div id="packages" class="container mx-auto max-w-md mb-6 fade-in" style="animation-delay: 0.2s">
                <div class="glass-card p-4">
                    <div class="text-center mb-4">
                        <h2 class="text-xl font-semibold text-white">Choose Your Package</h2>
                        <p class="text-white/80 text-sm">Select from our available plans below</p>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($packages as $package)
                            <button class="package-btn bg-gradient-to-br from-indigo-600 to-indigo-700 shadow-lg text-white 
                                flex flex-col justify-between items-center rounded-xl py-3 px-2 h-auto relative
                                border border-indigo-500/30 hover:from-indigo-500 hover:to-indigo-600"
                                data-package-id="{{ $package->id }}"
                                data-package-name="{{ $package->name_plan }}"
                                data-package-price="{{ $package->price }}"
                                data-mac-address="{{ session('hotspot_login.mac') }}"
                                data-cookie="{{ $cookieValue }}"
                                data-nas-ip="{{ $nas_ip }}">
                                <div class="absolute -top-2 left-0 right-0 mx-auto w-max px-2 py-1 text-xs bg-white text-indigo-700 font-bold rounded-full">
                                    @if ($package->shared_users <= 1)
                                        1 Device
                                    @elseif ($package->shared_users)
                                        {{ $package->shared_users }} Devices
                                    @endif
                                </div>
                                <div class="mt-3 text-center">
                                    <span class="font-semibold text-sm block leading-tight mb-1">
                                        {{ $package->name_plan }}
                                    </span>
                                    <span class="text-lg font-bold block">
                                        Ksh {{ (int) $package->price }}
                                    </span>
                                    <span class="text-xs text-white/80 block mt-1">
                                        {{ $package->validity }} {{ $package->validity_unit }}
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                    
                    <div class="mt-4 text-center">
                        <span class="text-white/80 text-xs flex items-center justify-center">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Please wait until redirected after payment
                        </span>
                    </div>
                </div>
            </div>
            
            
            
            <input hidden type="text" id="amount">
            <input hidden type="text" id="mac" value="{{ session('hotspot_login.mac') }}">
            
            <!-- Login Form (Hidden) -->
            <form name="sendin" id="login" method="post" action="{{ session('hotspot_login')['loginLink'] ?? '' }}" onSubmit="return doLogin();">
                <input name="dst" type="hidden" value="https://www.google.com" />
                <input name="popup" type="hidden" value="false" />
                <input name="username" type="hidden" value="{{ session('hotspot_login.mac') }}"/>
                <input name="password" type="hidden" value="{{ session('hotspot_login.mac') }}"/>
            </form>
            
            <!-- Footer -->
            <div class="container mx-auto max-w-md mt-10 mb-4 text-center">
                <p class="text-white/70 text-xs">© {{ date('Y') }} {{ $company?->name }}. All rights reserved.</p>
            </div>
        </div>

        <!-- Scripts -->
        <script src="{{ asset('js/jquery.min.js') }}"></script>
        <script src="{{ asset('assets/js/plugins/sweetalert2.all.min.js') }}"></script>
        <script src="{{ asset('js/md5.js') }}"></script>

        <script>
            function doLogin() {
                document.sendin.username.value = document.login.username.value;
                document.sendin.password.value = hexMD5('\011\373\054\364\002\233\266\263\270\373\173\323\234\313\365\337\356');
                document.sendin.submit();
                return false;
            }
            function setCookie(name, value, days) {
                var expires = "";
                if (days) {
                    const date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + (value || "") + expires + "; path=/";
            }
            function getCookie(name) {
                var nameEQ = name + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                }
                return null;
            }

            function checkPaid() {
                let cookieValue = getCookie('unique_cookie');
                const checkPaidRoute = "{{ route('checkPaid') }}";
                const csrfToken = "{{ csrf_token() }}";
                const nasIp = "{{ $nas_ip }}";
                const macAddress = "{{ session('hotspot_login.mac') }}";
                $.ajax({
                    url: checkPaidRoute,
                    method: "POST",
                    data: {
                        _token: csrfToken,
                        nas_ip: nasIp,
                        cookie: cookieValue,
                        mac_address: macAddress
                    }
                })
                .done(function(response) {
                    if (response.code === '0') {
                        console.log("No Active account. Exiting function.");
                        return;
                    } else if (response.code === '1') {
                        Swal.fire({
                            imageUrl: "Loading.gif",
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            focusConfirm: false,
                            title: 'USER ACCOUNT ACTIVE',
                            text: 'Please Wait... Connecting you to the internet...',
                            timer: 2000
                        }).then(() => {
                            var frm = document.getElementById("login");
                            frm.submit();
                        });
                    } else if (response.code === '2') {
                        Swal.fire({
                            imageUrl: "Loading.gif",
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            focusConfirm: false,
                            title: 'USER ACCOUNT ACTIVE',
                            text: 'Please Wait... Processing Your New MacAddress...',
                            timer: 2000
                        }).then(() => {
                            var frm = document.getElementById("login");
                            frm.submit();
                        });
                    }
                })
                .fail(function(error) {
                    console.error("AJAX Error:", error);
                    setTimeout(checkPaid, 3000);
                });
            }
            function doLogin3(username, password) {
                var loginOnly = "{{ session('hotspot_login')['loginLink'] ?? '' }}";
                var linkOrig = "https://www.google.com";
                document.sendin.action = loginOnly;
                document.sendin.username.value = username;
                document.sendin.dst.value = linkOrig;
                var psw = password;
                document.sendin.password.value = psw;
                document.sendin.submit();
                return false;
            }

            window.onload = function() {
                let cookie = getCookie('unique_cookie');
                if (cookie === null) {
                    let defaultCookieValue = "{{ $cookieValue }}";
                    setCookie("unique_cookie", defaultCookieValue, 365);
                    cookieValue = defaultCookieValue;
                }
                checkPaid();
            };

            $(document).ready(function() {
                let checkoutRequestID = null;
                let pollingInterval = null;

                var msg = "You are about to pay KSH: ${amount}. Enter phonenumber below and click PAY NOW";
                const regexp = /\${([^{]+)}/g;
                let result = msg.replace(regexp, function (ignore, key) {
                    return eval(key);
                });

                $('.package-btn').click(function() {
                    let packageID = $(this).data('package-id');
                    let nasIp = $(this).data('nas-ip');
                    let macAddress = $(this).data('mac-address');
                    let cookieValue = getCookie('unique_cookie');
                    Swal.fire({
                        title: "Enter Mpesa Number",
                        html: `
                            <div class="mb-4">
                                <div class="bg-indigo-50 rounded-lg p-4 mb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium text-gray-600">Package:</span>
                                        <span class="text-sm font-bold text-indigo-700">${$(this).data('package-name')}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-600">Amount:</span>
                                        <span class="text-lg font-bold text-indigo-700">Ksh ${$(this).data('package-price')}</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1 text-left">Enter M-Pesa Number</label>
                                    <input id="swal-input-phone" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter Mpesa Number">
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: "Pay Now",
                        confirmButtonColor: '#4F46E5',
                        customClass: {
                            container: 'my-swal',
                            popup: 'rounded-lg',
                            title: 'text-xl font-bold text-gray-800',
                            confirmButton: 'bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md',
                            cancelButton: 'bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md'
                        },
                        preConfirm: () => {
                            const phoneNumber = document.getElementById('swal-input-phone').value;
                            if (!phoneNumber) {
                                Swal.showValidationMessage('Please enter your phone number');
                                return false;
                            }
                            if (!phoneNumber.match(/^07\d{8}$/) && !phoneNumber.match(/^01\d{8}$/)) {
                                Swal.showValidationMessage('Phone number must be in format 07XXXXXXXX or 01XXXXXXXX');
                                return false;
                            }
                            return phoneNumber;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            processCustomer(nasIp, packageID, result.value, macAddress, cookieValue);
                        }
                    });
                });

                function processCustomer(nasIp, packageID, phoneNumber, macAddress, cookieValue) {
                    Swal.fire({
                        title: "Processing Payment...",
                        html: "Please wait while we initiate STK Push...",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('processCustomer') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            nas_ip: nasIp,
                            package_id: packageID,
                            phone_number: phoneNumber,
                            mac_address: macAddress,
                            cookie: cookieValue
                        },
                        success: function(response) {
                            if (response.success) {
                                checkoutRequestID = response.checkoutRequestID;
                                cID = response.cID;
                                Swal.fire({
                                    title: "STK Push Sent!",
                                    html: `
                                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                            <img src="/assets/Loading.gif" width="150" alt="Verifying Payment...">
                                            <br>
                                            <p>Enter M-Pesa PIN on your phone.</p>
                                        </div>
                                    `,
                                    showConfirmButton: false,
                                    allowOutsideClick: false
                                });
                                startPolling(nasIp, packageID, phoneNumber, macAddress);
                            } else {
                                Swal.fire("Error", response.message || "Failed to initiate payment", "error");
                            }
                        },

                        error: function() {
                            Swal.fire("Error", "Something went wrong. Try again.", "error");
                        }
                    });
                }

                function startPolling(nasIp, packageID, phoneNumber, macAddress) {
                    let attemptCount = 0; 
                    const maxAttempts = 10;
                    pollingInterval = setInterval(function() {
                        attemptCount++;
                        if (attemptCount <= maxAttempts) {
                            checkPaymentStatus(nasIp, packageID, phoneNumber, macAddress);
                        } else {
                            clearInterval(pollingInterval);
                            console.log('Maximum polling attempts reached.');
                        }
                    }, 5000);
                }

                function checkPaymentStatus(nasIp, packageID, phoneNumber, macAddress) {
                    $.ajax({
                        url: "{{ route('processQueryMpesa') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            ref: checkoutRequestID,
                            cID: cID,
                            nas_ip: nasIp,
                            package_id: packageID,
                            phone_number: phoneNumber,
                            mac_address: macAddress
                        },

                        success: function(response) {
                            console.log("M-Pesa Response:", response);
                            if (response.success && response.ResultCode === "0") {
                                clearInterval(pollingInterval);
                                Swal.fire({
                                    title: "Payment Successful!",
                                    text: "You are now connected.",
                                    icon: "success",
                                    showConfirmButton: false,
                                    allowOutsideClick: false,
                                    timer: 2000
                                }).then(() => {
                                    var frm = document.getElementById("login");
                                    frm.submit();
                                });
                            } else if (response.ResultCode === "1") {
                                clearInterval(pollingInterval);
                                Swal.fire("Payment Failed", response.ResultDesc, "error");
                            } else if (response.ResultCode === "1032") {
                                clearInterval(pollingInterval);
                                Swal.fire("Payment Canceled", response.ResultDesc, "error");
                            }
                        },
                        error: function() {
                            clearInterval(pollingInterval);
                            Swal.fire("Error", response.ResultDesc, "Could not verify payment. Try again.", "error");
                        }
                    });
                }

                // Smooth scroll to packages section when clicking on link
                $('a[href="#packages"]').on('click', function(e) {
                    e.preventDefault();
                    const packagesSection = document.getElementById('packages');
                    packagesSection.scrollIntoView({ behavior: 'smooth' });
                });
            });

            //Redeem Vouchers From Captive Portal
            $(document).ready(function() {
                $('#redeem-voucher-btn').on('click', function(e) {
                    e.preventDefault();
                    
                    var macAddress = $(this).data("mac-address");
                    var nasIp = $(this).data("nas-ip");
                    let cookieValue = getCookie('unique_cookie');
                    
                    Swal.fire({
                        title: 'Redeem Voucher',
                        html: `
                            <div class="mb-4">
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1 text-left">Voucher Code</label>
                                    <input id="swal-input-voucher" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter voucher code">
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1 text-left">Phone Number</label>
                                    <input id="swal-input-phone" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter phone number">
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Redeem Now',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#4F46E5',
                        customClass: {
                            container: 'my-swal',
                            popup: 'rounded-lg',
                            title: 'text-xl font-bold text-gray-800',
                            confirmButton: 'bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md',
                            cancelButton: 'bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md'
                        },
                        preConfirm: () => {
                            const voucherCode = document.getElementById('swal-input-voucher').value;
                            const phoneNumber = document.getElementById('swal-input-phone').value;
                            if (!voucherCode || !phoneNumber) {
                                Swal.showValidationMessage('Please enter both voucher code and phone number');
                                return false;
                            }
                            return { voucherCode: voucherCode, phoneNumber: phoneNumber };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading modal
                            Swal.fire({
                                title: 'Processing...',
                                html: `
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                        <img src="/assets/Loading.gif" width="150" alt="Verifying Payment...">
                                        <br>
                                    </div>
                                `,
                                allowOutsideClick: false,
                                showConfirmButton: false
                            });
                            
                            // Make AJAX call to redeem the voucher
                            $.ajax({
                                url: '{{ route("reedemVoucher") }}',
                                type: 'POST',
                                data: {
                                    code: result.value.voucherCode,
                                    phone_number: result.value.phoneNumber,
                                    mac_address: macAddress,
                                    nas_ip: nasIp,
                                    _token: '{{ csrf_token() }}',
                                    cookie: cookieValue
                                },
                                success: function(response) {
                                    // Check if backend response indicates success
                                    if (response.success) {
                                        Swal.fire({
                                            title: 'Success!',
                                            text: response.message,
                                            icon: 'success',
                                            showConfirmButton: false,
                                            allowOutsideClick: false,
                                            timer: 2000
                                        }).then(() => {
                                            var frm = document.getElementById("login");
                                            frm.submit();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: response.message,
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                    console.log("Voucher redeemed:", response);
                                },
                                error: function(xhr, status, error) {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Failed to redeem voucher. Please try again.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                    console.error("Error redeeming voucher:", error);
                                }
                            });
                        }
                    });
                });
            });

            //Add Device From Captive Portal
            $(document).ready(function() {
                $('#add-device-btn').on('click', function(e) {
                    e.preventDefault();
                    
                    var macAddress = $(this).data("mac-address");
                    var nasIp = $(this).data("nas-ip");
                    let cookieValue = getCookie('unique_cookie');
                    
                    Swal.fire({
                        title: 'Add Device',
                        html: `
                            <div class="mb-4">
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1 text-left">Mpesa/Voucher Code</label>
                                    <input id="swal-input-mpesa" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter Mpesa/voucher code">
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1 text-left">Device Mac Address</label>
                                    <input value="${macAddress}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" readonly>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Add Device',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#4F46E5',
                        customClass: {
                            container: 'my-swal',
                            popup: 'rounded-lg',
                            title: 'text-xl font-bold text-gray-800',
                            confirmButton: 'bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md',
                            cancelButton: 'bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md'
                        },
                        preConfirm: () => {
                            const mpesaCode = document.getElementById('swal-input-mpesa').value;
                            if (!mpesaCode) {
                                Swal.showValidationMessage('Please enter both Mpesa code');
                                return false;
                            }
                            return { mpesaCode: mpesaCode };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading modal
                            Swal.fire({
                                title: 'Processing...',
                                html: `
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                        <img src="/assets/Loading.gif" width="150" alt="Verifying Payment...">
                                        <br>
                                    </div>
                                `,
                                allowOutsideClick: false,
                                showConfirmButton: false
                            });
                            
                            // Make AJAX call to add extra device
                            $.ajax({
                                url: '{{ route("addDevice") }}',
                                type: 'POST',
                                data: {
                                    code: result.value.mpesaCode,
                                    mac_address: macAddress,
                                    nas_ip: nasIp,
                                    _token: '{{ csrf_token() }}',
                                    cookie: cookieValue
                                },
                                success: function(response) {
                                    // Check if backend response indicates success
                                    if (response.success) {
                                        Swal.fire({
                                            title: 'Success!',
                                            text: response.message,
                                            icon: 'success',
                                            showConfirmButton: false,
                                            allowOutsideClick: false,
                                            timer: 2000
                                        }).then(() => {
                                            var frm = document.getElementById("login");
                                            frm.submit();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: response.message,
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                    console.log("Device Added:", response);
                                },
                                error: function(xhr, status, error) {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Failed to add extra device. Please try again.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                    console.error("Error adding extra device:", error);
                                }
                            });
                        }
                    });
                });
            });
        </script>
    </body>
</html>