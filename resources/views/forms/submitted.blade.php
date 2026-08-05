<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Response Submitted - {{ $form->title }}</title>
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
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-card {
            background-color: #ffffff;
            border-radius: 24px;
            max-width: 500px;
            width: 100%;
            border: 1px solid rgba(0, 0, 0, 0.04);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            text-align: center;
            overflow: hidden;
        }

        .header-bar {
            height: 8px;
            background: var(--primary-gradient);
        }

        .icon-wrapper {
            width: 80px;
            height: 80px;
            background-color: #f0fdf4;
            color: #15803d;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 24px;
            box-shadow: 0 4px 10px rgba(22, 163, 74, 0.1);
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            background-color: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 50px;
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
            font-size: 0.85rem;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="header-bar"></div>
        <div class="p-5">
            <div class="icon-wrapper">
                <i class="bi bi-patch-check-fill"></i>
            </div>
            <h3 class="fw-bold mb-2">Thank You!</h3>
            <p class="text-secondary small mb-4">Your response for <strong>{{ $form->title }}</strong> was successfully recorded.</p>
            <hr class="text-light-gray my-4">
            <p class="text-muted small mb-0">The form creator has been notified. You can close this tab now.</p>
            
            <a href="https://laravel.com" class="brand-badge gap-2 small">
                <i class="bi bi-cpu-fill text-indigo"></i> Powered by <strong>AI Form Builder</strong>
            </a>
        </div>
    </div>
</body>
</html>
