<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cook Book - Przepisy kulinarne</title>
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
                    <li><a href="index.php" class="active">Przepisy</a></li>
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
        <div class="section-header">
            <h2>Nasze przepisy</h2>
        </div>
        
        <div class="filters-section">
            <?php if (isLoggedIn()): ?>
                <div style="text-align: center; margin-bottom: 2rem;">
                    <a href="add-recipe.php" class="btn btn-primary">+ Dodaj przepis</a>
                </div>
            <?php endif; ?>
            
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Szukaj przepisu..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Szukaj</button>
            </form>
            
            <div class="sort-options">
                <label>Sortuj według:</label>
                <select name="sort" onchange="window.location.href='?sort='+this.value+'&search=<?php echo $search; ?>&tag=<?php echo $tag; ?>'">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Najnowsze</option>
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
                    <?php if (isLoggedIn()): ?>
                        <?php
                        $favStmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND recipe_id = ?");
                        $favStmt->execute([$_SESSION['user_id'], $recipe['id']]);
                        $isFav = $favStmt->fetch() !== false;
                        ?>
                        <div class="favorite-icon <?php echo $isFav ? 'active' : ''; ?>" 
                             onclick="event.stopPropagation(); toggleFavorite(<?php echo $recipe['id']; ?>, this)">
                            ❤
                        </div>
                    <?php endif; ?>
                    
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
            <p class="no-results">Nie znaleziono przepisów spełniających kryteria.</p>
        <?php endif; ?>
    </main>

    <div class="wave-decoration"></div>
    <script src="script.js"></script>
</body>
</html><?php
require_once 'config.php';

// Pobieranie parametrów filtrowania
$search = $_GET['search'] ?? '';
$tag = $_GET['tag'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Budowanie zapytania SQL
$sql = "SELECT DISTINCT r.*, rs.avg_rating, rs.rating_count, rs.author,
        GROUP_CONCAT(t.name SEPARATOR ',') as tags
        FROM recipe_stats rs
        JOIN recipes r ON r.id = rs.id
        LEFT JOIN recipe_tags rt ON r.id = rt.recipe_id
        LEFT JOIN tags t ON rt.tag_id = t.id
        WHERE 1=1";

$params = [];

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
        $sql .= " ORDER BY r.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll();

// Pobieranie wszystkich tagów
$tagsStmt = $pdo->query("SELECT * FROM tags ORDER BY name");
$allTags = $tagsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Przepisy kulinarne</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="nav-container">
                <h1>Kulinarne Inspiracje</h1>
                <ul class="nav-menu">
                    <li><a href="index.php" class="active">Przepisy</a></li>
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
        <div class="filters-section">
            <h2>Przepisy</h2>
            
            <?php if (isLoggedIn()): ?>
                <a href="add-recipe.php" class="btn btn-primary">Dodaj przepis</a>
            <?php endif; ?>
            
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Szukaj przepisu..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Szukaj</button>
            </form>
            
            <div class="sort-options">
                <label>Sortuj według:</label>
                <select name="sort" onchange="window.location.href='?sort='+this.value+'&search=<?php echo $search; ?>&tag=<?php echo $tag; ?>'">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Najnowsze</option>
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
                    <img src="<?php echo $recipe['image_path'] ?: 'placeholder.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                    <div class="recipe-info">
                        <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                        <p class="author">Autor: <?php echo htmlspecialchars($recipe['author']); ?></p>
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
            <p class="no-results">Nie znaleziono przepisów spełniających kryteria.</p>
        <?php endif; ?>
    </main>

    <script src="script.js"></script>
</body>
</html>