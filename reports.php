<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Enable error reporting for debugging
if ($environment === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

try {
    // Get all categories
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();

    // Get filter values
    $selectedCategory = isset($_GET['category']) ? $_GET['category'] : 'all';

    // Base query for lessons
    $query = "SELECT 
                COALESCE(c.name, 'Sem Categoria') as category_name,
                COUNT(DISTINCT l.id) as lesson_count,
                SUM(CASE WHEN l.youtube_id IS NOT NULL AND l.youtube_id != '' THEN 1 ELSE 0 END) as video_count,
                COUNT(DISTINCT lf.id) as file_count
              FROM lessons l
              LEFT JOIN categories c ON l.category_id = c.id
              LEFT JOIN lesson_files lf ON l.id = lf.lesson_id";

    $whereConditions = [];
    $params = [];

    if ($selectedCategory !== 'all') {
        $whereConditions[] = "l.category_id = ?";
        $params[] = $selectedCategory;
    }

    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }

    $query .= " GROUP BY COALESCE(c.name, 'Sem Categoria')";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    // Process data for charts
    $categoryData = [];
    $filesByCategory = [];
    $videosByCategory = [];

    foreach ($data as $row) {
        $categoryName = $row['category_name'];
        
        // Lessons by category
        $categoryData[$categoryName] = $row['lesson_count'];
        
        // Files by category
        $filesByCategory[$categoryName] = $row['file_count'];
        
        // Videos by category
        $videosByCategory[$categoryName] = $row['video_count'];
    }

    $pageTitle = "Relatórios - EAD School";
    require_once 'includes/header.php';
} catch (PDOException $e) {
    // Log the error and show a user-friendly message
    error_log("Database error in reports.php: " . $e->getMessage());
    $error_message = $environment === 'development' ? $e->getMessage() : "Ocorreu um erro ao carregar os relatórios.";
}
?>

<div class="container py-4">
    <h1 class="h2 mb-4">Relatórios</h1>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php else: ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <label for="category" class="form-label">Categoria</label>
                    <select name="category" id="category" class="form-select">
                        <option value="all">Todas as Categorias</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo $selectedCategory == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <!-- Lessons by Category -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aulas por Categoria</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Videos by Category -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Vídeos por Categoria</h5>
                </div>
                <div class="card-body">
                    <canvas id="videosChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Files by Category -->
        <div class="col-md-12 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Arquivos por Categoria</h5>
                </div>
                <div class="card-body">
                    <canvas id="filesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    // Function to generate random colors
    function generateColors(count) {
        const colors = [];
        for (let i = 0; i < count; i++) {
            const hue = (i * 137.508) % 360; // Use golden angle approximation
            colors.push(`hsl(${hue}, 70%, 60%)`);
        }
        return colors;
    }

    // Common chart options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    };

    // Prepare data for charts
    const categoryData = <?php echo json_encode(array_values($categoryData)); ?>;
    const categoryLabels = <?php echo json_encode(array_keys($categoryData)); ?>;
    const filesData = <?php echo json_encode(array_values($filesByCategory)); ?>;
    const filesLabels = <?php echo json_encode(array_keys($filesByCategory)); ?>;
    const videosData = <?php echo json_encode(array_values($videosByCategory)); ?>;
    const videosLabels = <?php echo json_encode(array_keys($videosByCategory)); ?>;

    // Create charts
    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryData,
                backgroundColor: generateColors(categoryData.length)
            }]
        },
        options: commonOptions
    });

    new Chart(document.getElementById('videosChart'), {
        type: 'bar',
        data: {
            labels: videosLabels,
            datasets: [{
                data: videosData,
                backgroundColor: generateColors(videosData.length)
            }]
        },
        options: commonOptions
    });

    new Chart(document.getElementById('filesChart'), {
        type: 'bar',
        data: {
            labels: filesLabels,
            datasets: [{
                data: filesData,
                backgroundColor: generateColors(filesData.length)
            }]
        },
        options: commonOptions
    });
    </script>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
