@extends('layouts/layoutMaster')

@section('page-title')
    {{ __('Installation Ticket Details') }}
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>{{ __('Installation Ticket Details') }}</h5>
                    <div>
                        <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-secondary">
                            <i class="ti ti-arrow-left"></i> {{ __('Back to List') }}
                        </a>
                        @if($ticket->status != 'completed' && $ticket->status != 'converted' && 
                            ($ticket->technician_id == Auth::id() || Auth::user()->can('edit ticket')))
                            <a href="{{ route('tickets.edit', $ticket->id) }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-edit"></i> {{ __('Edit') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="mb-3 text-muted">{{ __('Ticket Information') }}</h6>
                                <div class="p-3 border rounded">
                                    <p class="mb-2"><strong>{{ __('Subject:') }}</strong> {{ $ticket->subject }}</p>
                                    <p class="mb-2"><strong>{{ __('Status:') }}</strong> 
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
                                    </p>
                                    <p class="mb-2"><strong>{{ __('Created:') }}</strong> {{ \Carbon\Carbon::parse($ticket->created_at)->format('Y-m-d H:i') }}</p>
                                    <p class="mb-2"><strong>{{ __('Installation Date:') }}</strong> 
                                        {{ $ticket->installation_date ? \Carbon\Carbon::parse($ticket->installation_date)->format('Y-m-d') : 'Not scheduled' }}
                                    </p>
                                    @if($ticket->completion_date)
                                        <p class="mb-2"><strong>{{ __('Completed On:') }}</strong> 
                                            {{ \Carbon\Carbon::parse($ticket->completion_date)->format('Y-m-d H:i') }}
                                        </p>
                                    @endif
                                    <p class="mb-0"><strong>{{ __('Description:') }}</strong></p>
                                    <div class="mt-2 p-2 bg-light rounded">
                                        {{ $ticket->description ?? 'No description provided.' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="mb-3 text-muted">{{ __('Lead Information') }}</h6>
                                <div class="p-3 border rounded">
                                    @if($ticket->lead)
                                        <p class="mb-2"><strong>{{ __('Name:') }}</strong> {{ $ticket->lead->name }}</p>
                                        <p class="mb-2"><strong>{{ __('Email:') }}</strong> {{ $ticket->lead->email }}</p>
                                        <p class="mb-2"><strong>{{ __('Phone:') }}</strong> {{ $ticket->lead->phone ?? 'N/A' }}</p>
                                    @else
                                        <p class="mb-0">{{ __('Lead information not available.') }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6 class="mb-3 text-muted">{{ __('Assignment Details') }}</h6>
                                <div class="p-3 border rounded">
                                    <p class="mb-2"><strong>{{ __('Assigned To:') }}</strong> 
                                        {{ $ticket->technician ? $ticket->technician->name : 'Not assigned' }}
                                    </p>
                                    <p class="mb-0"><strong>{{ __('Created By:') }}</strong> 
                                        {{ $ticket->creator ? $ticket->creator->name : 'System' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($ticket->notes)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="mb-2 text-muted">{{ __('Additional Notes') }}</h6>
                                <div class="p-3 border rounded">
                                    {{ $ticket->notes }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($ticket->status != 'converted' && ($ticket->technician_id == Auth::id() || Auth::user()->can('edit ticket')))
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">{{ __('Update Ticket Status') }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <form action="{{ route('tickets.status.update', $ticket->id) }}" method="POST">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label" for="status">{{ __('Status') }}</label>
                                                        <select name="status" id="status" class="form-control">
                                                            <option value="pending" {{ $ticket->status == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                                            <option value="assigned" {{ $ticket->status == 'assigned' ? 'selected' : '' }}>{{ __('Assigned') }}</option>
                                                            <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>{{ __('In Progress') }}</option>
                                                            <option value="completed" {{ $ticket->status == 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                                                            <option value="cancelled" {{ $ticket->status == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="form-group mb-3">
                                                        <label class="form-label" for="notes">{{ __('Add Notes (Optional)') }}</label>
                                                        <textarea name="notes" id="notes" class="form-control" rows="1"></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary">{{ __('Update Status') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($ticket->status == 'completed' && !$ticket->status == 'converted')
                        <div class="row mt-4">
                            <div class="col-12 text-center">
                                <a href="{{ route('tickets.convert', $ticket->id) }}" class="btn btn-success btn-lg">
                                    <i class="ti ti-user-plus me-1"></i> {{ __('Convert to Customer') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection 