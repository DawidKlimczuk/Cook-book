<?php
require_once 'config.php';
requireLogin();

// Obsługa dodawania/usuwania produktów
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' && !empty($_POST['product'])) {
            $product = sanitizeInput($_POST['product']);
            $stmt = $pdo->prepare("INSERT INTO pantry (user_id, product_name) VALUES (?, ?)");
            try {
                $stmt->execute([$_SESSION['user_id'], $product]);
            } catch (PDOException $e) {
                // Produkt już istnieje
            }
        } elseif ($_POST['action'] === 'remove' && isset($_POST['product_id'])) {
            $stmt = $pdo->prepare("DELETE FROM pantry WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['product_id'], $_SESSION['user_id']]);
        }
    }
}

// Pobieranie produktów użytkownika
$stmt = $pdo->prepare("SELECT * FROM pantry WHERE user_id = ? ORDER BY product_name");
$stmt->execute([$_SESSION['user_id']]);
$userProducts = $stmt->fetchAll();

// Wyszukiwanie przepisów na podstawie składników
$suggestedRecipes = [];
if (!empty($userProducts)) {
    $productNames = array_column($userProducts, 'product_name');
    
    // Budowanie zapytania do wyszukiwania przepisów
    $searchTerms = array_map(function($product) {
        return "r.ingredients LIKE :prod_" . md5($product);
    }, $productNames);
    
    $sql = "SELECT DISTINCT r.*, rs.avg_rating, rs.rating_count, rs.author,
            GROUP_CONCAT(t.name SEPARATOR ',') as tags,
            (SELECT COUNT(DISTINCT p.product_name) 
             FROM pantry p 
             WHERE p.user_id = :user_id 
             AND r.ingredients LIKE CONCAT('%', p.product_name, '%')
            ) as matching_ingredients
            FROM recipe_stats rs
            JOIN recipes r ON r.id = rs.id
            LEFT JOIN recipe_tags rt ON r.id = rt.recipe_id
            LEFT JOIN tags t ON rt.tag_id = t.id
            WHERE " . implode(" OR ", $searchTerms) . "
            GROUP BY r.id
            ORDER BY matching_ingredients DESC, rs.avg_rating DESC
            LIMIT 12";
    
    $params = ['user_id' => $_SESSION['user_id']];
    foreach ($productNames as $product) {
        $params["prod_" . md5($product)] = "%$product%";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $suggestedRecipes = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moja spiżarnia - Cook Book</title>
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
                    <li><a href="categories.php">Kategorie</a></li>
                    <li><a href="pantry.php" class="active">Moja spiżarnia</a></li>
                    <li><a href="random.php">Losowy przepis</a></li>
                    <li><a href="favorites.php">Ulubione</a></li>
                    <li><a href="logout.php">Wyloguj (<?php echo $_SESSION['username']; ?>)</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container">
        <div class="section-header">
            <h2>Moja spiżarnia 🥫</h2>
            <p>Dodaj produkty które masz w domu, a my zasugerujemy przepisy!</p>
        </div>

        <div class="pantry-container">
            <div class="pantry-sidebar">
                <h3>Twoje produkty (<?php echo count($userProducts); ?>)</h3>
                
                <form method="POST" class="add-product-form">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="product" placeholder="Dodaj produkt..." required>
                    <button type="submit" class="btn btn-primary">+</button>
                </form>
                
                <div class="products-list">
                    <?php foreach ($userProducts as $product): ?>
                        <div class="product-item">
                            <span><?php echo htmlspecialchars($product['product_name']); ?></span>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="remove-btn">×</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($userProducts)): ?>
                        <p class="no-products">Brak produktów w spiżarni</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pantry-recipes">
                <h3>Sugerowane przepisy</h3>
                
                <?php if (!empty($suggestedRecipes)): ?>
                    <div class="recipes-grid">
                        <?php foreach ($suggestedRecipes as $recipe): ?>
                            <div class="recipe-card" onclick="window.location.href='recipe.php?id=<?php echo $recipe['id']; ?>'">
                                <?php
                                $favStmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND recipe_id = ?");
                                $favStmt->execute([$_SESSION['user_id'], $recipe['id']]);
                                $isFav = $favStmt->fetch() !== false;
                                ?>
                                <div class="favorite-icon <?php echo $isFav ? 'active' : ''; ?>" 
                                     onclick="event.stopPropagation(); toggleFavorite(<?php echo $recipe['id']; ?>, this)">
                                    ❤
                                </div>
                                
                                <div class="matching-badge">
                                    <?php echo $recipe['matching_ingredients']; ?> składników
                                </div>
                                
                                <img src="<?php echo $recipe['image_path'] ?: 'placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                                <div class="recipe-info">
                                    <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= round($recipe['avg_rating']) ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                        <span class="rating-count">(<?php echo $recipe['rating_count']; ?>)</span>
                                    </div>
                                    <div class="recipe-tags">
                                        <?php if ($recipe['tags']): ?>
                                            <?php foreach (explode(',', $recipe['tags']) as $recipeTag): ?>
                                                <span class="tag-small">#<?php echo $recipeTag; ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-results">
                        <?php if (empty($userProducts)): ?>
                            Dodaj produkty do spiżarni, aby zobaczyć sugerowane przepisy.
                        <?php else: ?>
                            Nie znaleziono przepisów pasujących do Twoich produktów.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="wave-decoration"></div>
    <script src="script.js"></script>
</body>
</html>