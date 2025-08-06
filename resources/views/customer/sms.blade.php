{{-- {{ Form::open(['route' => 'customer.sms', 'method' => 'POST', 'class'=>'needs-validation', 'novalidate']) }}
{{Form::model($customer,array('route' => array('customer.sms', $customer->id), 'method' => 'POST', 'class'=>'needs-validation', 'novalidate')) }}
    <div class="modal-body">
        <div class="row g-2">
            <div class="form-group">
                {{ Form::label('sender', __('Send Via')) }}
                {{ Form::select('sender', [
                    'sms' => 'SMS', 
                    'whatsapp' => 'WhatsApp',
                    'both' => 'SMS & WhatsApp'
                ], 'sms', [
                    'class' => 'form-control', 
                    'required'
                ]) }}
            </div>
            <div id="custom_message_section" style="display: none;">
                <div class="form-group col-md-12">
                    <div class="text-light small fw-medium">Placeholders</div>
                        <div class="demo-inline-spacing">
                            <span class="badge bg-label-info">{username}</span>
                            <span class="badge bg-label-info">{contact}</span>
                            <span class="badge bg-label-info">{account}</span>
                            <span class="badge bg-label-info">{balance}</span>
                            <span class="badge bg-label-info">{amount}</span>
                            <span class="badge bg-label-info">{company}</span>
                            <span class="badge bg-label-info">{support}</span>
                            <span class="badge bg-label-info">{package}</span>
                            <span class="badge bg-label-info">{expiry}</span>
                            <span class="badge bg-label-info">{paybill}</span>
                        </div>
                    </div>
                <!-- </div> -->
                <!-- <div class="form-group col-md-12">
                    <div class="form-group mt-2" > -->
                        {{ Form::label('message', __('Custom Message')) }}
                        {{ Form::textarea('message', null, ['class' => 'form-control', 'rows' => 3]) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
        <input type="submit" value="{{ __('Send') }}" class="btn btn-primary">
    </div>
{{ Form::close() }} --}}
