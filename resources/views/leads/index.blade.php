@extends('layouts/layoutMaster')
@section('page-title')
    {{__('Manage Leads')}}
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{asset('css/summernote/summernote-bs4.css')}}">
@endpush
@section('content')

    <div class="row g-3 mb-6">
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card filter-card h-100">
                <div class="card-body cursor-pointer">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">Total Leads</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2"> {{ $leads->count() }}</h4>
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
                            <span class="text-heading">Pending Leads</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $leads->where('status', 'new')->count() }}</h4>
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
                            <span class="text-heading">Converted Leads</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $leads->where('status', 'converted')->count() }}</h4>
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
                            <span class="text-heading">Lost Leads</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $leads->where('status', 'lost')->count() }}</h4>
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

    <div class="row g-3">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Leads') }}</h5>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#createLeadModal" class="btn btn-sm btn-primary">
                        <i class="ti ti-plus"></i> {{ __('Create New Lead') }}
                    </a>
                </div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table ">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Phone') }}</th>
                                    <th>{{ __('Location') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Created By') }}</th>
                                    <th>{{ __('Created On') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leads as $lead)
                                    <tr>
                                        <td>
                                            {{-- <a href="@can('view lead'){{ route('leads.show', $lead->id) }}@else#@endcan"> --}}
                                                {{ $lead->name }}
                                            {{-- </a> --}}
                                        </td>
                                        <td>{{ $lead->email ?? '-' }}</td>
                                        <td>{{ $lead->phone ?? '-' }}</td>
                                        <td>{{ $lead->location ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $lead->status == 'new' ? 'primary' : 
                                                ($lead->status == 'contacted' ? 'info' : 
                                                ($lead->status == 'qualified' ? 'warning' : 
                                                ($lead->status == 'converted' ? 'success' : 'danger'))) }}">
                                                {{ ucfirst($lead->status) }}
                                            </span>
                                        </td>
                                        <td>@ {{ $lead->by ?? '-' }}</td>
                                        <td>{{ $lead->created_at ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex">
                                                @if($lead->status != 'converted' && Auth::user()->can('edit lead'))
                                                    <a href=""  data-bs-toggle="modal" data-bs-target="#convertLeadModal" class="btn btn-sm ms-1" data-bs-toggle="tooltip" title="{{ __('Convert to Ticket') }}">
                                                        <i class="ti ti-ticket text-success"></i>
                                                    </a>
                                                @endif
                                                
                                                @if(Gate::check('delete lead'))
                                                    <form action="{{ route('leads.destroy', $lead->id) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm show_confirm" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                            <i class="ti ti-trash text-danger"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $leads->onEachSide(2)->links('vendor.pagination.rounded') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Lead Modal -->
    <div class="modal fade" id="createLeadModal" tabindex="-1" aria-labelledby="createLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createLeadModalLabel">{{ __('Create New Lead') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('leads.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Lead Name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Lead Email') }}</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Lead Phone') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="phone" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Lead Location') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="location" required>
                            </div>
                             <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Created By') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="by" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Created On') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="date" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Create Lead') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if(isset($lead))
    <!-- Convert Lead Modal -->
    <div class="modal fade" id="convertLeadModal" tabindex="-1" aria-labelledby="convertLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="convertLeadModalLabel">{{ __('Convert Lead to Ticket') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('lead.store.ticket', $lead->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Assign Technician') }} <span class="text-danger">*</span></label>
                                <select type="text" class="form-select" name="technician_id" required>
                                    @foreach ($technicians as $tech)
                                        <option value="{{ $tech->id }}">
                                            {{ $tech->name }} ({{ ucfirst($tech->type) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Installation Date') }}</label>
                                <input type="date" name="installation_date" class="form-control">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('Installation Time') }} <span class="text-danger">*</span></label>
                                <input type="time" name="installation_time" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Convert Lead') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection 