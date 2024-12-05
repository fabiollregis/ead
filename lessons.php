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
$is_admin = ($user_type === 'admin');

// Buscar todas as categorias para o filtro
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializar variáveis de filtro
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Construir a query base
$query = "
    SELECT l.*, c.name as category_name,
           (SELECT COUNT(*) FROM lesson_files WHERE lesson_id = l.id) as file_count
    FROM lessons l 
    LEFT JOIN categories c ON l.category_id = c.id
    WHERE 1=1
";
$params = [];

// Adicionar filtros à query
if ($category_id > 0) {
    $query .= " AND l.category_id = ?";
    $params[] = $category_id;
}

if (!empty($search)) {
    $query .= " AND (l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Adicionar ordenação
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY l.created_at ASC";
        break;
    case 'title_asc':
        $query .= " ORDER BY l.title ASC";
        break;
    case 'title_desc':
        $query .= " ORDER BY l.title DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY l.created_at DESC";
        break;
}

// Executar a query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Aulas - EAD School";
require_once 'includes/header.php';
?>

<div class="container py-4">
    <!-- Filtros -->
    <div class="card border-0 shadow-sm lesson-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Buscar</label>
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               id="search" 
                               name="search" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Buscar por título ou descrição">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Categoria</label>
                    <select class="form-select" id="category" name="category">
                        <option value="0">Todas as categorias</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort" class="form-label">Ordenar por</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Mais recentes</option>
                        <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Mais antigas</option>
                        <option value="title_asc" <?php echo $sort == 'title_asc' ? 'selected' : ''; ?>>Título (A-Z)</option>
                        <option value="title_desc" <?php echo $sort == 'title_desc' ? 'selected' : ''; ?>>Título (Z-A)</option>
                    </select>
                </div>
                <?php if ($is_admin): ?>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="add-lesson.php" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle"></i> Nova Aula
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Lista de Aulas -->
    <?php if (empty($lessons)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Nenhuma aula encontrada.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($lessons as $lesson): ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm lesson-card">
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
                            <?php if (!empty($lesson['category_name'])): ?>
                                <span class="badge bg-primary mb-2">
                                    <?php echo htmlspecialchars($lesson['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($lesson['description'])): ?>
                                <p class="card-text small text-muted">
                                    <?php echo mb_strimwidth(htmlspecialchars($lesson['description']), 0, 100, '...'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event"></i>
                                    <?php echo date('d/m/Y', strtotime($lesson['created_at'])); ?>
                                </small>
                                <div class="btn-group">
                                    <a href="lesson.php?id=<?php echo $lesson['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Ver Aula">
                                        <i class="bi bi-play-circle"></i>
                                    </a>
                                    <?php if ($is_admin): ?>
                                        <a href="edit-lesson.php?id=<?php echo $lesson['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary"
                                           title="Editar Aula">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="confirmDelete(<?php echo $lesson['id']; ?>)"
                                                title="Excluir Aula">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Confirmação de Exclusão -->
<?php if ($is_admin): ?>
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle text-danger"></i> Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir esta aula? Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Cancelar
                </button>
                <form method="POST" action="delete-lesson.php" class="d-inline">
                    <input type="hidden" name="lesson_id" id="deleteLesson_id">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(lessonId) {
    document.getElementById('deleteLesson_id').value = lessonId;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
