@extends('layouts/layoutMaster')

@section('title', __('Dashboard'))
@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
  'resources/assets/vendor/libs/swiper/swiper.scss',
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss',
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
])
@endsection

@section('page-style')
<!-- Page -->
@vite([
    'resources/assets/vendor/scss/pages/cards-advance.scss'])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/swiper/swiper.js',
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/apex-charts/apexcharts.js'
  ])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/app-ecommerce-dashboard.js',
  'resources/assets/js/cards-statistics.js',
  'resources/assets/js/charts-apex.js'
])
@endsection
<script>
    console.log("Script is running...");
</script>

@push('css-page')
<style>
    .apexcharts-yaxis
    {
        transform: translate(20px, 0px) !important;
    }
</style>
@endpush
<!-- @push('theme-script')
    <script src="{{ asset('assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
@endpush -->
@push('script-page')
<script>
document.addEventListener("DOMContentLoaded", function () {
    var labels = {!! json_encode($chartData['label']) !!};
    var data = {!! json_encode($chartData['data']) !!};

    // Function to dynamically get primary theme color
    function getPrimaryColor() {
        var primaryColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--bs-primary')
            .trim();

        if (!primaryColor) {
            var tempElem = document.createElement("div");
            tempElem.className = "text-primary";
            document.body.appendChild(tempElem);
            primaryColor = getComputedStyle(tempElem).color;
            document.body.removeChild(tempElem);
        }

        return primaryColor || "#007bff"; // Default to Bootstrap blue if no primary color is found
    }

    var themePrimaryColor = getPrimaryColor();

    var chartBarOptions = {
        series: [
            {
                name: '{{ __("Orders") }}',
                data: data
            },
        ],
        chart: {
            height: 300,
            type: 'area',
            dropShadow: {
                enabled: true,
                color: '#000',
                top: 18,
                left: 7,
                blur: 10,
                opacity: 0.2
            },
            toolbar: { show: false }
        },
        dataLabels: { enabled: false },
        stroke: { width: 2, curve: 'smooth' },
        title: { text: '', align: 'left' },
        xaxis: {
            categories: labels,
            title: {
                text: '{{ __("Months") }}',
                style: { color: themePrimaryColor }
            },
            labels: {
                style: {
                    colors: themePrimaryColor,
                    fontSize: "12px",
                    fontWeight: 400
                }
            }
        },
        yaxis: {
            title: {
                text: '{{ __("Orders") }}',
                style: { color: themePrimaryColor },
                offsetX: -5, // Move the title slightly left
                offsetY: 0,   // Center it properly
                rotate: -90     // Ensure the text is horizontal
            },
            labels: {
                style: {
                    colors: themePrimaryColor,
                    fontSize: "12px",
                    fontWeight: 400
                }
            }
        },
        grid: { strokeDashArray: 4 },
        legend: { show: false },
        colors: [themePrimaryColor], // Use primary theme color for the chart line
    };

    var chartContainer = document.querySelector("#chart-sales");
    if (!chartContainer) {
        console.error("Error: #chart-sales container is missing.");
        return;
    }

    var arChart = new ApexCharts(chartContainer, chartBarOptions);
    arChart.render();
});

// Server Resource Gauges
document.addEventListener("DOMContentLoaded", function() {
    // Get the theme primary color
    function getPrimaryColor() {
        var primaryColor = getComputedStyle(document.documentElement)
            .getPropertyValue('--bs-primary')
            .trim();

        if (!primaryColor) {
            var tempElem = document.createElement("div");
            tempElem.className = "text-primary";
            document.body.appendChild(tempElem);
            primaryColor = getComputedStyle(tempElem).color;
            document.body.removeChild(tempElem);
        }

        return primaryColor || "#007bff"; // Default to Bootstrap blue if no primary color is found
    }

    const themePrimaryColor = getPrimaryColor();

    function initResourceGauge(elementId, title, color) {
        return new ApexCharts(document.querySelector(elementId), {
            series: [0],
            chart: {
                height: 350,
                type: 'radialBar',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                radialBar: {
                    startAngle: -135,
                    endAngle: 135,
                    hollow: {
                        margin: 15,
                        size: '70%',
                    },
                    track: {
                        background: '#e7e7e7',
                        strokeWidth: '97%',
                        margin: 5,
                        dropShadow: {
                            enabled: true,
                            top: 2,
                            left: 0,
                            blur: 4,
                            opacity: 0.15
                        }
                    },
                    dataLabels: {
                        name: {
                            offsetY: -10,
                            color: themePrimaryColor,
                            fontSize: '13px',
                            fontWeight: 600,
                            fontFamily: 'Public Sans'
                        },
                        value: {
                            offsetY: 5,
                            color: themePrimaryColor,
                            fontSize: '25px',
                            fontWeight: 600,
                            fontFamily: 'Public Sans',
                            formatter: function(val) {
                                return val.toFixed(2) + '%';
                            }
                        }
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    shadeIntensity: 0.15,
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 50, 65, 91]
                }
            },
            stroke: {
                dashArray: 4,
                lineCap: 'round',
                width: 15
            },
            labels: [title],
            colors: [function({ value }) {
                if (value <= 30) {
                    return '#28c76f'; // Green for low usage
                } else if (value <= 60) {
                    return '#ff9f43'; // Orange for medium usage
                } else {
                    return '#ea5455'; // Red for high usage
                }
            }],
            states: {
                hover: {
                    filter: {
                        type: 'darken',
                        value: 0.9
                    }
                }
            }
        });
    }

    const cpuGauge = initResourceGauge('#cpu-gauge', 'CPU Usage', themePrimaryColor);
    const memoryGauge = initResourceGauge('#memory-gauge', 'Memory Usage', themePrimaryColor);
    const diskGauge = initResourceGauge('#disk-gauge', 'Disk Usage', themePrimaryColor);

    cpuGauge.render();
    memoryGauge.render();
    diskGauge.render();

    function updateGauges() {
        fetch('{{ url("/server-metrics") }}')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Server metrics data:', data);
                cpuGauge.updateSeries([data.cpu]);
                memoryGauge.updateSeries([data.memory]);
                diskGauge.updateSeries([data.disk]);
                
                // Update the usage info text
                document.getElementById('cpu-info').textContent = `Core Load: ${data.cpu.toFixed(2)}%`;
                document.getElementById('memory-info').textContent = `${data.memory_used} / ${data.memory_total}`;
                document.getElementById('disk-info').textContent = `${data.disk_used} / ${data.disk_total}`;
            })
            .catch(error => {
                console.error('Error fetching server metrics:', error);
            });
    }

    // Update every 5 seconds
    setInterval(updateGauges, 5000);
    updateGauges(); // Initial update
});

