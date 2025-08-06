@extends('layouts/layoutMaster')
@section('page-title')
    {{__('Transaction Summary')}}
@endsection

@push('css-page')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
<style>
    div.dt-buttons {
        float: right;
        margin-left: 10px;
    }
    .dataTables_processing {
        height: 60px !important;
        background: rgba(0, 0, 0, 0.6) !important;
        color: white !important;
        font-size: 16px !important;
        z-index: 9999 !important;
        border-radius: 3px;
        padding-top: 15px !important;
    }
</style>
@endpush

@section('content')

    <div class="row mb-4">
        <!-- Period Filter -->
        <div class="col-md-3">
            <div class="card p-3">
                <h5>Filter by Period</h5>
                <form method="GET" action="{{ route('transaction.period') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="{{ old('start_date', $startDate ?? '') }}">
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="{{ old('end_date', $endDate ?? '') }}">
                    </div>
                    <div class="mb-3">
                        {{ Form::label('site_id', __('Select Site')) }}
                        <select name="site_id" id="site_id" class="form-control">
                            <option value="">All Sites</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}" {{ (old('site_id', $siteId ?? '') == $site->id) ? 'selected' : '' }}>
                                    {{ $site->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        {{ Form::label('service', __('Service Type')) }}
                        <select name="service" id="service" class="form-control">
                            <option value="all" {{ (old('service', $service ?? '') == 'all') ? 'selected' : '' }}>All Transactions</option>
                            <option value="Hotspot" {{ (old('service', $service ?? '') == 'Hotspot') ? 'selected' : '' }}>Hotspot Transactions</option>
                            <option value="PPPoE" {{ (old('service', $service ?? '') == 'PPPoE') ? 'selected' : '' }}>PPPoE Transactions</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                </form>
            </div>
        </div>
      
        <!-- Top Summary Cards -->
        <div class="col-md-9">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h6 class="card-title">Total Transactions</h6>
                            <p class="card-text fs-4" id="transactions-count">{{ $thisMonthEntries }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h6 class="card-title">Pending Transactions</h6>
                            <p class="card-text fs-4">{{ $thisMonthUnresolved }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h6 class="card-title">Total Monthly Sales</h6>
                            <p class="card-text fs-4" id="total-sales">Ksh {{ number_format($thisMonthIncome) }}</p>
                        </div>
                    </div>
                </div>
            </div>
         
            <div class="card">
                <div class="card-body">
                    <h5>Transactions</h5>
                    <div class="table-responsive">
                        <table id="transactions-table" class="table table-striped mb-6">
                            <thead>
                                <tr>
                                    <th>{{__('Customer')}}</th>
                                    <th>{{__('Package')}}</th>
                                    <th>{{__('Phone')}}</th>
                                    <th>{{__('Mpesa TRX')}}</th>
                                    <th>{{__('Amount')}}</th>
                                    <th>{{__('Status')}}</th>
                                    <th>{{__('Date')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $item)
                                    <tr>
                                        <td>{{ $item->customer->username ?? 'N/A' }}</td>
                                        <td>{{ $item->customer->package ?? 'N/A' }}</td>
                                        <td>{{ $item->phone ?? '-' }}</td>
                                        <td>{{ $item->mpesa_code ?? '-' }}</td>
                                        <td>{{ \Auth::user()->priceFormat($item->amount) }}</td>
                                        <td><span class="badge bg-label-success">{{ $item->status == 1 ? 'Success' : 'Pending' }}</span></td>
                                        <td>{{ \Carbon\Carbon::parse($item->date)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $transactions->onEachSide(2)->links('vendor.pagination.rounded') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
      
@endsection
@push('script-page')

@endpush