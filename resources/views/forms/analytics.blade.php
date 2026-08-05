@extends('layouts.dashboard')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Navigation & Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-custom me-3 py-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-1">{{ $form->title }} Analytics</h4>
                <p class="text-secondary small mb-0">Overview of visitor view counts, completion time averages, and choice distributions.</p>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="d-flex gap-2">
            <a href="{{ route('forms.submissions.index', $form->id) }}" class="btn btn-outline-custom py-2 px-3 shadow-sm rounded-3">
                <i class="bi bi-list-task me-1"></i> Submissions List
            </a>
            <a href="{{ route('forms.edit', $form->id) }}" class="btn btn-outline-custom py-2 px-3 shadow-sm rounded-3">
                <i class="bi bi-pencil-square me-1"></i> Edit Builder
            </a>
        </div>
    </div>

    <!-- Analytics Dashboard Cards -->
    <div class="row g-4 mb-5">
        <!-- Views -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-card-icon me-3 bg-primary-subtle text-primary" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <i class="bi bi-eye"></i>
                    </div>
                    <span class="text-secondary small fw-bold">TOTAL VIEWS</span>
                </div>
                <h2 class="fw-bold mb-1 text-dark">{{ number_format($views) }}</h2>
                <p class="small text-muted mb-0">Unique views generated.</p>
            </div>
        </div>

        <!-- Submissions -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-card-icon me-3 bg-success-subtle text-success" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <i class="bi bi-file-earmark-check"></i>
                    </div>
                    <span class="text-secondary small fw-bold">SUBMISSIONS</span>
                </div>
                <h2 class="fw-bold mb-1 text-dark">{{ number_format($submissionsCount) }}</h2>
                <p class="small text-muted mb-0">Completed answers saved.</p>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-card-icon me-3 bg-warning-subtle text-warning" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <i class="bi bi-percent"></i>
                    </div>
                    <span class="text-secondary small fw-bold">CONVERSION RATE</span>
                </div>
                <h2 class="fw-bold mb-1 text-dark">{{ $conversionRate }}%</h2>
                <p class="small text-muted mb-0">Views converted to answers.</p>
            </div>
        </div>

        <!-- Avg Time -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-card-icon me-3 bg-info-subtle text-info" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <i class="bi bi-stopwatch"></i>
                    </div>
                    <span class="text-secondary small fw-bold">AVG FILL TIME</span>
                </div>
                <h2 class="fw-bold mb-1 text-dark">{{ $avgDuration }}s</h2>
                <p class="small text-muted mb-0">Average stopwatch completion.</p>
            </div>
        </div>
    </div>

    <!-- Chart & Choices breakdown row -->
    <div class="row g-4">
        <!-- Submissions Trend (Chart.js) -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-graph-up text-indigo me-2"></i>Submission Volume Trend</h5>
                <div style="height: 300px; position: relative;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Audit/Latency logs -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-clock-history text-indigo me-2"></i>Recent Version Changes</h5>
                <div class="timeline-activity">
                    @forelse($form->versions()->latest()->take(4)->get() as $version)
                        <div class="d-flex mb-3 pb-3 border-bottom border-light">
                            <div class="me-3">
                                <span class="badge bg-indigo-subtle text-indigo rounded-pill px-2.5 py-1 fw-bold">
                                    v{{ $version->version_number }}
                                </span>
                            </div>
                            <div>
                                <p class="mb-1 small fw-semibold text-secondary">
                                    Saved by {{ $version->creator ? $version->creator->name : 'System/AI' }}
                                </p>
                                <span class="fs-8 text-muted d-block">
                                    {{ $version->created_at->diffForHumans() }} • {{ count($version->schema['fields'] ?? []) }} fields
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5 small">
                            No version records found.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Choice Field Breakdown metrics -->
    @if(count($choiceFieldsAnalytics) > 0)
        <div class="row mt-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-pie-chart text-indigo me-2"></i>Answer Choices Distribution</h5>
                    <div class="row g-4">
                        @foreach($choiceFieldsAnalytics as $key => $analytics)
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-4 p-4 h-100 bg-light bg-opacity-25 shadow-sm">
                                    <h6 class="fw-bold text-dark mb-1">{{ $analytics['label'] }}</h6>
                                    <span class="badge bg-secondary-subtle text-secondary small rounded-pill mb-3" style="font-size: 0.72rem;">
                                        {{ strtoupper($analytics['type']) }}
                                    </span>
                                    
                                    @php
                                        $totalResponses = array_sum($analytics['data']);
                                    @endphp

                                    @if($totalResponses > 0)
                                        @foreach($analytics['data'] as $option => $count)
                                            @php
                                                $percentage = round(($count / $totalResponses) * 100);
                                            @endphp
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1 small">
                                                    <span class="text-secondary fw-semibold">{{ $option }}</span>
                                                    <span class="text-muted fw-bold">{{ $count }} responses ({{ $percentage }}%)</span>
                                                </div>
                                                <div class="progress rounded-pill" style="height: 8px; background-color: #e2e8f0;">
                                                    <div class="progress-bar bg-indigo rounded-pill" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted small py-4 text-center mb-0">No response options submitted yet.</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('trendChart').getContext('2d');
        
        // Setup Chart
        const labels = {!! json_encode($trendLabels) !!};
        const dataValues = {!! json_encode($trendData) !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Submissions',
                    data: dataValues,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: '#64748b'
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#64748b'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
