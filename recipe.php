<?php
require_once 'config.php';

$recipeId = $_GET['id'] ?? 0;

// Pobieranie szczegółów przepisu
$stmt = $pdo->prepare("
    SELECT r.*, u.username as author, 
           AVG(rt.rating) as avg_rating,
           COUNT(DISTINCT rt.user_id) as rating_count
    FROM recipes r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN ratings rt ON r.id = rt.recipe_id
    WHERE r.id = ?
    GROUP BY r.id
");
$stmt->execute([$recipeId]);
$recipe = $stmt->fetch();

if (!$recipe) {
    header('Location: index.php');
    exit();
}

// Pobieranie tagów przepisu
$tagsStmt = $pdo->prepare("
    SELECT t.name 
    FROM tags t
    JOIN recipe_tags rt ON t.id = rt.tag_id
    WHERE rt.recipe_id = ?
");
$tagsStmt->execute([$recipeId]);
$recipeTags = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);

// Sprawdzanie czy przepis jest w ulubionych
$isFavorite = false;
$userRating = 0;

if (isLoggedIn()) {
    $favStmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND recipe_id = ?");
    $favStmt->execute([$_SESSION['user_id'], $recipeId]);
    $isFavorite = $favStmt->fetch() !== false;
    
    // Pobieranie oceny użytkownika
    $ratingStmt = $pdo->prepare("SELECT rating FROM ratings WHERE user_id = ? AND recipe_id = ?");
    $ratingStmt->execute([$_SESSION['user_id'], $recipeId]);
    $userRatingRow = $ratingStmt->fetch();
    $userRating = $userRatingRow ? $userRatingRow['rating'] : 0;
}

// Obsługa akcji AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'toggle_favorite') {
            if ($isFavorite) {
                $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?");
            } else {
                $stmt = $pdo->prepare("INSERT INTO favorites (user_id, recipe_id) VALUES (?, ?)");
            }
            $stmt->execute([$_SESSION['user_id'], $recipeId]);
            echo json_encode(['success' => true, 'isFavorite' => !$isFavorite]);
            exit();
        } elseif ($_POST['action'] === 'rate' && isset($_POST['rating'])) {
            $rating = (int)$_POST['rating'];
            if ($rating >= 1 && $rating <= 5) {
                $stmt = $pdo->prepare("INSERT INTO ratings (user_id, recipe_id, rating) VALUES (?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE rating = ?");
                $stmt->execute([$_SESSION['user_id'], $recipeId, $rating, $rating]);
                
                // Pobieranie nowej średniej
                $avgStmt = $pdo->prepare("SELECT AVG(rating) as avg, COUNT(*) as count FROM ratings WHERE recipe_id = ?");
                $avgStmt->execute([$recipeId]);
                $result = $avgStmt->fetch();
                
                echo json_encode([
                    'success' => true, 
                    'avgRating' => round($result['avg'], 1),
                    'ratingCount' => $result['count']
                ]);
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe['title']); ?> - Cook Book</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="nav-container">
                <a href="index.php" class="nav-brand">
                    <div class="logo-icon"></div>
                    <h1>Cook Book</h1>
                </a>
                <ul class="nav-menu">
                    <li><a href="index.php">Przepisy</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="favorites.php">Ulubione</a></li>
                        <li><a href="logout.php">Wyloguj (<?php echo $_SESSION['username']; ?>)</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Zaloguj się</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container">
        <div class="recipe-detail">
            <div class="recipe-header">
                <div>
                    <h1><?php echo htmlspecialchars($recipe['title']); ?></h1>
                    <p class="author">Autor: <?php echo htmlspecialchars($recipe['author']); ?></p>
                    <div class="rating" data-recipe-id="<?php echo $recipeId; ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo $i <= round($recipe['avg_rating']) ? 'filled' : ''; ?> 
                                         <?php echo $i <= $userRating ? 'user-rated' : ''; ?>"
                                  data-rating="<?php echo $i; ?>">★</span>
                        <?php endfor; ?>
                        <span class="rating-count">(<span class="count"><?php echo $recipe['rating_count']; ?></span>)</span>
                    </div>
                </div>
                
                <div class="recipe-actions">
                    <?php if (isLoggedIn()): ?>
                        <button class="favorite-btn <?php echo $isFavorite ? 'active' : ''; ?>" 
                                data-recipe-id="<?php echo $recipeId; ?>">❤</button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($recipe['image_path']): ?>
                <img src="<?php echo $recipe['image_path']; ?>" 
                     alt="<?php echo htmlspecialchars($recipe['title']); ?>" 
                     class="recipe-image">
            <?php endif; ?>
            
            <div class="recipe-tags" style="margin-bottom: 2rem;">
                <?php foreach ($recipeTags as $tag): ?>
                    <a href="index.php?tag=<?php echo $tag; ?>" class="tag">#<?php echo $tag; ?></a>
                <?php endforeach; ?>
            </div>
            
            <div class="recipe-content">
                <div class="ingredients-section">
                    <h2>Składniki</h2>
                    <ul class="ingredients-list">
                        <?php 
                        $ingredients = explode("\n", $recipe['ingredients']);
                        foreach ($ingredients as $ingredient): 
                            $ingredient = trim($ingredient);
                            if ($ingredient):
                        ?>
                            <li><?php echo htmlspecialchars($ingredient); ?></li>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </ul>
                </div>
                
                <div class="description-section">
                    <h2>Przygotowanie</h2>
                    <p><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>
                </div>
            </div>
        </div>
    </main>

    <div class="wave-decoration"></div>
    <script src="script.js"></script>
</body>
</html>