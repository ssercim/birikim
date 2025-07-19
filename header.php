<?php
// config.php dosyasını dahil ediyoruz. Bu dosya veritabanı bağlantısını ($pdo) ve formatlama fonksiyonlarını içerir.
// Bu dosyanın, header.php'nin dahil edildiği her sayfada (örn: index.php) en başta dahil edilmesi gerekmektedir.
// __DIR__ sabiti, mevcut dosyanın (header.php) bulunduğu dizini temsil eder.
// Bu sayede, config.php dosyası her zaman doğru yoldan dahil edilir.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php'; // functions.php dosyasını da dahil et

// Oturum başlatma (eğer henüz başlatılmamışsa)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş kontrolü (örnek: kullanıcı ID'si oturumda saklanıyorsa)
// Şu an için basit bir örnek kullanıcı ID'si kullanalım, ileride gerçek giriş sistemi eklenecek.
$current_user_id = 1; // Varsayılan olarak Serbülent'in ID'si

// Kullanıcı adını ve profil resmini çekme (navigasyon çubuğunda göstermek için)
$current_user_name = 'Misafir'; // Varsayılan değer
$current_user_profile_image = 'https://placehold.co/32x32/a78bfa/ffffff?text=PP'; // Varsayılan placeholder

if (isset($current_user_id)) {
    try {
        $stmt = $pdo->prepare("SELECT name, profile_image_path FROM users WHERE id = :id");
        $stmt->bindValue(':id', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_data) {
            $current_user_name = htmlspecialchars($user_data['name']);
            // Eğer veritabanında profil resmi yolu varsa onu kullan, yoksa placeholder
            if (!empty($user_data['profile_image_path'])) {
                $current_user_profile_image = htmlspecialchars($user_data['profile_image_path']);
            }
        }
    } catch (PDOException $e) {
        // Hata durumunda loglama yapabiliriz, kullanıcıya göstermeye gerek yok
        error_log("Kullanıcı adı veya profil resmi çekilirken hata oluştu: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Varlık Yönetim Sistemi</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Genel stil ayarları */
        body {
            font-family: "Inter", sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding-top: 4rem; /* Navigasyon çubuğu için boşluk */
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        /* Navigasyon çubuğu stilleri */
        .navbar {
            background-color: #1f2937; /* Koyu gri */
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed; /* Sayfanın en üstünde sabit kalır */
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000; /* Diğer öğelerin üzerinde görünmesini sağlar */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        .nav-links {
            display: flex;
            gap: 1.5rem;
        }
        .nav-links a {
            color: #d1d5db;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, box-shadow 0.2s ease-in-out, border 0.2s ease-in-out; /* border için de transition eklendi */
            font-weight: 500;
        }
        .nav-links a:hover {
            background-color: #374151;
            color: #ffffff;
        }
        .nav-links a.active {
            background-color: #2563eb; /* Önceki mavi renk geri geldi */
            color: #ffffff;
            /* Neon ışık efekti için box-shadow, cyan tonlarında */
            box-shadow: 0 0 8px rgba(0, 255, 255, 0.8), /* Cyan tonu */
                        0 0 15px rgba(0, 255, 255, 0.4); /* Cyan tonu */
            border: 1px solid rgba(0, 255, 255, 0.6); /* Hafif bir cyan sınır eklendi */
        }
        .user-info {
            color: #d1d5db;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .user-info img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #6366f1;
        }
        /* Mobil uyumluluk */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem;
            }
            .nav-links {
                margin-top: 1rem;
                width: 100%;
                flex-direction: column;
                gap: 0.5rem;
            }
            .nav-links a {
                width: 100%;
                text-align: center;
            }
            .user-info {
                margin-top: 1rem;
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">
            <i class="bi-box mr-2"></i>
            Varlık Yönetimi
        </a>
        <div class="nav-links">
            <a href="index.php" class="active">Ana Sayfa</a>
            <a href="assets.php">Varlıklarım</a>
            <a href="transactions.php">İşlemlerim</a>
            <a href="reports.php">Raporlar</a>
            <a href="admin/index.php">Admin Paneli</a>
        </div>
        <div class="user-info">
            <img src="<?php echo $current_user_profile_image; ?>" alt="Profil Resmi" onerror="this.onerror=null;this.src='https://placehold.co/32x32/a78bfa/ffffff?text=PP';">
            <span>Hoş Geldin, <?php echo $current_user_name; ?></span>
            <a href="logout.php" class="text-white ml-2 hover:text-gray-400" title="Çıkış Yap">
                <i class="bi-box-arrow-right text-xl"></i>
            </a>
        </div>
    </nav>
    <div class="container">
        <!-- Ana içerik bu div içine gelecek -->
