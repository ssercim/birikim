<?php
// Admin Paneli Giriş Bilgileri
// Bu bilgiler sadece lokal kullanım içindir ve internete açık bir sitede kullanılmamalıdır.

define('ADMIN_USERNAME', 'serbulent'); // Admin kullanıcı adınız
define('ADMIN_PASSWORD', '0976');     // Admin şifreniz

// Oturum başlatma
// Bu, kullanıcı girişini takip etmek için gereklidir.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı bağlantı bilgileri
define('DB_SERVER', 'localhost'); // Genellikle localhost
define('DB_USERNAME', 'root');    // MySQL kullanıcı adınız (XAMPP/WAMP için varsayılan root)
define('DB_PASSWORD', '0976');        // MySQL şifreniz (XAMPP/WAMP için varsayılan boş)
define('DB_NAME', 'asset');   // Veritabanı adınız

try {
    // PDO DSN (Data Source Name) içine charset=utf8mb4 ekleyerek
    // veritabanı bağlantısının karakter setini belirtiyoruz.
    // Bu, Türkçe karakterlerin doğru iletilmesini sağlar.
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);

    // Hata modunu PDO istisnalarına ayarla.
    // Bu, veritabanı sorgularında oluşabilecek hataların PHP tarafından yakalanmasını sağlar.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Bağlantı kurulduktan sonra da sunucu tarafında karakter setini ayarlamak için
    // SET NAMES komutunu çalıştırabiliriz. Bu, ek bir güvenlik katmanı sağlar.
    $pdo->exec("SET NAMES 'utf8mb4'");
    $pdo->exec("SET CHARACTER SET utf8mb4");

} catch (PDOException $e) {
    // Veritabanı bağlantısı kurulamadığında hata mesajı göster ve script'i durdur.
    die("HATA: Veritabanı bağlantısı kurulamadı. Lütfen veritabanı ayarlarınızı kontrol edin. Detay: " . $e->getMessage());
}

// --- Formatlama Fonksiyonları ---

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
