@extends('layouts.dashboard')

@section('title', 'Submissions - ' . $form->title)
@section('header_title', 'Form Submissions')

@section('content')
<div class="container-fluid px-0">
    <!-- Action Bar -->
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-custom me-3 py-2">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <div>
            <h4 class="fw-bold mb-1">{{ $form->title }}</h4>
            <span class="text-muted small"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Total Responses: {{ $submissions->total() }}</span>
        </div>
    </div>

    <!-- Search and Export Cockpit -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 p-4">
        <form method="GET" action="{{ route('forms.submissions.index', $form->id) }}" class="row g-3">
            <div class="col-12 col-md-8">
                <div class="position-relative">
                    <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" class="form-control ps-5" placeholder="Search submissions by answer content or IP address..." value="{{ $search }}" style="border-radius: 10px;">
                </div>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-custom w-50 justify-content-center">
                    Filter
                </button>
                @if($search)
                    <a href="{{ route('forms.submissions.index', $form->id) }}" class="btn btn-light w-50 border justify-content-center text-decoration-none d-flex align-items-center">
                        Clear
                    </a>
                @endif
                <button type="button" class="btn btn-gradient-primary w-50 justify-content-center d-flex align-items-center" onclick="window.showToast('CSV Export', 'CSV Export will be active in Module 10.')">
                    <i class="bi bi-download me-1"></i> Export
                </button>
            </div>
        </form>
    </div>

    <!-- Submissions Table Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-0">
            @php
                // Extract first 3 non-section fields for tabular display
                $displayFields = collect($form->schema['fields'] ?? [])->where('type', '!=', 'section')->take(3);
            @endphp

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 text-secondary text-uppercase fs-7 fw-bold" style="width: 80px;">ID</th>
                            <!-- Dynamic headers matching the schema fields -->
                            @foreach($displayFields as $f)
                                <th class="py-3 text-secondary text-uppercase fs-7 fw-bold">{{ Str::limit($f['label'], 25) }}</th>
                            @endforeach
                            <th class="py-3 text-secondary text-uppercase fs-7 fw-bold">Submitted At</th>
                            <th class="py-3 text-secondary text-uppercase fs-7 fw-bold text-center">Duration</th>
                            <th class="py-3 text-secondary text-uppercase fs-7 fw-bold">IP Address</th>
                            <th class="pe-4 py-3 text-secondary text-uppercase fs-7 fw-bold text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $sub)
                            <tr>
                                <td class="ps-4 fw-semibold text-secondary">#{{ $sub->id }}</td>
                                
                                <!-- Dynamic answers mapped in the columns -->
                                @foreach($displayFields as $f)
                                    @php
                                        $ans = $sub->answers->firstWhere('field_key', $f['key']);
                                        $val = $ans ? $ans->answer_value : '';
                                        
                                        if ($f['type'] === 'checkbox' && $val) {
                                            $decoded = json_decode($val, true);
                                            if (is_array($decoded)) $val = implode(', ', $decoded);
                                        } elseif ($f['type'] === 'file' && $val) {
                                            $val = basename($val);
                                        }
                                    @endphp
                                    <td class="text-truncate" style="max-width: 180px;">{{ Str::limit($val, 35) ?: '-' }}</td>
                                @endforeach

                                <td><span class="small text-secondary">{{ $sub->created_at->format('Y-m-d H:i') }}</span></td>
                                <td class="text-center">
                                    @if($sub->duration_seconds)
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border-0 px-2 py-1 fw-medium">
                                            {{ $sub->duration_seconds < 60 ? $sub->duration_seconds . 's' : round($sub->duration_seconds / 60) . 'm' }}
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td><code class="small text-dark">{{ $sub->ip_address }}</code></td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-outline-custom" onclick="viewSubmissionDetails({{ $sub->id }})">
                                        <i class="bi bi-eye me-1"></i> View Answers
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($displayFields) + 5 }}" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="bi bi-database-exclamation fs-2"></i></div>
                                    <h6 class="fw-bold">No submissions found</h6>
                                    <p class="text-secondary small mb-0">Wait for public users to fill out your form or clear your filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination Links -->
        @if($submissions->hasPages())
            <div class="card-footer bg-white border-0 py-3 rounded-bottom-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Showing {{ $submissions->firstItem() }} to {{ $submissions->lastItem() }} of {{ $submissions->total() }} entries
                    </div>
                    <div>
                        {{ $submissions->appends(['search' => $search])->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal: Submission Details Dynamic Modal -->
<div class="modal fade" id="submissionDetailsModal" tabindex="-1" aria-labelledby="submissionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="submissionDetailsModalLabel">Submission Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <!-- Loader Spinner -->
                <div id="modal-loader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted small mt-2">Loading response answers...</p>
                </div>

                <!-- Content Area -->
                <div id="modal-content" class="d-none">
                    <!-- Meta logs row -->
                    <div class="row g-3 mb-4 p-3 bg-light rounded-3 small mx-0">
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block uppercase fs-8 mb-1">SUBMISSION ID</span>
                            <strong id="modal-sub-id" class="text-dark">#0</strong>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block uppercase fs-8 mb-1">DATE SUBMITTED</span>
                            <strong id="modal-sub-date" class="text-dark">-</strong>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block uppercase fs-8 mb-1">FILLING DURATION</span>
                            <strong id="modal-sub-duration" class="text-dark">-</strong>
                        </div>
                        <div class="col-6 col-sm-3">
                            <span class="text-muted d-block uppercase fs-8 mb-1">SUBMITTER IP</span>
                            <strong id="modal-sub-ip" class="text-dark">-</strong>
                        </div>
                        <div class="col-12 border-top pt-2 mt-2">
                            <span class="text-muted d-block uppercase fs-8 mb-1">USER AGENT BROWSER</span>
                            <code id="modal-sub-agent" class="text-secondary small">-</code>
                        </div>
                    </div>

                    <!-- Answers Table -->
                    <h6 class="fw-bold mb-3"><i class="bi bi-list-task me-2 text-indigo"></i>Questions & Answers</h6>
                    <div class="table-responsive border rounded-3 bg-white">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-2.5 ps-3 small text-secondary fw-bold" style="width: 35%;">Field Label</th>
                                    <th class="py-2.5 small text-secondary fw-bold">Answer Response</th>
                                </tr>
                            </thead>
                            <tbody id="modal-answers-table-body">
                                <!-- JS appends rows here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function viewSubmissionDetails(id) {
        // Show modal and start loading spinner
        const modalEl = document.getElementById('submissionDetailsModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        const loader = document.getElementById('modal-loader');
        const content = document.getElementById('modal-content');
        loader.classList.remove('d-none');
        content.classList.add('d-none');

        // Fetch submission details via JSON endpoint
        fetch(`/submissions/${id}`)
            .then(response => {
                if (!response.ok) throw new Error("Network request failed");
                return response.json();
            })
            .then(data => {
                // Populate metadata values
                document.getElementById('modal-sub-id').innerText = '#' + data.id;
                document.getElementById('modal-sub-date').innerText = data.submitted_at;
                document.getElementById('modal-sub-duration').innerText = data.duration_seconds 
                    ? data.duration_seconds + ' seconds' 
                    : 'Unknown';
                document.getElementById('modal-sub-ip').innerText = data.ip_address;
                document.getElementById('modal-sub-agent').innerText = data.user_agent;

                // Populate Answers Table Rows
                const tableBody = document.getElementById('modal-answers-table-body');
                tableBody.innerHTML = '';

                data.answers.forEach(ans => {
                    let ansDisplay = '';
                    
                    if (ans.is_file && ans.value) {
                        ansDisplay = `
                            <div class="d-flex align-items-center">
                                <span class="text-truncate me-2 small fw-medium" style="max-width: 250px;">${ans.value.split('/').pop()}</span>
                                <a href="${ans.file_url}" target="_blank" class="btn btn-xs btn-outline-primary py-0.5 px-2 small fs-8">
                                    <i class="bi bi-download"></i> View File
                                </a>
                            </div>
                        `;
                    } else {
                        ansDisplay = `<span class="text-dark small style-wrap-pre">${ans.value || '<em class="text-muted">Empty</em>'}</span>`;
                    }

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="ps-3 fw-medium small text-secondary text-wrap">${ans.label}</td>
                        <td>${ansDisplay}</td>
                    `;
                    tableBody.appendChild(row);
                });

                // Transition states
                loader.classList.add('d-none');
                content.classList.remove('d-none');
            })
            .catch(err => {
                modal.hide();
                window.showToast('Request Failed', 'Could not load response answers.', true);
            });
    }
</script>
@endsection
