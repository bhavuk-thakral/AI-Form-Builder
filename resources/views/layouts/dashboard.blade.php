<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - AI Form Builder</title>
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
            --sidebar-width: 260px;
            --sidebar-bg: #0f172a; /* Slate 900 */
            --sidebar-color: #94a3b8; /* Slate 400 */
            --sidebar-active-bg: #1e293b; /* Slate 800 */
            --sidebar-active-color: #f8fafc; /* Slate 50 */
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --bg-color: #f8fafc;
            --card-border: 1px solid rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: #1e293b;
            min-height: 100vh;
        }

        /* Sidebar styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--sidebar-bg);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            box-shadow: 4px 0 20px rgba(0,0,0,0.08);
        }

        .sidebar-brand {
            padding: 24px;
            display: flex;
            align-items: center;
            color: #f8fafc;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar-brand i {
            margin-right: 12px;
            font-size: 1.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-nav {
            flex-grow: 1;
            padding: 24px 16px;
            list-style: none;
            margin: 0;
        }

        .sidebar-nav-item {
            margin-bottom: 8px;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--sidebar-color);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sidebar-nav-link i {
            margin-right: 12px;
            font-size: 1.2rem;
            transition: transform 0.2s ease;
        }

        .sidebar-nav-link:hover {
            color: var(--sidebar-active-color);
            background-color: rgba(255,255,255,0.03);
        }

        .sidebar-nav-link:hover i {
            transform: translateX(2px);
        }

        .sidebar-nav-link.active {
            color: var(--sidebar-active-color);
            background-color: var(--sidebar-active-bg);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .sidebar-nav-link.active i {
            color: #818cf8;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            background-color: rgba(0,0,0,0.15);
        }

        /* Top navbar styles */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 40px;
            transition: margin-left 0.3s ease;
        }

        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .page-title h2 {
            font-weight: 700;
            color: #0f172a;
        }

        /* Card customization */
        .stat-card {
            border: var(--card-border);
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.01), 0 1px 3px rgba(0,0,0,0.01);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(79, 70, 229, 0.05);
        }

        .stat-card-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .icon-blue {
            background-color: #eff6ff;
            color: #2563eb;
        }

        .icon-purple {
            background-color: #faf5ff;
            color: #7c3aed;
        }

        .icon-green {
            background-color: #f0fdf4;
            color: #16a34a;
        }

        .icon-orange {
            background-color: #fff7ed;
            color: #ea580c;
        }

        /* Action buttons with gradient */
        .btn-gradient-primary {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.18);
            transition: all 0.3s ease;
        }

        .btn-gradient-primary:hover {
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.3);
            transform: translateY(-1px);
            color: white;
        }

        .btn-outline-custom {
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 500;
            color: #475569;
            transition: all 0.2s ease;
            background-color: #ffffff;
        }

        .btn-outline-custom:hover {
            border-color: #cbd5e1;
            background-color: #f8fafc;
            color: #0f172a;
        }

        /* Toggle Sidebar Button */
        .toggle-sidebar-btn {
            display: none;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            z-index: 1100;
        }

        /* Toast notifications styling */
        .toast-container {
            z-index: 2000;
        }
        
        .custom-toast {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            background: #ffffff;
            backdrop-filter: blur(8px);
        }

        /* Responsive Breakpoints */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 24px;
            }
            .toggle-sidebar-btn {
                display: block;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <i class="bi bi-cpu-fill"></i>
            <span>AI Form Builder</span>
        </a>
        <ul class="sidebar-nav">
            <li class="sidebar-nav-item">
                <a href="{{ route('dashboard') }}" class="sidebar-nav-link active">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link" id="nav-forms-mock">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>My Forms</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link" id="nav-templates-mock">
                    <i class="bi bi-layout-three-columns"></i>
                    <span>Templates</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link" id="nav-analytics-mock">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Analytics</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-indigo-light rounded-circle p-2 bg-primary bg-opacity-10 text-indigo me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-person text-primary"></i>
                </div>
                <div class="overflow-hidden">
                    <h6 class="text-white mb-0 text-truncate">{{ auth()->user()->name }}</h6>
                    <small class="text-muted d-block text-truncate">{{ auth()->user()->email }}</small>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-danger w-100 border-0 text-start ps-3" style="border-radius: 8px;">
                    <i class="bi bi-box-arrow-left me-2"></i> Sign Out
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content" id="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="d-flex align-items-center">
                <button class="toggle-sidebar-btn me-3" id="toggle-sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div class="page-title">
                    <small class="text-muted text-uppercase tracking-wider fw-bold">Overview</small>
                    <h2 class="mb-0">@yield('header_title', 'Dashboard')</h2>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="text-muted small me-3 d-none d-md-inline"><i class="bi bi-calendar3 me-2"></i>{{ now()->format('l, d M Y') }}</span>
            </div>
        </div>

        <!-- Render Content -->
        @yield('content')
    </div>

    <!-- Toast Notifications Container -->
    <div class="position-fixed bottom-0 end-0 p-3 toast-container">
        <!-- Template Toast -->
        <div id="custom-toast" class="toast custom-toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
            <div class="toast-header border-0 pb-0">
                <strong class="me-auto text-indigo" id="toast-title">Notification</strong>
                <small class="text-muted">Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body pt-2" id="toast-body">
                Body message here.
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Toggle on Mobile
            const toggleBtn = document.getElementById('toggle-sidebar');
            const sidebar = document.getElementById('sidebar');
            
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (!sidebar.contains(e.target) && e.target !== toggleBtn) {
                        sidebar.classList.remove('show');
                    }
                });
            }

            // Toast Helper function
            window.showToast = function(title, message, isError = false) {
                const toastEl = document.getElementById('custom-toast');
                const toastTitleEl = document.getElementById('toast-title');
                const toastBodyEl = document.getElementById('toast-body');

                toastTitleEl.innerText = title;
                toastTitleEl.className = isError ? 'me-auto text-danger' : 'me-auto text-primary';
                toastBodyEl.innerText = message;

                const bsToast = new bootstrap.Toast(toastEl);
                bsToast.show();
            };

            // Hook helper toast on mock links
            ['nav-forms-mock', 'nav-templates-mock', 'nav-analytics-mock'].forEach(id => {
                const link = document.getElementById(id);
                if (link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        window.showToast('Feature Coming Soon', 'This action will be enabled in the upcoming modules.');
                    });
                }
            });

            // Display session toast messages
            @if(session('toast_success'))
                window.showToast('Success', "{{ session('toast_success') }}");
            @endif
            @if(session('toast_error'))
                window.showToast('Error', "{{ session('toast_error') }}", true);
            @endif
        });
    </script>
    @yield('scripts')
</body>
</html>
