{{ Form::open(['route' => 'sms.bulk.send', 'method' => 'POST', 'class'=>'needs-validation', 'novalidate']) }}
    <div class="modal-body">
        <div class="row g-2">
            <div class="form-group">
                {{ Form::label('sender', __('Send Via')) }}<x-required></x-required>
                {{ Form::select('sender', [
                    'sms' => 'SMS', 
                    'whatsapp' => 'WhatsApp',
                    'both' => 'SMS & WhatsApp'
                ], 'sms', [ // 'sms' is the default value
                    'class' => 'form-control', 
                    'required'
                ]) }}
            </div>
            <div class="form-group">
                {{ Form::label('service', __('Customer Service')) }}<x-required></x-required>
                {{ Form::select('service', [
                    'all' => 'All Customers', 
                    'Hotspot' => 'Hotspot Customers', 
                    'PPPoE' => 'PPPoE Customers'
                ], 'all', [
                    'class' => 'form-control', 
                    'required'
                ]) }}
            </div>
            <div class="form-group mt-2" id="site_section">
                {{ Form::label('site_id', __('Select Site')) }}<x-required></x-required>
                {{-- {{ Form::select('site_id', $sites->pluck('name', 'id'), null, ['class' => 'form-control']) }} --}}
                <select name="site_id" class="form-control">
                    <option value="0" {{ empty(old('site_id', $site_id ?? '')) ? 'selected' : '' }}>All Sites</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}" {{ old('site_id', $site_id ?? '') == $site->id ? 'selected' : '' }}>
                            {{ $site->name }}
                        </option>
                    @endforeach
                </select>
                
            </div>
            <div class="form-group">
                {{ Form::label('category', __('Customer Type')) }}<x-required></x-required>
                {{ Form::select('category', [
                    'all' => 'All Customers', 
                    'expired' => 'Expired Customers', 
                    'active' => 'Active Customers', 
                    'suspended' => 'Suspended Customers', 
                    'disabled' => 'Disabled Customers', 
                    'new' => 'New Customers'
                ], 'all', [ // 'all' is the default value
                    'class' => 'form-control', 
                    'required'
                ]) }}
            </div>
            <div class="form-group mt-3">
                {{ Form::label('mesage_type', __('Message Type')) }}
                {{ Form::select('message_type', [
                    '' => 'Select message type',
                    'template' => 'Use Template', 
                    'custom' => 'Custom Message'
                ], null, [
                    'class' => 'form-control', 
                    'required', 
                    'onchange' => "document.getElementById('template_section').style.display = this.value === 'template' ? 'block' : 'none';
                                document.getElementById('custom_message_section').style.display = this.value === 'custom' ? 'block' : 'none';"
                ]) }}
            </div>
            <div class="form-group mt-2" id="template_section" style="display: none;">
                {{ Form::label('template_id', __('Select Template')) }}<x-required></x-required>
                {{ Form::select('template_id', $smsTemplates->pluck('type', 'id'), null, ['class' => 'form-control']) }}
            </div>
            <div id="custom_message_section" style="display: none;">
                <div class="form-group col-md-12">
                    <div class="text-light small fw-medium">Placeholders</div>
                        <div class="demo-inline-spacing">
                            <span class="badge bg-label-info">{fullname}</span>
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
                        {{ Form::label('message', __('Custom Message')) }}<x-required></x-required>
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
{{ Form::close() }}
