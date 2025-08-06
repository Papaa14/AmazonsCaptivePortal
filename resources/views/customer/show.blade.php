@extends('layouts/layoutMaster')
@push('script-page')
@endpush
@section('page-title')
    {{__('Manage Customer-Detail')}}
@endsection
<style>
    .flatpickr-calendar {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 100% !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }
    .flatpickr-innerContainer{
        width: 100% !important;
        max-width: 100% !important;
        min-width: 100% !important;
    }
    .dayContainer {
        display: flex !important;
        flex-direction: row !important;
        width: 100% !important;
        max-width: 100% !important;
        min-width: 100% !important;
    }
    
    /* Reduced font size for table data */
    .table td {
        font-size: 0.95rem;
    }
    
    /* Keep header font size normal for readability */
    .table th {
        font-size: 0.95rem;
        font-weight: 600;
    }
    
    /* Ensure DataTables styling is consistent */
    .dataTables_wrapper .table td {
        font-size: 0.95rem;
    }
    
    /* Simple fix for card display */
    @media (max-width: 767.98px) {
        .avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
        }
    }
</style>
@push('script-page')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr("#flatpickr-update", {
            enableTime: true,
            time_24hr: true,
            dateFormat: "Y-m-d H:i",
            inline: true,
            onChange: function (selectedDates) {
                if (selectedDates.length > 0) {
                    const date = new Date(selectedDates[0].getTime() - (selectedDates[0].getTimezoneOffset() * 60000));
                    const formatted = date.toISOString().slice(0, 19).replace("T", " "); // Y-m-d H:i:s
                    document.getElementById("expiry-input").value = formatted;
                }
            }
        });

        flatpickr("#flatpickr-container-update", {
            enableTime: true,
            time_24hr: true,
            dateFormat: "Y-m-d H:i",
            inline: true,
            onChange: function (selectedDates) {
                if (selectedDates.length > 0) {
                    const date = new Date(selectedDates[0].getTime() - (selectedDates[0].getTimezoneOffset() * 60000));
                    const formatted = date.toISOString().slice(0, 19).replace("T", " "); // Y-m-d H:i:s
                    document.getElementById("expiry-input-update").value = formatted;
                }
            }
        });
    });
