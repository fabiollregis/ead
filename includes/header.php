<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configurações
$config_path = __DIR__ . '/../config.php';
if (file_exists($config_path)) {
    require_once $config_path;
} else {
    die("Erro: Arquivo de configuração não encontrado.");
}

if (!isset($pageTitle)) {
    $pageTitle = "EAD Tecno Solution";
}

// Verificar se está na página de login
$isLoginPage = basename($_SERVER['PHP_SELF']) === 'index.php';

// Se não for a página de login e o usuário não estiver logado, redirecionar
if (!$isLoginPage && !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Verificar se é admin
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT type FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_type = $stmt->fetchColumn();
    $is_admin = ($user_type === 'admin');
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'EAD School'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <?php
    // Buscar configurações do banco de dados
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('header_bg_color', 'header_text_color', 'header_hover_color', 'site_name', 'site_logo')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $header_bg_color = $settings['header_bg_color'] ?? '#0d6efd';
    $header_text_color = $settings['header_text_color'] ?? '#ffffff';
    $header_hover_color = $settings['header_hover_color'] ?? '#0b5ed7';
    $site_name = $settings['site_name'] ?? 'EAD School';
    $site_logo = $settings['site_logo'] ?? '';
    ?>
    
    <style>
        :root {
            --bs-body-bg: #f8f9fa;
        }
        [data-bs-theme="dark"] {
            --bs-body-bg: #212529;
            --bs-body-color: #dee2e6;
        }
        .navbar-custom {
            background-color: <?php echo $header_bg_color; ?> !important;
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link,
        .navbar-custom .navbar-text,
        .navbar-custom .theme-switch i {
            color: <?php echo $header_text_color; ?> !important;
        }
        .navbar-custom .nav-link:hover,
        .navbar-custom .navbar-brand:hover {
            color: <?php echo $header_hover_color; ?> !important;
        }
        .navbar-brand img {
            max-height: 40px;
            width: auto;
            margin-right: 10px;
        }
        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .lesson-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        .lesson-card .card-body {
            padding: 1.5rem;
        }
        .lesson-card .card-footer {
            background: transparent;
            border-top: 1px solid rgba(0,0,0,.125);
            padding: 1rem 1.5rem;
        }
        [data-bs-theme="dark"] .lesson-card {
            background: #2b3035;
        }
        [data-bs-theme="dark"] .lesson-card .card-footer {
            border-top: 1px solid rgba(255,255,255,.125);
        }
        .theme-switch {
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s;
        }
        .theme-switch:hover {
            background-color: rgba(var(--bs-secondary-rgb), 0.1);
        }
    </style>
</head>
<body>
    <?php if (!$isLoginPage): ?>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <?php if (!empty($site_logo) && file_exists('assets/images/' . $site_logo)): ?>
                    <img src="assets/images/<?php echo htmlspecialchars($site_logo); ?>" 
                         alt="Logo" 
                         class="me-2">
                <?php endif; ?>
                <?php echo htmlspecialchars($site_name); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php">
                            <i class="bi bi-house"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lessons.php' ? 'active' : ''; ?>" 
                           href="lessons.php">
                            <i class="bi bi-collection-play"></i> Aulas
                        </a>
                    </li>
                    <?php if ($is_admin): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" 
                           href="categories.php">
                            <i class="bi bi-folder"></i> Categorias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" 
                           href="users.php">
                            <i class="bi bi-people"></i> Usuários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'site-settings.php' ? 'active' : ''; ?>" 
                           href="site-settings.php">
                            <i class="bi bi-gear"></i> Configurações
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <div class="theme-switch" onclick="toggleTheme()" title="Alternar Tema">
                            <i class="bi bi-sun-fill" id="themeIcon"></i>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php" onclick="event.preventDefault(); window.location.href='profile.php';">
                                    <i class="bi bi-person"></i> Meu Perfil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Sair
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="bi bi-person-circle"></i> 
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php endif; ?>

    <script>
        // Verifica o tema atual no localStorage ou preferência do sistema
        function getPreferredTheme() {
            const storedTheme = localStorage.getItem('theme');
            if (storedTheme) {
                return storedTheme;
            }
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        // Aplica o tema
        function setTheme(theme) {
            document.documentElement.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
            updateThemeIcon(theme);
        }

        // Atualiza o ícone do tema
        function updateThemeIcon(theme) {
            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
            }
        }

        // Alterna entre os temas
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        }

        // Aplica o tema inicial
        document.addEventListener('DOMContentLoaded', function() {
            const preferredTheme = getPreferredTheme();
            setTheme(preferredTheme);

            // Adiciona listener para mudanças na preferência do sistema
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (!localStorage.getItem('theme')) {
                    setTheme(e.matches ? 'dark' : 'light');
                }
            });
        });
    </script>
