<?php
require_once 'config.php';
requireLogin();

$error = '';
$success = '';

// Pobieranie tagów
$tagsStmt = $pdo->query("SELECT * FROM tags ORDER BY name");
$allTags = $tagsStmt->fetchAll();

// Kategorie
$categories = [
    'sniadania' => ['name' => 'Śniadania', 'icon' => '🍳'],
    'drugie_sniadanie' => ['name' => 'Drugie śniadanie', 'icon' => '🥐'],
    'lunch' => ['name' => 'Lunch', 'icon' => '🥙'],
    'obiady' => ['name' => 'Obiady', 'icon' => '🍝'],
    'podwieczorek' => ['name' => 'Podwieczorek', 'icon' => '☕'],
    'kolacje' => ['name' => 'Kolacje', 'icon' => '🥗'],
    'desery' => ['name' => 'Desery', 'icon' => '🍰'],
    'przekaski' => ['name' => 'Przekąski', 'icon' => '🥪']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $ingredients = sanitizeInput($_POST['ingredients']);
    $description = sanitizeInput($_POST['description']);
    $category_id = $_POST['category_id'] ?? '';
    $tags = $_POST['tags'] ?? [];
    
    if (empty($title) || empty($ingredients) || empty($description) || empty($category_id)) {
        $error = 'Wszystkie pola są wymagane (w tym kategoria)';
    } else {
        // Upload zdjęcia
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imagePath = uploadImage($_FILES['image']);
            if (!$imagePath) {
                $error = 'Błąd podczas przesyłania zdjęcia. Dozwolone formaty: JPG, PNG, GIF, WEBP (max 5MB)';
            }
        }
        
        if (!$error) {
            try {
                $pdo->beginTransaction();
                
                // Dodawanie przepisu
                $stmt = $pdo->prepare("INSERT INTO recipes (user_id, category_id, title, ingredients, description, image_path) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $category_id, $title, $ingredients, $description, $imagePath]);
                $recipeId = $pdo->lastInsertId();
                
                // Dodawanie tagów
                if (!empty($tags)) {
                    $tagStmt = $pdo->prepare("INSERT INTO recipe_tags (recipe_id, tag_id) VALUES (?, ?)");
                    foreach ($tags as $tagId) {
                        $tagStmt->execute([$recipeId, $tagId]);
                    }
                }
                
                $pdo->commit();
                $success = 'Przepis został dodany pomyślnie!';
                
                // Przekierowanie po 2 sekundach
                header("refresh:2;url=recipe.php?id=$recipeId");
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Wystąpił błąd podczas dodawania przepisu';
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
    <title>Dodaj przepis - Cook Book</title>
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
                    <li><a href="categories.php">Kategorie</a></li>
                    <li><a href="pantry.php">Moja spiżarnia</a></li>
                    <li><a href="favorites.php">Ulubione</a></li>
                    <li><a href="logout.php">Wyloguj (<?php echo $_SESSION['username']; ?>)</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="container">
        <div class="section-header">
            <h2>Dodaj nowy przepis</h2>
        </div>
        
        <div class="recipe-detail">
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="auth-form">
                <div class="form-group">
                    <label for="title">Nazwa przepisu</label>
                    <input type="text" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="image">Zdjęcie przepisu</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label>Kategoria przepisu <span class="required">*</span></label>
                    <div class="category-selector">
                        <?php foreach ($categories as $key => $category): ?>
                            <label class="category-option">
                                <input type="radio" name="category_id" value="<?php echo $key; ?>" required
                                       <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $key) ? 'checked' : ''; ?>>
                                <div class="category-box">
                                    <span class="category-icon"><?php echo $category['icon']; ?></span>
                                    <span class="category-name"><?php echo $category['name']; ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="ingredients">Składniki (każdy w nowej linii)</label>
                    <textarea id="ingredients" name="ingredients" rows="6" required><?php echo isset($_POST['ingredients']) ? htmlspecialchars($_POST['ingredients']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="description">Opis przygotowania</label>
                    <textarea id="description" name="description" rows="8" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Tagi (opcjonalne)</label>
                    <div class="tags-filter">
                        <?php foreach ($allTags as $tag): ?>
                            <label class="tag-checkbox">
                                <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>">
                                <span class="tag">#<?php echo $tag['name']; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Dodaj przepis</button>
            </form>
        </div>
    </main>

    <div class="wave-decoration"></div>
    
    <style>
        .category-selector {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .category-option {
            display: block;
            cursor: pointer;
        }
        
        .category-option input[type="radio"] {
            display: none;
        }
        
        .category-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            border: 2px solid #eee;
            border-radius: 15px;
            transition: all 0.3s;
            text-align: center;
        }
        
        .category-option input[type="radio"]:checked + .category-box {
            border-color: var(--primary-orange);
            background-color: #fff5f0;
            transform: scale(1.05);
        }
        
        .category-box:hover {
            border-color: var(--light-orange);
            transform: translateY(-2px);
        }
        
        .category-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .category-name {
            font-size: 0.9rem;
            color: #666;
        }
        
        .category-option input[type="radio"]:checked + .category-box .category-name {
            color: var(--dark-orange);
            font-weight: 600;
        }
        
        .tag-checkbox {
            display: inline-block;
            margin: 0.3rem;
        }
        
        .tag-checkbox input[type="checkbox"] {
            display: none;
        }
        
        .tag-checkbox input[type="checkbox"]:checked + .tag {
            background-color: var(--primary-orange);
            color: white;
        }
        
        textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #eee;
            border-radius: 10px;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.3s;
        }
        
        textarea:focus {
            outline: none;
            border-color: var(--primary-orange);
        }
        
        input[type="file"] {
            padding: 0.8rem;
            border: 2px solid #eee;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
        }
    </style>
</body>
</html>