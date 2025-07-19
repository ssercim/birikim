<?php
// header.php dosyasını dahil ediyoruz. Bu dosya aynı zamanda config.php'yi de dahil eder.
require_once __DIR__ . '/header.php';
// Yardımcı fonksiyonlarımızı içeren functions.php dosyasını dahil ediyoruz.
require_once __DIR__ . '/functions.php';

// Şimdilik varsayılan kullanıcı ID'si (daha sonra giriş sistemi ile dinamikleşecek)
$current_user_id = 1; // Örneğin Serbülent'in ID'si

// Veritabanından dinamik verileri çekme
$user_info = getUserInfo($pdo, $current_user_id);
$current_user_name = $user_info ? htmlspecialchars($user_info['name']) : "Misafir";
$user_profile_image = $user_info && !empty($user_info['profile_image_path']) ? htmlspecialchars($user_info['profile_image_path']) : 'https://placehold.co/64x64/A8DADC/1D3557?text=AV';
$user_email = $user_info && !empty($user_info['email']) ? htmlspecialchars($user_info['email']) : 'bilgi@yok.com';
$user_phone = $user_info && !empty($user_info['phone_number']) ? htmlspecialchars(formatPhoneNumber($user_info['phone_number'])) : 'Telefon Yok';

// Toplam Varlık Değeri Hesaplama
$total_asset_value_try = getTotalAssetsValue($pdo, $current_user_id);
$current_balance_display = formatCurrency($total_asset_value_try, 'TRY');

// Son 5 işlemi çekme
$latest_transactions = getRecentTransactions($pdo, $current_user_id, 5);

