<?php
// Oturum başlatma
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Tüm oturum değişkenlerini temizle
$_SESSION = array();

// Oturum çerezini sil
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Oturumu yok et
session_destroy();

// Kullanıcıyı giriş sayfasına yönlendir
header('Location: login.php'); // login.php'ye yönlendir
exit;
?>