// Global tab fix
$(document).ready(function() {
    // Global function to handle tabs across the application
    $(document).on('click', '.tab-link', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var tabId = $(this).attr('data-tab');
        var tabContainer = $(this).closest('.row').find('.col-xl-9');
        
        // Hide all tab content in this container
        tabContainer.find('[class*="tab-content"]').hide();
        
        // Show the selected tab content
        $('#' + tabId).show();
        
        // Update active state on the links
        $(this).closest('.list-group').find('.tab-link').removeClass('active');
        $(this).addClass('active');
        
        return false;
    });
});
</script>
@endpush
@php
$admin_payment_setting = Utility::getAdminPaymentSetting();
@endphp

@section('content')

<div class="row g-6">
	<div class="col-lg-3 col-sm-6">
		<div class="card card-border-shadow-primary h-100">
			<div class="card-body">
				<div class="d-flex align-items-center mb-2">
					<div class="avatar me-4">
						<span class="avatar-initial rounded bg-label-primary"><i class='ti ti-users ti-28px'></i></span>
					</div>
					<h6 class="mb-0">{{$user->total_user}}</h6>
				</div>
				<p class="mb-1">Total Companies</p>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="card card-border-shadow-warning h-100">
			<div class="card-body">
				<div class="d-flex align-items-center mb-2">
					<div class="avatar me-4">
						<span class="avatar-initial rounded bg-label-warning"><i class='ti ti-shopping-cart-plus ti-28px'></i></span>
					</div>
					<h6 class="mb-0">{{$user->total_orders}}</h6>
				</div>
				<p class="mb-1">Total Orders</p>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="card card-border-shadow-danger h-100">
			<div class="card-body">
				<div class="d-flex align-items-center mb-2">
					<div class="avatar me-4">
						<span class="avatar-initial rounded bg-label-danger"><i class='ti ti-template ti-28px'></i></span>
					</div>
					<h6 class="mb-0">{{$user->total_plan}}</h6>
				</div>
				<p class="mb-1">Total Plans</p>
			</div>
		</div>
	</div>
	<div class="col-lg-3 col-sm-6">
		<div class="card card-border-shadow-info h-100">
			<div class="card-body">
				<div class="d-flex align-items-center mb-2">
					<div class="avatar me-4">
						<span class="avatar-initial rounded bg-label-info"><i class='ti ti-clock ti-28px'></i></span>
					</div>
					<h6 class="mb-0">{{isset($admin_payment_setting['currency_symbol']) ? $admin_payment_setting['currency_symbol'] : '$'}} {{number_format($user['total_orders_price'])}}</h6>
				</div>
				<p class="mb-1">Total Amount</p>
			</div>
		</div>
	</div>
	{{--<pre>{{ json_encode($chartData['data']) }}</pre>
<pre>{{ json_encode($chartData['label']) }}</pre>--}}

    <!-- Server Resource Gauges -->
    <div class="col-xxl-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Server Resource Utilization</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div id="cpu-gauge"></div>
                        <div class="text-center mt-2">
                            <p id="cpu-info">Core Load: 0.00%</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div id="memory-gauge"></div>
                        <div class="text-center mt-2">
                            <p id="memory-info">0 MB / 0 MB</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div id="disk-gauge"></div>
                        <div class="text-center mt-2">
                            <p id="disk-info">0 GB / 0 GB</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

  	<div class="col-xxl-12">
		<div class="card h-100">
			<div class="card-body p-0">
				<div class="row row-bordered g-0">
					<div class="col-md-12 position-relative p-6">
						<div class="card-header d-inline-block p-0 text-wrap position-absolute">
							<h5 class="mb-3 card-title">Recent Order</h5>
						</div>
						<div id="chart-sales" data-color="primary" data-height="280" class="p-3"></div>
					</div>
				</div>
			</div>
		</div>
  	</div>
</div>
@endsection
