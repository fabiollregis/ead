<?php
// Verificar e iniciar sessão se necessário
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
    $pageTitle = "EAD School";
}

// Buscar configurações do site
try {
    if (!isset($pdo)) {
        throw new Exception("Erro: Conexão com o banco de dados não estabelecida.");
    }
    
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_logo', 'site_name')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $site_logo = $settings['site_logo'] ?? '';
    $site_name = $settings['site_name'] ?? 'EAD School';
} catch (Exception $e) {
    error_log("Erro ao buscar configurações do site: " . $e->getMessage());
    $site_logo = '';
    $site_name = 'EAD School';
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --bs-body-bg: #f8f9fa;
        }
        [data-bs-theme="dark"] {
            --bs-body-bg: #212529;
            --bs-body-color: #dee2e6;
        }
        .course-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border-radius: 15px;
            overflow: hidden;
        }
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .course-card .card-body {
            padding: 1.5rem;
        }
        .course-card .card-footer {
            background: transparent;
            border-top: 1px solid rgba(0,0,0,.125);
            padding: 1rem 1.5rem;
        }
        [data-bs-theme="dark"] .course-card {
            background: #2b3035;
        }
        [data-bs-theme="dark"] .course-card .card-footer {
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
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .hero-section {
            padding: 5rem 0;
            background: linear-gradient(45deg, #0d6efd, #0dcaf0);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <?php 
                $logo_path = __DIR__ . '/../assets/images/' . $site_logo;
                if ($site_logo && file_exists($logo_path)): 
                ?>
                    <img src="assets/images/<?php echo htmlspecialchars($site_logo); ?>" 
                         alt="Logo" 
                         class="me-2"
                         style="max-height: 40px; width: auto;">
                <?php endif; ?>
                <?php echo htmlspecialchars($site_name); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house"></i> Início
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#cursos">
                            <i class="bi bi-collection-play"></i> Cursos
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <div class="theme-switch" onclick="toggleTheme()" title="Alternar Tema">
                            <i class="bi bi-sun-fill" id="themeIcon"></i>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="bi bi-box-arrow-in-right"></i> Entrar
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
        });
    </script>
