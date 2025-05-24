<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit();
    } else {
        $error = 'Nieprawidłowa nazwa użytkownika lub hasło';
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaloguj się - Cook Book</title>
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
                <h3>Selekcja Smaku</h3>
            </div>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Login:</label>
                    <input type="text" id="username" name="username" placeholder="........." required>
                </div>
                
                <div class="form-group">
                    <label for="password">Hasło:</label>
                    <input type="password" id="password" name="password" placeholder="........." required>
                </div>
                
                <button type="submit" class="btn btn-primary">Zaloguj</button>
            </form>
            
            <div style="text-align: center; margin-top: 2rem;">
                <p style="color: #666;">Nie masz jeszcze konta?</p>
                <a href="register.php" style="color: var(--primary-orange); text-decoration: none;">Zarejestruj się.</a>
            </div>
        </div>
    </main>

    <div class="wave-decoration"></div>
</body>
</html>