</script>


    <script>
        let liveUsageChart = null;
        let chartData = {
            labels: [],
            downloads: [],
            uploads: []
        };
        let chartLabelColor = null;

        function updateChart() {
            // console.log('updateChart called');
            
            if (!liveUsageChart) {
                console.error('Chart not initialized');
                return;
            }

            const username = '{{ $customer->username }}';
            // console.log('Fetching data for username:', username);

            $.ajax({
                url: `/customer/${username}/live-usage`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    // console.log('AJAX success, response:', response);
                    
                    try {
                        if (response && response.timestamp && response.download !== undefined && response.upload !== undefined) {
                            // Add new data point
                            chartData.labels.push(response.timestamp);
                            chartData.downloads.push(parseFloat(response.download));
                            chartData.uploads.push(parseFloat(response.upload));

                            // Keep only last 10 points
                            if (chartData.labels.length > 10) {
                                chartData.labels.shift();
                                chartData.downloads.shift();
                                chartData.uploads.shift();
                            }

                            // console.log('Current chart data:', chartData);

                            // Update chart with all data
                            liveUsageChart.updateSeries([
                                {
                                    name: 'Downloads',
                                    data: chartData.downloads
                                },
                                {
                                    name: 'Uploads',
                                    data: chartData.uploads
                                }
                            ], true);

                            // Update x-axis labels
                            liveUsageChart.updateOptions({
                                xaxis: {
                                    categories: chartData.labels,
                                    labels: {
                                        show: true,
                                        rotate: -45,
                                        style: {
                                            colors: chartLabelColor,
                                            fontSize: '12px'
                                        }
                                    }
                                }
                            }, false, true);
                            
                            // console.log('Chart updated successfully');
                        } else {
                            console.warn('Invalid data received:', response);
                        }
                    } catch (error) {
                        console.error('Error updating chart:', error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                }
            });
        }

        $(document).ready(function () {
            // console.log('Document ready');
            
            let cardColor, labelColor, headingColor, borderColor, legendColor;

            if (isDarkStyle) {
                cardColor = config.colors_dark.cardColor;
                labelColor = config.colors_dark.textMuted;
                legendColor = config.colors_dark.bodyColor;
                headingColor = config.colors_dark.headingColor;
                borderColor = config.colors_dark.borderColor;
            } else {
                cardColor = config.colors.cardColor;
                labelColor = config.colors.textMuted;
                legendColor = config.colors.bodyColor;
                headingColor = config.colors.headingColor;
                borderColor = config.colors.borderColor;
            }

            chartLabelColor = labelColor;

            const liveUsageChartEl = document.querySelector('#liveUsageChart');
            // console.log('Chart element found:', !!liveUsageChartEl);
            
            if (!liveUsageChartEl) {
                console.error('Live usage chart element not found');
                return;
            }

            const liveUsageChartOptions = {
                chart: {
                    height: 300,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    type: 'line',
                    animations: {
                        enabled: true,
                        easing: 'linear',
                        dynamicAnimation: {
                            speed: 1000
                        }
                    }
                },
                series: [
                    {
                        name: 'Downloads',
                        data: []
                    },
                    {
                        name: 'Uploads',
                        data: []
                    }
                ],
                stroke: {
                    curve: 'smooth',
                    width: [2, 2]
                },
                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'right'
                },
                colors: [config.colors.primary, config.colors.warning],
                grid: {
                    borderColor: borderColor,
                    padding: {
                        top: 10,
                        bottom: 0,
                        left: 20
                    },
                    xaxis: {
                        lines: {
                            show: false
                        }
                    },
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                markers: {
                    size: 4,
                    strokeWidth: 0,
                    hover: {
                        size: 6
                    }
                },
                xaxis: {
                    labels: {
                        show: true,
                        rotate: -45,
                        style: { 
                            colors: chartLabelColor,
                            fontSize: '12px' 
                        }
                    },
                    axisTicks: {
                        show: true
                    },
                    axisBorder: {
                        show: true
                    }
                },
                yaxis: {
                    labels: {
                        show: true,
                        style: { 
                            colors: chartLabelColor, 
                            fontSize: '12px' 
                        },
                        formatter: function(value) {
                            return value.toFixed(2) + " Mbps";
                        }
                    },
                    axisTicks: {
                        show: true
                    },
                    axisBorder: {
                        show: true
                    },
                    tickAmount: 5,
                    min: 0,
                    forceNiceScale: true
                },
                tooltip: {
                    enabled: true,
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(value) {
                            return value.toFixed(2) + " Mbps";
                        }
                    }
                }
            };

            try {
                // console.log('Initializing chart...');
                liveUsageChart = new ApexCharts(liveUsageChartEl, liveUsageChartOptions);
                liveUsageChart.render();
                // console.log('Chart rendered successfully');
                
                // Start periodic updates
                setInterval(updateChart, 5000);
                // console.log('Update interval set');
                
                // Initial data fetch
                updateChart();
            } catch (error) {
                console.error('Error initializing chart:', error);
            }

            // Total Data Usage Chart - Bar Chart
            // --------------------------------------------------------------------
            const data = @json($monthlyTotals);
            const middleIndex = Math.floor(data.length / 2);

            const dataUsageChartEl = document.querySelector('#dataUsageChart'),
            dataUsageChartOptions = {
                series: [
                    {
                        name: "Data Used",
                        data: data
                    }
                ],
                chart: {
                    height: 300,
                    type: 'bar',
                    stacked: false,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 8,
                        startingShape: 'rounded',
                        endingShape: 'rounded'
                    }
                },
                colors: [config.colors.primary, config.colors.warning],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 0
                },
                legend: {
                    show: true,
                    position: 'top',
                    horizontalAlign: 'right'
                },
                grid: {
                    borderColor: borderColor,
                    padding: {
                        top: 10,
                        bottom: 0,
                        left: 20
                    },
                    xaxis: {
                        lines: {
                            show: false
                        }
                    },
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                xaxis: {
                    categories: [
                        "Jan",
                        "Feb",
                        "Mar",
                        "Apr",
                        "May",
                        "Jun",
                        "Jul",
                        "Aug",
                        "Sep",
                        "Oct",
                        "Nov",
                        "Dec",
                    ],
                    labels: {
                        style: {
                            fontSize: '13px',
                            colors: labelColor
                        }
                    },
                    axisTicks: {
                        show: true
                    },
                    axisBorder: {
                        show: true
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: '13px',
                            colors: labelColor
                        },
                        formatter: function(value) {
                            if (value >= 1024) {
                                return (value / 1024).toFixed(2) + " GB";
                            }
                            return value.toFixed(2) + " MB";
                        }
                    },
                    axisTicks: {
                        show: true
                    },
                    axisBorder: {
                        show: true
                    },
                    tickAmount: 5,
                    min: 0,
                    forceNiceScale: true
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            if (value >= 1024) {
                                return (value / 1024).toFixed(2) + " GB";
                            }
                            return value.toFixed(2) + " MB";
                        }
                    }
                }
            };

            if (typeof dataUsageChartEl !== undefined && dataUsageChartEl !== null) {
                const dataUsageChart = new ApexCharts(dataUsageChartEl, dataUsageChartOptions);
                dataUsageChart.render();
            }
        });
    </script>
@endpush

