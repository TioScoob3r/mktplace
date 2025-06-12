<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin - Marketplace Digital</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #28a745;
            --sidebar-bg: #343a40;
            --sidebar-width: 250px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            padding-top: 1rem;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.25rem 0.5rem;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: var(--primary-color);
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 0.5rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .top-navbar {
            background-color: #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 1rem 1.5rem;
            margin-bottom: 0;
        }

        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .border-left-primary {
            border-left: 0.25rem solid var(--primary-color) !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #28a745 !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #17a2b8 !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #ffc107 !important;
        }

        .text-xs {
            font-size: 0.75rem;
        }

        .text-gray-800 {
            color: #5a5c69 !important;
        }

        .text-gray-300 {
            color: #dddfeb !important;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block !important;
            }
        }

        .mobile-menu-btn {
            display: none;
        }
    </style>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo isset($css_path) ? $css_path : '../assets/css/style.css'; ?>">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="px-3 mb-4">
            <h4 class="text-white">
                <i class="fas fa-cog"></i> Admin Panel
            </h4>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="products.php">
                    <i class="fas fa-box"></i> Produtos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users"></i> Usuários
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                    <i class="fas fa-shopping-cart"></i> Pedidos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pix_transactions.php' ? 'active' : ''; ?>" href="pix_transactions.php">
                    <i class="fas fa-qrcode"></i> Transações Pix
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pixup_config.php' ? 'active' : ''; ?>" href="pixup_config.php">
                    <i class="fas fa-cogs"></i> Configurações PixUp
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i> Relatórios
                </a>
            </li>
            <li class="nav-item mt-3">
                <hr class="text-muted">
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home"></i> Ver Site
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light top-navbar">
            <button class="btn btn-outline-secondary mobile-menu-btn" type="button" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="../index.php">
                            <i class="fas fa-home"></i> Ver Site
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>