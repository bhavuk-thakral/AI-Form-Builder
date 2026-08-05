@extends('layouts.dashboard')

@section('title', 'Form Builder - ' . $form->title)
@section('header_title', 'Form Builder')

@section('content')
<div class="container-fluid px-0">
    <!-- Back to Dashboard -->
    <div class="mb-4">
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-custom text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- Main Canvas Row -->
    <div class="row g-4">
        <!-- Canvas Columns Placeholder (Module 6 target) -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center py-5 bg-white">
                <div class="text-muted mb-3">
                    <i class="bi bi-ui-checks-grid text-indigo" style="font-size: 3.5rem;"></i>
                </div>
                <h5 class="fw-bold text-indigo">Form Canvas Builder</h5>
                <p class="text-muted small mx-auto mb-4" style="max-width: 450px;">
                    This canvas area is designated for the drag-and-drop field generator in Module 6. Currently, you can edit general settings in the sidebar panel.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-sm btn-primary bg-gradient" disabled>
                        <i class="bi bi-plus-circle me-1"></i> Add Text Field
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" disabled>
                        <i class="bi bi-braces me-1"></i> Raw JSON Schema
                    </button>
                </div>
            </div>
        </div>

        <!-- General Form Settings Sidebar (Module 5 CRUD Target) -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-sliders me-2 text-indigo"></i>Form Settings</h5>
                
                @if ($errors->any())
                    <div class="alert alert-danger border-0 rounded-3 small py-2 mb-3" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('forms.update', $form->id) }}">
                    @csrf
                    @method('PATCH')

                    <!-- Title -->
                    <div class="mb-3">
                        <label for="form-title" class="form-label small fw-semibold text-secondary">Form Title</label>
                        <input type="text" class="form-control" id="form-title" name="title" value="{{ old('title', $form->title) }}" placeholder="Application Form Title" required>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="form-desc" class="form-label small fw-semibold text-secondary">Description</label>
                        <textarea class="form-control" id="form-desc" name="description" rows="3" placeholder="Explain the purpose of this form...">{{ old('description', $form->description) }}</textarea>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label for="form-status" class="form-label small fw-semibold text-secondary">Publication Status</label>
                        <select class="form-select form-control" id="form-status" name="status">
                            <option value="draft" {{ old('status', $form->status) === 'draft' ? 'selected' : '' }}>Draft (Private)</option>
                            <option value="active" {{ old('status', $form->status) === 'active' ? 'selected' : '' }}>Active (Publicly Accessable)</option>
                            <option value="archived" {{ old('status', $form->status) === 'archived' ? 'selected' : '' }}>Archived (Closed)</option>
                        </select>
                        <small class="text-muted fs-8 mt-1 d-block">Only 'Active' status forms accept submissions.</small>
                    </div>

                    <button type="submit" class="btn btn-primary-gradient w-100 py-2">
                        <i class="bi bi-save me-1"></i> Save General Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
