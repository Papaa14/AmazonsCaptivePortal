@extends('layouts/layoutMaster')
@section('page-title')
    {{__('Vouchers for Package:')}} {{ $package->name_plan }}
@endsection

@push('script-page')
<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Show tooltip feedback
            const tooltip = document.getElementById('copy-tooltip');
            tooltip.innerText = 'Copied!';
            tooltip.style.display = 'block';
            
            // Hide tooltip after 1.5 seconds
            setTimeout(function() {
                tooltip.style.display = 'none';
            }, 1500);
        }, function() {
            // Show error if copying failed
            const tooltip = document.getElementById('copy-tooltip');
            tooltip.innerText = 'Copy failed!';
            tooltip.style.display = 'block';
            
            setTimeout(function() {
                tooltip.style.display = 'none';
            }, 1500);
        });
    }
</script>
@endpush

@section('content')
    <div class="row">
        <div class="col-md-12">  
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Vouchers for Package:') }} {{ $package->name_plan }}</h5>
                    <div class="float-end d-flex">
                        <a href="{{ route('voucher.index') }}" class="btn btn-sm btn-secondary me-2">
                            <i class="ti ti-arrow-left"></i> {{ __('Back to Vouchers') }}
                        </a>
                        @can('delete voucher')
                            <form action="{{ route('vouchers.deleteByPackage', $package->id) }}" method="POST" class="me-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('Are you sure you want to delete all vouchers for this package?') }}');">
                                    <i class="ti ti-trash"></i> {{ __('Delete All Vouchers') }}
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="mb-3">{{ __('Package Details') }}</h6>
                                    <p><strong>{{ __('Name:') }}</strong> {{ $package->name_plan }}</p>
                                    <p><strong>{{ __('Price:') }}</strong> {{ $package->price }}</p>
                                    <p><strong>{{ __('Validity:') }}</strong> {{ $package->validity }} {{ $package->validity_unit }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-3">
                                        <h6>{{ __('Voucher Statistics') }}</h6>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="bg-light p-3 rounded">
                                                <h2 class="mb-1">{{ $vouchers->count() }}</h2>
                                                <p class="text-muted mb-0">{{ __('Total Vouchers') }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="bg-light p-3 rounded">
                                                <h2 class="mb-1">{{ $vouchers->where('status', 0)->count() }}</h2>
                                                <p class="text-muted mb-0">{{ __('Unused Vouchers') }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="bg-light p-3 rounded">
                                                <h2 class="mb-1">{{ $vouchers->where('status', 1)->count() }}</h2>
                                                <p class="text-muted mb-0">{{ __('Used Vouchers') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Floating tooltip for copy feedback -->
                    <div id="copy-tooltip" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #333; color: white; padding: 10px 15px; border-radius: 5px; z-index: 9999;">
                        Copied!
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Code') }}</th>
                                    <th>{{ __('Compensation') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Devices') }}</th>
                                    <th>{{ __('Used Devices') }}</th>
                                    <th>{{ __('Used By') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    @if(Gate::check('delete voucher'))
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vouchers as $voucher)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                {{ $voucher->code }}
                                                <button type="button" class="btn btn-sm btn-icon ms-2" onclick="copyToClipboard('{{ $voucher->code }}')" title="{{ __('Copy to clipboard') }}">
                                                    <i class="ti ti-copy text-primary"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>{{ $voucher->is_compensation ? __('Yes') : __('No') }}</td>
                                        <td>
                                            @if ($voucher->status && $voucher->devices == 1)
                                                <span class="badge bg-label-warning">{{ __('Used') }}</span>
                                            @elseif ($voucher->status && $voucher->devices > 1 && $voucher->Used_devices < $voucher->devices)
                                                <span class="badge bg-label-warning">
                                                    {{ $voucher->Used_devices }} {{ __('Used') }}
                                                </span>
                                            @else
                                                <span class="badge bg-label-success">{{ __('Unused') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $voucher->devices ?? __('N/A') }}</td>
                                        <td>{{ $voucher->used_devices ?? __('N/A') }}</td>
                                        <td>{{ $voucher->used_by ?? __('N/A') }}</td>
                                        <td>{{ $voucher->created_at->format('Y-m-d H:i') }}</td>
                                        @if(Gate::check('delete voucher'))
                                            <td>
                                                <form action="{{ route('voucher.destroy', $voucher->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm bg-label-danger" onclick="return confirm('{{ __('Are you sure?') }}');">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
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