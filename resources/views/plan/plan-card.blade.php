@php
    $isCurrentPlan = \Auth::user()->type == 'company' && \Auth::user()->plan == $plan->id;
    $isSuperAdmin = \Auth::user()->type == 'super admin';
@endphp

@if(\Auth::user()->type === 'super admin' || (\Auth::user()->type === 'company' && $plan->is_visible && $plan->price > 0))
    <div class="col-xl-3 col-lg-3 order-1 order-md-0">
        <div class="card mb-6 border border-2 border-primary rounded primary-shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <span class="badge bg-primary">{{ $plan->name }}</span>
                    <div class="d-flex justify-content-center">
                        <sub class="h5 pricing-currency mb-auto mt-1 text-primary">
                            {{ isset($admin_payment_setting['currency_symbol']) ? $admin_payment_setting['currency_symbol'] : 'Ksh' }}
                        </sub>
                        <h3 class="mb-0 text-primary">{{ number_format($plan->price) }}</h3>
                        <sub class="h6 pricing-duration mt-auto mb-3 fw-normal">
                            /{{ __(\App\Models\Plan::$arrDuration[$plan->duration]) }}
                        </sub>
                    </div>
                </div>

                @if(\Auth::user()->type == 'company' && \Auth::user()->plan == $plan->id)
                    <div class="d-flex flex-row-reverse m-0 p-0 active-tag">
                        <span class="align-items-right">
                            <i class="f-10 lh-1 fas fa-circle text-primary"></i>
                            <span class="ms-2">{{ __('Active') }}</span>
                        </span>
                    </div>
                @endif

                <ul class="list-unstyled g-2 my-6">
                    <li class="mb-2 d-flex align-items-center">
                        <i class="ti ti-checks ti-18px text-primary me-2"></i>
                        <span>{{ __('Both PPPoE & Hotspot') }}</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="ti ti-checks ti-18px text-primary me-2"></i>
                        <span>{{ $plan->max_customers == -1 ? __('Unlimited') : $plan->max_customers }} {{ __('Customers') }}</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="ti ti-checks ti-18px text-primary me-2"></i>
                        <span>{{ __('Free Mikrotik Remote Access') }}</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="ti ti-checks ti-18px text-primary me-2"></i>
                        <span>{{ __('Hotspot Mpesa StK Push') }}</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="ti ti-checks ti-18px text-primary me-2"></i>
                        <span>{{ __('PPPoE Mpesa StK Push') }}</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="ti ti-checks ti-18px text-primary me-2"></i>
                        <span>{{ __('Multiple Payments Gateways') }}</span>
                    </li>
                </ul>

                @if(\Auth::user()->type == 'super admin')
                    <div class="d-flex align-items-center justify-content-center">
                        <a title="{{ __('Edit Plan') }}" href="#" class="btn btn-primary btn-icon m-1 badge"
                            data-url="{{ route('plans.edit', $plan->id) }}" data-ajax-popup="true"
                            data-title="{{ __('Edit Plan') }}" data-size="lg" data-toggle="tooltip"
                            data-original-title="{{ __('Edit') }}">
                            <i class="ti ti-edit"></i>
                        </a>
                        {!! Form::open([
                            'method' => 'DELETE',
                            'route' => ['plans.destroy', $plan->id],
                            'id' => 'delete-form-' . $plan->id,
                        ]) !!}
                        <a href="#!" class="bs-pass-para btn-icon mx-2 btn btn-danger btn-icon m-1 badge" data-bs-toggle="tooltip"
                            data-bs-original-title="{{ __('Delete') }}">
                            <i class="ti ti-trash"></i>
                        </a>
                        {!! Form::close() !!}
                    </div>
                @endif

                @if(\Auth::user()->type != 'super admin')
                    <div class="request-btn">
                        <div class="d-flex align-items-center">
                            @if($plan->id != \Auth::user()->plan && $plan->price > 0)
                                <a href="{{ route('stripe', \Illuminate\Support\Facades\Crypt::encrypt($plan->id)) }}"
                                    class="btn btn-lg btn-primary m-2">{{ __('Subscribe') }}</a>
                            @endif
                        </div>
                    </div>
                @endif

                @if(\Auth::user()->type == 'company')
                    @if(\Auth::user()->trial_expire_date && \Auth::user()->trial_plan == $plan->id)
                        <p class="display-total-time mb-0">
                            {{ __('Plan Trial Expiry : ') }}
                            {{ !empty(\Auth::user()->trial_expire_date) ? \Auth::user()->dateFormat(\Auth::user()->trial_expire_date) : 'lifetime' }}
                        </p>
                    @elseif(\Auth::user()->plan == $plan->id)
                        <p class="display-total-time mb-0">
                            {{ __('Plan Expiry : ') }}
                            {{ !empty(\Auth::user()->plan_expire_date) ? \Auth::user()->dateFormat(\Auth::user()->plan_expire_date) : 'lifetime' }}
                        </p>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endif
