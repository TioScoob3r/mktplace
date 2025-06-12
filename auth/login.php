<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirecionar se já estiver logado
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_message = 'Por favor, preencha todos os campos.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirecionar baseado no papel do usuário
            if ($user['role'] === 'admin') {
                header('Location: ../admin/index.php');
            } else {
                header('Location: ../index.php');
            }
            exit();
        } else {
            $error_message = 'Email ou senha incorretos.';
        }
    }
}

$page_title = 'Login';
$css_path = '../assets/css/style.css';
$home_path = '../index.php';
$auth_path = '';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-sign-in-alt fa-3x text-primary"></i>
                        <h2 class="mt-3">Login</h2>
                        <p class="text-muted">Acesse sua conta</p>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <?php echo showAlert($error_message, 'danger'); ?>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Entrar
                            </button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p class="mb-0">Não tem uma conta?</p>
                        <a href="register.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-plus"></i> Cadastrar-se
                        </a>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <small>
                            <strong>Conta Admin:</strong><br>
                            Email: admin@marketplace.com<br>
                            Senha: admin123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>