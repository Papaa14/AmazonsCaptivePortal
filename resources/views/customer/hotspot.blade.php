@extends('layouts/layoutMaster')
@php
   // $profile=asset(Storage::url('uploads/avatar/'));
$profile=\App\Models\Utility::get_file('uploads/avatar/');
$user = Auth::user();
@endphp

@section('page-title')
    {{__('Manage Customers')}}
@endsection

@push('css-page')

@endpush

@section('content')

    <div class="row g-4">
        {{-- User Status Cards --}}
        <div class="col-md-12">
            @if ($user->type == 'company')
                <div class="row g-3"> {{-- Adds uniform spacing (gap) between cards --}}
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="card filter-card h-100" data-key="all" data-value="">
                            <div class="card-body cursor-pointer">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div class="content-left">
                                        <span class="text-heading">Total Users</span>
                                        <div class="d-flex align-items-center my-1">
                                            <h4 class="mb-0 me-2">{{ $customersC->count() }}</h4>
                                        </div>
                                    </div>
                                    <div class="avatar">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="ti ti-users ti-26px"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="card filter-card h-100" data-key="status" data-value="active">
                            <div class="card-body cursor-pointer">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div class="content-left">
                                        <span class="text-heading">Active Users</span>
                                        <div class="d-flex align-items-center my-1">
                                            <h4 class="mb-0 me-2">{{ $actcustomers->count() }}</h4>
                                        </div>
                                    </div>
                                    <div class="avatar">
                                        <span class="avatar-initial rounded bg-label-danger">
                                            <i class="ti ti-user-plus ti-26px"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="card filter-card h-100" data-key="status" data-value="disabled">
                            <div class="card-body cursor-pointer">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div class="content-left">
                                        <span class="text-heading">Suspended Users</span>
                                        <div class="d-flex align-items-center my-1">
                                            <h4 class="mb-0 me-2">{{ $suscustomers->count() }}</h4>
                                        </div>
                                    </div>
                                    <div class="avatar">
                                        <span class="avatar-initial rounded bg-label-success">
                                            <i class="ti ti-user-check ti-26px"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="card filter-card h-100" data-key="status" data-value="expired">
                            <div class="card-body cursor-pointer">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div class="content-left">
                                        <span class="text-heading">Expired Users</span>
                                        <div class="d-flex align-items-center my-1">
                                            <h4 class="mb-0 me-2">{{ $expcustomers->count() }}</h4>
                                        </div>
                                    </div>
                                    <div class="avatar">
                                        <span class="avatar-initial rounded bg-label-warning">
                                            <i class="ti ti-user-search ti-26px"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Filters</h4>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="site-select" class="form-label">Site</label>
                            <select id="site-select" class="form-select filter-input" data-key="site">
                                <option value="">All</option>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->ip_address }}" {{ request('site') == $site->ip_address ? 'selected' : '' }}>
                                        {{ $site->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="status-select" class="form-label">Status</label>
                            <select id="status-select" class="form-select filter-input" data-key="status">
                                <option value="">All</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="disabled" {{ request('status') == 'disabled' ? 'selected' : '' }}>Disabled</option>
                                <option value="paused" {{ request('status') == 'paused' ? 'selected' : '' }}>Paused</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="connection-select" class="form-label">Connection</label>
                            <select id="connection-select" class="form-select filter-input" data-key="connection">
                                <option value="">All</option>
                                <option value="online" {{ request('connection') == 'online' ? 'selected' : '' }}>Online</option>
                                <option value="offline" {{ request('connection') == 'offline' ? 'selected' : '' }}>Offline</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="package-select" class="form-label">Package</label>
                            <select id="package-select" class="form-select filter-input" data-key="package">
                                <option value="">All</option>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}" {{ request('package') == $package->id ? 'selected' : '' }}>
                                        {{ $package->name_plan }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-2 justify-content-end">
            @if ($user->type == 'company')
                <div class="col-6 col-md-auto">
                    <a href="#"
                    data-size="md"
                    data-bs-toggle="tooltip"
                    title="{{ __('Import') }}"
                    data-url="{{ route('customer.file.import') }}"
                    data-ajax-popup="true"
                    data-title="{{ __('Import customer CSV file') }}"
                    class="btn btn-sm btn-info w-100">
                        <i class="ti ti-file-import"></i> {{ __('Import') }}
                    </a>
                </div>
                <div class="col-6 col-md-auto">
                    <a href="{{ route('customer.export') }}"
                    data-bs-toggle="tooltip"
                    title="{{ __('Export') }}"
                    class="btn btn-sm btn-secondary w-100">
                        <i class="ti ti-file-export"></i> {{ __('Export') }}
                    </a>
                </div>
            @endif

            @if ($user->type == 'company')
                <div class="col-6 col-md-auto">
                    <form action="{{ route('customers.refreshRadius') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning w-100">
                            <i class="ti ti-refresh"></i> {{ __('Refresh Radius') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatabl mb-6">
                            <thead>
                                <tr>
                                    <th>Fullname</th>
                                    <th>Account</th>
                                    <th>Phone</th>
                                    <th>Plan</th>
                                    <th>Statu</th>
                                    <th>Online</th>
                                    <th>Expiry</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hotspotCustomers as $customer)
                                <tr>
                                    <td>
                                        <a href="{{ route('customer.show', \Crypt::encrypt($customer->id)) }}">
                                            {{ $customer->fullname }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('customer.show', \Crypt::encrypt($customer->id)) }}">
                                            {{ $customer->username }}
                                        </a>
                                    </td>
                                    <td>{{ $customer->contact }}</td>
                                    <td>{{ $customer->package }}</td>
                                    <td>
                                        <span class="badge {{ $customer->status === 'on' ? 'bg-label-success' : 'bg-label-warning' }}">
                                            {{ $customer->status === 'on' ? 'Active' : 'Expired' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($customer->is_online)
                                            <span class="badge bg-label-success">Online</span>
                                        @else
                                            <span class="badge bg-label-warning">Offline</span>
                                        @endif
                                    </td>
                                    <td>{{ $customer->expiry }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script-page')
{{-- <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script> --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script>
    $(function () {
        let filters = {
            site: "{{ request('site') }}",
            connection: "{{ request('connection') }}",
            status: "{{ request('status') }}",
            package: "{{ request('package') }}",
            search: "{{ request('search') }}"
        };

        function buildQueryString(filters) {
            return Object.keys(filters)
                .filter(key => filters[key] !== '')
                .map(key => `${key}=${encodeURIComponent(filters[key])}`)
                .join('&');
        }

        function applyFilter(key, value) {
            filters[key] = value;
            let query = buildQueryString(filters);
            window.location.href = '?' + query;
        }

        // $('.filter-card').on('click', function () {
        //     let key = $(this).data('key');
        //     let value = $(this).data('value');
        //     applyFilter(key, value);
        // });
        $('.filter-card').on('click', function () {
            let key = $(this).data('key');
            let value = $(this).data('value');

            if (!key || key === 'all') {
                // If total users card is clicked, clear all filters
                window.location.href = window.location.pathname;
            } else {
                applyFilter(key, value);
            }
        });


        $('.filter-input').on('change', function () {
            let key = $(this).data('key');
            let value = $(this).val();
            applyFilter(key, value);
        });

        $('#search-invoice').on('keyup', function () {
            let val = $(this).val();
            filters['search'] = val;
            if (val.length >= 2 || val === '') {
                clearTimeout($.data(this, 'timer'));
                let wait = setTimeout(() => {
                    applyFilter('search', val);
                }, 3000);
                $(this).data('timer', wait);
            }
        });
    });
</script>
<script>
    new DataTable('table.datatabl', {
        fixedHeader: {
            header: true,
            footer: true
        }
    });
</script>
@endpush
