@extends('layouts.dashboard')

@section('title', 'Form Builder - ' . $form->title)
@section('header_title', 'Form Builder')

@section('styles')
<style>
    .toolbox-card {
        cursor: grab;
        transition: all 0.2s ease;
        border: 1.5px dashed #cbd5e1;
        border-radius: 12px;
        background-color: #ffffff;
    }
    .toolbox-card:hover {
        border-color: #4f46e5;
        background-color: #f8fafc;
        transform: translateY(-1px);
    }
    .canvas-container {
        min-height: 450px;
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        background-color: #f8fafc;
        padding: 20px;
        transition: background-color 0.2s ease;
    }
    .canvas-container.drag-over {
        background-color: #eff6ff;
        border-color: #3b82f6;
    }
    .field-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.02);
        margin-bottom: 16px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
    }
    .field-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04), 0 4px 6px -2px rgba(0,0,0,0.02);
    }
    .field-drag-handle {
        cursor: grab;
        color: #94a3b8;
    }
    .field-drag-handle:active {
        cursor: grabbing;
    }
    .field-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        border-bottom: 1px solid #f1f5f9;
        background-color: #faf5ff;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
    }
    .field-card-body {
        padding: 20px;
    }
    .field-settings-panel {
        background-color: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 20px;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
    }
    .rating-star {
        font-size: 1.5rem;
        color: #e2e8f0;
        cursor: pointer;
    }
    .rating-star.active {
        color: #f59e0b;
    }
    /* Tabs styling */
    .builder-nav-tabs .nav-link {
        border: none;
        color: #64748b;
        font-weight: 600;
        padding: 12px 20px;
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    .builder-nav-tabs .nav-link.active {
        background: var(--primary-gradient);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
    }
</style>
@endsection

@section('content')
@if($form->status === 'generating')
    <div class="container-fluid px-0">
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-5 bg-white">
            <div class="py-5">
                <div class="spinner-border text-indigo mb-4" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="fw-bold text-indigo">AI Co-pilot is updating your form...</h4>
                <p class="text-secondary small">Compiling layout details and committing database versions. The canvas will refresh shortly.</p>
            </div>
        </div>
    </div>
    
    <script>
        setTimeout(() => {
            window.location.reload();
        }, 2500);
    </script>
@else
<div class="container-fluid px-0">
    <!-- Builder Header Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-custom me-3 py-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-1" id="display-form-title">{{ $form->title }}</h4>
                <span class="badge bg-secondary" id="display-form-status">{{ ucfirst($form->status) }}</span>
            </div>
        </div>
        
        <!-- Tabs & Global Save Button -->
        <div class="d-flex gap-2 align-items-center">
            <ul class="nav nav-pills p-1 bg-light rounded-3 builder-nav-tabs shadow-sm" id="builderTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="canvas-tab" data-bs-toggle="tab" data-bs-target="#canvas-panel" type="button" role="tab" aria-controls="canvas-panel" aria-selected="true">
                        <i class="bi bi-ui-checks-grid me-2"></i> Canvas Builder
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="json-tab" data-bs-toggle="tab" data-bs-target="#json-panel" type="button" role="tab" aria-controls="json-panel" aria-selected="false">
                        <i class="bi bi-braces me-2"></i> Raw JSON Editor
                    </button>
                </li>
            </ul>

            <button type="button" class="btn btn-outline-custom py-2 px-3 shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#versionHistoryModal" style="border-radius: 8px;">
                <i class="bi bi-clock-history me-1"></i> Version History
            </button>

            <form id="save-form-schema" action="{{ route('forms.update', $form->id) }}" method="POST" class="mb-0">
                @csrf
                @method('PATCH')
                <!-- Hidden inputs mapping details -->
                <input type="hidden" name="title" id="form-title-input" value="{{ $form->title }}">
                <input type="hidden" name="description" id="form-desc-input" value="{{ $form->description }}">
                <input type="hidden" name="status" id="form-status-input" value="{{ $form->status }}">
                <input type="hidden" name="schema" id="form-schema-input">
                
                <button type="button" class="btn btn-gradient-primary py-2 px-4 shadow-sm" onclick="submitFormSchema()">
                    <i class="bi bi-cloud-upload me-1"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <!-- main Tab panel contents -->
    <div class="tab-content" id="builderTabsContent">
        <!-- Visual Canvas Builder Panel -->
        <div class="tab-pane fade show active" id="canvas-panel" role="tabpanel" aria-labelledby="canvas-tab">
            <div class="row g-4">
                <!-- Canvas Left Area -->
                <div class="col-12 col-xl-8">
                    <div class="canvas-container shadow-sm" id="canvas-sortable">
                        <!-- Dynamic Canvas Elements injected here -->
                    </div>
                </div>

                <!-- Right Control Panel: Toolbox & Form Settings -->
                <div class="col-12 col-xl-4">
                    <!-- AI Co-pilot Card -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 p-4 text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                        <h6 class="fw-bold mb-2"><i class="bi bi-stars me-2"></i>AI Co-pilot Editor</h6>
                        <p class="small text-white-50 mb-3" style="font-size: 0.82rem; line-height: 1.4;">Instruct the AI to modify fields (add, modify, delete, reorder, change options).</p>
                        <form method="POST" action="{{ route('forms.ai-edit', $form->id) }}">
                            @csrf
                            <div class="mb-3">
                                <textarea class="form-control form-control-sm border-0 shadow-sm text-white" name="instruction" rows="2" placeholder="e.g., Add a phone number field after email" style="background-color: rgba(255,255,255,0.15); border-radius: 8px; font-size: 0.85rem;" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-light btn-sm w-100 fw-bold text-indigo" style="border-radius: 8px; font-size: 0.85rem;">
                                Apply AI Instruction
                            </button>
                        </form>
                    </div>

                    <!-- Form Metadata Card -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 p-4">
                        <h6 class="fw-bold mb-3 text-indigo"><i class="bi bi-sliders me-2"></i>General settings</h6>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Form Title</label>
                            <input type="text" class="form-control form-control-sm" id="meta-title" value="{{ $form->title }}" oninput="updateMeta('title', this.value)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Description</label>
                            <textarea class="form-control form-control-sm" id="meta-desc" rows="2" oninput="updateMeta('description', this.value)">{{ $form->description }}</textarea>
                        </div>
                        <div>
                            <label class="form-label small fw-semibold text-secondary">Publication Status</label>
                            <select class="form-select form-select-sm form-control" id="meta-status" onchange="updateMeta('status', this.value)">
                                <option value="draft" {{ $form->status === 'draft' ? 'selected' : '' }}>Draft (Private)</option>
                                <option value="active" {{ $form->status === 'active' ? 'selected' : '' }}>Active (Public)</option>
                                <option value="archived" {{ $form->status === 'archived' ? 'selected' : '' }}>Archived (Closed)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Toolbox Card -->
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h6 class="fw-bold mb-3 text-indigo"><i class="bi bi-tools me-2"></i>Field Toolbox</h6>
                        <p class="text-muted small">Click any field to add it to the canvas builder immediately.</p>
                        
                        <div class="row g-2" id="toolbox">
                            <!-- Field Buttons -->
                            <div class="col-6" data-type="section">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('section')">
                                    <i class="bi bi-border-top text-primary me-2"></i> Section Heading
                                </div>
                            </div>
                            <div class="col-6" data-type="text">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('text')">
                                    <i class="bi bi-fonts text-primary me-2"></i> Short Text
                                </div>
                            </div>
                            <div class="col-6" data-type="textarea">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('textarea')">
                                    <i class="bi bi-text-paragraph text-primary me-2"></i> Long Text
                                </div>
                            </div>
                            <div class="col-6" data-type="number">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('number')">
                                    <i class="bi bi-123 text-primary me-2"></i> Number
                                </div>
                            </div>
                            <div class="col-6" data-type="email">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('email')">
                                    <i class="bi bi-envelope-at text-primary me-2"></i> Email Address
                                </div>
                            </div>
                            <div class="col-6" data-type="phone">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('phone')">
                                    <i class="bi bi-telephone text-primary me-2"></i> Phone
                                </div>
                            </div>
                            <div class="col-6" data-type="date">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('date')">
                                    <i class="bi bi-calendar text-primary me-2"></i> Date
                                </div>
                            </div>
                            <div class="col-6" data-type="dropdown">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('dropdown')">
                                    <i class="bi bi-menu-button-wide text-primary me-2"></i> Dropdown
                                </div>
                            </div>
                            <div class="col-6" data-type="radio">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('radio')">
                                    <i class="bi bi-ui-checks text-primary me-2"></i> Radio Buttons
                                </div>
                            </div>
                            <div class="col-6" data-type="checkbox">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('checkbox')">
                                    <i class="bi bi-check2-square text-primary me-2"></i> Checkbox List
                                </div>
                            </div>
                            <div class="col-6" data-type="file">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('file')">
                                    <i class="bi bi-cloud-arrow-up text-primary me-2"></i> File Upload
                                </div>
                            </div>
                            <div class="col-6" data-type="rating">
                                <div class="btn w-100 p-3 toolbox-card text-start small fw-medium" style="cursor: grab;" onclick="addNewField('rating')">
                                    <i class="bi bi-star text-primary me-2"></i> Star Rating
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Raw JSON Schema Editor Panel -->
        <div class="tab-pane fade" id="json-panel" role="tabpanel" aria-labelledby="json-tab">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 text-indigo"><i class="bi bi-braces-asterisk me-2"></i>JSON Schema Editor</h5>
                    <button type="button" class="btn btn-sm btn-outline-custom" onclick="syncJsonToCanvas()">
                        <i class="bi bi-arrow-repeat me-1"></i> Apply & Sync Canvas
                    </button>
                </div>
                <div class="alert alert-info border-0 rounded-3 small py-2 mb-3">
                    <i class="bi bi-info-circle me-1"></i> You can edit the JSON directly. Make sure the JSON follows the schema format.
                </div>
                <div class="alert alert-danger border-0 rounded-3 small py-2 mb-3 d-none" id="json-error-alert">
                    <strong id="json-error-title">Invalid JSON Syntax:</strong>
                    <span id="json-error-message"></span>
                </div>
                <textarea class="form-control font-monospace bg-dark text-light p-3" id="raw-json-textarea" rows="20" style="font-size: 0.9rem; line-height: 1.5; border-radius: 12px; resize: vertical;"></textarea>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- SortableJS Library -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
    // Global Form Schema State Variable
    let formSchema = {
        title: "{{ $form->title }}",
        description: "{{ $form->description ?? '' }}",
        fields: @json($form->schema['fields'] ?? [])
    };

    // Keep track of which fields are expanded in settings
    let expandedFields = {};

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize SortableJS on Canvas
        const el = document.getElementById('canvas-sortable');
        new Sortable(el, {
            group: {
                name: 'shared_fields',
                pull: true,
                put: true
            },
            handle: '.field-drag-handle',
            animation: 150,
            ghostClass: 'bg-indigo-light',
            onEnd: function() {
                reorderSchemaFields();
            },
            onAdd: function(evt) {
                const fieldType = evt.item.getAttribute('data-type');
                const targetIndex = evt.newIndex;
                
                if (evt.item.parentNode) {
                    evt.item.parentNode.removeChild(evt.item);
                }
                
                addNewFieldAt(fieldType, targetIndex);
            }
        });

        // Initialize SortableJS on Toolbox
        const toolboxEl = document.getElementById('toolbox');
        if (toolboxEl) {
            new Sortable(toolboxEl, {
                group: {
                    name: 'shared_fields',
                    pull: 'clone',
                    put: false
                },
                sort: false,
                animation: 150
            });
        }

        // Initialize display list
        renderCanvas();

        // Listen for tab switch to sync JSON schema text
        const jsonTab = document.getElementById('json-tab');
        if (jsonTab) {
            jsonTab.addEventListener('click', function() {
                document.getElementById('raw-json-textarea').value = JSON.stringify(formSchema, null, 4);
                hideJsonError();
            });
        }

        const canvasTab = document.getElementById('canvas-tab');
        if (canvasTab) {
            canvasTab.addEventListener('click', function() {
                syncJsonToCanvas();
            });
        }
    });

    // Helper: Slugify text
    function slugify(text) {
        return text.toString().toLowerCase().trim()
            .replace(/\s+/g, '_')           // Replace spaces with _
            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
            .replace(/\-\-+/g, '_')         // Replace multiple - with single _
            .replace(/^-+/, '')             // Trim - from start
            .replace(/-+$/, '');            // Trim - from end
    }

    // Update global form properties from inputs
    function updateMeta(field, val) {
        formSchema[field] = val;
        
        if (field === 'title') {
            document.getElementById('display-form-title').innerText = val;
            document.getElementById('form-title-input').value = val;
        } else if (field === 'description') {
            document.getElementById('form-desc-input').value = val;
        } else if (field === 'status') {
            document.getElementById('display-form-status').innerText = val.charAt(0).toUpperCase() + val.slice(1);
            document.getElementById('form-status-input').value = val;
        }
    }

    // Render HTML of the whole canvas builder
    function renderCanvas() {
        const canvas = document.getElementById('canvas-sortable');
        canvas.innerHTML = '';

        if (formSchema.fields.length === 0) {
            canvas.innerHTML = `
                <div class="text-center py-5" id="canvas-empty-state">
                    <div class="text-muted mb-3"><i class="bi bi-plus-circle" style="font-size: 3rem;"></i></div>
                    <h5 class="fw-bold">Your Form Canvas is Empty</h5>
                    <p class="text-secondary small">Add fields from the Toolbox on the right to start building your form.</p>
                </div>
            `;
            return;
        }

        formSchema.fields.forEach((field, index) => {
            if (!field.id) field.id = 'f_' + Date.now() + '_' + index; // generate temporary UI id if missing

            const isExpanded = expandedFields[field.id] || false;
            const fieldCard = document.createElement('div');
            fieldCard.className = 'field-card shadow-sm';
            fieldCard.setAttribute('data-id', field.id);

            // Generate header
            let typeBadge = getFieldTypeBadge(field.type);
            
            // Generate field preview area based on type
            let previewHTML = getFieldPreviewHTML(field);

            // Generate collapsible settings editor
            let settingsHTML = getFieldSettingsHTML(field, index, isExpanded);

            fieldCard.innerHTML = `
                <div class="field-card-header">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-grip-vertical field-drag-handle fs-5 me-2"></i>
                        <span class="badge ${typeBadge} me-2 text-uppercase fs-8">${field.type}</span>
                        <strong class="text-dark header-field-label-${index}">${field.label}</strong>
                        ${field.required ? '<span class="text-danger ms-1">*</span>' : ''}
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-light border-0 me-1" onclick="toggleSettings('${field.id}')" title="Configure Field">
                            <i class="bi ${isExpanded ? 'bi-chevron-up' : 'bi-chevron-down'} text-secondary"></i>
                        </button>
                        <button class="btn btn-sm btn-light border-0 me-1" onclick="duplicateField('${field.id}')" title="Duplicate">
                            <i class="bi bi-copy text-secondary"></i>
                        </button>
                        <button class="btn btn-sm btn-light border-0" onclick="deleteField('${field.id}')" title="Delete">
                            <i class="bi bi-trash text-danger"></i>
                        </button>
                    </div>
                </div>
                <div class="field-card-body">
                    ${previewHTML}
                </div>
                ${settingsHTML}
            `;

            canvas.appendChild(fieldCard);
        });
    }

    // Return badge colors for fields
    function getFieldTypeBadge(type) {
        switch(type) {
            case 'section': return 'bg-indigo text-indigo bg-indigo-light bg-opacity-10';
            case 'dropdown':
            case 'radio':
            case 'checkbox': return 'bg-info bg-opacity-10 text-info';
            case 'rating': return 'bg-warning bg-opacity-10 text-warning';
            case 'file': return 'bg-danger bg-opacity-10 text-danger';
            default: return 'bg-primary bg-opacity-10 text-primary';
        }
    }

    // Get input HTML preview for canvas
    function getFieldPreviewHTML(field) {
        const placeholder = field.placeholder || '';
        const helpText = field.help_text ? `<div class="form-text small">${field.help_text}</div>` : '';
        const defaultValue = field.default_value || '';

        switch(field.type) {
            case 'section':
                return `
                    <div class="py-2">
                        <h4 class="fw-bold border-bottom pb-2 mb-1">${field.label || 'Section Heading'}</h4>
                        <p class="text-muted small mb-0">${field.help_text || 'Optional section description details.'}</p>
                    </div>
                `;
            case 'textarea':
                return `
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-secondary">${field.label}</label>
                        <textarea class="form-control" rows="2" placeholder="${placeholder}" readonly>${defaultValue}</textarea>
                        ${helpText}
                    </div>
                `;
            case 'dropdown':
                let dropdownOptions = (field.options || []).map(opt => `<option value="${opt}">${opt}</option>`).join('');
                return `
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-secondary">${field.label}</label>
                        <select class="form-select form-control" readonly>
                            <option value="">${placeholder || 'Select option...'}</option>
                            ${dropdownOptions}
                        </select>
                        ${helpText}
                    </div>
                `;
            case 'radio':
                let radioOptions = (field.options || []).map((opt, i) => `
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="preview_radio_${field.id}" id="r_${field.id}_${i}" disabled>
                        <label class="form-check-label small" for="r_${field.id}_${i}">${opt}</label>
                    </div>
                `).join('');
                return `
                    <div class="mb-1">
                        <label class="form-label d-block small fw-semibold text-secondary">${field.label}</label>
                        ${radioOptions || '<span class="text-muted small">No options configured yet.</span>'}
                        ${helpText}
                    </div>
                `;
            case 'checkbox':
                let checkboxOptions = (field.options || []).map((opt, i) => `
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="c_${field.id}_${i}" disabled>
                        <label class="form-check-label small" for="c_${field.id}_${i}">${opt}</label>
                    </div>
                `).join('');
                return `
                    <div class="mb-1">
                        <label class="form-label d-block small fw-semibold text-secondary">${field.label}</label>
                        ${checkboxOptions || '<span class="text-muted small">No options configured yet.</span>'}
                        ${helpText}
                    </div>
                `;
            case 'file':
                return `
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-secondary">${field.label}</label>
                        <div class="border rounded-3 p-3 text-center bg-light">
                            <i class="bi bi-cloud-arrow-up text-secondary fs-4"></i>
                            <span class="d-block small text-muted">Click or drag file here to upload</span>
                        </div>
                        ${helpText}
                    </div>
                `;
            case 'rating':
                let stars = '';
                const maxStars = 5;
                for (let i = 1; i <= maxStars; i++) {
                    const isActive = i <= parseInt(defaultValue || 0) ? 'active bi-star-fill' : 'bi-star';
                    stars += `<i class="bi ${isActive} rating-star me-1"></i>`;
                }
                return `
                    <div class="mb-1">
                        <label class="form-label d-block small fw-semibold text-secondary">${field.label}</label>
                        <div class="d-flex align-items-center mb-1">
                            ${stars}
                        </div>
                        ${helpText}
                    </div>
                `;
            default: // text, email, number, phone, date
                return `
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-secondary">${field.label}</label>
                        <input type="${field.type === 'phone' ? 'tel' : field.type}" class="form-control" placeholder="${placeholder}" value="${defaultValue}" readonly>
                        ${helpText}
                    </div>
                `;
        }
    }

    // Get configuration settings box
    function getFieldSettingsHTML(field, index, isExpanded) {
        if (!isExpanded) return '';

        const hasOptions = ['dropdown', 'radio', 'checkbox'].includes(field.type);
        const optionsList = field.options || [];

        // Build other fields options list for conditional logic dropdown
        const otherFieldsOptions = formSchema.fields
            .filter((f, idx) => idx !== index && f.type !== 'section')
            .map(f => `<option value="${f.key}" ${field.condition_field === f.key ? 'selected' : ''}>${f.label || f.key}</option>`)
            .join('');

        // Build conditional logic HTML
        let conditionalLogicHTML = '';
        if (field.type !== 'section') {
            const isCondEnabled = !!field.condition_field;
            conditionalLogicHTML = `
                <div class="mb-3 border-top pt-3 mt-3">
                    <label class="form-label small fw-semibold text-secondary mb-2 d-block">
                        <i class="bi bi-diagram-2 me-1"></i> Conditional Visibility
                    </label>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="cond_toggle_${field.id}" ${isCondEnabled ? 'checked' : ''} 
                            onchange="toggleConditionalLogic(${index}, this.checked)">
                        <label class="form-check-label small text-muted" for="cond_toggle_${field.id}">Show field conditionally</label>
                    </div>
                    
                    ${isCondEnabled ? `
                    <div class="row g-2 mt-1 bg-light p-3 rounded-3 border">
                        <div class="col-12 mb-2">
                            <label class="form-label fs-8 text-secondary mb-1">Depends On Field</label>
                            <select class="form-select form-select-sm text-dark bg-white" onchange="updateFieldProperty(${index}, 'condition_field', this.value)">
                                <option value="">-- Select Field --</option>
                                ${otherFieldsOptions}
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fs-8 text-secondary mb-1">Operator</label>
                            <select class="form-select form-select-sm text-dark bg-white" onchange="updateFieldProperty(${index}, 'condition_operator', this.value); if(['empty','not_empty'].includes(this.value)) { updateFieldProperty(${index}, 'condition_value', ''); renderCanvas(); }">
                                <option value="equals" ${field.condition_operator === 'equals' ? 'selected' : ''}>Equals</option>
                                <option value="not_equals" ${field.condition_operator === 'not_equals' ? 'selected' : ''}>Not Equals</option>
                                <option value="contains" ${field.condition_operator === 'contains' ? 'selected' : ''}>Contains</option>
                                <option value="empty" ${field.condition_operator === 'empty' ? 'selected' : ''}>Is Empty</option>
                                <option value="not_empty" ${field.condition_operator === 'not_empty' ? 'selected' : ''}>Is Not Empty</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fs-8 text-secondary mb-1">Value</label>
                            <input type="text" class="form-control form-control-sm text-dark bg-white" value="${field.condition_value || ''}" 
                                oninput="updateFieldProperty(${index}, 'condition_value', this.value)" 
                                ${['empty', 'not_empty'].includes(field.condition_operator) ? 'disabled' : ''}>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
        }

        // Build validation options
        let validationOptions = '';
        if (field.type === 'text') {
            validationOptions = `
                <div class="col-6 mb-2">
                    <label class="form-label fs-8 text-secondary">Min Length</label>
                    <input type="number" class="form-control form-control-sm" value="${getValidationVal(field, 'min')}" oninput="updateFieldValidation(${index}, 'min', this.value)">
                </div>
                <div class="col-6 mb-2">
                    <label class="form-label fs-8 text-secondary">Max Length</label>
                    <input type="number" class="form-control form-control-sm" value="${getValidationVal(field, 'max')}" oninput="updateFieldValidation(${index}, 'max', this.value)">
                </div>
            `;
        } else if (field.type === 'number') {
            validationOptions = `
                <div class="col-6 mb-2">
                    <label class="form-label fs-8 text-secondary">Min Value</label>
                    <input type="number" class="form-control form-control-sm" value="${getValidationVal(field, 'min')}" oninput="updateFieldValidation(${index}, 'min', this.value)">
                </div>
                <div class="col-6 mb-2">
                    <label class="form-label fs-8 text-secondary">Max Value</label>
                    <input type="number" class="form-control form-control-sm" value="${getValidationVal(field, 'max')}" oninput="updateFieldValidation(${index}, 'max', this.value)">
                </div>
            `;
        } else if (field.type === 'file') {
            validationOptions = `
                <div class="col-6 mb-2">
                    <label class="form-label fs-8 text-secondary">Max Size (KB)</label>
                    <input type="number" class="form-control form-control-sm" value="${getValidationVal(field, 'max')}" placeholder="2048" oninput="updateFieldValidation(${index}, 'max', this.value)">
                </div>
                <div class="col-6 mb-2">
                    <label class="form-label fs-8 text-secondary">Allowed Formats</label>
                    <input type="text" class="form-control form-control-sm" value="${getValidationVal(field, 'mimes')}" placeholder="pdf,docx,jpg" oninput="updateFieldValidation(${index}, 'mimes', this.value)">
                </div>
            `;
        }

        // Build Options block
        let optionsBlockHTML = '';
        if (hasOptions) {
            let optionItems = optionsList.map((opt, optIndex) => `
                <div class="input-group input-group-sm mb-2">
                    <input type="text" class="form-control" value="${opt}" oninput="updateFieldOption(${index}, ${optIndex}, this.value)">
                    <button class="btn btn-outline-danger" type="button" onclick="deleteFieldOption(${index}, ${optIndex})">
                        <i class="bi bi-dash"></i>
                    </button>
                </div>
            `).join('');

            optionsBlockHTML = `
                <div class="mb-3 border-top pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-semibold text-secondary mb-0">Selectable Options</label>
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 small fs-8" onclick="addFieldOption(${index})">
                            <i class="bi bi-plus-circle me-1"></i> Add Option
                        </button>
                    </div>
                    ${optionItems || '<p class="text-muted small">No options defined yet. Add one!</p>'}
                </div>
            `;
        }

        return `
            <div class="field-settings-panel">
                <div class="row g-3">
                    <!-- Label -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Field Label</label>
                        <input type="text" class="form-control form-control-sm" value="${field.label}" oninput="updateFieldProperty(${index}, 'label', this.value)">
                    </div>

                    <!-- Key -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Unique Key</label>
                        <input type="text" class="form-control form-control-sm" value="${field.key}" oninput="updateFieldProperty(${index}, 'key', this.value, true)">
                        <small class="text-muted fs-8">Used in databases & JSON maps.</small>
                    </div>

                    <!-- Placeholder -->
                    ${field.type !== 'section' ? `
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Placeholder Text</label>
                        <input type="text" class="form-control form-control-sm" value="${field.placeholder || ''}" oninput="updateFieldProperty(${index}, 'placeholder', this.value)">
                    </div>
                    ` : ''}

                    <!-- Help Text -->
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Help Text</label>
                        <input type="text" class="form-control form-control-sm" value="${field.help_text || ''}" oninput="updateFieldProperty(${index}, 'help_text', this.value)">
                    </div>

                    <!-- Default Value -->
                    ${field.type !== 'section' ? `
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Default Value</label>
                        <input type="text" class="form-control form-control-sm" value="${field.default_value || ''}" oninput="updateFieldProperty(${index}, 'default_value', this.value)">
                    </div>
                    ` : ''}

                    <!-- Required Toggle -->
                    ${field.type !== 'section' ? `
                    <div class="col-12 col-md-6 d-flex align-items-center">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="req_${field.id}" ${field.required ? 'checked' : ''} onchange="updateFieldProperty(${index}, 'required', this.checked)">
                            <label class="form-check-label small fw-semibold text-secondary" for="req_${field.id}">Mark as Required</label>
                        </div>
                    </div>
                    ` : ''}
                </div>

                <!-- Custom Validation blocks -->
                ${validationOptions ? `
                <div class="mb-3 border-top pt-3 mt-3">
                    <label class="form-label small fw-semibold text-secondary mb-2">Custom Validations</label>
                    <div class="row g-2">
                        ${validationOptions}
                    </div>
                </div>
                ` : ''}

                <!-- Option block -->
                ${optionsBlockHTML}

                <!-- Conditional Visibility Block -->
                ${conditionalLogicHTML}
            </div>
        `;
    }

    // Toggle conditional logic configuration
    function toggleConditionalLogic(index, enabled) {
        let field = formSchema.fields[index];
        if (enabled) {
            field.condition_field = '';
            field.condition_operator = 'equals';
            field.condition_value = '';
        } else {
            delete field.condition_field;
            delete field.condition_operator;
            delete field.condition_value;
        }
        renderCanvas();
    }

    // Helper: Find validation rule value
    function getValidationVal(field, ruleKey) {
        if (!field.validations) return '';
        const found = field.validations.find(v => v.startsWith(ruleKey + ':'));
        return found ? found.split(':')[1] : '';
    }

    // Toggle configure panel open/close
    function toggleSettings(id) {
        expandedFields[id] = !expandedFields[id];
        renderCanvas();
    }

    // Add field helper
    function addNewField(type) {
        let label = 'New ' + type.charAt(0).toUpperCase() + type.slice(1);
        if (type === 'section') label = 'Section Title';
        
        const newField = {
            id: 'f_' + Date.now() + '_' + formSchema.fields.length,
            type: type,
            label: label,
            key: slugify(label) + '_' + Math.floor(Math.random() * 1000),
            placeholder: type === 'section' ? '' : 'Enter details...',
            help_text: '',
            required: false,
            default_value: type === 'rating' ? '0' : '',
            validations: [],
            options: ['Option A', 'Option B']
        };

        formSchema.fields.push(newField);
        expandedFields[newField.id] = true; // expand immediately on creation
        renderCanvas();
        window.showToast('Field Added', `Added "${label}" field to the canvas.`);
    }

    // Add field at index helper
    function addNewFieldAt(type, index) {
        let label = 'New ' + type.charAt(0).toUpperCase() + type.slice(1);
        if (type === 'section') label = 'Section Title';
        
        const newField = {
            id: 'f_' + Date.now() + '_' + formSchema.fields.length,
            type: type,
            label: label,
            key: slugify(label) + '_' + Math.floor(Math.random() * 1000),
            placeholder: type === 'section' ? '' : 'Enter details...',
            help_text: '',
            required: false,
            default_value: type === 'rating' ? '0' : '',
            validations: [],
            options: ['Option A', 'Option B']
        };

        formSchema.fields.splice(index, 0, newField);
        expandedFields[newField.id] = true;
        renderCanvas();
        window.showToast('Field Added', `Added "${label}" field at position ${index + 1}.`);
    }

    // Duplicate field
    function duplicateField(id) {
        const index = formSchema.fields.findIndex(f => f.id === id);
        if (index === -1) return;

        const source = formSchema.fields[index];
        const newId = 'f_' + Date.now() + '_' + (formSchema.fields.length + 1);
        
        const copy = JSON.parse(JSON.stringify(source));
        copy.id = newId;
        copy.key = slugify(copy.label) + '_' + Math.floor(Math.random() * 1000);
        copy.label = copy.label + ' (Copy)';

        formSchema.fields.splice(index + 1, 0, copy);
        expandedFields[newId] = true;
        renderCanvas();
        window.showToast('Field Duplicated', `Duplicated field "${source.label}".`);
    }

    // Delete field
    function deleteField(id) {
        const index = formSchema.fields.findIndex(f => f.id === id);
        if (index === -1) return;

        const label = formSchema.fields[index].label;
        formSchema.fields.splice(index, 1);
        delete expandedFields[id];
        renderCanvas();
        window.showToast('Field Deleted', `Removed "${label}" field.`);
    }

    // Update field properties in JSON state
    function updateFieldProperty(index, property, val, manualKeyEdit = false) {
        let field = formSchema.fields[index];
        field[property] = val;

        // Auto-generate key from label if not manually changed
        if (property === 'label' && !field.key_locked) {
            field.key = slugify(val);
            // Re-render canvas inputs without wiping cursor focuses
            const headerLabelEl = document.querySelector(`.header-field-label-${index}`);
            if (headerLabelEl) headerLabelEl.innerText = val;
        }

        if (property === 'key' && manualKeyEdit) {
            field.key_locked = true;
        }

        // Debounce full canvas updates so input typing is smooth
        clearTimeout(window.renderTimeout);
        window.renderTimeout = setTimeout(() => {
            renderCanvas();
        }, 800);
    }

    // Update validations list
    function updateFieldValidation(index, key, val) {
        let field = formSchema.fields[index];
        if (!field.validations) field.validations = [];

        // Remove old occurrences of this key
        field.validations = field.validations.filter(v => !v.startsWith(key + ':'));

        // Add new key:val if present
        if (val) {
            field.validations.push(`${key}:${val}`);
        }
        
        clearTimeout(window.renderTimeout);
        window.renderTimeout = setTimeout(() => {
            renderCanvas();
        }, 800);
    }

    // Manage options updates
    function addFieldOption(index) {
        let field = formSchema.fields[index];
        if (!field.options) field.options = [];
        field.options.push('New Option ' + (field.options.length + 1));
        renderCanvas();
    }

    function updateFieldOption(index, optIndex, val) {
        formSchema.fields[index].options[optIndex] = val;
    }

    function deleteFieldOption(index, optIndex) {
        formSchema.fields[index].options.splice(optIndex, 1);
        renderCanvas();
    }

    // Reorder schema fields on drag drop end
    function reorderSchemaFields() {
        const sortedIds = Array.from(document.getElementById('canvas-sortable').children)
            .map(el => el.getAttribute('data-id'));

        const newFieldsList = [];
        sortedIds.forEach(id => {
            const found = formSchema.fields.find(f => f.id === id);
            if (found) newFieldsList.push(found);
        });

        formSchema.fields = newFieldsList;
        // Do a full render refresh to maintain index synchronization
        renderCanvas();
    }

    // Sync JSON panel edit changes back to Visual Canvas
    function syncJsonToCanvas() {
        const rawJsonText = document.getElementById('raw-json-textarea').value;
        try {
            const parsed = JSON.parse(rawJsonText);
            
            // Basic structure validation
            if (typeof parsed !== 'object' || parsed === null) throw new Error("JSON must be a valid key-value object");
            if (!parsed.title) throw new Error("Form must have a 'title' field");
            if (!Array.isArray(parsed.fields)) throw new Error("Form 'fields' must be a JSON array");

            formSchema = parsed;
            
            // Sync metadata
            document.getElementById('meta-title').value = parsed.title;
            document.getElementById('meta-desc').value = parsed.description || '';
            document.getElementById('form-title-input').value = parsed.title;
            document.getElementById('form-desc-input').value = parsed.description || '';

            renderCanvas();
            hideJsonError();
            window.showToast('JSON Synchronized', 'Visual Canvas successfully updated from JSON schema.');
        } catch (e) {
            showJsonError(e.message);
        }
    }

    function showJsonError(msg) {
        const errAlert = document.getElementById('json-error-alert');
        const errMsg = document.getElementById('json-error-message');
        errMsg.innerText = msg;
        errAlert.classList.remove('d-none');
    }

    function hideJsonError() {
        document.getElementById('json-error-alert').classList.add('d-none');
    }
