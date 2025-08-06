@extends('layouts/layoutMaster')
@section('page-title')
    {{__('Manage Vouchers')}}
@endsection
@push('script-page')
    <script>
        $(document).on('click', '.code', function () {
            var type = $(this).val();
            if (type == 'manual') {
                $('#manual').removeClass('d-none');
                $('#manual').addClass('d-block');
                $('#auto').removeClass('d-block');
                $('#auto').addClass('d-none');
            } else {
                $('#auto').removeClass('d-none');
                $('#auto').addClass('d-block');
                $('#manual').removeClass('d-block');
                $('#manual').addClass('d-none');
            }
        });

        $(document).on('click', '#code-generate', function () {
            var length = 7;
            var result = '';
            var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            var charactersLength = characters.length;
            for (var i = 0; i < length; i++) {
                result += characters.charAt(Math.floor(Math.random() * charactersLength));
            }
            $('#auto-code').val(result);
        });
        $(document).on('click', '#manual_code', function () {
            var result = null;
            $('#auto-code').val(result);
        });
        $(document).on('click', '#auto_code', function () {
            var result = null;
            $('#manual-code').val(result);
        });
    </script>
@endpush
@section('content')
    <div class="row">

        <div class="col-md-12">  
            <div class="card">
                <div class="card-header">
                    <div class="float-end d-flex">
                        @can('create voucher')
                            <a href="#" data-size="sm" data-url="{{ route('voucher.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip" title="{{__('Create')}}" data-title="{{__('Create Package')}}" class="btn btn-sm btn-primary me-2">
                                <i class="ti ti-plus"></i> {{__('Generate Voucher')}}
                            </a>
                        @endcan
                        @can('delete voucher')
                            <form action="/vouchers/mass-delete-used-direct" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete all used vouchers?');">
                                    Delete All Used Vouchers
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
                <div class="card-body table-border-style mt-0">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Package Name') }}</th>
                                    <th>{{ __('Price') }}</th>
                                    <th>{{ __('Validity') }}</th>
                                    <th>{{ __('Total Vouchers') }}</th>
                                    <th>{{ __('Used') }}</th>
                                    <th>{{ __('Unused') }}</th>
                                    @if (Gate::check('edit voucher') || Gate::check('delete voucher') || Gate::check('show voucher'))
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($voucherGroups as $package)
                                    <tr>
                                        <td>{{ $package->name_plan }}</td>
                                        <td>{{ $package->price }}</td>
                                        <td>{{ $package->validity }} {{ $package->validity_unit }}</td>
                                        <td>{{ $package->vouchers_count }}</td>
                                        <td>
                                            <span class="badge bg-label-warning">{{ $package->used_vouchers_count }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-success">{{ $package->unused_vouchers_count }}</span>
                                        </td>
                                        @if (Gate::check('edit voucher') || Gate::check('delete voucher') || Gate::check('show voucher'))
                                        <td class="d-flex">
                                            @if(Gate::check('show voucher'))
                                                <a href="{{ route('voucher.show', $package->id) }}" class="btn btn-sm bg-label-info me-2">
                                                    {{ __('View Vouchers') }}
                                                </a>
                                            @endif
                                            @if(Gate::check('delete voucher'))
                                                <form action="{{ route('vouchers.deleteByPackage', $package->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm bg-label-danger" onclick="return confirm('{{ __('Are you sure you want to delete all vouchers for this package?') }}');">
                                                        {{ __('Delete All') }}
                                                    </button>
                                                </form>
                                            @endif
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
@push('script-page')
    <script>
        $(document).on('change', '#enable_burst', function() {
            if ($(this).is(':checked')) {
                $('.burst_div').removeClass('d-none');
                // $('#trial_days').attr("required", true);

            } else {
                $('.burst_div').addClass('d-none');
                // $('#trial_days').removeAttr("required");
            }
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let burstLimit = document.querySelector("input[name='burst_limit']");
            let burstThreshold = document.querySelector("input[name='burst_threshold']");
            let burstTime = document.querySelector("input[name='burst_time']");
            let burstPriority = document.querySelector("input[name='burst_priority']");
            let burstLimitAt = document.querySelector("input[name='burst_limit_at']");
            let burstCombined = document.getElementById("burst_combined");
            let enableBurstCheckbox = document.getElementById("enable_burst");
            let burstInputDiv = document.getElementById("burst_input");

            function updateBurstValue() {
                burstCombined.value = 
                    (burstLimit.value || "0") + " " + 
                    (burstThreshold.value || "0") + " " + 
                    (burstTime.value || "0") + " " + 
                    (burstPriority.value || "0") + " " + 
                    (burstLimitAt.value || "0");
            }

            function toggleBurstInputs() {
                if (enableBurstCheckbox.checked) {
                    burstInputDiv.style.display = "block";
                } else {
                    burstInputDiv.style.display = "none";
                }
            }

            // Initialize burst values on load
            updateBurstValue();
            toggleBurstInputs();

            // Event listeners
            burstLimit.addEventListener("input", updateBurstValue);
            burstThreshold.addEventListener("input", updateBurstValue);
            burstTime.addEventListener("input", updateBurstValue);
            burstPriority.addEventListener("input", updateBurstValue);
            burstLimitAt.addEventListener("input", updateBurstValue);
            enableBurstCheckbox.addEventListener("change", toggleBurstInputs);
        });
    </script>
    <script>
        $(document).on('change', '#enable_fup', function() {
            if ($(this).is(':checked')) {
                $('.fup_div').removeClass('d-none');

            } else {
                $('.fup_div').addClass('d-none');
            }
        });
    </script>
@endpush