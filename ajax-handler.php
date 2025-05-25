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
    
    // Usuwanie przepisu (tylko dla admina)
    if ($action === 'delete_recipe' && isset($_POST['recipe_id']) && $_SESSION['username'] === 'admin') {
        $recipeId = (int)$_POST['recipe_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Usuń powiązane rekordy
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE recipe_id = ?");
            $stmt->execute([$recipeId]);
            
            $stmt = $pdo->prepare("DELETE FROM ratings WHERE recipe_id = ?");
            $stmt->execute([$recipeId]);
            
            $stmt = $pdo->prepare("DELETE FROM recipe_tags WHERE recipe_id = ?");
            $stmt->execute([$recipeId]);
            
            // Pobierz ścieżkę do obrazka przed usunięciem
            $stmt = $pdo->prepare("SELECT image_path FROM recipes WHERE id = ?");
            $stmt->execute([$recipeId]);
            $recipe = $stmt->fetch();
            
            // Usuń przepis
            $stmt = $pdo->prepare("DELETE FROM recipes WHERE id = ?");
            $stmt->execute([$recipeId]);
            
            // Usuń plik obrazka jeśli istnieje
            if ($recipe && $recipe['image_path'] && file_exists($recipe['image_path'])) {
                unlink($recipe['image_path']);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true]);
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => 'database_error']);
            exit();
        }
    }
}

echo json_encode(['success' => false, 'error' => 'invalid_request']);
?>