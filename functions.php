<?php
// functions.php - Uygulama genelinde kullanılacak yardımcı fonksiyonlar

/**
 * Kullanıcı bilgilerini veritabanından çeker.
 * Yeni SQL şemasına göre users tablosundaki email, phone_number, profile_image_path sütunları eklendi.
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param int $userId Bilgileri çekilecek kullanıcının ID'si.
 * @return array|false Kullanıcı bilgileri dizisi veya bulunamazsa false.
 */
function getUserInfo($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, phone_number, profile_image_path FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Kullanıcı bilgisi çekilirken hata oluştu: " . $e->getMessage());
        return false;
    }
}

/**
 * Belirli bir para biriminin TRY karşılığını exchange_rates tablosundan çeker.
 * Eğer bulunamazsa veya TRY ise 1.00 döner.
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param string $currencyCode Dönüştürülecek para birimi kodu (örn: 'USD', 'EUR').
 * @return float TRY karşılığı kur değeri.
 */
function getExchangeRateToTRY($pdo, $currencyCode) {
    if ($currencyCode === 'TRY') {
        return 1.00;
    }
    try {
        $stmt = $pdo->prepare("
            SELECT rate FROM exchange_rates
            WHERE base_currency = :base_currency AND target_currency = 'TRY'
            ORDER BY rate_date DESC LIMIT 1
        ");
        $stmt->bindParam(':base_currency', $currencyCode, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && (float)$result['rate'] > 0) {
            return (float)$result['rate'];
        }
    } catch (PDOException $e) {
        error_log("Kur çekilirken hata oluştu ({$currencyCode}/TRY): " . $e->getMessage());
    }
    return 0.00; // Kur bulunamazsa veya hata olursa 0 dön
}

/**
 * Kullanıcının sahip olduğu tüm varlıkların toplam değerini TRY cinsinden hesaplar.
 * Yeni SQL şemasına göre, assets tablosundaki current_value alanını öncelikli olarak kullanır.
 * Eğer current_value NULL ise veya 0 ise, market_prices veya exchange_rates üzerinden hesaplar.
 *
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param int $userId Toplam değeri hesaplanacak kullanıcının ID'si.
 * @return float Toplam varlık değeri TRY cinsinden.
 */
function getTotalAssetsValue($pdo, $userId) {
    $totalValue = 0.0;
    try {
        $stmt_assets = $pdo->prepare("
            SELECT
                a.quantity,
                a.currency AS asset_currency,
                a.current_value,
                ad.id AS asset_definition_id,
                ad.name AS asset_name,
                ad.type AS asset_type,
                ad.symbol AS asset_symbol
            FROM
                assets a
            JOIN
                asset_definitions ad ON a.asset_definition_id = ad.id
            WHERE
                a.user_id = :user_id
        ");
        $stmt_assets->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt_assets->execute();
        $user_assets = $stmt_assets->fetchAll(PDO::FETCH_ASSOC);

        foreach ($user_assets as $asset) {
            $value_in_asset_currency = 0.0;
            $asset_currency = $asset['asset_currency'];

            // Öncelikle assets tablosundaki current_value'yu kullan
            if ($asset['current_value'] !== NULL && (float)$asset['current_value'] > 0) {
                $value_in_asset_currency = (float)$asset['current_value'];
            } else {
                // Eğer current_value yoksa veya sıfırsa, diğer tablolardan hesapla
                $quantity = (float)$asset['quantity'];
                $asset_definition_id = (int)$asset['asset_definition_id'];
                $asset_type = $asset['asset_type'];

                if ($asset_currency === 'TRY' && $asset_type === 'Döviz' && $asset['asset_symbol'] === NULL) { // TRY'nin symbol'ü NULL olduğu için kontrol eklendi
                    $value_in_asset_currency = $quantity;
                } else if ($asset_type === 'Döviz') {
                    $value_in_asset_currency = $quantity;
                } else if ($asset_type === 'Altın' || $asset_type === 'Diğer') {
                    $stmt_market_price = $pdo->prepare("
                        SELECT price FROM market_prices
                        WHERE asset_definition_id = :asset_definition_id AND currency = :currency
                        ORDER BY price_date DESC LIMIT 1
                    ");
                    $stmt_market_price->bindParam(':asset_definition_id', $asset_definition_id, PDO::PARAM_INT);
                    $stmt_market_price->bindParam(':currency', $asset_currency, PDO::PARAM_STR);
                    $stmt_market_price->execute();
                    $result_price = $stmt_market_price->fetch(PDO::FETCH_ASSOC);

                    if ($result_price && (float)$result_price['price'] > 0) {
                        $value_in_asset_currency = $quantity * (float)$result_price['price'];
                    } else {
                        error_log("Varlık (" . $asset['asset_name'] . " - ID: " . $asset_definition_id . ") için güncel piyasa fiyatı bulunamadı veya geçersiz (getTotalAssetsValue).");
                    }
                }
            }

            // Varlığın değerini TRY'ye çevir
            if ($value_in_asset_currency > 0) {
                $rate_to_try = getExchangeRateToTRY($pdo, $asset_currency);
                if ($rate_to_try > 0) {
                    $totalValue += $value_in_asset_currency * $rate_to_try;
                } else {
                    error_log("Varlık (" . $asset['asset_name'] . ") için TRY'ye dönüşüm kuru bulunamadı (getTotalAssetsValue). Para birimi: " . $asset_currency);
                }
            }
        }
        return round($totalValue, 2);
    } catch (PDOException $e) {
        error_log("Toplam varlık değeri hesaplanırken hata oluştu: " . $e->getMessage());
        return 0.0;
    }
}


/**
 * Güncel varlık piyasa fiyatlarını ve ikon yollarını çeker.
 * Yeni SQL şemasına göre tamamen yeniden yazıldı.
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @return array Güncel varlık fiyatlarının ve ikon yollarının listesi.
 */
function getAssetMarketPricesForDisplay($pdo) {
    try {
        $formatted_results = [];

        // Döviz kurlarını çek (base_currency -> TRY)
        $stmt_currencies = $pdo->query("
            SELECT
                ad.name AS asset_name,
                ad.symbol AS asset_code,
                ad.icon_path,
                er.rate AS selling_price
            FROM
                asset_definitions ad
            JOIN
                exchange_rates er ON ad.symbol = er.base_currency
            WHERE
                ad.type = 'Döviz'
            ORDER BY
                ad.name ASC
        ");
        $currency_results = $stmt_currencies->fetchAll(PDO::FETCH_ASSOC);

        foreach ($currency_results as $row) {
            $selling_price = (float)$row['selling_price'];
            $buying_price = $selling_price * 0.995; // Basit bir alış fiyatı varsayımı

            if ($row['asset_code'] === 'TRY') { // TRY için özel durum
                $buying_price = 1.00;
                $selling_price = 1.00;
            }

            $formatted_results[] = [
                'asset_name' => htmlspecialchars($row['asset_name']),
                'asset_code' => htmlspecialchars($row['asset_code']),
                'icon_path' => htmlspecialchars($row['icon_path'] ?: 'https://placehold.co/64x64/A8DADC/1D3557?text=' . $row['asset_code']),
                'buying_price' => round($buying_price, 2),
                'selling_price' => round($selling_price, 2),
            ];
        }

        // Altın ve diğer varlıkların piyasa fiyatlarını çek
        $stmt_commodities_other = $pdo->query("
            SELECT
                ad.name AS asset_name,
                ad.symbol AS asset_code,
                ad.icon_path,
                mp.price AS selling_price,
                mp.currency AS price_currency
            FROM
                asset_definitions ad
            JOIN
                market_prices mp ON ad.id = mp.asset_definition_id
            WHERE
                ad.type IN ('Altın', 'Diğer') AND mp.price_date = CURDATE()
            ORDER BY
                ad.name ASC
        ");
        $commodities_other_results = $stmt_commodities_other->fetchAll(PDO::FETCH_ASSOC);

        foreach ($commodities_other_results as $row) {
            $selling_price = (float)$row['selling_price'];
            $buying_price = $selling_price * 0.995; // Basit bir alış fiyatı varsayımı

            // Eğer fiyat TRY cinsinden değilse, TRY'ye çevir
            if ($row['price_currency'] !== 'TRY') {
                $rate_to_try = getExchangeRateToTRY($pdo, $row['price_currency']);
                if ($rate_to_try > 0) {
                    $buying_price *= $rate_to_try;
                    $selling_price *= $rate_to_try;
                } else {
                    error_log("Piyasa fiyatı (" . $row['asset_name'] . ") için TRY'ye dönüşüm kuru bulunamadı. Para birimi: " . $row['price_currency']);
                    continue; // Bu varlığı atla
                }
            }

            $formatted_results[] = [
                'asset_name' => htmlspecialchars($row['asset_name']),
                'asset_code' => htmlspecialchars($row['asset_code']),
                'icon_path' => htmlspecialchars($row['icon_path'] ?: 'https://placehold.co/64x64/A8DADC/1D3557?text=' . $row['asset_code']),
                'buying_price' => round($buying_price, 2),
                'selling_price' => round($selling_price, 2),
            ];
        }

        // Sonuçları alfabetik sıraya göre tekrar sırala
        usort($formatted_results, function($a, $b) {
            return strcmp($a['asset_name'], $b['asset_name']);
        });

        return $formatted_results;
    } catch (PDOException $e) {
        error_log("Güncel kurlar çekilirken hata oluştu: " . $e->getMessage());
        return [];
    }
}

/**
 * Belirli bir kullanıcının son işlemlerini transactions tablosundan çeker.
 * Yeni SQL şemasına göre tamamen yeniden yazıldı.
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param int $userId İşlemleri çekilecek kullanıcının ID'si.
 * @param int $limit Kaç adet son işlem çekileceği.
 * @return array Son işlemlerin listesi.
 */
function getRecentTransactions($pdo, $userId, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                t.transaction_date,
                t.transaction_type,
                t.amount,
                t.transaction_currency,
                t.unit_price,
                t.total_try_value,
                t.description,
                ad.name AS asset_name,
                tc.name AS category_name,
                ba_from.account_name AS from_account,
                ba_to.account_name AS to_account,
                p.name AS property_name
            FROM
                transactions t
            LEFT JOIN
                asset_definitions ad ON t.asset_id = ad.id
            LEFT JOIN
                transaction_categories tc ON t.category_id = tc.id
            LEFT JOIN
                bank_accounts ba_from ON t.from_account_id = ba_from.id
            LEFT JOIN
                bank_accounts ba_to ON t.to_account_id = ba_to.id
            LEFT JOIN
                properties p ON t.property_id = p.id
            WHERE
                t.user_id = :user_id
            ORDER BY
                t.transaction_date DESC, t.id DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted_transactions = [];
        foreach ($results as $transaction) {
            $transaction_type = $transaction['transaction_type'];
            $total_try_value = (float)$transaction['total_try_value'];

            // İşlem türüne göre miktarın pozitif veya negatif olması
            $display_amount = $total_try_value;
            $sign = '';
            switch ($transaction_type) {
                case 'Gider':
                case 'Satış':
                case 'Para Çekme':
                    $display_amount = -abs($total_try_value);
                    $sign = '-';
                    break;
                case 'Gelir':
                case 'Alış':
                case 'Para Yatırma':
                    $display_amount = abs($total_try_value);
                    $sign = '+';
                    break;
                case 'Transfer':
                    $sign = '';
                    break;
                default:
                    $sign = '';
                    break;
            }

            // İşlem adını belirle: Mülk adı > Kategori adı > Varlık adı > Açıklama > Bilinmeyen İşlem
            $transaction_name = $transaction['property_name'] ?: ($transaction['category_name'] ?: ($transaction['asset_name'] ?: ($transaction['description'] ?: 'Bilinmeyen İşlem')));

            $formatted_transactions[] = [
                'transaction_date' => $transaction['transaction_date'],
                'transaction_type' => htmlspecialchars($transaction_type),
                'name' => htmlspecialchars($transaction_name),
                'amount' => (float)$transaction['amount'],
                'transaction_currency' => htmlspecialchars($transaction['transaction_currency']),
                'unit_price' => (float)$transaction['unit_price'],
                'total_try_value' => round($display_amount, 2),
                'description' => htmlspecialchars($transaction['description'] ?: ''),
                'from_account' => htmlspecialchars($transaction['from_account'] ?: ''),
                'to_account' => htmlspecialchars($transaction['to_account'] ?: ''),
                'asset_name' => htmlspecialchars($transaction['asset_name'] ?: ''),
                'property_name' => htmlspecialchars($transaction['property_name'] ?: '')
            ];
        }
        return $formatted_transactions;
    } catch (PDOException $e) {
        error_log("Son işlemler çekilirken hata oluştu: " . $e->getMessage());
        return [];
    }
}

/**
 * Varlık dağılımı için veri çeker (Pie Chart için).
 * Her varlık türünün (Altın, Döviz, Diğer) toplam değerini TRY cinsinden hesaplar.
 * Yeni SQL şemasına göre, assets tablosundaki current_value alanını öncelikli olarak kullanır.
 *
 * @param PDO $pdo Veritabanı bağlantı nesnesi.
 * @param int $userId Kullanıcının ID'si.
 * @return array Etiketler (labels) ve seriler (series) içeren bir dizi.
 */
function getAssetDistributionForChart($pdo, $userId) {
    $chart_labels = [];
    $chart_series = [];

    try {
        $stmt_distribution = $pdo->prepare("
            SELECT
                a.quantity,
                a.currency AS asset_currency,
                a.current_value,
                ad.id AS asset_definition_id,
                ad.name AS asset_name,
                ad.type AS asset_type,
                ad.symbol AS asset_symbol
            FROM
                assets a
            JOIN
                asset_definitions ad ON a.asset_definition_id = ad.id
            WHERE
                a.user_id = :user_id
        ");
        $stmt_distribution->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt_distribution->execute();
        $user_assets = $stmt_distribution->fetchAll(PDO::FETCH_ASSOC);

        $category_totals = [];

        foreach ($user_assets as $asset) {
            $value_in_asset_currency = 0.0;
            $asset_currency = $asset['asset_currency'];
            $asset_type = $asset['asset_type'];

            // Öncelikle assets tablosundaki current_value'yu kullan
            if ($asset['current_value'] !== NULL && (float)$asset['current_value'] > 0) {
                $value_in_asset_currency = (float)$asset['current_value'];
            } else {
                // Eğer current_value yoksa veya sıfırsa, diğer tablolardan hesapla
                $quantity = (float)$asset['quantity'];
                $asset_definition_id = (int)$asset['asset_definition_id'];

                if ($asset_currency === 'TRY' && $asset_type === 'Döviz' && $asset['asset_symbol'] === NULL) { // TRY'nin symbol'ü NULL olduğu için kontrol eklendi
                    $value_in_asset_currency = $quantity;
                } else if ($asset_type === 'Döviz') {
                    $value_in_asset_currency = $quantity;
                } else if ($asset_type === 'Altın' || $asset_type === 'Diğer') {
                    $stmt_market_price = $pdo->prepare("
                        SELECT price FROM market_prices
                        WHERE asset_definition_id = :asset_definition_id AND currency = :currency
                        ORDER BY price_date DESC LIMIT 1
                    ");
                    $stmt_market_price->bindParam(':asset_definition_id', $asset_definition_id, PDO::PARAM_INT);
                    $stmt_market_price->bindParam(':currency', $asset_currency, PDO::PARAM_STR);
                    $stmt_market_price->execute();
                    $result_price = $stmt_market_price->fetch(PDO::FETCH_ASSOC);

                    if ($result_price && (float)$result_price['price'] > 0) {
                        $value_in_asset_currency = $quantity * (float)$result_price['price'];
                    } else {
                        error_log("Varlık (" . $asset['asset_name'] . " - ID: " . $asset_definition_id . ") için güncel piyasa fiyatı bulunamadı veya geçersiz (Dağılım Grafiği).");
                    }
                } else {
                     error_log("Bilinmeyen varlık kategorisi veya sembolü: " . $asset_type . " / " . $asset['asset_symbol'] . " (Dağılım Grafiği)");
                }
            }

            if ($value_in_asset_currency > 0) {
                // Varlığın değerini TRY'ye çevir
                $rate_to_try = getExchangeRateToTRY($pdo, $asset_currency);
                if ($rate_to_try > 0) {
                    $value_in_try = $value_in_asset_currency * $rate_to_try;
                    // Varlık kategorisine göre toplamı güncelle
                    if (!isset($category_totals[$asset_type])) {
                        $category_totals[$asset_type] = 0;
                    }
                    $category_totals[$asset_type] += $value_in_try;
                } else {
                    error_log("Varlık (" . $asset['asset_name'] . ") için TRY'ye dönüşüm kuru bulunamadı (Dağılım Grafiği). Para birimi: " . $asset_currency);
                }
            }
        }

        // Pie chart için etiket ve serileri hazırla
        foreach ($category_totals as $category => $total) {
            $chart_labels[] = htmlspecialchars($category);
            $chart_series[] = round($total, 2);
        }

    } catch (PDOException $e) {
        error_log("Varlık dağılımı çekilirken hata oluştu: " . $e->getMessage());
        // Hata durumunda boş veri dön
        return ['labels' => [], 'series' => []];
    }

    return ['labels' => $chart_labels, 'series' => $chart_series];
}

/**
 * Telefon numarasını (XXX) XXX XX XX formatında biçimlendirir.
 * @param string|null $phoneNumber Biçimlendirilecek telefon numarası.
 * @return string Biçimlendirilmiş telefon numarası veya boş string.
 */
function formatPhoneNumber($phoneNumber) {
    if (empty($phoneNumber)) {
        return '';
    }
    // Sadece rakamları al
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

    // Eğer numara 10 haneliyse (alan kodu olmadan)
    if (strlen($phoneNumber) == 10) {
        return '(' . substr($phoneNumber, 0, 3) . ') ' . substr($phoneNumber, 3, 3) . ' ' . substr($phoneNumber, 6, 2) . ' ' . substr($phoneNumber, 8, 2);
    }
    // Eğer numara 11 haneliyse (ülke kodu ile, örn: 05xx)
    else if (strlen($phoneNumber) == 11 && substr($phoneNumber, 0, 1) == '0') {
        return '(' . substr($phoneNumber, 1, 3) . ') ' . substr($phoneNumber, 4, 3) . ' ' . substr($phoneNumber, 7, 2) . ' ' . substr($phoneNumber, 9, 2);
    }
    // Diğer durumlar için orijinal veya basitçe biçimlendirilmiş hali
    return $phoneNumber;
}

/**
 * Tarihi "dd MMMM yyyy EEEE" (örn: 15 Temmuz 2025 Salı) formatında biçimlendirir.
 * IntlDateFormatter sınıfını kullanır.
 * @param string $dateString Biçimlendirilecek tarih stringi (örn: 'YYYY-MM-DD').
 * @param string $locale Biçimlendirme için kullanılacak ICU yerel ayarı (varsayılan 'tr-TR').
 * @param string $timezone Biçimlendirme için kullanılacak saat dilimi (varsayılan 'Europe/Istanbul').
 * @return string Biçimlendirilmiş tarih veya hata durumunda orijinal string.
 */
function formatDate($dateString, $locale = 'tr-TR', $timezone = 'Europe/Istanbul') {
    if (empty($dateString)) {
        return '';
    }
    try {
        $dateTime = new DateTime($dateString);
        if (class_exists('IntlDateFormatter')) {
            $formatter = new IntlDateFormatter(
                $locale,
                IntlDateFormatter::FULL,
                IntlDateFormatter::NONE,
                $timezone,
                IntlDateFormatter::GREGORIAN,
                'dd MMMM yyyy EEEE' // Biçim deseni: gün ay yıl haftanın günü
            );
            return $formatter->format($dateTime);
        } else {
            // IntlDateFormatter yoksa basit bir fallback
            return date('d F Y', $dateTime->getTimestamp());
        }
    } catch (Exception $e) {
        error_log("Tarih biçimlendirilirken hata oluştu: " . $e->getMessage());
        return $dateString; // Hata durumunda orijinal stringi döndür
    }
}

/**
 * Sayısal değeri para birimi formatında (örn: 1.000.000,00 TL) biçimlendirir.
 * NumberFormatter sınıfını kullanır.
 * @param float|int $amount Biçimlendirilecek sayısal değer.
 * @param string $currencyCode Para birimi kodu (örn: 'TRY', 'USD').
 * @param string $locale Biçimlendirme için kullanılacak ICU yerel ayarı (varsayılan 'tr-TR').
 * @return string Biçimlendirilmiş para birimi stringi veya hata durumunda orijinal sayı.
 */
function formatCurrency($amount, $currencyCode = 'TRY', $locale = 'tr-TR') {
    if (!is_numeric($amount)) {
        return (string)$amount; // Sayısal değilse stringe çevirip döndür
    }
    if (class_exists('NumberFormatter')) {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, $currencyCode);
    } else {
        // NumberFormatter yoksa basit bir fallback
        return number_format($amount, 2, ',', '.') . ' ' . $currencyCode;
    }
}

// `setlocale` komutunu buraya ekliyoruz.
// Bu, özellikle `IntlDateFormatter` gibi fonksiyonlar için olmasa da,
// PHP'nin diğer yerel ayar bağımlı fonksiyonları için faydalıdır.
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'Turkish'); // Sunucunuzda yüklü olanı deneyin
?>
