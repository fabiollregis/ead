<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Verificar se é admin
$stmt = $pdo->prepare("SELECT type FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_type = $stmt->fetchColumn();

if ($user_type !== 'admin') {
    $_SESSION['error_message'] = "Acesso negado. Apenas administradores podem editar usuários.";
    header("Location: dashboard.php");
    exit();
}

$success_message = $error_message = '';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header("Location: users.php");
    exit();
}

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $type = $_POST['type'];
    $new_password = trim($_POST['password']);

    if (empty($name) || empty($email)) {
        $error_message = "Nome e email são obrigatórios.";
    } else {
        // Verificar se o email já existe para outro usuário
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error_message = "Este email já está em uso por outro usuário.";
        } else {
            try {
                if (!empty($new_password)) {
                    // Atualizar com nova senha
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ?, type = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $hashed_password, $type, $user_id]);
                } else {
                    // Atualizar sem mudar a senha
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, type = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $type, $user_id]);
                }
                
                $_SESSION['success_message'] = "Usuário atualizado com sucesso!";
                header("Location: users.php");
                exit();
            } catch (PDOException $e) {
                $error_message = "Erro ao atualizar usuário. Por favor, tente novamente.";
            }
        }
    }
}

$pageTitle = "Editar Usuário - EAD School";
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-person-gear"></i> Editar Usuário
                    </h5>
                    <a href="users.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-left"></i> Voltar para Lista
                    </a>
                </div>
                <div class="card-body p-4">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" 
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Tipo de Usuário *</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="user" <?php echo $user['type'] === 'user' ? 'selected' : ''; ?>>
                                    Usuário
                                </option>
                                <option value="admin" <?php echo $user['type'] === 'admin' ? 'selected' : ''; ?>>
                                    Administrador
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Nova Senha (deixe em branco para manter a atual)</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password">
                            <div class="form-text">
                                Preencha apenas se desejar alterar a senha atual.
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
