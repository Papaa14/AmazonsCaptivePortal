@extends('layouts/layoutMaster')
@section('page-title')
    {{__('Manage sites')}}
@endsection
@push('script-page')
<script>
    function fetchNasStatus() {
        $.ajax({
            url: "{{ route('nas.check') }}",
            method: "GET",
            success: function (response) {
                if (!response.nas || !Array.isArray(response.nas)) {
                    console.error("Invalid response format", response);
                    return;
                }

                response.nas.forEach(nas => {
                    const badgeClass = nas.status === 'online' ? 'bg-success' : 'bg-danger';
                    const badge = `<span class="badge ${badgeClass}">${nas.status}</span>`;

                    const statusCell = $(`#nas-status-${nas.id}`);
                    if (statusCell.length) {
                        statusCell.html(badge);
                    }
                });
            },
            error: function (xhr, status, error) {
                console.error("Failed to fetch NAS status", error);
            }
        });
    }

    $(document).ready(function () {
        fetchNasStatus();
        setInterval(fetchNasStatus, 10000); // every 10 seconds
    });
</script>

@endpush
@section('content')
    <div class="row">
        <div class="float-end d-flex  mb-3">
            @can('create nas')
                <a href="#" data-size="sm" data-url="{{ route('nas.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip" title="{{__('Create')}}" data-title="{{__('Create Site')}}" class="btn btn-sm btn-primary me-2">
                    <i class="ti ti-plus"></i> {{__('Create Site')}}
                </a>
            @endcan
        </div>

            <div class="col-md-12">
                <div class="card">
                    <div class="card-body table-border-style">
                        <h5></h5>
                        <div class="table-responsive">
                        <table id="nas-status-table" class="table datatable">
                            <thead>
                                <tr>
                                    <th>{{ __('Site Name') }}</th>
                                    <th>{{ __('IP Address') }}</th>
                                    <th>{{ __('Secret') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if (Gate::check('edit nas') || Gate::check('delete nas') || Gate::check('show nas'))
                                        <th>{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($nases as $k=>$nas)
                                    <tr data-ip="{{ $nas->nasname }}">
                                        <td>{{$nas->shortname}}</td>
                                        <td>{{$nas->nasname}}</td>
                                        <td>{{ $nas['secret']}}</td>
                                        <td>
                                            @if ($nas['nasapi'] == 1)
                                                <span >{{ __('Radius') }}</span>
                                            @else
                                                <span >{{ __('Radius') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($nas['status'] == "Online")
                                                <span class="badge bg-label-success">{{ __('Online') }}</span>
                                            @else
                                                <span class="badge bg-label-danger">{{ __('Offline') }}</span>
                                            @endif
                                        </td>

                                        @if (Gate::check('edit nas') || Gate::check('delete nas') || Gate::check('show nas'))
                                            <td class="Action">
                                                <span>
                                                    @can('show nas')
                                                        <a href="{{ route('nas.show', \Crypt::encrypt($nas['id'])) }}" 
                                                        data-bs-toggle="tooltip" title="{{ __('View NAS') }}" class="btn btn-sm btn-primary">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                    @endcan
                                                    @can('edit nas')
                                                        {{-- <a href="#" class="" data-url="{{ route('nas.edit',$nas['id']) }}" data-ajax-popup="true"  data-size="sm" data-bs-toggle="tooltip" title="{{__('Edit')}}"  data-title="{{ __('Edit NAS') }}">
                                                            <i class="ti ti-pencil"></i>
                                                        </a> --}}
                                                        <form method="POST" action="{{ route('nas.reboot', $nas->id) }}" style="display:inline;" onsubmit="return confirm('Reboot this NAS?')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="{{ __('Reboot Nas') }}">
                                                                <i class="ti ti-power"></i>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                    {{-- @can('delete nas')
                                                        {!! Form::open(['method' => 'DELETE', 'route' => ['nas.destroy', $nas['id']], 'id' => 'delete-form-' . $nas['id'], 'style' => 'display:inline']) !!}
                                                            <a href="#" class="" data-bs-toggle="tooltip" title="{{ __('Delete NAS') }}"
                                                            data-confirm="{{ __('Are you sure you want to delete this NAS?') }}"
                                                            data-confirm-yes="document.getElementById('delete-form-{{ $nas->id }}').submit();">
                                                                <i class="ti ti-trash"></i>
                                                            </a>
                                                        {!! Form::close() !!}
                                                    @endcan --}}
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
    </div>
@endsection
