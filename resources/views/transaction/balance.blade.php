@extends('layouts/layoutMaster')
@section('page-title')
    {{__('Transaction Summary')}}
@endsection

@push('css-page')
{{--  <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">--}}
@endpush

@push('script-page')
    <script>
        var filename = $('#filename').val();

        function saveAsPDF() {
            var element = document.getElementById('printableArea');
            var opt = {
                margin: 0.3,
                filename: filename,
                image: {type: 'jpeg', quality: 1},
                html2canvas: {scale: 4, dpi: 72, letterRendering: true},
                jsPDF: {unit: 'in', format: 'A4'}
            };
            html2pdf().set(opt).from(element).save();

        }
    </script>
@endpush

@section('content')
        <a href="#" class="btn btn-sm btn-primary mb-2" onclick="saveAsPDF()"data-bs-toggle="tooltip" title="{{__('Download')}}" data-original-title="{{__('Download')}}">
            <span class="btn-inner--icon"><i class="ti ti-download"></i></span>
        </a>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                <div class="table-responsive">
                <table class="table datatabl">
                            <thead>
                            <tr>
                                <th>{{__('Customer')}}</th>
                                <th>{{__('Expiry')}}</th>
                                <th>{{__('Balance')}}</th>
                                <th>{{__('Type')}}</th>
                                <th>{{__('Stataus')}}</th>
                                {{-- <th>{{__('Action')}}</th> --}}
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($customerBalance as $customer)
                                <tr>
                                    <td>
                                        <a href="{{ route('customer.show', encrypt($customer->id)) }}">                      
                                            {{ $customer->account }}
                                        </a>
                                    </td>
                                    <td>{{ $customer->expiry }}</td>
                                    <td>{{ \Auth::user()->priceFormat($customer->balance) }}</td>
                                    <td>{{ $customer->service }}</td>
                                    <td>
                                         @if ($customer->status == 'on')
                                            <span class="badge bg-label-success">Active</span> 
                                        @else
                                            <span class="badge bg-label-warning">Expired</span>
                                        @endif
                                    </td>
                                    {{-- <td>
                                         @if(Gate::check('show customer'))
                                            <a href="{{ route('customer.show', encrypt($customer->id)) }}" class="inline-block transition-all rounded-md font-medium px-[13px] py-[6px] text-primary-500 border border-primary-500 hover:bg-primary-500 hover:text-white">
                                            
                                            </a>
                                        @endif
                                    </td> --}}
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script>
    new DataTable('table.datatabl', {
        fixedHeader: {
            header: true,
            footer: true
        }
    });
</script>
@endpush