    {{Form::model($plan, array('route' => array('plans.update', $plan->id), 'method' => 'PUT', 'enctype' => "multipart/form-data", 'class'=>'needs-validation', 'novalidate')) }}
    <div class="modal-body">
        {{-- start for ai module--}}
        @php
            $settings = \App\Models\Utility::settings();
        @endphp
        @if(!empty($settings['chat_gpt_key']))
        <div class="text-end">
            <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true" data-url="{{ route('generate',['plan']) }}"
               data-bs-placement="top" data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"></i> <span>{{__('Generate with AI')}}</span>
            </a>
        </div>
        @endif
        {{-- end for ai module--}}

    <div class="row">
        <div class="form-group col-md-6">
            {{Form::label('name',__('Name'),['class'=>'form-label'])}}<x-required></x-required>
            {{Form::text('name',null,array('class'=>'form-control font-style','placeholder'=>__('Enter Plan Name'),'required'=>'required'))}}
        </div>
        @if($plan->price > 0)
            <div class="form-group col-md-6">
                {{Form::label('price',__('Price'),['class'=>'form-label'])}}<x-required></x-required>
                {{Form::number('price',null,array('class'=>'form-control','placeholder'=>__('Enter Plan Price'),'required'=>'required' ,'step' => '0.01'))}}
            </div>
            <div class="form-group col-md-6">
                {{ Form::label('duration', __('Duration'),['class'=>'form-label']) }}<x-required></x-required>
                {!! Form::select('duration', $arrDuration, null,array('class' => 'form-control select','required'=>'required')) !!}
            </div>
        @endif
        <div class="form-group col-md-6">
            {{ Form::label('max_customers', __('Maximum Customers'), ['class' => 'form-label']) }}<x-required></x-required>
            {{ Form::number('max_customers', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Maximum Customers'), 'min' => '-1']) }}
            <small class="form-text text-muted">{{ __('Use -1 for unlimited customers') }}</small>
        </div>


        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'),['class'=>'form-label']) }}
            {!! Form::textarea('description', null, ['class'=>'form-control','rows'=>'2', 'placeholder' => __('Enter Description')]) !!}
        </div>

        <div class="col-md-6">
            <label for="is_visible" class="form-label">{{ __('Plan Visibilty(on/off)') }}</label>
            <div class="form-check form-switch custom-switch-v1 float-end">
                <input type="hidden" name="is_visible" value="0">
                <input type="checkbox" name="is_visible" class="form-check-input input-primary pointer" value="1" id="is_visible" {{ $plan['is_visible'] == 1 ? 'checked="checked"' : '' }}>
                <label class="form-check-label" for="is_visible"></label>
            </div>
        </div>
    </div>
    </div>

    <div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Update')}}" class="btn  btn-primary">
</div>
    {{ Form::close() }}

