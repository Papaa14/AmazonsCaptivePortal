@extends('layouts/layoutMaster')
@php
    $dir = asset(Storage::url('uploads/plan'));
    $currentUser = \Auth::user();
    $isCompany = $currentUser->type == 'company';
@endphp
@section('page-title')
    {{ $isCompany ? __('Active License') : __('Manage Plan') }}
@endsection
@push('css-page')
<style>
    .price-card .list-unstyled .theme-avtar {
        width: 20px;
        margin-right: 5px !important;
    }

    .request-btn .btn {
        padding: 8px 12px !important;
    }

    .license-info {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .license-info .info-item {
        margin-bottom: 10px;
        color: #fff;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .modal {
        display: none;
    }

    .modal.fade.show {
        display: block !important;
    }

    .modal-backdrop {
        z-index: 1040;
    }

    .modal {
        z-index: 1050;
    }

    .modal-dialog {
        margin: 1.75rem auto;
        max-width: 500px;
    }

    .modal-dialog-xl {
        max-width: 800px;
    }

    .modal-content {
        position: relative;
        background-color: #fff;
        border: 1px solid rgba(0, 0, 0, 0.2);
        border-radius: 0.3rem;
        outline: 0;
    }

    .modal-header {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
    }

    .modal-body {
        padding: 1rem;
        max-height: 70vh;
        overflow-y: auto;
    }

    .modal-footer {
        padding: 1rem;
        border-top: 1px solid #dee2e6;
    }

    .plan-modal .plan-card {
        margin-bottom: 20px;
    }

    @media screen and (max-width:991px) {
        .plan_card {
            width: 50%;
        }
    }
    @media screen and (max-width:767px) {
        .plan_card {
            width: 100%;
        }
        .plan_card .price-card {
            height: auto;
            margin-bottom: 0;
        }
    }
    @media screen and (max-width:481px) {
        .plan_card .card-body .row .col-6 {
            width: 100%;
        }
        .plan_card .card-body .row .col-6:not(:first-of-type) .list-unstyled {
            margin: 0 0 20px !important;
        }
        .plan_card .card-body .row .col-6:first-of-type .list-unstyled {
            margin: 20px 0 7px !important;
        }
        .plan_card .price-card {
            max-height: unset;
        }
    }
</style> 
@endpush

@section('content')
    <div class="row">
    {{-- COMPANY VIEW --}}
    @if($isCompany && isset($currentPlan))
        {{-- Company License Info --}}
        <div class="col-12 mb-6 row">
            <div class="col-3">
                <div class="card border border-2 border-primary rounded">
                    <div class="card-header border-primary">
                        <h4 class="card-title">{{ __('Current License Information') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="license-info">
                            <div class="info-item">
                                <strong>{{ __('Plan Name') }}:</strong> {{ $currentPlan->name }}
                            </div>
                            <div class="info-item">
                                <strong>{{ __('Subscription Type') }}:</strong> {{ __(\App\Models\Plan::$arrDuration[$currentPlan->duration]) }}
                            </div>
                            <div class="info-item">
                                <strong>{{ __('Maximum Customers') }}:</strong> 
                                {{ $currentPlan->max_customers == -1 ? __('Unlimited') : $currentPlan->max_customers }}
                            </div>
                            <div class="info-item">
                                <strong>{{ __('Extra Customers') }}:</strong> 
                                {{ $currentUser->extra_customers}}
                            </div>
                            <div class="info-item">
                                <strong>{{ __('Plan Expires On') }}:</strong> 
                                {{ !empty($currentUser->plan_expire_date) ? $currentUser->dateFormat($currentUser->plan_expire_date) : __('Lifetime') }}
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button type="button" class="btn btn-light" id="renewPlan">
                                {{-- <i class="ti ti-refresh"></i>  --}}
                                {{ __('Renew Plan') }}
                            </button>
                            @if($currentPlan->max_customers != -1)
                                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addExtraClientsModal">
                                    {{-- <i class="ti ti-users"></i>  --}}
                                    {{ __('Add Extra Clients') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-9">
                <div class="card">
                    <div class="card-header mb-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between">
                        <div class="card-title">
                            <h5 class="mb-0">
                                Recent Payments
                            </h5>
                        </div>
                        <div class="mt-3 mt-sm-0">
                            <h5 class="mb-0">
                                Company ID : {{ $currentUser->company_id }}
                            </h5>
                        </div>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Order Id') }}</th>
                                        <th>{{ __('Plan Name') }}</th>
                                        <th>{{ __('Price') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Mpesa Code') }}</th>
                                        <th>{{ __('Date') }}</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($companyOrders as $key => $order)
                                        <tr>
                                            <td>{{ $order->order_id }}</td>
                                            <td>{{ $order->plan_name }}</td>
                                            <td>{{ isset($admin_payment_setting['currency_symbol']) ? $admin_payment_setting['currency_symbol'] : '$' }}{{ number_format($order->price) }}
                                            </td>

                                            <td>
                                                @if ($order->payment_status == 'success' || $order->payment_status == 'Approved')
                                                    <span
                                                        class="status_badge badge bg-label-success p-2 px-3 rounded">{{ ucfirst($order->payment_status) }}</span>
                                                @elseif($order->payment_status == 'succeeded')
                                                    <span
                                                        class="status_badge badge bg-label-success p-2 px-3 rounded">{{ __('Success') }}</span>
                                                @elseif($order->payment_status == 'Pending')
                                                    <span
                                                        class="status_badge badge bg-label-warning p-2 px-3 rounded">{{ __('Pending') }}</span>
                                                @else
                                                    <span
                                                        class="status_badge badge bg-label-danger p-2 px-3 rounded">{{ ucfirst($order->payment_status) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $order->receipt }}</td>

                                            <td>{{ $order->created_at->format('d M Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            {{ $companyOrders->onEachSide(2)->links('vendor.pagination.rounded') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h4 class="card-title text-white">{{ __('Our Plans') }}</h4>
        @foreach($plans as $plan)
            @include('plan.plan-card', ['plan' => $plan])
        @endforeach

        <!-- Extra Clients Modal -->
        <div class="modal fade" id="addExtraClientsModal" tabindex="-1" aria-labelledby="addExtraClientsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addExtraClientsModalLabel">{{ __('Add Extra Clients') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="extraClientsForm">
                            @if($isCompany && isset($currentPlan) && $currentPlan->duration != 'Lifetime' && !empty($currentUser->plan_expire_date))
                                @if (now()->gt($currentUser->plan_expire_date))
                                    <div class="alert alert-warning">
                                        {{ __('Note: Your current plan has expired. The renewal cost will be included in the total.') }}
                                    </div>
                                @endif
                            @endif
                            <div class="mb-3">
                                <p>{{ __('Current Plan') }}: <strong>{{ $currentPlan->name }}</strong></p>
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="extraClients" class="form-label">{{ __('Number of Additional Clients') }}</label>
                                        <input type="number" class="form-control" id="extraClients" min="1" value="1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-primary w-100" id="calculateCost">{{ __('Calculate') }}</button>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <p>{{ __('Cost per additional client per day') }}: 
                                    <strong id="dailyCost"></strong>
                                </p>
                                <p>{{ __('Remaining days in your plan') }}: 
                                    <strong id="remainingDays"></strong> {{ __('days') }}
                                </p>
                                <p class="h5">{{ __('Total Cost for Additional Clients') }}: 
                                    <strong id="totalCostDisplay"></strong>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label for="mpesaPhoneExtra" class="form-label">{{ __('Mpesa Phone Number') }}</label>
                                <input type="text" class="form-control" id="mpesaPhoneExtra" placeholder="0700000000" required>
                                <div class="form-text">{{ __('Enter phone number in format: 07XXXXXXXX') }}</div>
                            </div>
                            <input type="hidden" id="currentPlanId" value="{{ $currentPlan->id }}">
                            <input type="hidden" id="totalCost" name="amount">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="button" class="btn btn-primary" id="proceedPayment">{{ __('Proceed to Payment') }}</button>
                    </div>
                </div>
            </div>
        </div>

    {{-- SUPER ADMIN VIEW --}}
    @elseif(\Auth::user()->type === 'super admin')
        <div class="col-12">
            <div class="float-end mb-3">
                @can('create plan')
                    <a href="#" data-size="lg" data-url="{{ route('plans.create') }}" data-ajax-popup="true"
                        data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create New Plan') }}"
                        class="btn btn-sm btn-primary">
                        <i class="ti ti-plus"></i> {{ __('Create New Plan') }}
                    </a>
                @endcan
            </div>
        </div>

        {{-- Display all plans --}}
        @foreach($plans as $plan)
            @include('plan.plan-card', ['plan' => $plan])
        @endforeach

    @endif
</div>

@endsection

@push('script-page')
    <script>
        function showModal(modalId) {
            var modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();
        }

        @if(isset($currentPlan) && $currentPlan->duration != 'Lifetime' && !empty($currentUser->plan_expire_date))
            $(document).ready(function() {
                let maxCustomers = parseInt('{{ $currentPlan->max_customers }}');
                let price = parseFloat('{{ $currentPlan->price }}');
                let costPerClient = (maxCustomers <= 0) ? 0 : price / maxCustomers;
                let daysInMonth = 30;

                var planExpireDate = '{{ $currentUser->plan_expire_date }}';

                function calculateRemainingDays() {
                    var today = new Date();
                    var expireDate = new Date(planExpireDate);
                    var timeDiff = expireDate - today;
                    return Math.ceil(timeDiff / (1000 * 3600 * 24));
                }

                function calculateProratedCost() {
                    var numClients = parseInt($('#extraClients').val()) || 0;
                    var remainingDays = calculateRemainingDays();

                    var dailyCost = costPerClient / daysInMonth;
                    var totalCost = 0;

                    if (remainingDays <= 0) {
                        // Plan expired — charge full monthly cost per client
                        totalCost = numClients * costPerClient;
                        remainingDays = 0; // optional: show 0 instead of negative days
                    } else {
                        // Prorated cost
                        totalCost = numClients * dailyCost * remainingDays;
                    }

                    var symbol = '{{ $admin_payment_setting['currency_symbol'] ?? 'Ksh' }}';
                    $('#remainingDays').text(remainingDays);
                    $('#dailyCost').text(symbol + Math.round(dailyCost));
                    $('#totalCostDisplay').text(symbol + Math.round(totalCost));
                    $('#totalCost').val(Math.round(totalCost));
                }

                $('#calculateCost').click(function() {
                    calculateProratedCost();
                });

                $('#addExtraClientsModal').on('shown.bs.modal', function() {
                    calculateProratedCost();
                });

                $('#proceedPayment').click(function() {
                    var phone = $('#mpesaPhoneExtra').val();
                    var numClients = $('#extraClients').val();
                    var amount = $('#totalCost').val();

                    if (!phone || !numClients) {
                        show_toastr('error', '{{ __('Please fill all required fields') }}');
                        return;
                    }

                    $.ajax({
                        url: '{{ route('plan.extra.clients.mpesa') }}',
                        type: 'POST',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            "phone": phone,
                            "num_clients": numClients,
                            "amount": amount,
                            "plan_id": $('#currentPlanId').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                checkoutRequestID = response.checkoutRequestID;
                                cID = response.cID;
                                txn_id = response.txn_id;
                                extras = response.extras;

                                Swal.fire({
                                    title: "STK Push Sent!",
                                    html: `
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <img src="/assets/Loading.gif" width="150" alt="Verifying Payment...">
                                            <br>
                                            <p>Enter M-Pesa PIN on your phone.</p>
                                        </div>
                                    `,
                                    showConfirmButton: false,
                                    allowOutsideClick: false
                                });

                                startPolling(cID, checkoutRequestID, txn_id, extras);
                            } else {
                                Swal.fire("Error", response.message || "Failed to initiate payment", "error");
                            }
                        },
                        error: function() {
                            Swal.fire("Error", "Something went wrong. Try again.", "error");
                        }
                    });
                    function startPolling(cID, checkoutRequestID, txn_id, extras) {
                        let attemptCount = 0;
                        const maxAttempts = 10;
                        pollingInterval = setInterval(function() {
                            attemptCount++;
                            if (attemptCount <= maxAttempts) {
                                checkPaymentStatus(cID, checkoutRequestID, txn_id, extras);
                            } else {
                                clearInterval(pollingInterval);
                                Swal.fire({
                                    icon: "warning",
                                    title: "Unable to Verify Payment",
                                    text: "We couldn't confirm your payment. Please check your M-Pesa or try again.",
                                    confirmButtonColor: '#0d6efd'
                                });
                            }
                        }, 5000);
                    }
                    function checkPaymentStatus(cID, checkoutRequestID, txn_id, extras) {
                        $.ajax({
                            url: "{{ route('plan.renew.verify') }}",
                            method: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                ref: checkoutRequestID,
                                cID: cID,
                                txn_id: txn_id
                                extras: extras
                            },
                            success: function(response) {
                                if (response.success && response.status === "confirmed") {
                                    clearInterval(pollingInterval);
                                    Swal.fire({
                                        title: "Payment Successful!",
                                        text: "Your subscription has been updated.",
                                        icon: "success",
                                        showConfirmButton: false,
                                        allowOutsideClick: false,
                                        timer: 2000
                                    });
                                } else if (response.status === "failed") {
                                    clearInterval(pollingInterval);
                                    Swal.fire("Payment Failed", "error");
                                } else if (response.status === "cancelled") {
                                    clearInterval(pollingInterval);
                                    Swal.fire("Payment Canceled", "error");
                                } else if (response.status === "insufficient_funds") {
                                    clearInterval(pollingInterval);
                                    Swal.fire("Insufficient Funds", "error");
                                }
                            },
                            error: function() {
                                clearInterval(pollingInterval);
                                Swal.fire("Error", "Could not verify payment. Try again.", "error");
                            }
                        });
                    }              

                });
            });
            @endif

    </script>
    @if($isCompany && isset($currentPlan) && $currentPlan->duration != 'Lifetime' && !empty($currentUser->plan_expire_date))
        <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
        <script src="{{ asset('assets/js/sweetalert2.all.min.js') }}"></script>
        <script>
            const currencySymbol = @json($admin_payment_setting['currency_symbol'] ?? 'Ksh');
            const amount = {{ $currentPlan->price }};
            const pName =  '{{ $currentPlan->name }}';

            $(document).ready(function() {
                let checkoutRequestID = null;
                let pollingInterval = null;

                var msg = "You are about to pay KSH: ${amount}. Enter phonenumber below and click PAY NOW";
                const regexp = /\${([^{]+)}/g;
                let result = msg.replace(regexp, function (ignore, key) {
                    return eval(key);
                });

                $('#renewPlan').click(function() {
                    let planName = {{ $currentPlan->id }};
                    let cID = {{ $currentUser->id }};

                    Swal.fire({
                        title: "Enter Mpesa Number",
                        html: `
                            <div class="mb-4">
                                <div class="alert alert-primary p-3 mb-4 rounded">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-semibold text-muted">Package:</span>
                                        <span class="fw-bold text-primary"> ${pName} Plan</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-semibold text-muted">Amount:</span>
                                        <span class="fw-bold fs-5 text-primary">${currencySymbol} ${amount}</span>
                                    </div>
                                </div>
                                <div class="mb-3 text-start">
                                    <label class="form-label">Enter M-Pesa Number</label>
                                    <input id="swal-input-phone" class="form-control" placeholder="Enter Mpesa Number">
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: "Pay Now",
                        confirmButtonColor: '#0d6efd',
                        customClass: {
                            container: 'my-swal',
                            popup: 'rounded',
                            title: 'fs-4 fw-bold text-dark',
                            confirmButton: 'btn btn-primary',
                            cancelButton: 'btn btn-secondary'
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
                            processCustomer(planName, amount, cID, result.value);
                        }
                    });
                });

                function processCustomer(planName, amount, cID, phoneNumber) {
                    Swal.fire({
                        title: "Processing Payment...",
                        html: "Please wait while we initiate STK Push...",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('plan.renew.mpesa') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            plan : planName,
                            amount: amount,
                            phone: phoneNumber,
                            cID: cID
                        },
                        success: function(response) {
                            if (response.success) {
                                checkoutRequestID = response.checkoutRequestID;
                                cID = response.cID;
                                txn_id = response.txn_id;

                                Swal.fire({
                                    title: "STK Push Sent!",
                                    html: `
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <img src="/assets/Loading.gif" width="150" alt="Verifying Payment...">
                                            <br>
                                            <p>Enter M-Pesa PIN on your phone.</p>
                                        </div>
                                    `,
                                    showConfirmButton: false,
                                    allowOutsideClick: false
                                });

                                startPolling(cID, checkoutRequestID, txn_id);
                            } else {
                                Swal.fire("Error", response.message || "Failed to initiate payment", "error");
                            }
                        },
                        error: function() {
                            Swal.fire("Error", "Something went wrong. Try again.", "error");
                        }
                    });
                }

                function startPolling(cID, checkoutRequestID, txn_id) {
                    let attemptCount = 0;
                    const maxAttempts = 10;
                    pollingInterval = setInterval(function() {
                        attemptCount++;
                        if (attemptCount <= maxAttempts) {
                            checkPaymentStatus(cID, checkoutRequestID, txn_id);
                        } else {
                            clearInterval(pollingInterval);
                            Swal.fire({
                                icon: "warning",
                                title: "Unable to Verify Payment",
                                text: "We couldn't confirm your payment. Please check your M-Pesa or try again.",
                                confirmButtonColor: '#0d6efd'
                            });
                        }
                    }, 5000);
                }

                function checkPaymentStatus(cID, checkoutRequestID, txn_id) {
                    $.ajax({
                        url: "{{ route('plan.renew.verify') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            ref: checkoutRequestID,
                            cID: cID,
                            txn_id: txn_id
                        },
                        success: function(response) {
                            if (response.success && response.status === "confirmed") {
                                clearInterval(pollingInterval);
                                Swal.fire({
                                    title: "Payment Successful!",
                                    text: "Your subscription has been updated.",
                                    icon: "success",
                                    showConfirmButton: false,
                                    allowOutsideClick: false,
                                    timer: 2000
                                });
                            } else if (response.status === "failed") {
                                clearInterval(pollingInterval);
                                Swal.fire("Payment Failed", "error");
                            } else if (response.status === "cancelled") {
                                clearInterval(pollingInterval);
                                Swal.fire("Payment Canceled", "error");
                            } else if (response.status === "insufficient_funds") {
                                clearInterval(pollingInterval);
                                Swal.fire("Insufficient Funds", "error");
                            }
                        },
                        error: function() {
                            clearInterval(pollingInterval);
                            Swal.fire("Error", "Could not verify payment. Try again.", "error");
                        }
                    });
                }
            });
        </script>
    @endif
@endpush