@section('content') 
<div class="row g-3">  
    <div class="col-sm-12 col-lg-8">
        <div class="card h-100">
            <!-- Card Header - Basic User Info -->
            <div class="card-header">
                <div class="row align-items-center">
                    <!-- Avatar and Name -->
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <div class="d-flex align-items-center">
                            <div class="avatar {{ $online ? 'avatar-online' : '' }} me-3">
                                <div class="rounded d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background-color: #007bff; color: white; font-size: 16px; font-weight: bold;">
                                    {{ strtoupper(substr(explode(' ', $customer->fullname)[0], 0, 1)) }}{{ strtoupper(substr(explode(' ', $customer->fullname)[1] ?? '', 0, 1)) }}
                                </div>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">{{ $customer->fullname ?? 'N/A' }}</h5>
                                <small class="text-muted">{{ $customer['email'] ?? 'N/A' }}</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Connection Info -->
                    <div class="col-12 col-md-6">
                        <div class="row text-md-end">
                            <div class="col-4 col-md-12 mb-1">
                                <small class="text-muted me-1">MAC:</small>
                                <small class="text-body">{{ $customer['mac_address'] ?? 'N/A' }}</small>
                            </div>
                            <div class="col-4 col-md-12 mb-1">
                                <small class="text-muted me-1">IP:</small>
                                <small class="text-body">{{ optional($session)->ip ?? 'N/A' }}</small>
                            </div>
                            <div class="col-4 col-md-12">
                                <small class="text-muted me-1">Device:</small>
                                <small class="text-body">{{ $deviceVendor }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
                <!-- All Controls in Single Row -->
                <div class="row align-items-center">
                    <div class="col-12">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <!-- Username -->
                            <div class="text-center">
                                <a data-bs-toggle="offcanvas" data-bs-target="#offcanvasEnd" aria-controls="offcanvasEnd" style="cursor: pointer;">
                                    <div>
                                        <span class="{{ $customer->is_active == 1 ? 'text-success' : 'text-danger' }} fw-bold fs-5">
                                            {{ $customer['username'] }}
                                        </span>
                                        @if($customer->corporate == 1)
                                            <div class="bg-danger text-white rounded"><small>Corporate</small></div>
                                        @elseif($customer->is_active == 0)
                                            <div class="bg-danger text-white"><small>Deactivated</small></div>
                                        @endif
                                    </div>
                                </a>
                            </div>
                            
                            <!-- Balance -->
                            <div class="text-center">
                                <h6 class="mb-0">{{$balance}}</h6>
                                <small>Balance</small>
                            </div>
                            
                            <!-- Use Balance Button -->
                            <div>
                                <button class="btn bg-label-primary btn-md" data-bs-toggle="modal" data-bs-target="#useBalance">
                                    Use Balance
                                </button>
                            </div>

                            @if($customer->service == "PPPoE")
                            <!-- Action Buttons -->
                            <form action="{{ route('customer.deactivate', $customer->id) }}" method="POST" class="m-0">
                                @csrf
                                @method('POST')
                                <button type="submit"  onclick="return confirm('This will {{ $customer->is_active == 1 ? 'Deactivate' : 'Activate' }} Customer Account?')" class="btn {{ $customer->is_active == 1 ? 'bg-label-success' : 'bg-label-danger' }} btn-md">
                                    {{ $customer->is_active == 1 ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <form action="{{ route('customer.corporate', $customer->id) }}" method="POST" class="m-0">
                                @csrf
                                @method('POST')
                                <button type="submit" onclick="return confirm('{{ $customer->corporate == 1 ? 'Revert to Normal Account' : 'Convert Customer to Corporate' }} ?')" class="btn {{ $customer->corporate == 1 ? 'bg-label-success' : 'bg-label-warning' }} btn-md">
                                    Corporate
                                </button>
                            </form>
                            <button type="button" class="btn bg-label-info btn-md" data-bs-toggle="modal" data-bs-target="#overridePackageModal">
                                Override Package
                            </button>
                            <a href="{{ route('customer.addChild', $customer->id) }}" class="btn bg-label-info btn-md">
                                Add Child Account
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Add horizontal line -->
                <hr class="my-3 border-secondary opacity-25">
                
                <!-- Customer Details and Child Accounts -->
                <div class="row">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <!-- <h6 class="mb-2">Account Details</h6> -->
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tbody>
                                    <!-- <tr>
                                        <th width="40%">Service</th>
                                        <td>{{$customer['service']}}</td>
                                    </tr> -->
                                    <tr>
                                        <th>Old Username</th>
                                        <td>{{$customer['username']}}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            @if($customer->is_active == 1)
                                                <span class="text-success">Active</span>
                                            @else
                                                <span class="badge bg-warning">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <!-- @if($customer->service == "PPPoE")
                                    <tr>
                                        <th>Location</th>
                                        <td>{{$customer['location']}}</td>
                                    </tr>
                                    @endif -->
                                    @if($customer->isChild())
                                    <tr>
                                        <th>Parent Account</th>
                                        <td>
                                            <a href="{{ route('customer.show', ['customer' => encrypt($customer->parent->id)]) }}">
                                                {{ $customer->parent->account }} ({{ $customer->parent->fullname }})
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Expiry Link</th>
                                        <td>
                                            @if($customer->inherit_expiry)
                                                <span class="text-success">Linked to parent</span>
                                            @else
                                                <span class="text-warning">Independent</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    <!-- <tr>
                                        <th>Created On</th>
                                        <td>{{ \Carbon\Carbon::parse($customer['created_at'])->format('Y-m-d') }}</td>
                                    </tr> -->
                                    <tr>
                                        <th>Phone No</th>
                                        <td>{{$customer['contact']}}</td>
                                    </tr>
                                    <tr>
                                        <th>Expiry</th>
                                        <td>{{$customer['expiry']}}</td>
                                    </tr>
                                    @if($customer->is_override)
                                    <tr>
                                        <th>Override Package</th>
                                        <td>{{$customer['override_download']}}{{$customer['override_download_unit']}}/{{$customer['override_upload']}}{{$customer['override_upload_unit']}}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    @if($customer->service == "PPPoE")
                    <div class="col-12 col-md-6">
                        @if($customer->children && $customer->children->count() > 0)
                        <!-- <h6 class="mb-2">Child Accounts</h6> -->
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Username</th>
                                        <th>Password</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customer->children as $child)
                                    <tr>
                                        <td>
                                            <a href="{{ route('customer.show', ['customer' => encrypt($child->id)]) }}">
                                                {{ $child->username }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="text-nowrap">{{ $child->password }}</span>
                                        </td>
                                        <td>
                                            @if($child->is_active == 1)
                                                <span class="badge bg-label-success">Active</span>
                                            @else
                                                <span class="badge bg-label-warning">Expired</span>
                                            @endif
                                        </td>
                                        <td>
                                            {!! Form::open(['method' => 'DELETE', 'route' => ['customer.destroy', $child['id']], 'id' => 'delete-form-' . $child['id'], 'style' => 'display:inline']) !!}
                                                <button class="text-danger" data-bs-toggle="tooltip" title="{{ __('Delete child') }}"
                                                data-confirm="{{ __('Are you sure you want to delete this child account?') }}">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            {!! Form::close() !!}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <h6 class="mb-2">Child Accounts</h6>
                        <div class="text-muted">No child accounts found</div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12 col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="profile-statistics">
                    <div class="">
                        <div class="row g-2">
                            <div class="col-6">
                                <form action="{{ route('customer.refresh', $customer->id) }}" method="POST">
                                    @csrf
                                    @method('POST') 
                                    <button type="submit" onclick="return confirm('This will Refresh Customer Account?')" class="btn bg-label-success btn-md btn-block w-100">Refresh</button>
                                </form>
                            </div>
                            <div class="col-6">
                                <a href="javascript:extend('1672')" class="btn bg-label-success btn-md btn-block w-100" data-bs-toggle="modal" data-bs-target="#extendExp">Extend</a>
                            </div>
                        </div>
                        <div class="row mb-3 g-2">
                            <div class="col-6">
                                <button type="button" class="btn bg-label-primary btn-md btn-block w-100" data-bs-toggle="modal" data-bs-target="#depositCash">Deposit</button>
                            </div>
                            <div class="col-6">
                                <button class="btn bg-label-primary btn-md btn-block w-100" type="button" data-bs-toggle="modal" data-bs-target="#changePlan">Change Plan</button>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <form action="{{ route('customer.clearmac', $customer->id) }}" method="POST">
                                    @csrf
                                    @method('POST') 
                                    <button type="submit" class="btn bg-label-info btn-md btn-block w-100">Clear Mac</button>
                                </form>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn bg-label-warning btn-md btn-block w-100" data-bs-toggle="modal" data-bs-target="#sendSms">Send SMS</button>
                            </div>
                        </div>
                        
                        <hr class="my-3 border-light">
                        
                        <div class="row mb-3 g-2">
                            <div class="col-6">
                                @if($customer->is_suspended)
                                    <a href="{{ route('customer.unsuspend', $customer->id) }}" class="btn bg-label-success btn-md btn-block w-100">Resume</a>
                                @else
                                    <a href="{{ route('customer.suspend', $customer->id) }}" class="btn bg-label-danger btn-md btn-block w-100">Pause</a>
                                @endif

                            </div>
                            <div class="col-6">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#updateExp" class="btn bg-label-danger btn-md btn-block w-100">
                                    {{__('Edit Expiry')}}
                                </a>
                            </div>
                        </div>
                        <a href="" class="btn bg-label-info btn-md btn-block w-100 mb-3" data-bs-toggle="modal" data-bs-target="#resolvePay">Resolve Payment</a>
                        @if($customer->service == "PPPoE")
                        @can('create invoice')
                            <a href="{{ route('invoice.create', $customer->id) }}" class="btn bg-label-success btn-md btn-block w-100">
                                {{ __('Create Invoice') }}
                            </a>
                        @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-md-12 mt-4">
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-auto">
                            <span class="d-block text-muted mb-1">Uptime</span>
                            <h5 class="mb-0">
                                @php
                                    if ($online) {
                                        echo '<span class="text-success">Online: ' . $displayUptime . '</span>';
                                    } else {
                                        echo '<span class="text-danger">Offline: ' . $displayUptime . '</span>';
                                    }
                                @endphp
                            </h5>
                        </div>
                        <div class="avatar ms-3">
                            <span class="avatar-initial bg-primary rounded">
                                <i class="ti ti-clock ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-auto">
                            <span class="d-block text-muted mb-1">Data Used</span>
                            <h5 class="mb-0">
                                @if ($customer->service != 'Hotspot')
                                    @if($monthData)
                                        @if($monthData->upload >= 1073741824 || $monthData->download >= 1073741824) 
                                            {{ number_format($monthData->download / 1073741824, 1) }}/{{ number_format($monthData->upload / 1073741824, 1) }}GB
                                        @else
                                            {{ number_format($monthData->download / 1048576, 1) }}/{{ number_format($monthData->upload / 1048576, 1) }}MB
                                        @endif
                                    @else
                                        <p>N/A</p>
                                    @endif
                                @else
                                    @if($customer->used_data != null)
                                        @if($customer->used_data >= 1073741824) 
                                            {{ number_format($customer->used_data / 1073741824, 1) }}GB
                                        @else
                                            {{ number_format($customer->used_data / 1048576, 1) }}MB
                                        @endif
                                    @else
                                        <p>N/A</p>
                                    @endif
                                @endif
                            </h5>
                        </div>
                        <div class="avatar ms-3">
                            <span class="avatar-initial bg-danger rounded">
                                <i class="ti ti-download ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-auto">
                            <span class="d-block text-muted mb-1">Package</span>
                            <h5 class="mb-0 text-truncate">{{$customer['package']}}</h5>
                        </div>
                        <div class="avatar ms-3">
                            <span class="avatar-initial bg-success rounded">
                                <i class="ti ti-package ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-auto">
                            <span class="d-block text-muted mb-1">Days Left</span>
                            <h5 class="mb-0 text-truncate">{{ $expiryStatus }}</h5>
                        </div>
                        <div class="avatar ms-3">
                            <span class="avatar-initial bg-warning rounded">
                                <i class="ti ti-calendar ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-12">
        <div class="nav-align-top mb-6">
            <ul class="nav nav-pills mb-4 nav-fill col-xl-12" role="tablist">
                <li class="nav-item mb-1 mb-sm-0">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" 
                        data-bs-target="#navs-pills-justified-pppoe" aria-controls="navs-pills-justified-pppoe" aria-selected="true">
                        <span class="d-none d-sm-block"><i class="tf-icons ti ti-brand-google-analytics ti-sm me-1_5 align-text-bottom"></i>Trafic</span>
                        <i class="ti ti-brand-google-analytics ti-sm d-sm-none"></i>
                    </button>
                </li>
                <li class="nav-item mb-1 mb-sm-0">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" 
                        data-bs-target="#navs-pills-justified-static" aria-controls="navs-pills-justified-static" aria-selected="false">
                        <span class="d-none d-sm-block"><i class="tf-icons ti ti-cash-register ti-sm me-1_5 align-text-bottom"></i>Transactions</span>
                        <i class="ti ti-cash-register ti-sm d-sm-none"></i>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" 
                        data-bs-target="#navs-pills-justified-dhcp" aria-controls="navs-pills-justified-dhcp" aria-selected="false">
                        <span class="d-none d-sm-block"><i class="tf-icons ti ti-invoice ti-sm me-1_5 align-text-bottom"></i>Invoices</span>
                        <i class="ti ti-invoice ti-sm d-sm-none"></i>
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" 
                        data-bs-target="#navs-pills-justified-logs" aria-controls="navs-pills-justified-logs" aria-selected="false">
                        <span class="d-none d-sm-block"><i class="tf-icons ti ti-logs ti-sm me-1_5 align-text-bottom"></i>Customer Logs</span>
                        <i class="ti ti-logs ti-sm d-sm-none"></i>
                    </button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-pills-justified-pppoe" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Realtime Trafic</h5>
                                    <div class="card-tools pull-right"></div>
                                </div>
                                <div class="card-body py-3">
                                    <div id="liveUsageChart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Monthly Data Usage</h5>
                                    <div class="card-tools pull-right"></div>
                                </div>
                                <div class="card-body border-radius-none">
                                    <div  id="dataUsageChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="navs-pills-justified-static" role="tabpanel">
                    <div class="pt-3">
                        <div class="table-responsive">
                            <table class="table datata table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{__('Trans ID')}}</th>
                                        <th>{{__('Mpesa Code')}}</th>
                                        <th>{{__('Amount')}}</th>
                                        <th>{{__('Phone Number')}}</th>
                                        <th>{{__('Package ID')}}</th>
                                        <th>{{__('Date')}}</th>
                                        <th>{{__('Method')}}</th>
                                        <th>{{__('Status')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transactions as $transaction)
                                        <tr>
                                            <td><span class="fw-semibold">{{ $transaction->payment_id ?: '-' }}</span></td>
                                            <td><span class="fw-semibold">{{ $transaction->mpesa_code ?: '-' }}</span></td>
                                            <td class="fw-semibold">{{ Auth::user()->priceFormat($transaction->amount) }}</td>
                                            <td>{{ $transaction->phone ?: '-' }}</td>
                                            <td>
                                                @if($transaction->package)
                                                    {{ $transaction->package->name_plan }}
                                                @else
                                                    <span class="text-danger">No Package</span>
                                                @endif
                                            </td>
                                            <td>{{ Auth::user()->dateFormat($transaction->date) }}</td>
                                            <td>{{ $transaction->gateway ?: '-' }}</td>
                                            <td>
                                                @if($transaction->status == 1)
                                                    <span class="badge bg-label-success">Completed</span>
                                                @elseif($transaction->status == 0)
                                                    <span class="badge bg-label-warning">Pending</span>
                                                @else
                                                    <span class="badge bg-label-secondary">Unknown</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="navs-pills-justified-dhcp" role="tabpanel">
                    <div class="pt-3">
                        <div class="table-responsive">
                            <table class="table datatab table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{__('Invoice #')}}</th>
                                        <th>{{__('Plan')}}</th>
                                        <th>{{__('Amount')}}</th>
                                        <th>{{__('Issue Date')}}</th>
                                        <th>{{__('Due Date')}}</th>
                                        <th>{{__('Status')}}</th>
                                        <th>{{__('Action')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoices as $invoice)
                                        <tr>
                                            <td>
                                                <a href="{{ route('invoice.show', $invoice->id) }}" class="text-primary fw-semibold">
                                                    {{ $invoice->invoice_id }}
                                                </a>
                                            </td>
                                            <td>{{ $invoice->ref_number }}</td>
                                            <td class="fw-semibold">{{ Auth::user()->priceFormat($invoice->getTotal()) }}</td>
                                            <td>{{ Auth::user()->dateFormat($invoice->issue_date) }}</td>
                                            <td>{{ Auth::user()->dateFormat($invoice->due_date) }}</td>
                                            <td>
                                                @if($invoice->status == 0)
                                                    <span class="badge bg-label-success">{{__('Paid')}}</span>
                                                @elseif($invoice->status == 1)
                                                    @if($invoice->due_date < date('Y-m-d'))
                                                        <span class="badge bg-label-danger">{{__('Overdue')}}</span>
                                                    @else
                                                        <span class="badge bg-label-warning">{{__('Unpaid')}}</span>
                                                    @endif
                                                @elseif($invoice->status == 2)
                                                    <span class="badge bg-label-info">{{__('Partially Paid')}}</span>
                                                @elseif($invoice->status == 3)
                                                    <span class="badge bg-label-secondary">{{__('Cancelled')}}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button type="button" class="btn btn-sm dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="{{ route('invoice.show', $invoice->id) }}">
                                                            <i class="ti ti-eye me-1"></i> {{__('View')}}
                                                        </a>
                                                        @if($invoice->status != 0)
                                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#payInvoice-{{$invoice->id}}">
                                                                <i class="ti ti-cash me-1"></i> {{__('Pay')}}
                                                            </a>
                                                        @endif
                                                        {{-- <a class="dropdown-item" href="{{ route('invoice.pdf', $invoice->id) }}" target="_blank">
                                                            <i class="ti ti-file-download me-1"></i> {{__('Download')}}
                                                        </a> --}}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="navs-pills-justified-logs" role="tabpanel">
                    <div class="pt-3">
                        <div class="table-responsive">
                            <table class="table datatabl table-bordered">
                                <thead>
                                    <tr>
                                        <th>
                                            Username
                                        </th>
                                        <th>
                                            Password
                                        </th>
                                        <th>
                                            Site
                                        </th>
                                        <th>
                                            Mac Address
                                        </th>
                                        {{-- <th>
                                            Interface
                                        </th> --}}
                                        <th>
                                            Log
                                        </th>
                                        <th>
                                            Date
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($authLogs as $session)
                                        <tr>
                                            <td>{{ $session->username }}</td>
                                            <td>{{ $session->pass }}</td>
                                            <td>{{ $session->nasipaddress }}</td>
                                            <td>{{ $session->mac }}</td>
                                            {{-- <td>{{ $session->nasportid }}</td> --}}
                                            <td>@if( $session->username != $customer->username )
                                                    Wrong Username - Added to Disabled Pool
                                                @elseif( $session->pass != $customer->password )
                                                    Wrong Password - Added to Disabled Pool
                                                @elseif( $session->mac != $customer->mac_address )
                                                    New Mac Detected - Added to Disabled Pool
                                                @else
                                                    {{ $session->reply }}
                                                @endif
                                            </td>
                                            <td>{{ $session->authdate }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Payment Modals -->
{{-- @foreach ($invoices as $invoice)
    @if($invoice->status != 0)
    <div class="modal fade" id="payInvoice-{{$invoice->id}}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{__('Pay Invoice')}} #{{ $invoice->invoice_id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('customer.payment', $customer->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                        
                        <div class="mb-3">
                            <label class="form-label">{{__('Amount Due')}}</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ Auth::user()->priceFormat($invoice->getDue()) }}" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" for="payment_amount">{{__('Payment Amount')}}</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="payment_amount" name="amount" value="{{ $invoice->getDue() }}" min="1" max="{{ $invoice->getDue() }}" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" for="payment_type">{{__('Payment Method')}}</label>
                            <select class="form-select" id="payment_type" name="payment_type" required>
                                <option value="Cash">{{__('Cash')}}</option>
                                <option value="Bank">{{__('Bank Transfer')}}</option>
                                <option value="MPESA">{{__('MPESA')}}</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" for="payment_date">{{__('Payment Date')}}</label>
                            <input type="date" class="form-control" id="payment_date" name="date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label" for="payment_notes">{{__('Notes')}}</label>
                            <textarea class="form-control" id="payment_notes" name="notes" rows="2"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">{{__('Make Payment')}}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach --}}

<!-- Update Expiry Date -->
<div class="modal fade" id="updateExp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Expiry Date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('customer.updateExpiry', $customer->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <div id="flatpickr-container-update"></div>
                        <input type="hidden" name="expiry" id="expiry-input-update">
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!--/ Update Expiry Date -->

<!-- Extend Expiry Date -->
<div class="modal fade" id="extendExp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Extend Expiry Date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('customer.updateExtend', $customer->id) }}" method="POST">
                    @csrf
                    <div id="flatpickr-update"></div> 
                    <input type="hidden" name="extend_expiry" id="expiry-input">
                    <button class="btn btn-primary w-100 mt-3" type="submit">Extend</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!--/ Extend Expiry Date -->

<!-- Deposit Balance -->
<div class="modal fade" id="depositCash" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel2">Deposit Cash</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('customer.depositCash', $customer->id) }}" method="POST">
                    @csrf
                    <div class="col-12">
                        <label class="form-label" for="cash">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">Ksh</span>
                            <input type="number" id="balance" name="balance" class="form-control" placeholder="1000" required min="-100000"/>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--/ Deposit Balance -->

<!-- Resolve Payment -->
<div class="modal fade" id="resolvePay" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel2">Resolve Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('customer.resolvePayment', $customer->id) }}" method="POST">
                    @csrf
                    <div class="col-12">
                        <label class="form-label" for="resolve">Mpesa Code</label>
                        <input type="text" id="mpesacode" name="mpesacode" class="form-control" placeholder="MHT6GHJJ"/>
                    </div>
                    {{-- <div class="input-group mt-3">
                        <span class="input-group-text">Ksh</span>
                        <input type="number" id="amount" name="amount" class="form-control" placeholder="1000" required min="1"/>
                    </div> --}}
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--/ Resolve Payment -->

<!-- Send SMS -->
<div class="modal fade" id="sendSms" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel2">Send SMS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('customer.sms', $customer->id) }}" method="POST">
                    @csrf
                        <div class="form-group col-md-12">
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
                        <div class="form-group col-md-12">
                            <div class="text-light small fw-medium">Placeholders</div>
                                <div class="demo-inline-spacing">
                                    <span class="badge bg-label-info">{fullname}</span>
                                    <span class="badge bg-label-info">{username}</span>
                                    <span class="badge bg-label-info">{account}</span>
                                    <span class="badge bg-label-info">{balance}</span>
                                    {{-- <span class="badge bg-label-info">{amount}</span> --}}
                                    <span class="badge bg-label-info">{company}</span>
                                    <span class="badge bg-label-info">{support}</span>
                                    <span class="badge bg-label-info">{package}</span>
                                    <span class="badge bg-label-info">{expiry}</span>
                                    {{-- <span class="badge bg-label-info">{paybill}</span> --}}
                                </div>
                                {{ Form::label('message', __('Custom Message')) }}
                                {{ Form::textarea('message', null, ['class' => 'form-control', 'rows' => 3]) }}
                        </div>
                        <div class="form-group mt-2">
                            <button type="submit" class="btn btn-primary">Submit</button>
                            <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--/ Send SMS -->

<!-- Use Balance -->
<div class="modal fade" id="useBalance" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel2">Use Balance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('customer.useBalance', $customer->id) }}" method="POST">
                    @csrf
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label" for="type">Payment For:</label>
                            <select id="type" class="form-select" name="type">
                                <option value="installation">Installation Fee</option>
                                <option value="package">Package Renewal</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-group mt-3">
                        <span class="input-group-text">Ksh</span>
                        <input type="number" id="amount" name="amount" class="form-control" placeholder="1000" required min="1"/>
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--/ Use Balance -->

<!-- Change Plan -->
<div class="modal fade" id="changePlan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel2">Change Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('customer.changePlan', $customer->id) }}" method="POST">
                    @csrf
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="package" class="form-label">
                                Service
                            </label>
                            <select name="package" required
                                class="form-select">
                                <option>Select Package</option>
                                @foreach ($arrPackage as $package)
                                    <option value="{{ $package->id }}">{{ $package->name_plan }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--/ Change Plan -->

<!-- Edit Profile -->
<div class="col-lg-3 col-md-6">
    <div class="mt-4">
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEnd" aria-labelledby="offcanvasEndLabel">
            <div class="offcanvas-header">
                <h5 id="offcanvasEndLabel" class="offcanvas-title">Edit Customer Details</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body my-auto mx-0 flex-grow-0">
            <form action="{{ route('customer.update', $customer->id) }}" method="POST">
                @csrf
                @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" for="fullname">Full Name</label>
                        <input type="text" id="fullname" class="form-control" placeholder="John Doe" value="{{$customer['fullname']}}" aria-label="John Doe" name="fullname" />
                    </div>
                    <div class="row">
                        <div class="mb-3 col-6">
                            <label class="form-label" for="account">Account</label>
                            <input type="text" readonly  class="form-control" placeholder="John" name="account" id="account" value="{{$customer['account']}}" required  aria-label="John" />
                        </div>
                        <div class="mb-3 col-6">
                            <label class="form-label" for="username">Old Username</label>
                            <input type="text"  class="form-control" placeholder="Doe"  name="username" id="username" value="{{$customer['username']}}" aria-label="Doe" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-6">
                            <label class="form-label" for="pppoe_password">Secret</label>
                            <input type="text" id="password" class="form-control" placeholder="12345678" value="{{$customer['password']}}" aria-label="12345678" name="password" />
                        </div>
                        <div class="mb-3  col-6">
                            <label class="form-label" for="phonenumber">Phone Number</label>
                            <input type="text" id="contact" class="form-control phone-mask" placeholder="+254712345678" value="{{$customer['contact']}}" aria-label="+254712345678" name="contact" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="text" id="email" class="form-control" placeholder="john.doe@example.com" value="{{$customer['email']}}" aria-label="john.doe@example.com" name="email" />
                    </div>
                    <div class="row">
                        <div class="mb-3 col-6">
                            <label class="form-label" for="housenumber">House Number</label>
                            <input type="text" id="housenumber" class="form-control" placeholder="B6" value="{{$customer['housenumber']}}" aria-label="housenumber" name="housenumber" />
                        </div>
                        <div class="mb-3 col-6">
                            <label class="form-label" for="apartment">Apartmnent</label>
                            <input type="text" id="apartment" class="form-control" placeholder="Future Flats" aria-label="apartment" value="{{$customer['apartment']}}" name="apartment" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="location">Location</label>
                        <input type="text" id="location" class="form-control" placeholder="Ruiru" aria-label="location" value="{{$customer['location']}}" name="location" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="service">Service Type</label>
                        <input type="text" id="service" class="form-control" value="{{$customer['service']}}" name="service" readonly/>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="service">
                            Service
                        </label>
                        <select name="package" required
                            class="form-select">
                            @foreach ($arrPackage as $package)
                                <option value="{{ $package->id }}" {{ $customer->package_id === $package->id ? 'selected' : '' }}>{{ $package->name_plan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary me-3">Submit</button>
                    <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Edit Profile -->

<!-- Override Package -->
<div class="modal fade" id="overridePackageModal" tabindex="-1" aria-labelledby="overridePackageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="overridePackageModalLabel">Override Package Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('customer.overridePackage', ['id' => encrypt($customer->id)]) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_override" name="is_override" {{ $customer->is_override ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_override">Enable Package Override</label>
                        </div>
                    </div>

                    <div id="overrideSettings" class="{{ $customer->is_override ? '' : 'd-none' }}">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="override_download" class="form-label">Download Speed</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="override_download" name="override_download" value="{{ $customer->override_download }}" min="1">
                                    <select class="form-select" id="override_download_unit" name="override_download_unit">
                                        <option value="K" {{ $customer->override_download_unit == 'K' ? 'selected' : '' }}>Kbps</option>
                                        <option value="M" {{ $customer->override_download_unit == 'M' ? 'selected' : '' }}>Mbps</option>
                                        <option value="G" {{ $customer->override_download_unit == 'G' ? 'selected' : '' }}>Gbps</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="override_upload" class="form-label">Upload Speed</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="override_upload" name="override_upload" value="{{ $customer->override_upload }}" min="1">
                                    <select class="form-select" id="override_upload_unit" name="override_upload_unit">
                                        <option value="K" {{ $customer->override_upload_unit == 'K' ? 'selected' : '' }}>Kbps</option>
                                        <option value="M" {{ $customer->override_upload_unit == 'M' ? 'selected' : '' }}>Mbps</option>
                                        <option value="G" {{ $customer->override_upload_unit == 'G' ? 'selected' : '' }}>Gbps</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isOverrideCheckbox = document.getElementById('is_override');
    const overrideSettings = document.getElementById('overrideSettings');
    
    isOverrideCheckbox.addEventListener('change', function() {
        overrideSettings.classList.toggle('d-none', !this.checked);
    });
});
</script>
@endsection
@push('script-page')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script>
    $(document).ready(function() {
        // Initialize DataTables with consistent options and no duplicate headers/footers
        $('.datatabl, .datatab, .datata').each(function() {
            $(this).DataTable({
                fixedHeader: true,
                paging: true,
                ordering: true,
                info: true,
                searching: true,
                lengthChange: true,
                pageLength: 10,
                language: {
                    paginate: {
                        previous: '<i class="ti ti-chevron-left"></i>',
                        next: '<i class="ti ti-chevron-right"></i>'
                    }
                }
            });
        });
    });
</script>
@endpush


