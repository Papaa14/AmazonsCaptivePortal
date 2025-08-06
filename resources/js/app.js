// resources/js/app.js
import './bootstrap';
import Alpine from 'alpinejs';
import './../../vendor/power-components/livewire-powergrid/dist/powergrid'
import flatpickr from "flatpickr"; 

import 'flatpickr/dist/flatpickr.min.css';
import 'tom-select/dist/css/tom-select.bootstrap5.css';
// import "slim-select/dist/slimselect.css";
import '/node_modules/slim-select/dist/slimselect.css';


import SlimSelect from 'slim-select'
window.SlimSelect = SlimSelect

import TomSelect from "tom-select";
window.TomSelect = TomSelect

window.Alpine = Alpine;

Alpine.start();

import.meta.glob([
  '../assets/img/**',
  // '../assets/json/**',
  '../assets/vendor/fonts/**'
]);

//amazons js

// --- Global Config & Helpers ---
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function showToastr(type, message, title = '') {
    toastr.options = {
        "closeButton": true,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": true,
        "timeOut": "5000"
    };
    toastr[type](message, title);
}

// --- Page-Specific Logic ---
document.addEventListener('DOMContentLoaded', () => {
    // --- OFFERS PAGE LOGIC ---
    const pageDataEl = document.getElementById('page-data');
    if (pageDataEl) {
        // Check if user has seen the welcome modal
        const hasSeenWelcome = localStorage.getItem('amazons_welcome_seen');
        if (!hasSeenWelcome) {
            // Show welcome modal after a short delay
            setTimeout(() => {
                const welcomeModal = new bootstrap.Modal(document.getElementById('welcomeBackModal'));
                welcomeModal.show();
                
                // Mark as seen when modal is closed
                document.getElementById('welcomeBackModal').addEventListener('hidden.bs.modal', () => {
                    localStorage.setItem('amazons_welcome_seen', 'true');
                });
            }, 500);
        }
        // Retrieve data from Blade
        const userCredit = parseInt(pageDataEl.dataset.userCredit, 10);
        const userPhone = pageDataEl.dataset.userPhone;
        const MOCK_PLANS = JSON.parse(pageDataEl.dataset.plans);
        const buyUrl = pageDataEl.dataset.buyUrl;
        const voucherUrl = pageDataEl.dataset.voucherUrl;

        // Initialize Owl Carousel
        $('#imageSlider').owlCarousel({
            items: 1, loop: true, autoplay: true, autoplayTimeout: 4000,
            autoplayHoverPause: true, dots: false, nav: false
        });

        // Event Handlers
        $('#refreshBtn').on('click', function() {
            $(this).addClass('rotating');
            showToastr('info', 'Refreshing your data...');
            setTimeout(() => {
                $(this).removeClass('rotating');
                toastr.clear();
                showToastr('success', 'Data refreshed!');
                // In a real app, this would be an AJAX call to get fresh data
            }, 1500);
        });

        $('#voucherConnectBtn').on('click', function() {
            const btn = $(this);
            btn.prop('disabled', true).text('...');
            fetch(voucherUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToastr('success', 'Voucher connected successfully! Redirecting...', 'Success');
                    setTimeout(() => window.location.href = data.redirect_url, 1500);
                }
            }).catch(() => showToastr('error', 'Something went wrong.'))
              .finally(() => btn.prop('disabled', false).text('CONNECT'));
        });

        $('#packagesBox').on('click', '.subscribePlanBtn', function() {
            const planId = $(this).data('package-id');
            const plan = MOCK_PLANS.find(p => p.id === planId);

            if (userCredit >= plan.price) {
                showConfirmationModal(plan);
            } else {
                showPaymentModal(plan, userCredit, userPhone);
            }
        });

        function showConfirmationModal(plan) {
            const modalBody = $('#confirmPurchaseModalBody');
            modalBody.html(`<p>No payment required — you're just about to connect to this plan absolutely free!</p>
<div class="alert alert-info">
  <h5 class="alert-heading">${plan.name}</h5>
  <p class="mb-0">A total of <strong>KES 0.00</strong> will be deducted from your balance.</p>
</div>
`);
            
            $('#confirmPurchaseBtn').off('click').on('click', () => {
                $('#confirmPurchaseModal').modal('hide');
                subscribeToPlan(plan);
            });
            $('#confirmPurchaseModal').modal('show');
        }

        function showPaymentModal(plan, credit, phone) {
            const requiredAmount = plan.price - credit;
            const modalBodyHtml = `
                <p><strong>Plan selected:</strong> ${plan.name}</p>
                <p><strong>Account Credit:</strong> KES ${credit}</p>
                <p class="fs-5">You'll pay <strong>KES ${requiredAmount}</strong> to complete the subscription</p>
                <div class="input-group mb-3"><span class="input-group-text">MPESA No.</span><input type="tel" class="form-control" id="mpesaNumber" value="${phone}"></div>
                <div class="d-flex justify-content-between align-items-center"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="initiateStkPushBtn">Pay</button></div>
                <hr><div class="text-center my-3"><strong>OR</strong></div>
                <div class="paybill-instructions"><p class="fw-bold">Use our PAYBILL instructions</p><ol class="ps-3"><li>Go to M-PESA on your phone</li><li>Select Pay Bill option</li><li>Enter Business no: <strong>4140961</strong></li><li>Enter Account no: <strong>${phone}</strong></li><li>Enter the Amount: <strong>${requiredAmount}</strong></li><li>Enter your M-PESA PIN and Send</li><li><strong>Check back and proceed after making payment.</strong></li></ol></div>
            `;
            $('#paymentModalBody').html(modalBodyHtml);
            
            $('#initiateStkPushBtn').on('click', () => {
                initiateStkPush(plan);
            });
            $('#paymentModal').modal('show');
        }

        function initiateStkPush(plan) {
            $('#paymentModal').modal('hide');
            showToastr('success', 'Please check your phone for the prompt.', 'STK Push initiated!');
            // Simulate payment confirmation
            setTimeout(() => {
                showToastr('info', 'Payment received. Activating your plan...');
                subscribeToPlan(plan);
            }, 4000);
        }

        function subscribeToPlan(plan) {
            showToastr('info', `Activating your ${plan.name} plan...`);
            fetch(buyUrl, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ plan_id: plan.id })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToastr('success', `Your ${plan.name} plan is now active. Enjoy!`, 'Subscription Complete!');
                    setTimeout(() => { window.location.href = data.redirect_url; }, 2000);
                }
            }).catch(() => showToastr('error', 'Activation failed. Please contact support.'));
        }
        
        // Development utility: Add a way to reset welcome modal (can be removed in production)
        if (window.location.search.includes('reset_welcome')) {
            localStorage.removeItem('amazons_welcome_seen');
            window.location.href = window.location.pathname;
        }
    }

    // --- CONNECTED PAGE LOGIC ---
    const svgContainer = document.getElementById('connected-svg-container');
    if (svgContainer) {
        // The original SVG content from assets/connected.svg
        svgContainer.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200" style="width:100%;height:100%;">
  <defs>
    <!-- Gradient for modern look -->
    <linearGradient id="wifiGradient" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#00D4FF;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#0080FF;stop-opacity:1" />
    </linearGradient>
    
    <!-- Glow effect -->
    <filter id="glow" x="-50%" y="-50%" width="200%" height="200%">
      <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
      <feMerge> 
        <feMergeNode in="coloredBlur"/>
        <feMergeNode in="SourceGraphic"/>
      </feMerge>
    </filter>
    
    <!-- Drop shadow -->
    <filter id="shadow" x="-50%" y="-50%" width="200%" height="200%">
      <feDropShadow dx="2" dy="4" stdDeviation="3" flood-color="rgba(0,0,0,0.3)"/>
    </filter>
  </defs>
  
  <!-- Background circle -->
  <circle cx="100" cy="100" r="90" fill="url(#wifiGradient)" opacity="0.1" filter="url(#shadow)"/>
  
  <!-- WiFi signal arcs -->
  <g fill="none" stroke="url(#wifiGradient)" stroke-linecap="round" filter="url(#glow)">
    <!-- Outermost arc -->
    <path d="M 40 120 Q 100 60 160 120" stroke-width="6" opacity="0.8"/>
    
    <!-- Middle arc -->
    <path d="M 55 125 Q 100 80 145 125" stroke-width="5" opacity="0.9"/>
    
    <!-- Inner arc -->
    <path d="M 70 130 Q 100 100 130 130" stroke-width="4" opacity="1"/>
  </g>
  
  <!-- Central dot (connection point) -->
  <circle cx="100" cy="140" r="8" fill="url(#wifiGradient)" filter="url(#glow)"/>
  
  <!-- Connection indicator dots -->
  <g fill="url(#wifiGradient)" opacity="0.8">
    <circle cx="120" cy="130" r="3">
      <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite"/>
    </circle>
    <circle cx="135" cy="125" r="2.5">
      <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite" begin="0.3s"/>
    </circle>
    <circle cx="80" cy="130" r="3">
      <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite" begin="0.6s"/>
    </circle>
    <circle cx="65" cy="125" r="2.5">
      <animate attributeName="opacity" values="0.3;1;0.3" dur="2s" repeatCount="indefinite" begin="0.9s"/>
    </circle>
  </g>
  
  <!-- Signal strength bars -->
  <g fill="url(#wifiGradient)" opacity="0.7">
    <rect x="145" y="100" width="4" height="20" rx="2">
      <animate attributeName="height" values="15;25;15" dur="1.5s" repeatCount="indefinite"/>
      <animate attributeName="y" values="105;95;105" dur="1.5s" repeatCount="indefinite"/>
    </rect>
    <rect x="152" y="95" width="4" height="30" rx="2">
      <animate attributeName="height" values="25;35;25" dur="1.5s" repeatCount="indefinite" begin="0.2s"/>
      <animate attributeName="y" values="100;90;100" dur="1.5s" repeatCount="indefinite" begin="0.2s"/>
    </rect>
    <rect x="159" y="90" width="4" height="40" rx="2">
      <animate attributeName="height" values="35;45;35" dur="1.5s" repeatCount="indefinite" begin="0.4s"/>
      <animate attributeName="y" values="95;85;95" dur="1.5s" repeatCount="indefinite" begin="0.4s"/>
    </rect>
  </g>

  <!-- Connected checkmark -->
  <g transform="translate(160,60)" opacity="0.9">
    <circle cx="0" cy="0" r="15" fill="#00FF88" filter="url(#shadow)"/>
    <path d="M -6 0 L -2 4 L 6 -4" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
  </g>
</svg>`; }
});