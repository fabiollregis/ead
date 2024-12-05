<?php
// Arquivo de verificação de segurança
require_once 'config.php';

// Verifica se o usuário é admin
check_admin();

// Array para armazenar os resultados
$security_checks = [];

// 1. Verificar permissões de diretórios
$directories = [
    'uploads' => ['required' => true, 'permissions' => 0777],
    'logs' => ['required' => true, 'permissions' => 0755]
];

foreach ($directories as $dir => $config) {
    $path = __DIR__ . '/' . $dir;
    $exists = file_exists($path);
    $writable = is_writable($path);
    $perms = file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
    
    $security_checks['directories'][$dir] = [
        'exists' => $exists,
        'writable' => $writable,
        'permissions' => $perms,
        'status' => ($exists && $writable) ? 'OK' : 'ATENÇÃO'
    ];
}

// 2. Verificar configurações do PHP
$php_checks = [
    'display_errors' => ['expected' => 'Off', 'current' => ini_get('display_errors')],
    'error_reporting' => ['expected' => 'E_ALL & ~E_DEPRECATED & ~E_STRICT', 'current' => ini_get('error_reporting')],
    'log_errors' => ['expected' => 'On', 'current' => ini_get('log_errors')],
    'upload_max_filesize' => ['expected' => '64M', 'current' => ini_get('upload_max_filesize')],
    'post_max_size' => ['expected' => '64M', 'current' => ini_get('post_max_size')],
    'memory_limit' => ['expected' => '256M', 'current' => ini_get('memory_limit')],
    'max_execution_time' => ['expected' => '300', 'current' => ini_get('max_execution_time')]
];

$security_checks['php_settings'] = $php_checks;

// 3. Verificar configurações de sessão
$session_checks = [
    'session.cookie_httponly' => ['expected' => '1', 'current' => ini_get('session.cookie_httponly')],
    'session.use_only_cookies' => ['expected' => '1', 'current' => ini_get('session.use_only_cookies')],
    'session.cookie_secure' => ['expected' => '1', 'current' => ini_get('session.cookie_secure')],
    'session.cookie_samesite' => ['expected' => 'Strict', 'current' => ini_get('session.cookie_samesite')]
];

$security_checks['session_settings'] = $session_checks;

// 4. Verificar arquivos críticos
$critical_files = [
    '.htaccess',
    'config.php',
    'uploads/.htaccess',
    'logs/.htaccess'
];

foreach ($critical_files as $file) {
    $path = __DIR__ . '/' . $file;
    $security_checks['critical_files'][$file] = [
        'exists' => file_exists($path),
        'readable' => is_readable($path),
        'writable' => is_writable($path)
    ];
}

// 5. Verificar conexão com banco de dados
try {
    $pdo->query("SELECT 1");
    $security_checks['database'] = ['status' => 'OK', 'message' => 'Conexão estabelecida com sucesso'];
} catch (PDOException $e) {
    $security_checks['database'] = ['status' => 'ERRO', 'message' => 'Falha na conexão com o banco de dados'];
}

// Exibir resultados
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Segurança - EAD School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4">Relatório de Segurança</h1>
        
        <!-- Diretórios -->
        <h2 class="h4 mb-3">Permissões de Diretórios</h2>
        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Diretório</th>
                        <th>Existe</th>
                        <th>Gravável</th>
                        <th>Permissões</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($security_checks['directories'] as $dir => $check): ?>
                    <tr>
                        <td><?= htmlspecialchars($dir) ?></td>
                        <td><?= $check['exists'] ? 'Sim' : 'Não' ?></td>
                        <td><?= $check['writable'] ? 'Sim' : 'Não' ?></td>
                        <td><?= $check['permissions'] ?></td>
                        <td>
                            <span class="badge bg-<?= $check['status'] === 'OK' ? 'success' : 'warning' ?>">
                                <?= htmlspecialchars($check['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Configurações PHP -->
        <h2 class="h4 mb-3">Configurações do PHP</h2>
        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Configuração</th>
                        <th>Valor Esperado</th>
                        <th>Valor Atual</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($security_checks['php_settings'] as $setting => $values): ?>
                    <tr>
                        <td><?= htmlspecialchars($setting) ?></td>
                        <td><?= htmlspecialchars($values['expected']) ?></td>
                        <td><?= htmlspecialchars($values['current']) ?></td>
                        <td>
                            <span class="badge bg-<?= $values['expected'] == $values['current'] ? 'success' : 'warning' ?>">
                                <?= $values['expected'] == $values['current'] ? 'OK' : 'ATENÇÃO' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Configurações de Sessão -->
        <h2 class="h4 mb-3">Configurações de Sessão</h2>
        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Configuração</th>
                        <th>Valor Esperado</th>
                        <th>Valor Atual</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($security_checks['session_settings'] as $setting => $values): ?>
                    <tr>
                        <td><?= htmlspecialchars($setting) ?></td>
                        <td><?= htmlspecialchars($values['expected']) ?></td>
                        <td><?= htmlspecialchars($values['current']) ?></td>
                        <td>
                            <span class="badge bg-<?= $values['expected'] == $values['current'] ? 'success' : 'warning' ?>">
                                <?= $values['expected'] == $values['current'] ? 'OK' : 'ATENÇÃO' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Arquivos Críticos -->
        <h2 class="h4 mb-3">Arquivos Críticos</h2>
        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Existe</th>
                        <th>Legível</th>
                        <th>Gravável</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($security_checks['critical_files'] as $file => $check): ?>
                    <tr>
                        <td><?= htmlspecialchars($file) ?></td>
                        <td><?= $check['exists'] ? 'Sim' : 'Não' ?></td>
                        <td><?= $check['readable'] ? 'Sim' : 'Não' ?></td>
                        <td><?= $check['writable'] ? 'Sim' : 'Não' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Status do Banco de Dados -->
        <h2 class="h4 mb-3">Banco de Dados</h2>
        <div class="alert alert-<?= $security_checks['database']['status'] === 'OK' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($security_checks['database']['message']) ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
