<?php
/**
 * Funções Auxiliares do Sistema
 * Contém funções utilitárias usadas em todo o sistema
 */

/**
 * Verifica se o usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se o usuário é administrador
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redireciona para página de login se não estiver logado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit();
    }
}

/**
 * Redireciona se não for administrador
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

/**
 * Sanitiza dados de entrada
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Valida email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Gera mensagem de alerta Bootstrap
 */
function showAlert($message, $type = 'info') {
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

/**
 * Conta itens no carrinho
 */
function getCartCount() {
    return isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
}

/**
 * Calcula total do carrinho
 */
function getCartTotal() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    $total = 0;
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            $total += $product['price'] * $quantity;
        }
    }
    
    return $total;
}

/**
 * Formata valor monetário
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Gera token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>