@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="auth-card p-4 p-md-5 animated-fade-in">
    <div class="text-center">
        <div class="brand-logo">
            <i class="bi bi-cpu-fill"></i>
        </div>
        <h3 class="fw-bold text-indigo mb-1">Welcome Back</h3>
        <p class="text-muted mb-4 small">Sign in to manage your AI Powered Forms.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success border-0 rounded-3 small py-2 mb-4" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 rounded-3 small py-2 mb-4" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
        @csrf

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label small fw-medium text-secondary">Email Address</label>
            <div class="input-group">
                <span class="input-group-text input-group-text-left"><i class="bi bi-envelope"></i></span>
                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="name@example.com" required autofocus autocomplete="username">
            </div>
        </div>

        <!-- Password -->
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="form-label small fw-medium text-secondary mb-0">Password</label>
            </div>
            <div class="input-group">
                <span class="input-group-text input-group-text-left"><i class="bi bi-lock"></i></span>
                <input id="password" type="password" class="form-control form-control-right-btn" name="password" placeholder="••••••••" required autocomplete="current-password">
                <span class="input-group-text input-group-text-right toggle-password-visibility" data-target="password">
                    <i class="bi bi-eye"></i>
                </span>
            </div>
        </div>

        <!-- Remember Me -->
        <div class="form-check mb-4 text-start">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label for="remember_me" class="form-check-label small text-muted">Remember me</label>
        </div>

        <button type="submit" class="btn btn-primary-gradient w-100 mb-4 py-2">
            Sign In
        </button>

        <div class="text-center small">
            <span class="text-muted">New to AI Forms?</span>
            <a href="{{ route('register') }}" class="text-indigo fw-medium text-decoration-none ms-1">Create Account</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form client-side validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Toggle password visibility
        const toggles = document.querySelectorAll('.toggle-password-visibility');
        toggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
    });
</script>
@endsection
