@extends('layouts/layoutMaster')
@section('page-title')
    {{__('Manage Invoices')}}
@endsection
@push('css-page')
<link rel="stylesheet" href="{{ asset('assets/css/plugins/flatpickr.min.css') }}">
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<style>
    /* DataTables Pagination Styling Fixes */
    .dataTables_paginate .paginate_button {
        padding: 0.25rem 0.5rem;
        margin: 0 2px;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .dataTables_paginate .paginate_button.current {
        background-color: #5e72e4 !important;
        border-color: #5e72e4 !important;
        color: white !important;
    }
    
    .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
        background-color: #f8f9fa !important;
        border-color: #dee2e6 !important;
        color: #212529 !important;
    }
    
    .dataTables_paginate .paginate_button.disabled {
        opacity: 0.5;
        cursor: default;
    }
    
    .dataTables_info {
        padding-top: 0.85em;
    }
    
    /* Make sure the pagination links are visible */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        min-width: 1.5em;
        padding: 0.4em 0.8em;
        text-align: center;
    }
    
    /* Fix for action buttons alignment */
    .action-btn {
        display: inline-flex;
        gap: 12px;
        align-items: center;
        justify-content: center;
    }
    
    .action-btn a {
        color: #5e72e4;
        padding: 4px;
        border-radius: 4px;
        background: transparent;
        transition: all 0.2s ease;
        line-height: 1;
    }
    
    .action-btn a:hover {
        color: #324cdd;
        transform: scale(1.1);
    }
    
    .action-btn a i {
        font-size: 18px;
    }
    
    /* Different colors for different action types */
    .action-btn a i.ti-eye {
        color: #11cdef;
    }
    
    .action-btn a i.ti-pencil {
        color: #fb6340;
    }
    
    .action-btn a i.ti-trash {
        color: #f5365c;
    }
    
    .action-btn a i.ti-link {
        color: #2dce89;
    }
    
    /* Custom CSS for three-dot dropdown */
    .dropdown-toggle {
        border: none;
        background: none;
        padding: 0;
        cursor: pointer;
    }

    .dropdown-menu {
        border: none;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
        padding: 0;
    }

    .dropdown-item {
        padding: 0.5rem 1rem;
        display: block;
        color: #000;
        text-decoration: none;
        white-space: nowrap;
        background-color: transparent;
        border: 0;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    /* Remove the arrow from the dropdown */
    .dropdown-toggle::after {
        display: none;
    }
    
    /* Prevent horizontal scrolling */
    .table-responsive {
        overflow-x: visible;
    }
    
    /* Make sure the table fits within its container */
    #pppoe-invoice-table {
        width: 100% !important;
        table-layout: fixed;
    }
    
    /* Handle text overflow in table cells */
    #pppoe-invoice-table td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Adjust column widths for better display */
    #pppoe-invoice-table th:nth-child(1),
    #pppoe-invoice-table td:nth-child(1) {
        width: 15%;
    }
    
    #pppoe-invoice-table th:nth-child(2),
    #pppoe-invoice-table td:nth-child(2) {
        width: 20%;
    }
    
    #pppoe-invoice-table th:nth-child(7),
    #pppoe-invoice-table td:nth-child(7) {
        width: 10%;
        text-align: center;
    }
    
    /* Hide certain columns on smaller screens */
    @media screen and (max-width: 767px) {
        #pppoe-invoice-table th:nth-child(3),
        #pppoe-invoice-table td:nth-child(3),
        #pppoe-invoice-table th:nth-child(4),
        #pppoe-invoice-table td:nth-child(4) {
            display: none;
        }
    }
</style>
@endpush