</script>

<!-- Modal: Version History -->
<div class="modal fade" id="versionHistoryModal" tabindex="-1" aria-labelledby="versionHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-sm bg-white">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold text-indigo" id="versionHistoryModalLabel">
                    <i class="bi bi-clock-history me-2"></i>Version Rollback History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <p class="text-muted small mb-4">Select a version state below to restore. Rollbacks commit a new revision log checkpoint, ensuring no history is lost.</p>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0">
                        <thead>
                            <tr class="text-secondary small fw-bold" style="border-bottom: 2px solid #f3f4f6;">
                                <th class="pb-3">Version</th>
                                <th class="pb-3">Saved At</th>
                                <th class="pb-3">Author</th>
                                <th class="pb-3">Fields</th>
                                <th class="pb-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($form->versions()->orderBy('version_number', 'desc')->get() as $ver)
                                <tr>
                                    <td class="py-3">
                                        <span class="badge bg-indigo-subtle text-indigo rounded-pill px-3 py-1.5 fw-bold">
                                            v{{ $ver->version_number }}
                                        </span>
                                    </td>
                                    <td class="py-3 small text-secondary">
                                        {{ $ver->created_at->format('M d, Y h:i A') }}
                                    </td>
                                    <td class="py-3 small text-secondary fw-semibold">
                                        {{ $ver->creator ? $ver->creator->name : 'System/AI' }}
                                    </td>
                                    <td class="py-3 small text-secondary">
                                        {{ count($ver->schema['fields'] ?? []) }} fields
                                    </td>
                                    <td class="py-3 text-end">
                                        <form method="POST" action="{{ route('forms.restore-version', [$form->id, $ver->id]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-indigo px-3 py-1 fw-bold rounded-pill" style="font-size: 0.8rem;">
                                                Restore
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted small">
                                        <i class="bi bi-folder-x fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                        No versions logged for this form.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Submit global save request
    function submitFormSchema() {
        // Double check tab sync if currently viewing JSON panel
        const jsonTab = document.getElementById('json-tab');
        if (jsonTab.classList.contains('active')) {
            syncJsonToCanvas();
            const errAlert = document.getElementById('json-error-alert');
            if (!errAlert.classList.contains('d-none')) {
                window.showToast('Validation Error', 'Fix syntax errors in JSON Schema before saving.', true);
                return;
            }
        }

        // Set serialized schema input
        const schemaInput = document.getElementById('form-schema-input');
        schemaInput.value = JSON.stringify(formSchema);

        // Submit form
        document.getElementById('save-form-schema').submit();
    }
</script>
@endif
@endsection
