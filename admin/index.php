<?php
// Admin paneli yapılandırma dosyasını dahil et
// Bu dosya oturum başlatma, ADMIN_USERNAME/ADMIN_PASSWORD tanımlarını ve veritabanı bağlantısını içerir.
require_once 'config.php'; // admin/config.php dosyasını dahil ediyoruz

// Eğer kullanıcı giriş yapmamışsa, giriş sayfasına yönlendir
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); // login.php'ye yönlendir
    exit;
}

// Hangi admin sayfasının yükleneceğini belirle
// Varsayılan olarak 'users' sayfasını yükle
$page = $_GET['page'] ?? 'users';

// Sayfa içeriğini dahil etmeden önce güvenlik kontrolü
// Sadece izin verilen sayfaların dahil edilmesini sağla
$allowed_pages = [
    'users' => 'users.php', // users.php'yi dahil ediyoruz
    // Diğer admin sayfaları buraya eklenecek
    // 'asset_definitions' => 'asset_definitions.php',
    // 'bank_accounts' => 'bank_accounts.php',
    // 'transaction_categories' => 'transaction_categories.php',
    // 'user_settings' => 'user_settings.php',
];

$include_file = $allowed_pages[$page] ?? null;

// Eğer sayfa bulunamazsa veya izin verilmeyen bir sayfa istenirse hata mesajı göster
if (!$include_file) {
    // Basit bir hata mesajı gösterebiliriz
    echo "<h3 class='text-xl font-semibold text-gray-700'>Sayfa bulunamadı veya erişim izniniz yok.</h3>";
    exit; // Script'i durdur
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - <?php echo ucfirst(str_replace('_', ' ', $page)); ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: "Inter", sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #1f2937; /* Koyu gri */
            color: #ffffff;
            padding: 2rem 1rem;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            color: #d1d5db; /* Açık gri */
            text-decoration: none;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
            font-weight: 500;
        }
        .sidebar-link:hover {
            background-color: #374151; /* Daha koyu gri */
            color: #ffffff;
        }
        .sidebar-link.active {
            background-color: #4f46e5; /* Mor */
            color: #ffffff;
            font-weight: 600;
        }
        .sidebar-link svg {
            margin-right: 0.75rem;
            width: 20px;
            height: 20px;
        }
        .main-content {
            flex-grow: 1;
            padding: 2rem;
            overflow-y: auto; /* İçerik taşarsa kaydırma çubuğu çıksın */
        }
        .logout-button {
            background-color: #dc2626; /* Kırmızı */
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-align: center;
            margin-top: auto; /* En alta hizala */
            transition: background-color 0.2s ease-in-out;
        }
        .logout-button:hover {
            background-color: #b91c1c;
        }
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                padding: 1rem;
                flex-direction: row;
                justify-content: space-around;
                flex-wrap: wrap;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }
            .sidebar-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }
            .sidebar-link svg {
                margin-right: 0.5rem;
            }
            .main-content {
                padding: 1.5rem;
            }
            .logout-button {
                width: 100%;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h1 class="text-2xl font-bold mb-6 text-center">Admin Paneli</h1>
        <nav class="flex flex-col gap-2">
            <a href="?page=users" class="sidebar-link <?php echo ($page === 'users' ? 'active' : ''); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                Kullanıcılar
            </a>
            <!-- Diğer admin sayfaları için linkler buraya eklenecek -->
            <!--
            <a href="?page=asset_definitions" class="sidebar-link <?php // echo ($page === 'asset_definitions' ? 'active' : ''); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.104c1.452 1.01 3.102 1.01 4.554 0a60.07 60.07 0 00-15.797-2.104zM5.25 7.5A2.25 2.25 0 017.5 5.25h9a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 002.25 2.25h-16.5A2.25 2.25 0 002.25 18V7.5z" />
                </svg>
                Varlık Tanımları
            </a>
            <a href="?page=transaction_categories" class="sidebar-link <?php // echo ($page === 'transaction_categories' ? 'active' : ''); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.22A3.004 3.004 0 0012 15c.864 0 1.674.24 2.347.664M12 21v-8.25m0 0l1.394-1.395M12 12.75l-1.394-1.395M12 12.75V10.5m0 0a.75.75 0 01-.75-.75h-3.5a.75.75 0 01-.75-.75V6.75a.75.75 0 01.75-.75h3.5a.75.75 0 01.75.75v.75m-4.5 3.75l-2.623-2.622c-.89-.89-2.336 0-3.225L7.5 3.75m9.75 9.75l2.623 2.622c.89.89.89 2.336 0 3.225L16.5 20.25m-4.5-11.25H15c.621 0 1.125.504 1.125 1.125v.75M12 10.5h-1.5m0 0H9.75m0 0V10.5m0 0a.75.75 0 01-.75-.75h-3.5a.75.75 0 01-.75-.75V6.75a.75.75 0 01.75-.75h3.5a.75.75 0 01.75.75v.75" />
                </svg>
                İşlem Kategorileri
            </a>
            <a href="?page=bank_accounts" class="sidebar-link <?php // echo ($page === 'bank_accounts' ? 'active' : ''); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9H19.5a2.25 2.25 0 012.25 2.25v2.25a2.25 2.25 0 01-2.25 2.25H2.25a2.25 2.25 0 01-2.25-2.25v-2.25A2.25 2.25 0 012.25 9zM12 12.75h.008v.008H12v-.008zM12 15.75h.008v.008H12v-.008z" />
                </svg>
                Banka Hesapları
            </a>
            <a href="?page=user_settings" class="sidebar-link <?php // echo ($page === 'user_settings' ? 'active' : ''); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.17.992c.075.445.295.838.627 1.166l.73.73c.328.33.72.552 1.165.628l.992.17c.542.09.94.56.94 1.11v1.094c0 .55-.398 1.02-.94 1.11l-.992.17c-.445.075-.838.295-1.166.627l-.73.73c-.33.328-.552.72-.628 1.165l-.17.992c-.09.542-.56.94-1.11.94h-1.093c-.55 0-1.02-.398-1.11-.94l-.17-.992c-.075-.445-.295-.838-.627-1.166l-.73-.73c-.328-.33-.72-.552-1.165-.628l-.992-.17c-.542-.09-.94-.56-.94-1.11V12c0-.55.398-1.02.94-1.11l.992-.17c.445-.075.838-.295 1.166-.627l.73-.73c.33-.328.552-.72.628 1.165l.17-.992z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Kullanıcı Ayarları
            </a>
            -->
        </nav>
        <a href="logout.php" class="logout-button">Çıkış Yap</a>
    </aside>

    <main class="main-content">
        <?php
        // Belirlenen admin sayfasını dahil et
        if ($include_file && file_exists($include_file)) {
            require_once $include_file;
        } else {
            echo "<h3 class='text-xl font-semibold text-gray-700'>Sayfa bulunamadı veya erişim izniniz yok.</h3>";
        }
        ?>
    </main>
</body>
</html>