<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $form->title }} - Powered by AI Form Builder</title>
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --bg-gradient: radial-gradient(circle at 50% 50%, #f8fafc 0%, #e2e8f0 100%);
            --text-dark: #0f172a;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            color: var(--text-dark);
            padding: 40px 20px;
        }

        .public-container {
            max-width: 680px;
            margin: 0 auto;
        }

        .public-card {
            background-color: #ffffff;
            border-radius: 24px;
            border: 1px solid rgba(0, 0, 0, 0.04);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }

        .form-header-bar {
            height: 8px;
            background: var(--primary-gradient);
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
        }

        .btn-submit {
            background: var(--primary-gradient);
            color: #ffffff;
            border: none;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.35);
            transform: translateY(-1px);
            color: #ffffff;
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        .rating-star {
            font-size: 2rem;
            color: #cbd5e1;
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .rating-star.hovered, .rating-star.selected {
            color: #f59e0b;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            background-color: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 50px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .brand-badge:hover {
            color: #4f46e5;
            border-color: rgba(79, 70, 229, 0.2);
        }

        .brand-badge i {
            color: #4f46e5;
        }
    </style>
</head>
<body>
    <div class="public-container">
        <!-- Form Panel -->
        <div class="card public-card border-0 mb-4 overflow-hidden">
            <div class="form-header-bar"></div>
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold mb-2">{{ $form->title }}</h2>
                @if($form->description)
                    <p class="text-secondary small mb-4">{{ $form->description }}</p>
                @endif
                <hr class="text-light-gray my-4">

                <!-- Backend Errors -->
                @if($errors->any())
                    <div class="alert alert-danger border-0 rounded-3 small py-2 mb-4" role="alert">
                        <strong class="d-block mb-1">Please correct the following errors:</strong>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('forms.public.submit', $form->share_token) }}" enctype="multipart/form-data" id="public-response-form" class="needs-validation" novalidate>
                    @csrf
                    <!-- Analytics stopwatch tracker -->
                    <input type="hidden" name="duration_seconds" id="duration-input" value="0">

                    <!-- Render Schema Fields -->
                    @foreach($form->schema['fields'] as $field)
                        @php
                            $key = $field['key'] ?? null;
                            if (!$key) continue;
                            
                            $required = !empty($field['required']);
                            $placeholder = $field['placeholder'] ?? '';
                            $helpText = $field['help_text'] ?? '';
                            $defaultValue = old($key, $field['default_value'] ?? '');
                        @endphp

                        @if($field['type'] === 'section')
                            <!-- Section Heading -->
                            <div class="mt-5 mb-4 pt-3 border-top">
                                <h4 class="fw-bold text-indigo mb-1">{{ $field['label'] }}</h4>
                                @if($helpText)
                                    <p class="text-secondary small mb-0">{{ $helpText }}</p>
                                @endif
                            </div>
                        @else
                            <div class="mb-4">
                                <label for="field_{{ $key }}" class="form-label small fw-semibold text-secondary mb-1">
                                    {{ $field['label'] }}
                                    @if($required)
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>

                                @if($field['type'] === 'textarea')
                                    <!-- Long Text -->
                                    <textarea class="form-control" id="field_{{ $key }}" name="{{ $key }}" rows="3" placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}>{{ $defaultValue }}</textarea>

                                @elseif($field['type'] === 'dropdown')
                                    <!-- Select List -->
                                    <select class="form-select" id="field_{{ $key }}" name="{{ $key }}" {{ $required ? 'required' : '' }}>
                                        <option value="">{{ $placeholder ?: 'Choose option...' }}</option>
                                        @foreach($field['options'] ?? [] as $opt)
                                            <option value="{{ $opt }}" {{ $defaultValue === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>

                                @elseif($field['type'] === 'radio')
                                    <!-- Radio Options -->
                                    <div class="pt-1">
                                        @foreach($field['options'] ?? [] as $opt)
                                            <div class="form-check form-check-inline me-3">
                                                <input class="form-check-input" type="radio" name="{{ $key }}" id="opt_{{ $key }}_{{ loop->index }}" value="{{ $opt }}" {{ $defaultValue === $opt ? 'checked' : '' }} {{ $required ? 'required' : '' }}>
                                                <label class="form-check-label small" for="opt_{{ $key }}_{{ loop->index }}">{{ $opt }}</label>
                                            </div>
                                        @endforeach
                                    </div>

                                @elseif($field['type'] === 'checkbox')
                                    <!-- Checkbox options list -->
                                    <div class="pt-1">
                                        @php
                                            $checkedList = is_array($defaultValue) ? $defaultValue : (is_string($defaultValue) ? json_decode($defaultValue, true) ?: [] : []);
                                        @endphp
                                        @foreach($field['options'] ?? [] as $opt)
                                            <div class="form-check form-check-inline me-3">
                                                <input class="form-check-input checkbox-group" type="checkbox" name="{{ $key }}[]" id="opt_{{ $key }}_{{ loop->index }}" value="{{ $opt }}" {{ in_array($opt, $checkedList) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="opt_{{ $key }}_{{ loop->index }}">{{ $opt }}</label>
                                            </div>
                                        @endforeach
                                    </div>

                                @elseif($field['type'] === 'rating')
                                    <!-- Star rating picker widget -->
                                    <div class="rating-widget py-1" data-key="{{ $key }}">
                                        @php
                                            $starsVal = (int)($defaultValue ?: 0);
                                        @endphp
                                        <input type="hidden" name="{{ $key }}" id="rating_input_{{ $key }}" value="{{ $starsVal }}" {{ $required ? 'required' : '' }}>
                                        <div class="d-flex align-items-center">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star-fill rating-star me-2 {{ $i <= $starsVal ? 'selected' : '' }}" data-value="{{ $i }}"></i>
                                            @endfor
                                        </div>
                                    </div>

                                @elseif($field['type'] === 'file')
                                    <!-- File upload file selector -->
                                    <input type="file" class="form-control" id="field_{{ $key }}" name="{{ $key }}" {{ $required ? 'required' : '' }}>

                                @else
                                    <!-- Standard input types (text, email, number, phone, date) -->
                                    <input type="{{ $field['type'] === 'phone' ? 'tel' : $field['type'] }}" class="form-control" id="field_{{ $key }}" name="{{ $key }}" placeholder="{{ $placeholder }}" value="{{ $defaultValue }}" {{ $required ? 'required' : '' }}>
                                @endif

                                @if($helpText)
                                    <div class="form-text small mt-1 text-muted">{{ $helpText }}</div>
                                @endif
                            </div>
                        @endif
                    @endforeach

                    <div class="d-grid mt-5">
                        <button type="submit" class="btn btn-submit py-3 fs-6">
                            Submit Form Response
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Branding Footer -->
        <div class="text-center py-2">
            <a href="https://laravel.com" class="brand-badge gap-2 small">
                <i class="bi bi-cpu-fill"></i> Powered by <strong>AI Form Builder</strong>
            </a>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Analytics: stopwatch loader
            const startTime = Date.now();
            const durationInput = document.getElementById('duration-input');
            setInterval(() => {
                const diffSec = Math.round((Date.now() - startTime) / 1000);
                durationInput.value = diffSec;
            }, 1000);

            // Rating logic handler
            const ratingWidgets = document.querySelectorAll('.rating-widget');
            ratingWidgets.forEach(widget => {
                const stars = widget.querySelectorAll('.rating-star');
                const hiddenInput = widget.querySelector('input[type="hidden"]');

                stars.forEach(star => {
                    // Hover highlight
                    star.addEventListener('mouseover', function() {
                        const val = parseInt(this.getAttribute('data-value'));
                        highlightStars(stars, val, 'hovered');
                    });

                    // Mouse leave reset
                    star.addEventListener('mouseleave', function() {
                        resetStars(stars, 'hovered');
                    });

                    // Click select
                    star.addEventListener('click', function() {
                        const val = parseInt(this.getAttribute('data-value'));
                        hiddenInput.value = val;
                        highlightStars(stars, val, 'selected');
                    });
                });
            });

            function highlightStars(stars, maxVal, className) {
                stars.forEach(star => {
                    const starVal = parseInt(star.getAttribute('data-value'));
                    if (starVal <= maxVal) {
                        star.classList.add(className);
                    } else {
                        star.classList.remove(className);
                    }
                });
            }

            function resetStars(stars, className) {
                stars.forEach(star => star.classList.remove(className));
            }

            // Client side validation
            const form = document.getElementById('public-response-form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            }
        });
    </script>
</body>
</html>
