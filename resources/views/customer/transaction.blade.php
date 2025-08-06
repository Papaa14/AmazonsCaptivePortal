@extends('layouts/layoutMaster')
@push('script-page')
<script>
$(document).ready(function() {
    var dataTable = $('.transaction-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('customer.transaction.data') }}",
            type: "POST",
            data: function(d) {
                d._token = "{{ csrf_token() }}";
                d.date = $('#date').val();
                d.category = $('#category').val();
            }
        },
        columns: [
            {data: 'date', name: 'date'},
            {data: 'amount', name: 'amount'},
            {data: 'account', name: 'account'},
            {data: 'type', name: 'type'},
            {data: 'category', name: 'category'},
            {data: 'description', name: 'description'},
        ],
        language: {
            processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        drawCallback: function(settings) {
            // Any additional functionality after the table is drawn
        }
    });
    
    $('#search-form').on('submit', function(e) {
        e.preventDefault();
        dataTable.draw();
    });
    
    $('.reset-btn').on('click', function(e) {
        e.preventDefault();
        $('#date').val('');
        $('#category').val('');
        dataTable.draw();
    });
});
</script>
@endpush
@section('page-title')
    {{__('Transaction')}}
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="row d-flex justify-content-end mt-2">
                        <form id="search-form">
                            <div class="row">
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                    <div class="all-select-box">
                                        <div class="btn-box">
                                            {{ Form::label('date', __('Date'),['class'=>'text-type']) }}
                                            {{ Form::text('date', isset($_GET['date'])?$_GET['date']:null, array('class' => 'form-control datepicker-range', 'id' => 'date')) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                    <div class="all-select-box">
                                        <div class="btn-box">
                                            {{ Form::label('category', __('Category'),['class'=>'text-type']) }}
                                            {{ Form::select('category',  [''=>'All']+$category,isset($_GET['category'])?$_GET['category']:'', array('class' => 'form-control select2', 'id' => 'category')) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto my-auto">
                                    <button type="submit" class="apply-btn" data-toggle="tooltip" data-original-title="{{__('apply')}}">
                                        <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                    </button>
                                    <a href="#" class="reset-btn" data-toggle="tooltip" data-original-title="{{__('Reset')}}">
                                        <span class="btn-inner--icon"><i class="ti ti-trash-restore-alt"></i></span>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped mb-0 transaction-table">
                            <thead>
                            <tr>
                                <th> {{__('Date')}}</th>
                                <th> {{__('Amount')}}</th>
                                <th> {{__('Account')}}</th>
                                <th> {{__('Type')}}</th>
                                <th> {{__('Category')}}</th>
                                <th> {{__('Description')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
