<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Get lesson ID from URL
$lesson_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$lesson_id) {
    header("Location: browse.php");
    exit();
}

// Get lesson details with category
$stmt = $pdo->prepare("
    SELECT l.*, c.name as category_name
    FROM lessons l 
    LEFT JOIN categories c ON l.category_id = c.id 
    WHERE l.id = ?
");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    header("Location: browse.php");
    exit();
}

// Get lesson files
$stmt = $pdo->prepare("SELECT * FROM lesson_files WHERE lesson_id = ? ORDER BY id DESC");
$stmt->execute([$lesson_id]);
$files = $stmt->fetchAll();

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Function to get file icon based on type
function getFileIcon($fileType) {
    if (strpos($fileType, 'pdf') !== false) {
        return 'bi-file-pdf';
    } elseif (strpos($fileType, 'word') !== false || strpos($fileType, 'document') !== false) {
        return 'bi-file-word';
    } elseif (strpos($fileType, 'excel') !== false || strpos($fileType, 'spreadsheet') !== false) {
        return 'bi-file-excel';
    } elseif (strpos($fileType, 'zip') !== false || strpos($fileType, 'rar') !== false) {
        return 'bi-file-zip';
    } elseif (strpos($fileType, 'image') !== false) {
        return 'bi-file-image';
    } else {
        return 'bi-file-text';
    }
}

$pageTitle = htmlspecialchars($lesson['title']) . " - EAD School";
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Vídeo e Informações Principais -->
            <div class="card border-0 shadow-sm lesson-card mb-4">
                <div class="ratio ratio-16x9 rounded-top">
                    <iframe 
                        src="https://www.youtube.com/embed/<?php echo htmlspecialchars($lesson['youtube_id']); ?>" 
                        title="<?php echo htmlspecialchars($lesson['title']); ?>"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h3 class="card-title mb-2"><?php echo htmlspecialchars($lesson['title']); ?></h3>
                            <?php if (!empty($lesson['teacher_name'])): ?>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-person-circle"></i>
                                    <?php echo htmlspecialchars($lesson['teacher_name']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="edit-lesson.php?id=<?php echo $lesson['id']; ?>" 
                               class="btn btn-outline-primary rounded">
                                <i class="bi bi-pencil-square"></i> Editar
                            </a>
                            <button type="button" 
                                    class="btn btn-outline-danger rounded"
                                    onclick="confirmDelete(<?php echo $lesson['id']; ?>)">
                                <i class="bi bi-trash3"></i> Excluir
                            </button>
                        </div>
                    </div>
                    <?php if (!empty($lesson['description'])): ?>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($lesson['description'])); ?></p>
                    <?php endif; ?>
                    <div class="d-flex align-items-center text-muted mt-3">
                        <i class="bi bi-calendar-event me-2"></i>
                        <small>Publicado em <?php echo date('d/m/Y', strtotime($lesson['created_at'])); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Arquivos da Aula -->
            <div class="card border-0 shadow-sm lesson-card">
                <div class="card-header bg-transparent border-0 p-4">
                    <h5 class="mb-0">
                        <i class="bi bi-file-earmark-text me-2"></i>
                        Materiais da Aula
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($files)): ?>
                        <p class="text-muted mb-0">Nenhum arquivo disponível.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($files as $file): ?>
                                <a href="download.php?file_id=<?php echo $file['id']; ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 py-3 px-0 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="bi <?php 
                                            $icon = match (strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION))) {
                                                'pdf' => 'bi-file-pdf text-danger',
                                                'doc', 'docx' => 'bi-file-word text-primary',
                                                'xls', 'xlsx' => 'bi-file-excel text-success',
                                                'zip', 'rar' => 'bi-file-zip text-warning',
                                                'txt' => 'bi-file-text text-info',
                                                'jpg', 'jpeg', 'png' => 'bi-file-image text-primary',
                                                default => 'bi-file-earmark text-secondary'
                                            };
                                            echo $icon;
                                            ?> fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($file['original_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo formatFileSize($file['size']); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <i class="bi bi-download text-primary"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                    Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir esta aula?</p>
                <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <form action="delete-lesson.php" method="POST" class="d-inline">
                    <input type="hidden" name="lesson_id" id="deleteLesson_id">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(lessonId) {
    document.getElementById('deleteLesson_id').value = lessonId;
    let deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Função auxiliar para formatar o tamanho do arquivo
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>

<?php require_once 'includes/footer.php'; ?>
