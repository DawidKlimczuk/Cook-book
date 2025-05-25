<?php
require_once 'config.php';

// Definiowanie kategorii
$categories = [
    'sniadania' => [
        'name' => 'Śniadania',
        'icon' => '🍳',
        'description' => 'Rozpocznij dzień od pysznego śniadania'
    ],
    'drugie_sniadanie' => [
        'name' => 'Drugie śniadanie',
        'icon' => '🥐',
        'description' => 'Lekkie przekąski na przedpołudnie'
    ],
    'lunch' => [
        'name' => 'Lunch',
        'icon' => '🥙',
        'description' => 'Szybkie i pożywne dania na południe'
    ],
    'obiady' => [
        'name' => 'Obiady',
        'icon' => '🍝',
        'description' => 'Główne danie dnia'
    ],
    'podwieczorek' => [
        'name' => 'Podwieczorek',
        'icon' => '☕',
        'description' => 'Słodkie i słone przekąski popołudniowe'
    ],
    'kolacje' => [
        'name' => 'Kolacje',
        'icon' => '🥗',
        'description' => 'Lekkie dania na wieczór'
    ],
    'desery' => [
        'name' => 'Desery',
        'icon' => '🍰',
        'description' => 'Słodkie zakończenie posiłku'
    ],
    'przekaski' => [
        'name' => 'Przekąski',
        'icon' => '🥪',
        'description' => 'Szybkie przekąski na każdą porę'
    ]
];

// Pobieranie wybranej kategorii
$selectedCategory = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
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

// Filtrowanie po kategorii
if ($selectedCategory && isset($categories[$selectedCategory])) {
    $sql .= " AND r.category_id = :category";
    $params['category'] = $selectedCategory;
}

if ($search) {
    $sql .= " AND r.title LIKE :search";
    $params['search'] = "%$search%";
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

// Liczenie przepisów w każdej kategorii
$categoryCounts = [];
foreach ($categories as $key => $category) {
    $countSql = "SELECT COUNT(DISTINCT r.id) FROM recipes r WHERE r.category_id = ?";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([$key]);
    $categoryCounts[$key] = $countStmt->fetchColumn();
}

// Sprawdzanie czy użytkownik jest adminem
$isAdmin = isLoggedIn() && $_SESSION['username'] === 'admin';
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategorie - Cook Book</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="nav-container">
                <a href="index.php" class="nav-brand">
                    <div class="logo-icon">
                        <img src="images/logomain.png" alt="Logo" class="logo-img2">  
                    </div>
                    <h1>Cook Book</h1>
                </a>
                <ul class="nav-menu">
                    <li><a href="index.php">Przepisy</a></li>
                    <li><a href="categories.php" class="active">Kategorie</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="pantry.php">Moja spiżarnia</a></li>
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
            <h2>Kategorie przepisów</h2>
        </div>

        <?php if (!$selectedCategory): ?>
            <!-- Wyświetlanie kategorii -->
            <div class="categories-grid">
                <?php foreach ($categories as $key => $category): ?>
                    <a href="?category=<?php echo $key; ?>" class="category-card">
                        <div class="category-icon"><?php echo $category['icon']; ?></div>
                        <h3><?php echo $category['name']; ?></h3>
                        <p class="category-description"><?php echo $category['description']; ?></p>
                        <p class="recipe-count"><?php echo $categoryCounts[$key]; ?> przepisów</p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Wyświetlanie przepisów z kategorii -->
            <div class="filters-section">
                <h3>
                    <a href="categories.php" style="text-decoration: none; color: #666;">← Kategorie</a> / 
                    <?php echo $categories[$selectedCategory]['icon'] . ' ' . $categories[$selectedCategory]['name']; ?>
                </h3>
                
                <form method="GET" class="search-form">
                    <input type="hidden" name="category" value="<?php echo $selectedCategory; ?>">
                    <input type="text" name="search" placeholder="Szukaj w kategorii..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Szukaj</button>
                </form>
                
                <div class="sort-options">
                    <label>Sortuj według:</label>
                    <select name="sort" onchange="window.location.href='?category=<?php echo $selectedCategory; ?>&sort='+this.value+'&search=<?php echo $search; ?>'">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Najnowsze</option>
                        <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Ocena</option>
                        <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Popularność</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Nazwa</option>
                    </select>
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
                        
                        <?php if ($isAdmin): ?>
                            <div class="delete-icon" 
                                 onclick="event.stopPropagation(); deleteRecipe(<?php echo $recipe['id']; ?>)"
                                 title="Usuń przepis">
                                ✕
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
                <p class="no-results">Nie znaleziono przepisów w tej kategorii.</p>
            <?php endif; ?>
        <?php endif; ?>
    </main>

<a href="random.php" class="random-recipe-btn">Zaskocz mnie!</a>
    <div class="wave-decoration"></div>
    <script src="script.js"></script>
</body>
</html>