<?php
// Admin paneli yapılandırma dosyasını dahil et
// Bu dosya ADMIN_USERNAME ve ADMIN_PASSWORD tanımlarını içerir.
require_once 'config.php'; // config.php dosyasını dahil ediyoruz

$error_message = ''; // Hata mesajlarını tutmak için değişken

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Kullanıcı adı ve şifreyi kontrol et
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        // Giriş başarılı, oturum değişkenini ayarla
        $_SESSION['admin_logged_in'] = true;
        // Kullanıcıyı admin paneline yönlendir
        header('Location: index.php'); // index.php'ye yönlendir
        exit; // Yönlendirme sonrası script'in çalışmasını durdur
    } else {
        // Giriş başarısız, hata mesajı göster
        $error_message = 'Hatalı kullanıcı adı veya şifre!';
    }
}

// Eğer zaten giriş yapılmışsa, admin paneline yönlendir
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php'); // index.php'ye yönlendir
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: "Inter", sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .input-field {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #cbd5e0;
            border-radius: 0.5rem;
            font-size: 1rem;
        }
        .submit-button {
            width: 100%;
            padding: 0.75rem;
            background-color: #6366f1; /* Mor */
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }
        .submit-button:hover {
            background-color: #4f46e5;
        }
        .error-message {
            background-color: #fee2e2;
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Admin Paneli Girişi</h2>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Kullanıcı Adı" class="input-field" required>
            <input type="password" name="password" placeholder="Şifre" class="input-field" required>
            <button type="submit" class="submit-button">Giriş Yap</button>
        </form>
    </div>
</body>
</html>