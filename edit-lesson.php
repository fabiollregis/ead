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
    $_SESSION['error_message'] = "Acesso negado. Apenas administradores podem editar aulas.";
    header("Location: lessons.php");
    exit();
}

$success_message = $error_message = '';
$lesson_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($lesson_id <= 0) {
    header("Location: add-lesson.php");
    exit();
}

// Get all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get lesson details
$stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    header("Location: add-lesson.php");
    exit();
}

// Get lesson files
$stmt = $pdo->prepare("SELECT * FROM lesson_files WHERE lesson_id = ?");
$stmt->execute([$lesson_id]);
$files = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $youtube_url = trim($_POST['youtube_url']);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    
    // Extract YouTube video ID from URL if URL was changed
    if (strpos($youtube_url, 'youtube.com') !== false || strpos($youtube_url, 'youtu.be') !== false) {
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches);
        $youtube_id = $matches[1] ?? '';
    } else {
        $youtube_id = $youtube_url; // Keep existing ID if URL wasn't changed
    }
    
    if (empty($title) || empty($youtube_id)) {
        $error_message = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            $pdo->beginTransaction();

            // Update lesson
            $stmt = $pdo->prepare("UPDATE lessons SET title = ?, description = ?, youtube_id = ?, category_id = ? WHERE id = ?");
            $stmt->execute([$title, $description, $youtube_id, $category_id, $lesson_id]);

            // Handle file uploads
            if (!empty($_FILES['files']['name'][0])) {
                $allowedTypes = [
                    // Documentos
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    // Arquivos compactados
                    'application/zip',
                    'application/x-zip',
                    'application/x-zip-compressed',
                    'application/x-rar',
                    'application/x-rar-compressed',
                    'application/vnd.rar',
                    'application/octet-stream',
                    // Texto e dados
                    'text/plain',
                    'text/sql',
                    'application/sql',
                    'application/json',
                    // Imagens
                    'image/jpeg',
                    'image/jpg',
                    'image/png'
                ];
                
                $uploadErrors = [];
                $uploadsDir = __DIR__ . '/uploads';
                
                // Criar diretório de uploads se não existir
                if (!file_exists($uploadsDir)) {
                    if (!mkdir($uploadsDir, 0777, true)) {
                        throw new Exception('Não foi possível criar o diretório de uploads.');
                    }
                }

                // Verificar se o diretório tem permissão de escrita
                if (!is_writable($uploadsDir)) {
                    throw new Exception('O diretório de uploads não tem permissão de escrita.');
                }
                
                foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                        $originalName = $_FILES['files']['name'][$key];
                        $fileType = $_FILES['files']['type'][$key];
                        $fileSize = $_FILES['files']['size'][$key];
                        
                        // Validar tipo de arquivo
                        if (!in_array($fileType, $allowedTypes)) {
                            $uploadErrors[] = "Tipo de arquivo não permitido: $originalName";
                            continue;
                        }
                        
                        // Validar tamanho do arquivo (máximo 50MB)
                        $maxFileSize = 50 * 1024 * 1024; // 50MB em bytes
                        if ($fileSize > $maxFileSize) {
                            $uploadErrors[] = "Arquivo muito grande: $originalName (máximo 50MB)";
                            continue;
                        }
                        
                        // Generate unique filename
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                        $fileName = uniqid() . '_' . time() . '.' . $extension;
                        $targetPath = $uploadsDir . '/' . $fileName;
                        
                        if (move_uploaded_file($tmp_name, $targetPath)) {
                            // Save file information to database
                            $stmt = $pdo->prepare("INSERT INTO lesson_files (lesson_id, file_name, original_name, file_size, file_type) VALUES (?, ?, ?, ?, ?)");
                            if (!$stmt->execute([$lesson_id, $fileName, $originalName, $fileSize, $fileType])) {
                                // Se falhar ao salvar no banco, remove o arquivo
                                unlink($targetPath);
                                throw new Exception("Erro ao salvar informações do arquivo no banco de dados: $originalName");
                            }
                        } else {
                            $uploadErrors[] = "Erro ao fazer upload do arquivo: $originalName";
                        }
                    } else {
                        $errorMessage = match($_FILES['files']['error'][$key]) {
                            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Arquivo muito grande: $originalName",
                            UPLOAD_ERR_PARTIAL => "Upload incompleto: $originalName",
                            UPLOAD_ERR_NO_FILE => "Nenhum arquivo selecionado",
                            default => "Erro desconhecido ao fazer upload: $originalName"
                        };
                        $uploadErrors[] = $errorMessage;
                    }
                }

                if (!empty($uploadErrors)) {
                    throw new Exception("Erros durante o upload:<br>" . implode("<br>", $uploadErrors));
                }
            }

            // Handle file deletions
            if (!empty($_POST['delete_files'])) {
                foreach ($_POST['delete_files'] as $file_id) {
                    // Get file name before deleting record
                    $stmt = $pdo->prepare("SELECT file_name FROM lesson_files WHERE id = ? AND lesson_id = ?");
                    $stmt->execute([$file_id, $lesson_id]);
                    $file = $stmt->fetch();
                    
                    if ($file) {
                        // Delete file from storage
                        $filePath = __DIR__ . '/uploads/' . $file['file_name'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        
                        // Delete record from database
                        $stmt = $pdo->prepare("DELETE FROM lesson_files WHERE id = ? AND lesson_id = ?");
                        $stmt->execute([$file_id, $lesson_id]);
                    }
                }
            }

            $pdo->commit();
            $success_message = "Aula atualizada com sucesso!";
            
            // Refresh page to show updated data
            header("Location: edit-lesson.php?id=" . $lesson_id . "&success=1");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Erro ao atualizar aula. Por favor, tente novamente.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = $e->getMessage();
        }
    }
}

