<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Marketplace Digital</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo isset($css_path) ? $css_path : 'assets/css/style.css'; ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo isset($home_path) ? $home_path : 'index.php'; ?>">
                <i class="fas fa-store"></i> Marketplace Digital
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($home_path) ? $home_path : 'index.php'; ?>">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo isset($pages_path) ? $pages_path : 'pages/'; ?>products.php">
                            <i class="fas fa-shopping-bag"></i> Produtos
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($pages_path) ? $pages_path : 'pages/'; ?>cart.php">
                                <i class="fas fa-shopping-cart"></i> Carrinho 
                                <?php $cart_count = getCartCount(); if ($cart_count > 0): ?>
                                    <span class="badge bg-warning text-dark"><?php echo $cart_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo isset($pages_path) ? $pages_path : 'pages/'; ?>history.php">
                                    <i class="fas fa-history"></i> Histórico
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo isset($pages_path) ? $pages_path : 'pages/'; ?>downloads.php">
                                    <i class="fas fa-download"></i> Downloads
                                </a></li>
                                <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo isset($admin_path) ? $admin_path : 'admin/'; ?>index.php">
                                        <i class="fas fa-cog"></i> Painel Admin
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo isset($auth_path) ? $auth_path : 'auth/'; ?>logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Sair
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($auth_path) ? $auth_path : 'auth/'; ?>login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo isset($auth_path) ? $auth_path : 'auth/'; ?>register.php">
                                <i class="fas fa-user-plus"></i> Cadastrar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>