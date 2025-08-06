@extends('layouts/layoutMaster')

@section('title')
    {{ __('Available Plans') }}
@endsection

@push('css-page')
<style>
    .plan-card {
        margin-bottom: 30px;
        padding: 0 15px;
    }
    
    @media (max-width: 768px) {
        .plan-card {
            padding: 0 10px;
            margin-bottom: 20px;
        }
    }
</style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">{{ __('Available Plans') }}</h4>
                    <a href="{{ route('plans.index') }}" class="btn btn-primary">
                        <i class="ti ti-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($plans as $plan)
                            @if($plan->price > 0)
                                <div class="col-md-4 plan-card">
                                    @include('plan.plan-card')
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 