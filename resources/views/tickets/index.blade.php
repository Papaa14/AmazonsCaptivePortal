@extends('layouts/layoutMaster')

@section('page-title')
    {{ __('Installation Tickets') }}
@endsection

@push('css')
<style>
    .count-card {
        transition: transform 0.3s ease;
    }
    .count-card:hover {
        transform: translateY(-5px);
    }
</style>
@endpush

@section('content')
    <div class="row g-3 mb-3">
        <!-- Tickets Statistics -->
        <div class="col-sm-6 col-xl-3">
            <div class="card count-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-bold d-block mb-1">{{ __('Resolved Tickets') }}</span>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0 me-2">{{ $tickets->where('status', 'closed')->count() }}</h3>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-ticket ti-xl"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card count-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-bold d-block mb-1">{{ __('Tickets In progress') }}</span>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0 me-2">{{ $tickets->where('status', 'in_progress')->count() }}</h3>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-clock ti-xl"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card count-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-bold d-block mb-1">{{ __('Tickets Due') }}</span>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0 me-2">{{ $tickets->where('status', 'assigned')->count() }}</h3>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-tool ti-xl"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card count-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-bold d-block mb-1">{{ __('Converted Tickets') }}</span>
                            <div class="d-flex align-items-center">
                                <h3 class="mb-0 me-2">{{ $tickets->where('status', 'converted')->count() }}</h3>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-check ti-xl"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>{{ __('Tickets') }}</h5>
                    @can('create ticket')
                        <a href="#" data-bs-toggle="modal" data-bs-target="#createTicketModal" class="btn btn-sm btn-primary">
                            <i class="ti ti-plus"></i> {{ __('Create New Ticket') }}
                        </a>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Ticket #') }}</th>
                                    <th>{{ __('Subject') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Technician') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                    <tr>
                                        <td>
                                            <a href="{{ route('tickets.show', $ticket->id) }}" class="fw-semibold text-primary">
                                                #{{ $ticket->ticket_id }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ \Str::limit($ticket->subject, 30) }}</span>
                                        </td>
                                        <td>
                                            @if($ticket->lead)
                                                {{ $ticket->lead->name }}
                                            @else
                                                {{ $ticket->customer->account ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($ticket->technician)
                                                {{ $ticket->technician->name }}
                                            @else
                                                <span class="text-muted">{{ __('Not Assigned') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($ticket->installation_date)
                                                {{ \Carbon\Carbon::parse($ticket->installation_date)->format('Y-m-d') }}
                                            @else
                                                <span class="text-muted">{{ __('Not Scheduled') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($ticket->status == 'pending')
                                                <span class="badge bg-label-warning">{{ __('Pending') }}</span>
                                            @elseif($ticket->status == 'assigned')
                                                <span class="badge bg-label-info">{{ __('Assigned') }}</span>
                                            @elseif($ticket->status == 'in_progress')
                                                <span class="badge bg-label-primary">{{ __('In Progress') }}</span>
                                            @elseif($ticket->status == 'completed')
                                                <span class="badge bg-label-success">{{ __('Completed') }}</span>
                                            @elseif($ticket->status == 'converted')
                                                <span class="badge bg-label-success">{{ __('Converted to Customer') }}</span>
                                            @elseif($ticket->status == 'cancelled')
                                                <span class="badge bg-label-danger">{{ __('Cancelled') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a class="btn btn-sm ms-1" href="{{ route('tickets.show', $ticket->id) }}">
                                                <i class="ti ti-eye me-1"></i> {{ __('View') }}
                                            </a>
                                            @if(Gate::check('delete ticket'))
                                                <form action="{{ route('tickets.destroy', $ticket->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm show_confirm" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                        <i class="ti ti-trash text-danger"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
        <!-- Create Ticket Modal -->
    <div class="modal fade" id="createTicketModal" tabindex="-1" aria-labelledby="createTicketModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTicketModalLabel">{{ __('Create New Ticket') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    {{-- <input type="hidden" name="_token" value="{{ csrf_token() }}"> --}}
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    {{ __('Customer') }} <span class="text-danger">*</span>
                                </label>
                                <select class="form-select select2" name="customer_id" required>
                                    <option value="" disabled selected>Select a customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->account }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Assign Technician') }} <span class="text-danger">*</span></label>
                                <select type="text" class="form-select" name="technician_id" required>
                                    @foreach ($technicians as $tech)
                                        <option value="{{ $tech->id }}">
                                            {{ $tech->name }} ({{ ucfirst($tech->type) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Due Date') }}</label>
                                <input type="date" name="installation_date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Due Time') }} <span class="text-danger">*</span></label>
                                <input type="time" name="installation_time" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Ticket Subject') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('Ticket Description') }}</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('Ticket Notes') }}</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-medium text-dark">
                                    Attachments
                                </label>
                                <div id="fileUploader">
                                    <div class="position-relative border border-secondary-subtle rounded py-4 px-4 text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-3">
                                            <div class="border rounded d-flex align-items-center justify-content-center text-primary fs-5" style="width: 35px; height: 35px;">
                                                <i class="ti ti-upload"></i>
                                            </div>
                                            <p class="mb-0 text-start">
                                                <strong class="text-dark">Click to upload</strong><br>
                                                your file here
                                            </p>
                                        </div>
                                        <input type="file" name="attachments[]" id="fileInput" multiple class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer">
                                    </div>
                                    <ul id="fileList" class="mt-2 list-unstyled"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Create Ticket') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5', // or 'bootstrap4' depending on your version
            placeholder: 'Select a customer',
            allowClear: true,
            width: '100%'
        });
    });
</script>
@endpush 