<?php
// PHP dosyalarımızı dahil edelim
// config.php ve functions.php'yi doğrudan burada dahil ediyoruz
// Bu, $pdo değişkeninin her zaman tanımlı olmasını sağlar.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// header.php zaten oturumu başlatıyor ve kullanıcı ID'sini çekiyor.
// Bu yüzden $current_user_id, $current_user_name, $current_user_profile_image burada zaten tanımlı olacaktır.
// Ancak, index.php'ye özel bazı ek kullanıcı bilgilerini çekelim.
$current_user_id = 1; // Varsayılan olarak Serbülent'in ID'si, header.php'den geliyor

// Kullanıcı bilgilerini çekme (email, phone_number gibi ek detaylar için)
$user_info = null;
try {
    $stmt = $pdo->prepare("SELECT name, email, phone_number, profile_image_path FROM users WHERE id = :id");
    $stmt->bindValue(':id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Kullanıcı detayları çekilirken hata oluştu: " . $e->getMessage());
}

// Eğer user_info çekilemezse veya belirli anahtarlar yoksa varsayılan değerler
$current_user_name = htmlspecialchars($user_info['name'] ?? 'Misafir');
$user_email = htmlspecialchars($user_info['email'] ?? 'bilgi@yok.com');
$user_phone = htmlspecialchars(formatPhoneNumber($user_info['phone_number'] ?? 'Telefon Yok'));
$user_profile_image = htmlspecialchars($user_info['profile_image_path'] ?? 'https://placehold.co/64x64/A8DADC/1D3557?text=AV');


// Toplam varlık değerini çekme (functions.php'de tanımlı olmalı)
// Bu fonksiyon, kullanıcının tüm varlıklarının TRY cinsinden toplam değerini döndürmelidir.
// Eğer böyle bir fonksiyon yoksa, bu kısım hata verebilir veya manuel olarak hesaplanmalıdır.
// Varsayılan olarak 0.00 TL gösterelim eğer fonksiyon yoksa.
$total_assets_value = 0.00;
if (function_exists('getTotalAssetsValue')) {
    $total_assets_value = getTotalAssetsValue($pdo, $current_user_id);
}
$current_balance_display = "₺ " . number_format($total_assets_value, 2, ',', '.');

// Güncel kurları çekme (functions.php'de tanımlı olmalı)
$exchange_rates_data = [];
if (function_exists('getAssetMarketPricesForDisplay')) {
    $exchange_rates_data = getAssetMarketPricesForDisplay($pdo);
}

// Son işlemleri çekme (functions.php'de tanımlı olmalı)
$recent_transactions = [];
if (function_exists('getRecentTransactions')) {
    $recent_transactions = getRecentTransactions($pdo, $current_user_id, 5); // Son 5 işlemi çek
}

// Varlık dağılımı için veri çekme (Pie Chart için) - Orijinal dosyadaki gibi geri getirildi
$asset_distribution_data_for_chart = ['labels' => [], 'series' => []];
if (function_exists('getAssetDistributionForChart')) {
    $asset_distribution_data_for_chart = getAssetDistributionForChart($pdo, $current_user_id);
}
$asset_distribution_labels = $asset_distribution_data_for_chart['labels'];
$asset_distribution_series = $asset_distribution_data_for_chart['series'];


// Şablon başlığı
$site_title = "Varlık Yönetim Sistemi";
?>

<?php include 'header.php'; // header.php dosyasını dahil et ?>

<style>
    /* Sayfanın genel arka plan rengini koyu bir tona ayarla */
    body {
        background-color: #2D3748; /* Tailwind gray-800'e yakın, gözü yormayan bir ton */
    }
    /* Ana içerik alanındaki genel metin rengini ayarla (kartlar beyaz kalacağı için içlerindeki metinler siyah kalmalı) */
    main {
        color: #E2E8F0; /* Koyu arka plan üzerinde açık bir metin rengi */
    }
</style>

<!-- Kenarlara daha yakın olması için padding azaltıldı ve sütun genişlikleri ayarlandı -->
<main class="flex-grow px-4 py-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- SOL SÜTUN: Güncel Kurlar (lg:col-span-4 olarak genişletildi) -->
        <div class="lg:col-span-4 flex flex-col gap-6">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h5 class="text-xl font-semibold text-gray-800 mb-4">Güncel Kurlar</h5>
                <?php if (!empty($exchange_rates_data)): ?>
                    <?php foreach ($exchange_rates_data as $rate): ?>
                        <div class="flex items-center justify-between border-b border-gray-200 pb-3 mb-3 last:border-b-0 last:pb-0 last:mb-0">
                            <div class="flex items-center">
                                <?php
                                $symbol_for_placeholder = strtoupper(substr($rate['asset_code'] ?? 'SYM', 0, 3));
                                $icon_src = htmlspecialchars($rate['icon_path'] ?? 'https://placehold.co/40x40/E63946/FFFFFF?text=' . $symbol_for_placeholder);
                                ?>
                                <img src="<?php echo $icon_src; ?>"
                                     class="w-10 h-10 rounded-full mr-3 object-cover"
                                     alt="<?php echo htmlspecialchars($rate['asset_name'] ?? 'Bilinmeyen Kur'); ?> İkonu"
                                     onerror="this.onerror=null;this.src='https://placehold.co/40x40/E63946/FFFFFF?text=<?php echo $symbol_for_placeholder; ?>';">
                                <div>
                                    <p class="font-medium text-gray-700"><?php echo htmlspecialchars($rate['asset_code'] ?? 'N/A'); ?></p>
                                    <h6 class="text-sm text-gray-500">1 <?php echo htmlspecialchars($rate['asset_name'] ?? 'N/A'); ?></h6>
                                </div>
                            </div>
                            <div class="text-right">
                                <small class="text-gray-500">Satış</small>
                                <h6 class="text-lg font-semibold text-green-600">₺ <?php echo number_format($rate['selling_price'] ?? 0.00, 2, ',', '.'); ?></h6>
                            </div>
                            <div class="text-right ml-4">
                                <small class="text-gray-500">Alış</small>
                                <h6 class="text-lg font-semibold text-red-600">₺ <?php echo number_format($rate['buying_price'] ?? 0.00, 2, ',', '.'); ?></h6>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-600">Güncel kur bilgisi bulunamadı. Lütfen <a href="admin/index.php" class="text-blue-600 hover:underline">Admin Paneli</a>'nden kurları güncelleyin.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ORTA SÜTUN: Toplam Varlık ve Grafikler (lg:col-span-4 olarak ayarlandı) -->
        <div class="lg:col-span-4 flex flex-col gap-6">
            <!-- Toplam Varlık Değeri Kartı -->
            <div class="bg-gradient-to-r from-purple-600 to-indigo-700 text-white p-6 rounded-xl shadow-lg flex flex-col justify-between h-48">
                <small class="text-purple-200">Toplam Varlık Değeriniz</small>
                <h2 class="text-4xl font-bold mt-2 mb-4"><?php echo $current_balance_display; ?></h2>
                <div class="flex justify-between items-end">
                    <div>
                        <small class="text-purple-200">Geçerlilik Tarihi</small>
                        <p class="text-lg font-semibold">12/2028</p>
                    </div>
                    <div>
                        <small class="text-purple-200">Kart Sahibi</small>
                        <p class="text-lg font-semibold"><?php echo $current_user_name; ?></p>
                    </div>
                </div>
            </div>

            <!-- Grafik Alanları -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h5 class="text-xl font-semibold text-gray-800 mb-4">Varlık Dağılımı</h5>
                    <div id="pie-chart" class="w-full h-64"></div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h5 class="text-xl font-semibold text-gray-800 mb-4">Gelir/Gider Analizi</h5>
                    <div id="chart" class="w-full h-64"></div>
                </div>
            </div>
        </div>

        <!-- SAĞ SÜTUN: Profil, Hızlı İşlemler, Son İşlemler, Para Gönder (lg:col-span-4 olarak genişletildi) -->
        <div class="lg:col-span-4 flex flex-col gap-6">
            <!-- Profil Kartı -->
            <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                <div class="mb-4 relative w-32 h-32 mx-auto">
                    <img src="<?php echo $user_profile_image; ?>"
                         class="w-full h-full rounded-full object-cover border-4 border-purple-500 shadow-md"
                         alt="Profil Resmi"
                         onerror="this.onerror=null;this.src='https://placehold.co/128x128/A8DADC/1D3557?text=<?php echo substr($current_user_name, 0, 1); ?>';">
                    <a href="index.php" class="absolute bottom-0 right-0 bg-blue-500 text-white rounded-full p-2 shadow-md hover:bg-blue-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.38-2.828-2.829z" />
                        </svg>
                    </a>
                </div>
                <h3 class="text-2xl font-semibold text-gray-800 mb-2"><?php echo $current_user_name; ?></h3>
                <p class="text-gray-600 mb-1"><strong>Email:</strong> <?php echo $user_email; ?></p>
                <p class="text-gray-600"><strong>Telefon:</strong> <?php echo $user_phone; ?></p>
            </div>

            <!-- Hızlı İşlemler -->
            <div class="bg-white p-6 rounded-xl shadow-lg grid grid-cols-2 gap-4 text-center">
                <a href="#" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                    </svg>
                    <small class="text-gray-700 font-medium">Para Yükle</small>
                </a>
                <a href="#" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <small class="text-gray-700 font-medium">Tara & Öde</small>
                </a>
                <a href="#" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <small class="text-gray-700 font-medium">Gönder</small>
                </a>
                <a href="#" class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z" />
                    </svg>
                    <small class="text-gray-700 font-medium">İste</small>
                </a>
            </div>

            <!-- Son İşlemler -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h5 class="text-xl font-semibold text-gray-800 mb-4">Son İşlemler</h5>
                <?php if (!empty($recent_transactions)): ?>
                    <?php foreach ($recent_transactions as $transaction): ?>
                        <div class="flex items-center justify-between mb-4 last:mb-0">
                            <div class="flex items-center">
                                <img src="https://placehold.co/40x40/A8DADC/1D3557?text=AV" class="w-10 h-10 rounded-full object-cover mr-3" alt="Kullanıcı Resmi">
                                <div>
                                    <p class="font-medium text-gray-700"><?php echo htmlspecialchars($transaction['asset_name'] ?? 'Bilinmeyen Varlık'); ?></p>
                                    <small class="text-gray-500"><?php echo htmlspecialchars($transaction['transaction_type'] ?? 'Bilinmeyen Tip'); ?></small>
                                </div>
                            </div>
                            <div class="text-right">
                                <small class="text-gray-500 block"><?php echo date('d/m/Y', strtotime($transaction['transaction_date'] ?? date('Y-m-d'))); ?></small>
                                <?php
                                $value_class = '';
                                $sign = '';
                                $transaction_type = $transaction['transaction_type'] ?? '';
                                switch ($transaction_type) {
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
                                <strong class="block text-lg <?php echo $value_class; ?>"><span class="mr-1"><?php echo $sign; ?></span> ₺ <?php echo number_format($transaction['total_try_value'] ?? 0.00, 2, ',', '.'); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-600 text-center mt-4">Henüz bir işlem bulunamadı. Lütfen <a href="add_transaction.php" class="text-blue-600 hover:underline">Yeni İşlem Ekle</a> sayfasından işlem girin.</p>
                <?php endif; ?>

                <div class="border-t border-gray-200 pt-4 mt-4 text-center">
                    <a class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition-colors" href="transactions.php">
                        Tüm İşlemleri Görüntüle
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Para Gönder (Örnek) -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-500 p-6 rounded-xl shadow-lg">
                <h5 class="text-xl font-semibold text-white mb-4">Para Gönder</h5>
                <div class="flex items-center space-x-4">
                    <a href="#">
                        <img src="https://placehold.co/64x64/FF5733/ffffff?text=AW" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-md" alt="Profil">
                    </a>
                    <a href="#">
                        <img src="https://placehold.co/64x64/33FF57/ffffff?text=AD" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-md" alt="Profil">
                    </a>
                    <a href="#">
                        <img src="https://placehold.co/64x64/3357FF/ffffff?text=AE" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-md" alt="Profil">
                    </a>
                    <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center border-2 border-white shadow-md">
                        <a href="#" class="text-gray-700 text-3xl font-bold">+</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; // footer.php dosyasını dahil et ?>

<!-- ApexCharts kütüphanesi ve grafik kodları -->
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        // Pie Chart Options (Varlık Dağılımı için)
        var pieOptions = {
            series: <?php echo json_encode($asset_distribution_series); ?>, // PHP'den gelen dinamik veriler
            labels: <?php echo json_encode($asset_distribution_labels); ?>, // PHP'den gelen dinamik etiketler
            chart: {
                width: 380,
                type: 'donut', // Donut olarak ayarlandı
            },
            colors: ['#8B5CF6', '#EF4444', '#3B82F6', '#F59E0B', '#10B981', '#6366F1'], // Tailwind renklerine yakın
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    // Yüzde ve değeri birlikte gösterme
                    var name = opts.w.globals.labels[opts.seriesIndex];
                    var value = opts.w.globals.series[opts.seriesIndex];
                    var total = opts.w.globals.series.reduce((a, b) => a + b, 0);
                    var percentage = (total > 0) ? (value / total * 100).toFixed(1) : 0; // Toplam 0 ise hatayı önle
                    return name + ": " + percentage + "% (" + value.toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' }) + ")";
                }
            },
            legend: {
                position: 'right', // Legend'ı sağa al
                offsetY: 0,
                height: 200,
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' });
                    }
                }
            }
        };

        var pieChart = new ApexCharts(document.querySelector("#pie-chart"), pieOptions);
        pieChart.render();

        // Bar Chart Options (Gelir/Gider Analizi)
        var barOptions = {
            series: [{
                name: 'Gelir',
                data: [44, 55, 57, 56, 61, 58, 63, 60, 66] // Örnek gelir verileri
            }, {
                name: 'Gider',
                data: [76, 85, 101, 98, 87, 105, 91, 114, 94] // Örnek gider verileri
            }, {
                name: 'Transfer',
                data: [35, 41, 36, 26, 45, 48, 52, 53, 41] // Örnek transfer verileri
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: ['Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki'],
            },
            yaxis: {
                title: {
                    text: '₺ (binler)',
                    style: {
                        color: '#4B5563' // Tailwind gray-700
                    }
                },
                labels: {
                    style: {
                        colors: '#4B5563' // Tailwind gray-700
                    }
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "₺ " + val + " bin"
                    }
                }
            },
            colors: ['#10B981', '#F59E0B', '#3B82F6'] // Tailwind renklerine yakın
        };

        var barChart = new ApexCharts(document.querySelector("#chart"), barOptions);
        barChart.render();
    });
</script>
