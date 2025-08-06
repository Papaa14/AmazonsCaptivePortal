@extends('layouts/layoutMaster')
@section('page-title')
    {{__('Transaction Summary')}}
@endsection

@push('css-page')
{{--  <link rel="stylesheet" href="{{ asset('css/datatable/buttons.dataTables.min.css') }}">--}}
@endpush

@push('script-page')
    {{--    <script src="{{ asset('assets/js/plugins/simple-datatables.js') }}"></script>--}}
    {{--   <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script src="{{ asset('js/datatable/jszip.min.js') }}"></script>
    <script src="{{ asset('js/datatable/pdfmake.min.js') }}"></script>
    <script src="{{ asset('js/datatable/vfs_fonts.js') }}"></script>
      <script src="{{ asset('js/datatable/dataTables.buttons.min.js') }}"></script>--}}
    {{--    <script src="{{ asset('js/datatable/buttons.html5.min.js') }}"></script>--}}
    {{--    <script type="text/javascript" src="{{ asset('js/datatable/buttons.print.min.js') }}"></script>--}}

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

    {{--<div class="row">
    <a href="{{route('transaction.export')}}" data-bs-toggle="tooltip" title="{{__('Export')}}" class="btn btn-sm btn-secondary me-2">
                <i class="ti ti-file-export"></i>
            </a>
    --}}
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
                                <th>{{__('Mpesa Code')}}</th>
                                <th>{{__('Account')}}</th>
                                <th>{{__('Paybill/Till')}}</th>
                                <th>{{__('Name')}}</th>
                                <th>{{__('Amount')}}</th>
                                <th>{{__('Status')}}</th>
                                <th>{{__('Date')}}</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach ($mpesaTransactions as $transaction)
                                <tr>
                                    <td>{{  $transaction->TransID }}</td>
                                    <td>{{  $transaction->BillRefNumber }}</td>
                                    <td>{{  $transaction->BusinessShortCode}}</td>
                                    <td>{{  $transaction->FirstName}}</td>
                                    <td>{{\Auth::user()->priceFormat($transaction->TransAmount)}}</td>
                                    <td>
                                        @if($transaction->status)
                                            <span class="badge bg-label-success">{{  $transaction->customer }}</span> 
                                        @else
                                            <span class="badge bg-label-warning">Unresolved</span>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->TransTime }}</td>
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