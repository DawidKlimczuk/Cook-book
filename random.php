<?php
require_once 'config.php';

// Pobieranie losowego przepisu
$stmt = $pdo->query("
    SELECT r.*, u.username as author, 
           COALESCE(AVG(rt.rating), 0) as avg_rating,
           COUNT(DISTINCT rt.user_id) as rating_count
    FROM recipes r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN ratings rt ON r.id = rt.recipe_id
    GROUP BY r.id, r.title, r.image_path, r.user_id, r.ingredients, r.description, r.created_at, u.username
    ORDER BY RAND()
    LIMIT 1
");

$recipe = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Losowy przepis - Cook Book</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg,rgb(223, 88, 64) 0%,rgb(167, 47, 47) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        .random-container {
            text-align: center;
            max-width: 600px;
            padding: 2rem;
        }

        .random-title {
            color: white;
            font-size: 3rem;
            margin-bottom: 3rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeInDown 1s ease;
        }

        .recipe-reveal {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            transform: scale(0);
            animation: revealCard 0.6s ease forwards;
            animation-delay: 0.5s;
        }

        .recipe-reveal img {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .recipe-reveal-content {
            padding: 2rem;
        }

        .recipe-reveal h2 {
            color: var(--dark-orange);
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .recipe-reveal .author {
            color: #666;
            margin-bottom: 1rem;
        }

        .recipe-reveal .rating {
            margin-bottom: 2rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .shuffle-button {
            padding: 1rem 2rem;
            background: linear-gradient(45deg, #ff6b6b, #ff8787);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(255, 107, 107, 0.4);
            animation: pulse 2s infinite;
        }

        .shuffle-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(255, 107, 107, 0.6);
        }

        .loading {
            display: none;
            color: white;
            font-size: 2rem;
            margin-top: 2rem;
        }

        .loading.show {
            display: block;
            animation: bounce 1s infinite;
        }

        .no-recipes {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .no-recipes h2 {
            color: var(--dark-orange);
            margin-bottom: 1rem;
        }

        .back-link {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            transition: all 0.3s;
            background: rgba(255,255,255,0.2);
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            backdrop-filter: blur(10px);
        }

        .back-link:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes revealCard {
            to {
                transform: scale(1);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .sparkles {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .sparkle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: white;
            border-radius: 50%;
            animation: sparkleFloat 4s linear infinite;
        }

        @keyframes sparkleFloat {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sparkles" id="sparkles"></div>
    
    <a href="index.php" class="back-link">← Powrót do przepisów</a>

    <div class="random-container">
        <h1 class="random-title">✨ Losowy Przepis ✨</h1>
        
        <?php if ($recipe): ?>
            <div class="recipe-reveal" id="recipeCard">
                <img src="<?php echo $recipe['image_path'] ?: 'placeholder.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                <div class="recipe-reveal-content">
                    <h2><?php echo htmlspecialchars($recipe['title']); ?></h2>
                    <p class="author">Autor: <?php echo htmlspecialchars($recipe['author']); ?></p>
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo $i <= round($recipe['avg_rating']) ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                        <span class="rating-count">(<?php echo $recipe['rating_count']; ?> ocen)</span>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="recipe.php?id=<?php echo $recipe['id']; ?>" class="btn btn-primary">Zobacz przepis</a>
                        <button onclick="shuffleRecipe()" class="btn btn-secondary">Losuj ponownie</button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="no-recipes">
                <h2>😔 Brak przepisów</h2>
                <p>Dodaj najpierw jakieś przepisy do bazy!</p>
                <a href="index.php" class="btn btn-primary" style="margin-top: 1rem;">Wróć do strony głównej</a>
            </div>
        <?php endif; ?>
        
        <div class="loading" id="loading">
            🎲 Losuję nowy przepis...
        </div>
    </div>

    <script>
        // Generowanie iskierek w tle
        function createSparkles() {
            const sparklesContainer = document.getElementById('sparkles');
            const sparkleCount = 50;
            
            for (let i = 0; i < sparkleCount; i++) {
                const sparkle = document.createElement('div');
                sparkle.className = 'sparkle';
                sparkle.style.left = Math.random() * 100 + '%';
                sparkle.style.animationDelay = Math.random() * 4 + 's';
                sparkle.style.animationDuration = (Math.random() * 3 + 4) + 's';
                sparklesContainer.appendChild(sparkle);
            }
        }
        
        createSparkles();
        
        function shuffleRecipe() {
            const card = document.getElementById('recipeCard');
            const loading = document.getElementById('loading');
            
            // Animacja zniknięcia
            card.style.animation = 'revealCard 0.5s ease reverse';
            
            setTimeout(() => {
                loading.classList.add('show');
                
                // Przeładuj stronę po krótkiej animacji
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }, 500);
        }
        
        // Dźwięk przy pojawieniu się karty
        function playRevealSound() {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(523, audioContext.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(1047, audioContext.currentTime + 0.3);
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        }
        
        // Odtwórz dźwięk po załadowaniu
        window.addEventListener('load', () => {
            setTimeout(playRevealSound, 500);
        });
    </script>
</body>
</html>