// Şablon başlığı
$site_title = "Kişisel Birikimlerim";
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kişisel birikimlerinizi takip edin ve yönetin.">
    <meta name="author" content="Serbülent">
    <title><?php echo $site_title; ?> - Genel Bakış</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- ApexCharts CSS -->
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.css" rel="stylesheet">

    <style>
        /* Genel stil sıfırlamaları ve temel font */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa; /* Açık gri arka plan */
        }
        /* Sidebar stilleri */
        .sidebar {
            background-color: #1a202c; /* Koyu gri sidebar */
            color: #e2e8f0; /* Açık gri metin */
        }
        .sidebar .nav-link {
            color: #a0aec0; /* Gri menü metni */
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link.active {
            background-color: #2d3748; /* Aktif menü arka planı */
            color: #ffffff; /* Aktif menü metni */
            border-radius: 0.5rem;
        }
        .sidebar .nav-link:hover {
            background-color: #2d3748; /* Hover arka planı */
            color: #ffffff; /* Hover metni */
            border-radius: 0.5rem;
        }
        .navbar-brand {
            color: #ffffff !important;
            font-weight: 600;
        }
        /* Kart stilleri */
        .custom-card {
            background-color: #ffffff;
            border-radius: 0.75rem; /* Köşeleri yuvarlak */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* Hafif gölge */
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        /* Profil resmi stilleri */
        .profile-image {
            width: 64px;
            height: 64px;
            border-radius: 9999px; /* Tamamen yuvarlak */
            object-fit: cover;
            border: 2px solid #cbd5e0; /* Açık gri çerçeve */
        }
        .custom-block-profile-image {
            width: 130px;
            height: 130px;
            border-radius: 9999px;
            object-fit: cover;
            border: 4px solid #cbd5e0;
        }
        .exchange-image {
            width: 48px;
            height: 48px;
            border-radius: 0.5rem;
            object-fit: cover;
            margin-right: 1rem;
        }
        /* Bildirimler ve sosyal menü stilleri */
        .notifications-block-wrap {
            width: 300px;
            right: 0;
            left: auto;
        }
        .notifications-block {
            padding: 0.75rem;
            display: flex;
            align-items: center;
        }
        .notifications-icon-wrap {
            width: 40px;
            height: 40px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 0.75rem;
        }
        /* Renkler (Tailwind uyumlu) */
        .bg-success { background-color: #10b981; } /* green-500 */
        .bg-info { background-color: #3b82f6; } /* blue-500 */
        .bg-danger { background-color: #ef4444; } /* red-500 */
        .primary-bg {
            background-color: #4f46e5; /* indigo-600 */
            color: white;
        }
        /* Alt kısım buton stilleri */
        .custom-block-bottom-item {
            flex: 1;
            text-align: center;
            padding: 1rem;
        }
        .custom-block-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #6366f1; /* indigo-500 */
        }
        .custom-block-bottom-item small {
            color: #6366f1;
        }
        .custom-block-bottom-item:hover .custom-block-icon,
        .custom-block-bottom-item:hover small {
            color: #4338ca; /* Koyu indigo hover */
        }
        /* Profil düzenleme ikonu */
        .custom-block-edit-icon {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #6366f1;
            color: white;
            border-radius: 9999px;
            padding: 0.5rem;
            font-size: 1.25rem;
            transform: translate(25%, 25%);
        }
        .custom-block-profile-image-wrap {
            position: relative;
            display: inline-block;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Mobil görünüm için başlık ve menü butonu -->
    <header class="bg-gray-800 text-white p-4 flex items-center justify-between shadow-md sticky top-0 z-50 md:hidden">
        <a class="text-xl font-semibold flex items-center" href="index.php">
            <i class="bi-box mr-2"></i>
            <?php echo $site_title; ?>
        </a>
        <button class="text-white text-2xl focus:outline-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi-list"></i>
        </button>
    </header>

    <!-- Ana düzenleyici div -->
    <div class="flex flex-1">
        <!-- Sidebar Menü -->
        <nav id="sidebarMenu" class="bg-gray-800 text-gray-300 w-64 p-4 flex-shrink-0 md:block hidden md:sticky md:top-0 md:h-screen overflow-y-auto">
            <div class="py-4 px-3">
                <a class="text-2xl font-semibold mb-6 flex items-center text-white" href="index.php">
                    <i class="bi-box mr-2"></i>
                    <?php echo $site_title; ?>
                </a>
                <ul class="space-y-2">
                    <li>
                        <a class="nav-link active block p-3 rounded-lg flex items-center hover:bg-gray-700 hover:text-white transition-colors duration-200" aria-current="page" href="index.php">
                            <i class="bi-house-fill mr-3"></i>
                            Genel Bakış
                        </a>
                    </li>
                    <li>
                        <a class="nav-link block p-3 rounded-lg flex items-center hover:bg-gray-700 hover:text-white transition-colors duration-200" href="add_transaction.php">
                            <i class="bi-plus-circle mr-3"></i>
                            Yeni İşlem Ekle
                        </a>
                    </li>
                    <li>
                        <a class="nav-link block p-3 rounded-lg flex items-center hover:bg-gray-700 hover:text-white transition-colors duration-200" href="admin_panel.php">
                            <i class="bi-gear mr-3"></i>
                            Yönetim Paneli
                        </a>
                    </li>
                    <li class="border-t border-gray-700 pt-2 mt-2">
                        <a class="nav-link block p-3 rounded-lg flex items-center hover:bg-gray-700 hover:text-white transition-colors duration-200" href="#">
                            <i class="bi-box-arrow-left mr-3"></i>
                            Çıkış Yap
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Ana İçerik Alanı -->
        <main class="flex-1 p-6 md:ml-0 overflow-auto">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Genel Bakış</h1>
                <small class="text-gray-600">Merhaba <?php echo $current_user_name; ?>, hoş geldiniz!</small>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <!-- Toplam Varlık Değeri Kartı -->
                    <div class="custom-card bg-gradient-to-r from-indigo-500 to-purple-600 text-white p-6 shadow-lg">
                        <small class="text-indigo-200">Toplam Varlık Değeriniz</small>
                        <h2 class="text-4xl font-bold mt-2 mb-4"><?php echo $current_balance_display; ?></h2>

                        <div class="flex justify-between items-center text-indigo-100 text-sm">
                            <div>
                                <small>Geçerlilik Tarihi</small>
                                <p>12/2028</p>
                            </div>
                            <div class="text-right">
                                <small>Kart Sahibi</small>
                                <p><?php echo $current_user_name; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Gelir/Gider Analizi Grafiği -->
                    <div class="custom-card">
                        <h5 class="text-xl font-semibold text-gray-800 mb-4">Gelir/Gider Analizi</h5>
                        <div id="chart"></div>
                    </div>

                    <!-- Güncel Kurlar -->
                    <div class="custom-card">
                        <h5 class="text-xl font-semibold text-gray-800 mb-4">Güncel Kurlar</h5>
                        <?php
                        $exchange_rates_data = getAssetMarketPricesForDisplay($pdo);
                        if (!empty($exchange_rates_data)): ?>
                            <?php foreach ($exchange_rates_data as $rate): ?>
                                <div class="flex items-center border-b border-gray-200 pb-3 mb-3 last:border-b-0 last:pb-0 last:mb-0">
                                    <img src="<?php echo htmlspecialchars($rate['icon_path']); ?>" class="exchange-image" alt="<?php echo htmlspecialchars($rate['asset_name']); ?> İkonu" onerror="this.onerror=null;this.src='https://placehold.co/48x48/E63946/FFFFFF?text=<?php echo htmlspecialchars($rate['asset_code']); ?>';">
                                    <div class="flex-1">
                                        <p class="text-gray-700 font-medium"><?php echo htmlspecialchars($rate['asset_code']); ?></p>
                                        <h6 class="text-sm text-gray-500">1 <?php echo htmlspecialchars($rate['asset_name']); ?></h6>
                                    </div>
                                    <div class="text-right mr-4">
                                        <small class="text-gray-500">Satış</small>
                                        <h6 class="text-gray-800 font-semibold">₺ <?php echo number_format($rate['selling_price'], 2, ',', '.'); ?></h6>
                                    </div>
                                    <div class="text-right">
                                        <small class="text-gray-500">Alış</small>
                                        <h6 class="text-gray-800 font-semibold">₺ <?php echo number_format($rate['buying_price'], 2, ',', '.'); ?></h6>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-gray-500">Güncel kur bilgisi bulunamadı. Lütfen <a href="admin_panel.php" class="text-indigo-600 hover:underline">Yönetim Paneli</a>'nden kurları güncelleyin.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <!-- Profil Kartı -->
                    <div class="custom-card text-center">
                        <div class="custom-block-profile-image-wrap mb-4 mx-auto">
                            <img src="<?php echo $user_profile_image; ?>" class="custom-block-profile-image mx-auto" alt="Profil Resmi" onerror="this.onerror=null;this.src='https://placehold.co/130x130/A8DADC/1D3557?text=<?php echo substr($current_user_name, 0, 1); ?>';">
                            <a href="index.php" class="bi-pencil-square custom-block-edit-icon absolute bottom-0 right-0 bg-indigo-600 text-white rounded-full p-2 text-lg transform translate-x-1/4 translate-y-1/4 hover:bg-indigo-700 transition-colors duration-200"></a>
                        </div>
                        <p class="flex justify-between items-center mb-2 text-gray-700">
                            <strong class="font-semibold">Ad:</strong>
                            <span><?php echo $current_user_name; ?></span>
                        </p>
                        <p class="flex justify-between items-center mb-2 text-gray-700">
                            <strong class="font-semibold">Email:</strong>
                            <a href="mailto:<?php echo $user_email; ?>" class="text-indigo-600 hover:underline"><?php echo $user_email; ?></a>
                        </p>
                        <p class="flex justify-between items-center mb-0 text-gray-700">
                            <strong class="font-semibold">Telefon:</strong>
                            <a href="tel:<?php echo $user_phone; ?>" class="text-indigo-600 hover:underline"><?php echo $user_phone; ?></a>
                        </p>
                    </div>

                    <!-- Hızlı İşlem Butonları -->
                    <div class="custom-card flex justify-around items-center bg-white p-4">
                        <div class="flex flex-col items-center p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                            <a href="#" class="flex flex-col items-center">
                                <i class="bi-wallet text-indigo-600 text-2xl mb-2"></i>
                                <small class="text-gray-600">Para Yükle</small>
                            </a>
                        </div>
                        <div class="flex flex-col items-center p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                            <a href="#" class="flex flex-col items-center">
                                <i class="bi-upc-scan text-indigo-600 text-2xl mb-2"></i>
                                <small class="text-gray-600">Tara & Öde</small>
                            </a>
                        </div>
                        <div class="flex flex-col items-center p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                            <a href="#" class="flex flex-col items-center">
                                <i class="bi-send text-indigo-600 text-2xl mb-2"></i>
                                <small class="text-gray-600">Gönder</small>
                            </a>
                        </div>
                        <div class="flex flex-col items-center p-2 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                            <a href="#" class="flex flex-col items-center">
                                <i class="bi-arrow-down text-indigo-600 text-2xl mb-2"></i>
                                <small class="text-gray-600">İste</small>
                            </a>
                        </div>
                    </div>

                    <!-- Son İşlemler -->
                    <div class="custom-card">
                        <h5 class="text-xl font-semibold text-gray-800 mb-4">Son İşlemler</h5>
                        <?php if (!empty($latest_transactions)): ?>
                            <?php foreach ($latest_transactions as $transaction): ?>
                                <div class="flex items-center mb-4 last:mb-0">
                                    <img src="https://placehold.co/64x64/A8DADC/1D3557?text=AV" class="profile-image mr-3" alt="Kullanıcı Resmi">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($transaction['name']); ?></p>
                                        <small class="text-gray-500"><?php echo htmlspecialchars($transaction['transaction_type']); ?></small>
                                    </div>
                                    <div class="text-right">
                                        <small class="text-gray-500"><?php echo date('d/m/Y', strtotime($transaction['transaction_date'])); ?></small>
                                        <?php
                                        $value_class = '';
                                        $sign = '';
                                        switch ($transaction['transaction_type']) {
                                            case 'Gider':
                                            case 'Alış':
                                            case 'Para Çekme':
                                                $value_class = 'text-red-600';
                                                $sign = '-';
                                                break;
                                            case 'Gelir':
                                            case 'Satış':
                                            case 'Para Yatırma':
                                                $value_class = 'text-green-600';
                                                $sign = '+';
                                                break;
                                            case 'Transfer':
                                                $value_class = 'text-blue-600';
                                                $sign = '';
                                                break;
                                            default:
                                                $value_class = 'text-gray-600';
                                                $sign = '';
                                                break;
                                        }
                                        ?>
                                        <strong class="block <?php echo $value_class; ?>"><span class="mr-1"><?php echo $sign; ?></span> <?php echo formatCurrency($transaction['total_try_value'], 'TRY'); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-gray-500">Henüz bir işlem bulunamadı. Lütfen <a href="add_transaction.php" class="text-indigo-600 hover:underline">Yeni İşlem Ekle</a> sayfasından işlem girin.</p>
                        <?php endif; ?>
                        <div class="border-t border-gray-200 pt-4 mt-4 text-center">
                            <a class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow hover:bg-indigo-700 transition-colors duration-200" href="index.php">
                                Tüm İşlemleri Görüntüle
                                <i class="bi-arrow-up-right-circle-fill ml-2"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Para Gönder Kutusu -->
                    <div class="custom-card primary-bg bg-indigo-600 text-white">
                        <h5 class="text-xl font-semibold mb-4">Para Gönder</h5>
                        <div class="flex space-x-4">
                            <a href="#">
                                <img src="tools/images/profile/young-woman-with-round-glasses-yellow-sweater.jpg" class="profile-image" alt="Kişi 1">
                            </a>
                            <a href="#">
                                <img src="tools/images/profile/young-beautiful-woman-pink-warm-sweater.jpg" class="profile-image" alt="Kişi 2">
                            </a>
                            <a href="#">
                                <img src="tools/images/profile/senior-man-white-sweater-eyeglasses.jpg" class="profile-image" alt="Kişi 3">
                            </a>
                            <div class="w-16 h-16 rounded-full bg-indigo-700 flex items-center justify-center text-white text-2xl">
                                <a href="#">
                                    <i class="bi-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
<?php
// footer.php dosyasını dahil ediyoruz
require_once __DIR__ . '/footer.php';
?>
