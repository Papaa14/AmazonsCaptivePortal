{{ Form::open(['url' => 'vouchers', 'class' => 'needs-validation', 'novalidate']) }}
    <div class="modal-body">
        <div class="row g-2">
            <div class="form-group col-md-12">
                {{ Form::label('code', __('Code Type'), ['class' => 'form-label']) }}<x-required></x-required>
                <div class="d-flex radio-check">
                    <div class="form-check form-check-inline form-group col-md-6">
                        <input type="radio" id="manual_code" value="manual" name="code_type" class="form-check-input code">
                        <label class="custom-control-label" for="manual_code">{{ __('Manual') }}</label>
                    </div>
                    <div class="form-check form-check-inline form-group col-md-6">
                        <input type="radio" id="auto_code" value="auto" name="code_type" class="form-check-input code" checked="checked">
                        <label class="custom-control-label" for="auto_code">{{ __('Auto Generate') }}</label>
                    </div>
                </div>
            </div>

            <div class="form-group col-md-12 d-none" id="manual">
                <input class="form-control font-uppercase" name="manualCode" type="text" id="manual-code" placeholder="{{ __('Enter Code') }}">
            </div>
            <div class="form-group col-md-6 d-block" id="auto">
                {{ Form::label('quantity', __('Number of Vouchers'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('quantity', null, ['class' => 'form-control', 'placeholder' => __('Enter Number of Vouchers')]) }}
            </div>

            <div class="form-group col-md-6 d-block" id="auto">
                {{ Form::label('devices', __('Number of Devices'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::number('devices', 1, ['class' => 'form-control',  'placeholder' => __('Enter Number of Devices Allowed')]) }}
            </div>

            <div class="form-group col-md-12">
                {{ Form::label('package', __('Select Package'), ['class' => 'form-label']) }}<x-required></x-required>
                {!! Form::select('package', array_combine($arrPackage, $arrPackage), null, ['class' => 'form-control select', 'required' => 'required']) !!}
            </div>

            <div class="form-group col-md-12">
                <label for="is_compensation" class="form-label">{{ __('Compensation') }}</label>
                <div class="form-check form-switch custom-switch-v1 float-end">
                    <input type="checkbox" name="is_compensation" class="form-check-input input-primary pointer" value="1" id="is_compensation">
                    <label class="form-check-label" for="is_compensation"></label>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
    </div>
{{ Form::close() }}
