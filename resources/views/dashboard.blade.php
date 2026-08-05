@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard')

@section('content')
<!-- Stats Cards -->
<div class="row g-4 mb-5">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="card-body d-flex align-items-center">
                <div class="stat-card-icon icon-blue me-3">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted small fw-bold mb-1">TOTAL FORMS</h6>
                    <h3 class="card-title fw-bold mb-0" id="stat-total-forms">{{ $stats['total_forms'] }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="card-body d-flex align-items-center">
                <div class="stat-card-icon icon-purple me-3">
                    <i class="bi bi-send-fill"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted small fw-bold mb-1">SUBMISSIONS</h6>
                    <h3 class="card-title fw-bold mb-0" id="stat-submissions">{{ $stats['total_submissions'] }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="card-body d-flex align-items-center">
                <div class="stat-card-icon icon-green me-3">
                    <i class="bi bi-stars"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted small fw-bold mb-1">AI GENERATIONS</h6>
                    <h3 class="card-title fw-bold mb-0" id="stat-ai-generated">{{ $stats['ai_generated'] }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="card-body d-flex align-items-center">
                <div class="stat-card-icon icon-orange me-3">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div>
                    <h6 class="card-subtitle text-muted small fw-bold mb-1">CONVERSION RATE</h6>
                    <h3 class="card-title fw-bold mb-0" id="stat-conversion-rate">{{ $stats['conversion_rate'] }}%</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Creation Cockpit & Search -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4">
    <div class="position-relative flex-grow-1" style="max-width: 480px;">
        <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">
            <i class="bi bi-search"></i>
        </span>
        <input type="text" class="form-control ps-5 border-0 shadow-sm" placeholder="Search forms by title..." style="border-radius: 12px; padding-top: 12px; padding-bottom: 12px;">
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-custom shadow-sm d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#importFormModal">
            <i class="bi bi-file-earmark-arrow-up me-2"></i> Import
        </button>
        <button class="btn btn-gradient-primary shadow-sm d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#createFormModal">
            <i class="bi bi-plus-circle me-2"></i> Create Form
        </button>
    </div>
</div>

<!-- Forms Table Card -->
<div class="card border-0 shadow-sm rounded-4 mb-5">
    <div class="card-header bg-white border-0 py-3 rounded-top-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Active Forms</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3 text-secondary text-uppercase fs-7 fw-bold">Form Name</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold">Status</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold text-center">Submissions</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold">Last Updated</th>
                        <th class="pe-4 py-3 text-secondary text-uppercase fs-7 fw-bold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($forms as $form)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-3 bg-indigo-light p-2 bg-primary bg-opacity-10 text-primary me-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                        <i class="bi bi-file-earmark-text-fill fs-5"></i>
                                    </div>
                                    <div>
                                        <span class="d-block fw-semibold text-indigo">{{ $form['title'] }}</span>
                                        <small class="text-muted d-block fs-8">{{ $form['fields_count'] }} fields</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success border-0 px-2 py-1.5 rounded-2 small fw-medium">Active</span>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold">{{ $form['submissions_count'] }}</span>
                            </td>
                            <td>
                                <span class="text-secondary small">{{ $form['updated_at'] }}</span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-custom me-2" onclick="editFormMock('{{ $form['title'] }}')" title="Edit Form">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-custom me-2" onclick="viewSubmissionsMock('{{ $form['title'] }}')" title="Submissions">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-custom me-2" onclick="shareFormMock('{{ $form['title'] }}', '{{ $form['public_url'] }}')" title="Share">
                                        <i class="bi bi-share"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteFormMock('{{ $form['title'] }}')" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted mb-3">
                                    <i class="bi bi-folder-x fs-1"></i>
                                </div>
                                <h6 class="fw-bold">No forms created yet</h6>
                                <p class="text-secondary small mb-3">Create your first manual form or generate one with AI.</p>
                                <button class="btn btn-gradient-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createFormModal">
                                    <i class="bi bi-plus-circle me-1"></i> Create Form
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Create Form Options -->
<div class="modal fade" id="createFormModal" tabindex="-1" aria-labelledby="createFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="createFormModalLabel">Create a New Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <div class="d-grid gap-3">
                    <button class="btn btn-outline-custom text-start p-3 d-flex align-items-center border-2 border-primary border-opacity-10 hover-border-primary" onclick="createFormOption('manual')" style="border-radius: 16px;">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-magic fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Create Manually (Canvas Builder)</h6>
                            <small class="text-muted text-wrap d-block">Drag & drop fields, toggle settings, and construct JSON schema via a GUI.</small>
                        </div>
                    </button>
                    <button class="btn btn-outline-custom text-start p-3 d-flex align-items-center border-2 border-primary border-opacity-10 hover-border-primary" onclick="createFormOption('ai')" style="border-radius: 16px;">
                        <div class="rounded-3 bg-purple-light bg-info bg-opacity-10 text-info p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-stars fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Generate with AI Assistant</h6>
                            <small class="text-muted text-wrap d-block">Enter a prompt (e.g. "internship register application") to auto-generate a form.</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Share Form Details -->
<div class="modal fade" id="shareFormModal" tabindex="-1" aria-labelledby="shareFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-indigo" id="shareFormModalLabel">Share Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-muted small">Anyone with the link will be able to fill out and submit response answers.</p>
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Public Form URL</label>
                    <div class="input-group shadow-sm" style="border-radius: 12px; overflow: hidden;">
                        <input type="text" id="share-public-url" class="form-control border-0 bg-light" readonly>
                        <button class="btn btn-primary" type="button" onclick="copyPublicUrl()">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                </div>
                <div class="text-center">
                    <div class="mb-3 d-inline-block border p-3 rounded-4 bg-light shadow-sm">
                        <!-- QR Code placeholder -->
                        <div class="d-flex align-items-center justify-content-center bg-white border border-opacity-10" style="width: 150px; height: 150px;">
                            <i class="bi bi-qr-code text-indigo" style="font-size: 8rem;"></i>
                        </div>
                    </div>
                    <p class="small text-muted mb-0"><i class="bi bi-qr-code-scan me-1 text-primary"></i> Scan QR code to view public form on mobile.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Import Form Details -->
<div class="modal fade" id="importFormModal" tabindex="-1" aria-labelledby="importFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="importFormModalLabel">Import Form Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="border-2 border-dashed border-primary border-opacity-20 rounded-4 p-4 text-center bg-light bg-opacity-50">
                    <i class="bi bi-cloud-arrow-up fs-1 text-indigo mb-3"></i>
                    <h6 class="fw-bold mb-2">Upload DOCX or XLSX files</h6>
                    <p class="text-muted small mb-3">Docx headings become sections, Xlsx sheets create rows mapping.</p>
                    <input type="file" id="import-file-uploader" class="d-none" accept=".docx,.xlsx">
                    <button class="btn btn-outline-custom btn-sm shadow-sm" onclick="document.getElementById('import-file-uploader').click()">
                        Choose File
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function editFormMock(title) {
        window.showToast('Edit Form', `Loading canvas builder for form: "${title}"...`);
    }

    function viewSubmissionsMock(title) {
        window.showToast('Submissions', `Loading answer submission listings for "${title}"...`);
    }

    function shareFormMock(title, url) {
        document.getElementById('share-public-url').value = url;
        const modal = new bootstrap.Modal(document.getElementById('shareFormModal'));
        modal.show();
    }

    function deleteFormMock(title) {
        if (confirm(`Are you sure you want to delete the form "${title}"?`)) {
            window.showToast('Delete Form', `Form "${title}" deleted successfully.`);
        }
    }

    function copyPublicUrl() {
        const copyText = document.getElementById('share-public-url');
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value)
            .then(() => {
                window.showToast('Copied to Clipboard!', 'Form link copied. You can share it now.');
                const modalEl = document.getElementById('shareFormModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            })
            .catch(() => {
                window.showToast('Failed to copy', 'Please copy it manually.', true);
            });
    }

    function createFormOption(type) {
        const modalEl = document.getElementById('createFormModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        if (type === 'manual') {
            window.showToast('Canvas Builder', 'Redirecting to drag & drop canvas...');
        } else if (type === 'ai') {
            window.showToast('AI Form Generation', 'Opening prompt assistant...');
        }
    }
</script>
@endsection
