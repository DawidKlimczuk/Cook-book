<?php
require_once 'config.php';
requireLogin();

// Pobieranie parametrów filtrowania
$search = $_GET['search'] ?? '';
$tag = $_GET['tag'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Budowanie zapytania SQL dla ulubionych
$sql = "SELECT DISTINCT r.*, rs.avg_rating, rs.rating_count, rs.author,
        GROUP_CONCAT(t.name SEPARATOR ',') as tags
        FROM favorites f
        JOIN recipe_stats rs ON f.recipe_id = rs.id
        JOIN recipes r ON r.id = rs.id
        LEFT JOIN recipe_tags rt ON r.id = rt.recipe_id
        LEFT JOIN tags t ON rt.tag_id = t.id
        WHERE f.user_id = :user_id";

$params = ['user_id' => $_SESSION['user_id']];

if ($search) {
    $sql .= " AND r.title LIKE :search";
    $params['search'] = "%$search%";
}

if ($tag) {
    $sql .= " AND r.id IN (
        SELECT recipe_id FROM recipe_tags rt2
        JOIN tags t2 ON rt2.tag_id = t2.id
        WHERE t2.name = :tag
    )";
    $params['tag'] = $tag;
}

$sql .= " GROUP BY r.id";

// Sortowanie
switch($sort) {
    case 'rating':
        $sql .= " ORDER BY rs.avg_rating DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY rs.rating_count DESC";
        break;
    case 'name':
        $sql .= " ORDER BY r.title ASC";
        break;
    default:
        $sql .= " ORDER BY f.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll();

// Liczba ulubionych
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
$countStmt->execute([$_SESSION['user_id']]);
$favoriteCount = $countStmt->fetchColumn();

// Pobieranie wszystkich tagów
$tagsStmt = $pdo->query("SELECT * FROM tags ORDER BY name");
$allTags = $tagsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulubione przepisy - Cook Book</title>
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
                    <li><a href="pantry.php">Moja spiżarnia</a></li>
                    <li><a href="favorites.php" class="active">Ulubione</a></li>
                    <li><a href="logout.php">Wyloguj (<?php echo $_SESSION['username']; ?>)</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container">
        <div class="section-header">
            <h2>Ulubione przepisy (<?php echo $favoriteCount; ?>)</h2>
        </div>
        
        <div class="filters-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Szukaj przepisu..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Szukaj</button>
            </form>
            
            <div class="sort-options">
                <label>Sortuj według:</label>
                <select name="sort" onchange="window.location.href='?sort='+this.value+'&search=<?php echo $search; ?>&tag=<?php echo $tag; ?>'">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Ostatnio dodane</option>
                    <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Ocena</option>
                    <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Popularność</option>
                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Nazwa</option>
                </select>
            </div>
            
            <div class="tags-filter">
                <?php foreach ($allTags as $t): ?>
                    <a href="?tag=<?php echo $t['name']; ?>" 
                       class="tag <?php echo $tag === $t['name'] ? 'active' : ''; ?>">
                        #<?php echo $t['name']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="recipes-grid">
            <?php foreach ($recipes as $recipe): ?>
                <div class="recipe-card" onclick="window.location.href='recipe.php?id=<?php echo $recipe['id']; ?>'">
                    <div class="favorite-icon active" 
                         onclick="event.stopPropagation(); toggleFavorite(<?php echo $recipe['id']; ?>, this)">
                        ❤
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
        
        <?php if (empty($recipes)): ?>
            <p class="no-results">Nie masz jeszcze żadnych ulubionych przepisów.</p>
        <?php endif; ?>
    </main>

    <a href="random.php" class="random-recipe-btn">Zaskocz mnie!</a>
    
    <div class="wave-decoration"></div>
    <script src="script.js"></script>
</body>
</html>