if (isset($_GET['success'])) {
    $success_message = "Aula atualizada com sucesso!";
}

$pageTitle = "Editar Aula - " . $lesson['title'];
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square"></i> Editar Aula
                    </h5>
                    <a href="lessons.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-left"></i> Voltar para Lista
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Título da Aula *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($lesson['title']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3"><?php echo htmlspecialchars($lesson['description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Categoria</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $lesson['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="youtube_url" class="form-label">ID do Vídeo do YouTube *</label>
                            <input type="text" class="form-control" id="youtube_url" name="youtube_url" 
                                   value="<?php echo htmlspecialchars($lesson['youtube_id']); ?>" required>
                            <div class="form-text">Cole o ID do vídeo ou a URL completa do YouTube</div>
                        </div>

                        <!-- Existing Files -->
                        <?php if (!empty($files)): ?>
                            <div class="mb-3">
                                <label class="form-label">Arquivos Existentes</label>
                                <div class="list-group">
                                    <?php foreach ($files as $file): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <input type="checkbox" name="delete_files[]" 
                                                       value="<?php echo $file['id']; ?>" 
                                                       id="file_<?php echo $file['id']; ?>" 
                                                       class="form-check-input me-2">
                                                <label for="file_<?php echo $file['id']; ?>">
                                                    <?php echo htmlspecialchars($file['original_name']); ?>
                                                </label>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo number_format($file['file_size'] / 1024, 2); ?> KB
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="form-text text-danger">
                                    Marque os arquivos que deseja excluir
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="files" class="form-label">Adicionar Novos Arquivos</label>
                            <input type="file" class="form-control" id="files" name="files[]" multiple>
                            <div class="form-text">
                                Tipos permitidos: PDF, DOC, DOCX, XLS, XLSX, ZIP, RAR, SQL, TXT, JSON, PNG, JPG, JPEG<br>
                                Você pode selecionar múltiplos arquivos (Tamanho máximo: 50MB por arquivo)
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Atualizar Aula</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
