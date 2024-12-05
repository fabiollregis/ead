<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Create uploads directory if it doesn't exist
$uploadsDir = __DIR__ . '/uploads';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

$success_message = $error_message = '';

// Get all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $youtube_url = trim($_POST['youtube_url']);
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $teacher_name = trim($_POST['teacher_name'] ?? '');
    
    // Extract YouTube video ID from URL
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches);
    $youtube_id = $matches[1] ?? '';
    
    if (empty($title) || empty($youtube_id)) {
        $error_message = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            $pdo->beginTransaction();

            // Insert lesson
            $stmt = $pdo->prepare("INSERT INTO lessons (title, description, youtube_id, category_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $description, $youtube_id, $category_id]);
            $lesson_id = $pdo->lastInsertId();

            // Handle file uploads
            if (!empty($_FILES['files']['name'][0])) {
                $allowedTypes = [
                    'application/pdf', 
                    'application/msword', 
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                    'application/vnd.ms-excel', 
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/zip',
                    'application/x-zip',
                    'application/x-zip-compressed',
                    'application/x-rar',
                    'application/x-rar-compressed',
                    'application/vnd.rar',
                    'application/octet-stream',
                    'text/plain',
                    'text/sql',
                    'application/sql',
                    'image/jpeg', 
                    'image/jpg', 
                    'image/png', 
                    'application/json'
                ];
                
                $uploadErrors = [];
                
                foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                        $originalName = $_FILES['files']['name'][$key];
                        $fileType = $_FILES['files']['type'][$key];
                        $fileSize = $_FILES['files']['size'][$key];
                        
                        if (!in_array($fileType, $allowedTypes)) {
                            $uploadErrors[] = "Tipo de arquivo não permitido: $originalName";
                            continue;
                        }
                        
                        // Generate unique filename
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                        $fileName = uniqid() . '_' . time() . '.' . $extension;
                        $targetPath = $uploadsDir . '/' . $fileName;
                        
                        if (move_uploaded_file($tmp_name, $targetPath)) {
                            // Save file information to database
                            $stmt = $pdo->prepare("INSERT INTO lesson_files (lesson_id, file_name, original_name, file_size, file_type) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$lesson_id, $fileName, $originalName, $fileSize, $fileType]);
                        } else {
                            $uploadErrors[] = "Erro ao fazer upload do arquivo: $originalName";
                        }
                    }
                }
            }

            $pdo->commit();
            $success_message = "Aula adicionada com sucesso!";
            
            // Redirect after successful insertion
            header("Location: add-lesson.php?success=1");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Erro ao adicionar aula. Por favor, tente novamente. Erro: " . $e->getMessage();
        }
    }
}

// Handle messages
if (isset($_GET['success'])) {
    $success_message = "Aula adicionada com sucesso!";
}

$pageTitle = "Adicionar Aula - EAD School";
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Add Lesson Form -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Adicionar Nova Aula</h5>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Título da Aula *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="teacher_name" class="form-label">Professor</label>
                                    <input type="text" class="form-control" id="teacher_name" name="teacher_name" 
                                           placeholder="Nome do professor">
                                </div>
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Categoria</label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">Selecione uma categoria</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="youtube_url" class="form-label">URL do Vídeo do YouTube *</label>
                                    <input type="url" class="form-control" id="youtube_url" name="youtube_url" 
                                           placeholder="https://www.youtube.com/watch?v=..." required>
                                    <div class="form-text">Cole a URL completa do vídeo do YouTube</div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="files" class="form-label">Arquivos da Aula</label>
                            <input type="file" class="form-control" id="files" name="files[]" multiple>
                            <div class="form-text">
                                Tipos permitidos: PDF, DOC, DOCX, XLS, XLSX, ZIP, RAR, TXT, JPG, PNG, JSON, SQL<br>
                                Você pode selecionar múltiplos arquivos
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Adicionar Aula</button>
                            <a href="dashboard.php" class="btn btn-secondary">Voltar para Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
