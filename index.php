<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configurações
$config_path = __DIR__ . '/config.php';
if (file_exists($config_path)) {
    require_once $config_path;
} else {
    die("Erro: Arquivo de configuração não encontrado.");
}

$pageTitle = "EAD - Plataforma de Ensino Online";
$isPublicHome = true;

// Se já estiver logado, redirecionar para o dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Processar login se for POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $error = '';

    if (empty($email) || empty($password)) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Email ou senha incorretos.";
        }
    }
}

// Buscar todas as categorias
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar aulas agrupadas por categoria
$categorizedLessons = [];
foreach ($categories as $category) {
    $stmt = $pdo->prepare("
        SELECT l.*, 
               (SELECT COUNT(*) FROM lesson_files WHERE lesson_id = l.id) as file_count
        FROM lessons l 
        WHERE l.category_id = ?
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$category['id']]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($lessons)) {
        $categorizedLessons[$category['id']] = [
            'name' => $category['name'],
            'lessons' => $lessons
        ];
    }
}

require_once 'includes/public_header.php';
?>

<!-- Modal de Login -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">
                    <i class="bi bi-box-arrow-in-right"></i> Entrar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Entrar
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-center">
                <p class="mb-0">Sistema exclusivo para alunos cadastrados</p>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <!-- Hero Section -->
    <div class="row align-items-center py-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold mb-4">Aprenda no seu ritmo</h1>
            <p class="lead mb-4">
                Explore nossa plataforma de ensino online com cursos em diversas áreas do conhecimento.
                Estude quando e onde quiser!
            </p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                <button type="button" class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="bi bi-box-arrow-in-right"></i> Entrar na Plataforma
                </button>
            </div>
        </div>
        <div class="col-lg-6 d-none d-lg-block">
            <div class="hero-image-container">
                <img src="assets/img/hero-image.svg" alt="Educação Online" class="img-fluid hero-image">
            </div>
        </div>
    </div>

    <style>
    .hero-image-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 2rem;
    }
    .hero-image {
        max-width: 100%;
        height: auto;
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
        transition: transform 0.3s ease-in-out;
    }
    .hero-image:hover {
        transform: translateY(-5px);
    }
    </style>

    <!-- Cursos Disponíveis -->
    <?php if (empty($categorizedLessons)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Nenhum curso disponível no momento.
        </div>
    <?php else: ?>
        <?php foreach ($categorizedLessons as $categoryId => $category): ?>
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center p-4">
                    <h3 class="mb-0">
                        <i class="bi bi-folder2-open me-2"></i>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </h3>
                </div>
                <div class="card-body p-4">
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                        <?php foreach ($category['lessons'] as $lesson): ?>
                            <div class="col">
                                <div class="card h-100 border-0 shadow-sm course-card">
                                    <?php if (!empty($lesson['youtube_id'])): ?>
                                        <div class="ratio ratio-16x9 rounded-top">
                                            <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($lesson['youtube_id']); ?>/mqdefault.jpg" 
                                                 class="card-img-top" 
                                                 alt="<?php echo htmlspecialchars($lesson['title']); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title mb-2">
                                            <?php echo htmlspecialchars($lesson['title']); ?>
                                        </h5>
                                        <?php if (!empty($lesson['description'])): ?>
                                            <p class="card-text small text-muted">
                                                <?php echo mb_strimwidth(htmlspecialchars($lesson['description']), 0, 100, '...'); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer bg-transparent p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-file-text"></i>
                                                <?php echo $lesson['file_count']; ?> materiais
                                            </small>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded" 
                                                    data-bs-toggle="modal" data-bs-target="#loginModal">
                                                <i class="bi bi-lock"></i> Acessar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
