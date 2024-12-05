<?php
session_start();
$pageTitle = "Meu Perfil - EAD Tecno Solution";

// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// Log para debug
error_log("Acessando profile.php - Session ID: " . session_id());
error_log("User ID na sessão: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Não definido'));

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    error_log("Usuário não autenticado - Redirecionando para index.php");
    header("Location: index.php");
    exit();
}

require_once 'includes/header.php';

try {
    // Buscar informações do usuário sem verificar status
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        error_log("Usuário não encontrado: " . $_SESSION['user_id']);
        $_SESSION['error_message'] = "Erro ao carregar informações do usuário.";
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erro no banco de dados: " . $e->getMessage());
    die("Erro ao acessar o banco de dados: " . $e->getMessage());
}

// Mensagens de sucesso/erro
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Processar o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    $errors = [];
    
    // Validar nome e email
    if (empty($name)) {
        $errors[] = "O nome é obrigatório.";
    }
    if (empty($email)) {
        $errors[] = "O email é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido.";
    }
    
    // Verificar se email já existe (exceto para o usuário atual)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Este email já está em uso.";
    }
    
    // Se uma nova senha foi fornecida
    if (!empty($new_password)) {
        // Verificar senha atual
        if (empty($current_password)) {
            $errors[] = "A senha atual é obrigatória para alterar a senha.";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Senha atual incorreta.";
        }
        
        // Validar nova senha
        if (strlen($new_password) < 6) {
            $errors[] = "A nova senha deve ter pelo menos 6 caracteres.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "As senhas não coincidem.";
        }
    }
    
    // Se não houver erros, atualizar o perfil
    if (empty($errors)) {
        try {
            // Iniciar transação
            $pdo->beginTransaction();
            
            // Atualizar nome e email
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $_SESSION['user_id']]);
            
            // Se uma nova senha foi fornecida, atualizá-la
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            }
            
            // Commit da transação
            $pdo->commit();
            
            // Atualizar nome na sessão
            $_SESSION['user_name'] = $name;
            
            $_SESSION['success_message'] = "Perfil atualizado com sucesso!";
            header("Location: profile.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback em caso de erro
            $pdo->rollBack();
            $errors[] = "Erro ao atualizar o perfil. Por favor, tente novamente.";
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-person-circle"></i> Meu Perfil
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="profile.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5>Alterar Senha</h5>
                        <p class="text-muted small">Preencha apenas se desejar alterar sua senha.</p>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Senha Atual</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Salvar Alterações
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
