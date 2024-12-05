<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Primeiro, vamos buscar todas as categorias
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Depois, vamos buscar as aulas para cada categoria
$categorizedLessons = [];
$totalLessons = 0;
$totalFiles = 0;

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
        
        $totalLessons += count($lessons);
        foreach ($lessons as $lesson) {
            $totalFiles += $lesson['file_count'];
        }
    }
}

$pageTitle = "Dashboard - EAD School";
require_once 'includes/header.php';
?>

<div class="container py-4">
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm lesson-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="bi bi-collection-play fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="card-title mb-1">Total de Aulas</h6>
                            <h3 class="mb-0"><?php echo $totalLessons; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm lesson-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="bi bi-file-earmark-text fs-4 text-success"></i>
                        </div>
                        <div>
                            <h6 class="card-title mb-1">Arquivos Anexados</h6>
                            <h3 class="mb-0"><?php echo $totalFiles; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm lesson-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <i class="bi bi-lightning fs-4 text-info"></i>
                        </div>
                        <div>
                            <h6 class="card-title mb-1">Ações Rápidas</h6>
                            <div class="d-grid gap-2">
                                <a href="add-lesson.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg"></i> Nova Aula
                                </a>
                                <a href="categories.php" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-folder"></i> Categorias
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lessons by Category -->
    <?php if (empty($categorizedLessons)): ?>
        <div class="card border-0 shadow-sm lesson-card">
            <div class="card-body text-center py-5">
                <i class="bi bi-journal-x display-1 text-muted"></i>
                <p class="lead text-muted mt-3">Nenhuma aula cadastrada ainda.</p>
                <a href="add-lesson.php" class="btn btn-primary mt-2">
                    <i class="bi bi-plus-lg"></i> Adicionar Primeira Aula
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($categorizedLessons as $categoryId => $category): ?>
            <div class="card mb-4 border-0 shadow-sm lesson-card">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center p-4">
                    <h5 class="mb-0">
                        <i class="bi bi-folder2-open me-2"></i>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </h5>
                    <a href="add-lesson.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Nova Aula
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                        <?php foreach ($category['lessons'] as $lesson): ?>
                            <div class="col">
                                <div class="card h-100 border-0 shadow-sm lesson-card">
                                    <!-- Thumbnail do vídeo -->
                                    <div class="ratio ratio-16x9 rounded-top">
                                        <iframe 
                                            src="https://www.youtube.com/embed/<?php echo htmlspecialchars($lesson['youtube_id']); ?>" 
                                            title="<?php echo htmlspecialchars($lesson['title']); ?>"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen>
                                        </iframe>
                                    </div>
                                    
                                    <!-- Corpo do card -->
                                    <div class="card-body p-3">
                                        <h6 class="card-title">
                                            <a href="lesson.php?id=<?php echo $lesson['id']; ?>" 
                                               class="text-decoration-none stretched-link">
                                                <?php echo htmlspecialchars($lesson['title']); ?>
                                            </a>
                                        </h6>
                                        <?php if (!empty($lesson['teacher_name'])): ?>
                                            <p class="text-muted mb-0">
                                                <small>
                                                    <i class="bi bi-person-circle"></i>
                                                    <?php echo htmlspecialchars($lesson['teacher_name']); ?>
                                                </small>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Rodapé do card -->
                                    <div class="card-footer bg-transparent p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-event"></i>
                                                <?php echo date('d/m/Y', strtotime($lesson['created_at'])); ?>
                                            </small>
                                            <div>
                                                <!-- Botão Ver Aula -->
                                                <a href="lesson.php?id=<?php echo $lesson['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary rounded" 
                                                   title="Ver Aula">
                                                    <i class="bi bi-play-circle"></i>
                                                </a>
                                            </div>
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
