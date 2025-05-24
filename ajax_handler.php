<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'toggle_favorite' && isset($_POST['recipe_id'])) {
        $recipeId = (int)$_POST['recipe_id'];
        $userId = $_SESSION['user_id'];
        
        // Sprawdzenie czy przepis jest już w ulubionych
        $checkStmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND recipe_id = ?");
        $checkStmt->execute([$userId, $recipeId]);
        $isFavorite = $checkStmt->fetch() !== false;
        
        if ($isFavorite) {
            // Usuń z ulubionych
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?");
            $stmt->execute([$userId, $recipeId]);
        } else {
            // Dodaj do ulubionych
            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, recipe_id) VALUES (?, ?)");
            $stmt->execute([$userId, $recipeId]);
        }
        
        echo json_encode(['success' => true, 'isFavorite' => !$isFavorite]);
        exit();
    }
}

echo json_encode(['success' => false, 'error' => 'invalid_request']);
?>