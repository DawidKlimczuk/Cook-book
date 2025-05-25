<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    $gdpr = isset($_POST['gdpr']) ? 1 : 0;
    
    if (!$gdpr) {
        $error = 'Musisz zaakceptować przetwarzanie danych osobowych';
    } elseif (strlen($password) < 6) {
        $error = 'Hasło musi mieć co najmniej 6 znaków';
    } else {
        // Sprawdzanie czy użytkownik już istnieje
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Użytkownik o takiej nazwie lub emailu już istnieje';
        } else {
            // Rejestracja użytkownika
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, newsletter) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashedPassword, $newsletter])) {
                $success = 'Rejestracja zakończona sukcesem! Możesz się teraz zalogować.';
            } else {
                $error = 'Wystąpił błąd podczas rejestracji';
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
    <title>Zarejestruj się - Cook Book</title>
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
                    <li><a href="login.php" class="active">Zaloguj się</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <div class="auth-container">
            <div class="auth-logo">
                <div class="logo-icon"></div>
                <h3>Cook Book</h3>
            </div>
            
            <h2>Zarejestruj się</h2>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Adres email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Login (nick użytkownika)</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Hasło</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="newsletter" name="newsletter">
                    <label for="newsletter">Chcę otrzymywać powiadomienia o nowych przepisach</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="gdpr" name="gdpr" required>
                    <label for="gdpr">Wyrażam zgodę na przetwarzanie danych osobowych zgodnie z RODO <span class="required">*</span></label>
                </div>
                <p class="required">* pole obowiązkowe</p>
                
                <button type="submit" class="btn btn-primary">Zarejestruj się</button>
            </form>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="login.php" style="color: var(--primary-orange); text-decoration: none;">Masz już konto? Zaloguj się</a>
            </div>
        </div>
    </main>
    
    <div class="wave-decoration"></div>
</body>
</html>