{{Form::model($nas->shortname,,array('route' => array('nas.update', $nas->id), 'method' => 'PUT', 'class'=>'needs-validation', 'novalidate')) }}
<div class="modal-body">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                    {{ Form::label('site_name', __('Site Name'), ['class' => 'form-label']) }}<x-required></x-required>
                    {{ Form::text('site_name', $nas->shortname, ['class' => 'form-control', 'required' => true, 'placeholder' => __('Enter Site Name')]) }}
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Update') }}" class="btn btn-primary"> <!-- Changed from "Create" to "Update" -->
    </div>
{{ Form::close() }}
