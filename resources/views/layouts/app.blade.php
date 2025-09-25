<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Task Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <style>
        body {
            background-color: #ffffffff;
            color: #fff;
            font-size: 12px; /* lebih kecil agar pas di HP */
        }
        .container {
            max-width: 480px; /* seperti layar mobile */
            margin: 0 auto;
            padding: 0;
        }
        .card {
            border-radius: 12px;
            background-color: #111;
            border: none;
        }
        .card-body {
            padding: 12px 16px;
        }
        .task-time {
            font-size: 0.8rem;
            color: #aaa;
        }
        .task-status {
            font-size: 0.75rem;
            color: #666;
        }
        .btn-icon {
            background: none;
            border: none;
            color: #fff;
        }
        .btn-icon:hover {
            color: #ffc107;
        }
        /* Header sticky di atas */
        .header-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: #000;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>