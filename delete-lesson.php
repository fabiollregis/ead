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
    $_SESSION['error_message'] = "Acesso negado. Apenas administradores podem excluir aulas.";
    header("Location: lessons.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['lesson_id'])) {
    $lesson_id = (int)$_POST['lesson_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Get all files for this lesson
        $stmt = $pdo->prepare("SELECT file_name FROM lesson_files WHERE lesson_id = ?");
        $stmt->execute([$lesson_id]);
        $files = $stmt->fetchAll();
        
        // Delete physical files
        foreach ($files as $file) {
            $filePath = __DIR__ . '/uploads/' . $file['file_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Delete lesson files from database (will be cascaded due to foreign key)
        $stmt = $pdo->prepare("DELETE FROM lesson_files WHERE lesson_id = ?");
        $stmt->execute([$lesson_id]);
        
        // Delete the lesson
        $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
        $stmt->execute([$lesson_id]);
        
        $pdo->commit();
        
        $_SESSION['success_message'] = "Aula excluída com sucesso!";
        header("Location: lessons.php");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erro ao excluir a aula. Por favor, tente novamente.";
        header("Location: lessons.php");
        exit();
    }
} else {
    header("Location: lessons.php");
    exit();
}
