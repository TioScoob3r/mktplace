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
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validações
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = 'Por favor, preencha todos os campos.';
    } elseif (!validateEmail($email)) {
        $error_message = 'Por favor, insira um email válido.';
    } elseif (strlen($password) < 6) {
        $error_message = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'As senhas não coincidem.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Verificar se o email já existe
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error_message = 'Este email já está cadastrado.';
        } else {
            // Criar usuário
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $hashed_password])) {
                $success_message = 'Conta criada com sucesso! Você pode fazer login agora.';
            } else {
                $error_message = 'Erro ao criar conta. Tente novamente.';
            }
        }
    }
}

$page_title = 'Cadastro';
$css_path = '../assets/css/style.css';
$home_path = '../index.php';
$auth_path = '';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-success"></i>
                        <h2 class="mt-3">Cadastro</h2>
                        <p class="text-muted">Crie sua conta</p>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <?php echo showAlert($error_message, 'danger'); ?>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <?php echo showAlert($success_message, 'success'); ?>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome Completo</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="name" name="name" required 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>
                        </div>
                        
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
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            </div>
                            <div class="form-text">Mínimo de 6 caracteres</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Senha</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-user-plus"></i> Criar Conta
                            </button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p class="mb-0">Já tem uma conta?</p>
                        <a href="login.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-sign-in-alt"></i> Fazer Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>