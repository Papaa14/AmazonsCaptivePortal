@php
    $userPlan = \App\Models\Plan::getPlan(\Auth::user()->show_dashboard());
@endphp
{{Form::open(array('url'=>'roles','method'=>'post', 'class'=>'needs-validation', 'novalidate'))}}
<div class="modal-body">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                {{Form::label('name',__('Name'),['class'=>'form-label'])}}<x-required></x-required>
                {{Form::text('name',null,array('class'=>'form-control','placeholder'=>__('Enter Role Name'),'required' => 'required'))}}
                @error('name')
                <small class="invalid-name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </small>
                @enderror
            </div>
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="staff" role="tabpanel" aria-labelledby="pills-home-tab">
                    @php
                        $modules=['dashboard','message', 'report','ticket','lead','voucher', 'customer', 'invoice', 'transaction', 'package', 'sites', 'company settings','user','role','revenue','payment'];
                        $dashboardPermissions = [
                            'show company dashboard'
                        ];
                    //    if(\Auth::user()->type == 'company'){
                    //        $modules[] = 'permission';
                    //    }
                    @endphp
                    <div class="col-md-12">
                        <div class="form-group">
                            @if(!empty($permissions))
                                <h6 class="my-3">{{__('Assign General Permission to Roles')}}</h6>
                                <table class="table table-striped mb-0" id="dataTable-1">
                                    <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" class="form-check-input" name="staff_checkall"  id="staff_checkall" >
                                        </th>
                                        <th>{{__('Module')}} </th>
                                        <th>{{__('Permissions')}} </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($modules as $module)
                                        <tr>
                                            <td><input type="checkbox" class="form-check-input ischeck staff_checkall"  data-id="{{str_replace(' ', '', str_replace('&', '', $module))}}" ></td>
                                            <td><label class="ischeck staff_checkall" data-id="{{str_replace(' ', '', str_replace('&', '', $module))}}">{{ ucfirst($module) }}</label></td>
                                            <td>
                                                <div class="row ">
                                                    @if($module == 'dashboard')
                                                        @foreach($dashboardPermissions as $permission)
                                                            @if($key = array_search($permission, $permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,ucwords(str_replace('show ', '', str_replace(' dashboard', '', $permission))),['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        @if(in_array('view '.$module,(array) $permissions))
                                                            @if($key = array_search('view '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '',  str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'View',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif

                                                        @if(in_array('add '.$module,(array) $permissions))
                                                            @if($key = array_search('add '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Add',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif

                                                        @if(in_array('move '.$module,(array) $permissions))
                                                            @if($key = array_search('move '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Move',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif

                                                        @if(in_array('manage '.$module,(array) $permissions))
                                                            @if($key = array_search('manage '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Manage',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('create '.$module,(array) $permissions))
                                                            @if($key = array_search('create '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Create',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('edit '.$module,(array) $permissions))
                                                            @if($key = array_search('edit '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Edit',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('delete '.$module,(array) $permissions))
                                                            @if($key = array_search('delete '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Delete',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('show '.$module,(array) $permissions))
                                                            @if($key = array_search('show '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Show',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif


                                                        @if(in_array('send '.$module,(array) $permissions))
                                                            @if($key = array_search('send '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Send',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif

                                                        @if(in_array('create payment '.$module,(array) $permissions))
                                                            @if($key = array_search('create payment '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Create Payment',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('delete payment '.$module,(array) $permissions))
                                                            @if($key = array_search('delete payment '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Delete Payment',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('income '.$module,(array) $permissions))
                                                            @if($key = array_search('income '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Income',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('expense '.$module,(array) $permissions))
                                                            @if($key = array_search('expense '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Expense',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('income vs expense '.$module,(array) $permissions))
                                                            @if($key = array_search('income vs expense '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Income VS Expense',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('loss & profit '.$module,(array) $permissions))
                                                            @if($key = array_search('loss & profit '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Loss & Profit',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('tax '.$module,(array) $permissions))
                                                            @if($key = array_search('tax '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Tax',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif

                                                        @if(in_array('invoice '.$module,(array) $permissions))
                                                            @if($key = array_search('invoice '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Invoice',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('bill '.$module,(array) $permissions))
                                                            @if($key = array_search('bill '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Bill',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('duplicate '.$module,(array) $permissions))
                                                            @if($key = array_search('duplicate '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Duplicate',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('balance sheet '.$module,(array) $permissions))
                                                            @if($key = array_search('balance sheet '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Balance Sheet',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('ledger '.$module,(array) $permissions))
                                                            @if($key = array_search('ledger '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Ledger',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if(in_array('trial balance '.$module,(array) $permissions))
                                                            @if($key = array_search('trial balance '.$module,$permissions))
                                                                <div class="col-md-3 custom-control custom-checkbox">
                                                                    {{Form::checkbox('permissions[]',$key,false, ['class'=>'form-check-input isscheck staff_checkall isscheck_'.str_replace(' ', '', str_replace('&', '', $module)),'id' =>'permission'.$key])}}
                                                                    {{Form::label('permission'.$key,'Trial Balance',['class'=>'custom-control-label'])}}<br>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Create')}}" class="btn  btn-primary">
</div>

{{Form::close()}}

<script>
    $(document).ready(function () {
        $("#staff_checkall").click(function(){
            $('.staff_checkall').not(this).prop('checked', this.checked);
        });
    });
</script>
