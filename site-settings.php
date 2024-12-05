<?php
session_start();
require_once 'config.php';

// Debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Verificar se é admin
$stmt = $pdo->prepare("SELECT type FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_type = $stmt->fetchColumn();

if ($user_type !== 'admin') {
    $_SESSION['error'] = "Acesso restrito a administradores.";
    header("Location: dashboard.php");
    exit();
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Processar remoção da logo
        if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_logo'");
            $stmt->execute();
            $old_logo = $stmt->fetchColumn();
            
            if ($old_logo && file_exists('assets/images/' . $old_logo)) {
                unlink('assets/images/' . $old_logo);
            }
            
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = '' WHERE setting_key = 'site_logo'");
            $stmt->execute();
        }
        // Processar upload da nova logo
        elseif (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file = $_FILES['site_logo'];
            
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Tipo de arquivo não permitido. Use JPG, PNG ou GIF.');
            }
            
            // Verificar tamanho do arquivo (2MB máximo)
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception('O arquivo é muito grande. Tamanho máximo permitido: 2MB.');
            }
            
            // Criar diretório de uploads se não existir
            if (!is_dir('assets/images')) {
                mkdir('assets/images', 0777, true);
            }
            
            // Gerar nome único para o arquivo
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $new_filename = 'logo_' . uniqid() . '.' . $file_extension;
            $upload_path = 'assets/images/' . $new_filename;
            
            // Remover logo antiga se existir
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_logo'");
            $stmt->execute();
            $old_logo = $stmt->fetchColumn();
            
            if ($old_logo && file_exists('assets/images/' . $old_logo)) {
                unlink('assets/images/' . $old_logo);
            }
            
            // Fazer upload do novo arquivo
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Atualizar no banco
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_logo', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$new_filename, $new_filename]);
            } else {
                throw new Exception('Erro ao fazer upload do arquivo.');
            }
        }
        
        // Atualizar outras configurações
        $settings = [
            'site_name' => $_POST['site_name'] ?? '',
            'site_description' => $_POST['site_description'] ?? '',
            'header_bg_color' => $_POST['header_bg_color'] ?? '#0d6efd',
            'header_text_color' => $_POST['header_text_color'] ?? '#ffffff',
            'header_hover_color' => $_POST['header_hover_color'] ?? '#0b5ed7'
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        
        $pdo->commit();
        $_SESSION['success'] = 'Configurações atualizadas com sucesso!';
        header('Location: site-settings.php');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Erro ao atualizar as configurações: ' . $e->getMessage();
    }
}

// Buscar configurações atuais
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-gear-fill"></i> Configurações do Site
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <!-- Logo do Site -->
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Logo do Site</h5>
                            <?php if (!empty($settings['site_logo'])): ?>
                                <div class="current-logo mb-3">
                                    <p class="text-muted mb-2">Logo atual:</p>
                                    <img src="assets/images/<?php echo htmlspecialchars($settings['site_logo']); ?>" 
                                         alt="Logo atual" 
                                         class="img-thumbnail"
                                         style="max-height: 100px;">
                                    <div class="mt-2">
                                        <button type="submit" 
                                                name="remove_logo" 
                                                value="1" 
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Tem certeza que deseja remover a logo?')">
                                            <i class="bi bi-trash"></i> Remover Logo
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="site_logo" class="form-label">
                                    <?php echo !empty($settings['site_logo']) ? 'Alterar Logo' : 'Adicionar Logo'; ?>
                                </label>
                                <input type="file" 
                                       class="form-control" 
                                       id="site_logo" 
                                       name="site_logo" 
                                       accept="image/jpeg,image/png,image/gif">
                                <div class="form-text">
                                    Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB
                                </div>
                            </div>
                        </div>

                        <!-- Cores do Header -->
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Cores do Header</h5>
                            <div class="mb-3">
                                <label for="header_bg_color" class="form-label">Cor de Fundo:</label>
                                <input type="color" class="form-control form-control-color" 
                                       id="header_bg_color" 
                                       name="header_bg_color" 
                                       value="<?php echo htmlspecialchars($settings['header_bg_color'] ?? '#0d6efd'); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="header_text_color" class="form-label">Cor do Texto:</label>
                                <input type="color" class="form-control form-control-color" 
                                       id="header_text_color" 
                                       name="header_text_color" 
                                       value="<?php echo htmlspecialchars($settings['header_text_color'] ?? '#ffffff'); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="header_hover_color" class="form-label">Cor do Hover:</label>
                                <input type="color" class="form-control form-control-color" 
                                       id="header_hover_color" 
                                       name="header_hover_color" 
                                       value="<?php echo htmlspecialchars($settings['header_hover_color'] ?? '#0a58ca'); ?>">
                            </div>
                        </div>

                        <!-- Outras configurações -->
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Outras Configurações</h5>
                            <div class="mb-3">
                                <label for="site_name" class="form-label">Nome do Site</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" 
                                       value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="site_description" class="form-label">Descrição do Site</label>
                                <textarea class="form-control" id="site_description" name="site_description" 
                                          rows="3"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
