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
    $_SESSION['error_message'] = "Acesso negado. Apenas administradores podem excluir usuários.";
    header("Location: users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    // Não permitir excluir o próprio usuário
    if ($user_id === $_SESSION['user_id']) {
        $_SESSION['error_message'] = "Você não pode excluir seu próprio usuário.";
        header("Location: users.php");
        exit();
    }
    
    try {
        // Verificar se o usuário existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Usuário não encontrado.");
        }

        // Excluir o usuário
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $_SESSION['success_message'] = "Usuário excluído com sucesso!";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erro ao excluir usuário: " . $e->getMessage();
    }
    
    header("Location: users.php");
    exit();
} else {
    header("Location: users.php");
    exit();
}