@section('content')
    <div class="row">
        <div class="float-end d-flex">
            <span class="badge bg-info me-2 d-flex align-items-center">
                <i class="ti ti-filter me-1"></i> {{__('Filtered: PPPoE Invoices')}}
            </span>

            <a href="{{ route('invoice.export') }}" class="btn btn-sm btn-secondary me-2" data-bs-toggle="tooltip" title="{{__('Export')}}">
                <i class="ti ti-file-export"></i> {{__('Export')}}
            </a>

            @can('create invoice')
                <a href="{{ route('invoice.create', 0) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{__('Create')}}">
                    <i class="ti ti-plus"></i> {{__('Create')}}
                </a>
            @endcan
        </div>
        <div class="col-sm-12">
            <div class="mt-2 mb-3 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        <div class="row d-flex align-items-center justify-content-end">
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                <div class="btn-box">
                                    {{ Form::label('issue_date_filter', __('Issue Date'),['class'=>'form-label'])}}
                                    {{ Form::date('issue_date_filter', isset($_GET['issue_date'])?$_GET['issue_date']:'', array('class' => 'form-control month-btn','id'=>'issue_date_filter', 'placeholder' => __('Issue Date'))) }}
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 mr-2">
                                <div class="btn-box">
                                    {{ Form::label('customer_filter', __('Customer'),['class'=>'form-label'])}}
                                    {{ Form::select('customer_filter', $customer, isset($_GET['customer']) ? $_GET['customer'] : '', ['class' => 'form-control select', 'id' => 'customer_filter']) }}
                                </div>
                            </div>
                            <div class="col-auto float-end ms-2 mt-4">
                                <a href="#" class="btn btn-sm btn-primary me-1 filter-apply"
                                   data-bs-toggle="tooltip" data-bs-original-title="{{ __('Apply') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                </a>
                                <a href="#" class="btn btn-sm btn-danger filter-reset" data-bs-toggle="tooltip"
                                   data-bs-original-title="{{ __('Reset') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-refresh text-white-off"></i></span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table id="pppoe-invoice-table" class="table table-hover w-100">
                            <thead>
                            <tr>
                                <th> {{ __('Invoice') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Issue Date') }}</th>
                                <th>{{ __('Due Date') }}</th>
                                <th>{{ __('Due Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                                @if (Gate::check('edit invoice') || Gate::check('delete invoice') || Gate::check('show invoice'))
                                    <th>{{ __('Action') }}</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded by DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script src="{{ asset('assets/js/plugins/flatpickr.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing DataTable...');
            
            // Initialize date picker if available
            try {
                if (typeof flatpickr === 'function') {
                    flatpickr("#issue_date_filter", {
                        dateFormat: "Y-m-d"
                    });
                }
            } catch (e) {
                console.warn('Flatpickr initialization failed:', e);
            }
            
            // Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#pppoe-invoice-table')) {
                $('#pppoe-invoice-table').DataTable().destroy();
            }
            
            // Create DataTable with simplified configuration
            try {
                var dataTable = $('#pppoe-invoice-table').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    scrollX: false,
                    autoWidth: false,
                    ajax: {
                        url: "{{ route('invoice.index') }}",
                        type: "GET",
                        data: function(d) {
                            d.customer = $('#customer_filter').val();
                            d.issue_date = $('#issue_date_filter').val();
                            return d;
                        }
                    },
                    columns: [
                        { data: 'invoice_id', name: 'invoice_id', width: '15%' },
                        { data: 'customer', name: 'customer', width: '20%' },
                        { data: 'issue_date', name: 'issue_date', width: '15%' },
                        { data: 'due_date', name: 'due_date', width: '15%' },
                        { data: 'due_amount', name: 'due_amount', width: '15%' },
                        { data: 'status', name: 'status', width: '10%' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, width: '10%' }
                    ],
                    order: [[0, 'desc']]
                });
                
                // Filter handlers
                $('.filter-apply').on('click', function(e) {
                    e.preventDefault();
                    dataTable.draw();
                });
                
                $('.filter-reset').on('click', function(e) {
                    e.preventDefault();
                    $('#customer_filter').val('');
                    $('#issue_date_filter').val('');
                    dataTable.draw();
                });
                
            } catch (error) {
                console.error('Error initializing DataTable:', error);
            }
        });
        
        // Safe wrapper for dash.js error
        window.addEventListener('load', function() {
            setTimeout(function() {
                try {
                    if (typeof dash !== 'undefined' && dash && typeof dash.remove === 'function') {
                        dash.remove();
                    }
                } catch (e) {
                    console.log('Dash.js error prevented');
                }
            }, 100);
        });
        
        function copyToClipboard(element) {
            var copyText = element.id;
            navigator.clipboard.writeText(copyText);
            show_toastr('success', 'Url copied to clipboard', 'success');
        }
    </script>
@endpush
