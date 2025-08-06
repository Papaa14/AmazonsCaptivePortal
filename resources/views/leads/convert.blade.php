@extends('layouts/layoutMaster')

@section('page-title')
    {{__('Convert Lead to Installation Ticket')}}
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Convert Lead to Installation Ticket') }}</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('lead.store.ticket', $lead->id) }}" class="needs-validation" novalidate>
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">{{ __('Lead Information') }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex flex-column">
                                            <div class="mb-2">
                                                <span class="fw-bold">{{ __('Name') }}:</span> 
                                                <span>{{ $lead->name }}</span>
                                            </div>
                                            <div class="mb-2">
                                                <span class="fw-bold">{{ __('Email') }}:</span> 
                                                <span>{{ $lead->email ?? 'N/A' }}</span>
                                            </div>
                                            <div class="mb-2">
                                                <span class="fw-bold">{{ __('Phone') }}:</span> 
                                                <span>{{ $lead->phone }}</span>
                                            </div>
                                            <div class="mb-2">
                                                <span class="fw-bold">{{ __('Location') }}:</span> 
                                                <span>{{ $lead->location }}</span>
                                            </div>
                                            <div>
                                                <span class="fw-bold">{{ __('Notes') }}:</span> 
                                                <span>{{ $lead->notes ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">{{ __('Installation Ticket Details') }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Assign Technician') }} <span class="text-danger">*</span></label>
                                            <select class="form-select" name="technician_id" required>
                                                <option value="">{{ __('Select Technician') }}</option>
                                                @foreach($technicians as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">
                                                {{ __('Please select a technician') }}
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Installation Date') }} <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="installation_date" required min="{{ date('Y-m-d') }}">
                                            <div class="invalid-feedback">
                                                {{ __('Please select a valid installation date') }}
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Installation Time') }} <span class="text-danger">*</span></label>
                                            <input type="time" class="form-control" name="installation_time" required>
                                            <div class="invalid-feedback">
                                                {{ __('Please select an installation time') }}
                                            </div>
                                        </div>
                                        
                                        <div class="mb-0">
                                            <label class="form-label">{{ __('Additional Notes for Technician') }}</label>
                                            <textarea class="form-control" name="notes" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <a href="{{ route('leads.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('Create Installation Ticket') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all forms we want to apply validation to
            var forms = document.querySelectorAll('.needs-validation')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
@endsection 