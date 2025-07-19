<?php
// Sayfanın ve içeriğin UTF-8 olarak gönderildiğini tarayıcıya bildir.
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Yerel Ayar (Locale) Testi</h1>";
echo "<p>Bu sayfa, sunucunuzun Türkçe yerel ayarları destekleyip desteklemediğini test eder.</p>";

// Denenecek Türkçe yerel ayarlar
// Windows sistemleri için 'Turkish' veya 'tr_TR' deneyin.
// Linux/Unix sistemleri için 'tr_TR.UTF-8' veya 'tr_TR' deneyin.
$locales_to_try = [
    'tr_TR.UTF-8', // Linux/Unix sistemleri için yaygın
    'tr_TR',       // Genel kullanım
    'Turkish',     // Bazı Windows sistemleri için
    'tr',          // Daha kısa genel tanım
    'tur'          // Üç harfli ISO kodu
];

$locale_set = false;
$system_locale_result = ''; // setlocale'in döndürdüğü değeri saklamak için
foreach ($locales_to_try as $locale) {
    // setlocale fonksiyonu sadece strftime gibi eski fonksiyonları etkiler.
    // IntlDateFormatter için doğrudan bir etkisi yoktur, ancak yine de uyumluluk için ayarlanır.
    $setlocale_result = setlocale(LC_TIME, $locale);
    if ($setlocale_result !== false) {
        echo "<p><b>Başarılı:</b> Yerel ayar '{$locale}' olarak ayarlandı. (Sistem yanıtı: '{$setlocale_result}')</p>";
        $locale_set = true;
        $system_locale_result = $setlocale_result; // Başarılı olan sistem yerel ayarını kaydet
        break; // Başarılı olursa döngüden çık
    }
}

// IntlDateFormatter için kullanılacak ICU uyumlu yerel ayar
$icu_locale = 'tr-TR'; // Türkçe için standart BCP 47 kodu

if (!$locale_set) {
    echo "<p style='color: red;'><b>Hata:</b> Türkçe yerel ayarlar sunucunuzda bulunamadı veya ayarlanamadı.</p>";
    echo "<p>Bu durumda, `IntlDateFormatter` kullanılsa bile, sistem yerel ayar bilgilerini tam olarak alamayabilir.</p>";
    echo "<p>Sunucunuzun işletim sistemine göre doğru yerel ayar paketlerinin yüklü olduğundan emin olun.</p>";
} else {
    echo "<p><b>Bilgi:</b> `IntlDateFormatter` için ICU uyumlu yerel ayar olarak '{$icu_locale}' kullanılacaktır.</p>";
}


echo "<h2>Test Tarihi:</h2>";
$test_date_timestamp = strtotime('2025-07-15'); // 15 Temmuz 2025 Salı
$test_date_datetime = new DateTime('@' . $test_date_timestamp); // DateTime nesnesine dönüştür

// strftime yerine IntlDateFormatter kullanma
if (class_exists('IntlDateFormatter')) {
    echo "<p><b>IntlDateFormatter ile biçimlendirme:</b></p>";

    // Tam tarih (örn: 15 Temmuz 2025 Salı)
    $formatter_full = new IntlDateFormatter(
        $icu_locale, // ICU uyumlu yerel ayarı kullan
        IntlDateFormatter::FULL,
        IntlDateFormatter::NONE,
        'Europe/Istanbul', // Kendi saat diliminizi belirtebilirsiniz
        IntlDateFormatter::GREGORIAN,
        'dd MMMM yyyy EEEE' // Biçim deseni: gün ay yıl haftanın günü
    );
    echo "<p><b>Haftanın günü, gün ay yıl:</b> " . $formatter_full->format($test_date_datetime) . "</p>";

    // Sadece haftanın günü (örn: Salı)
    $formatter_weekday = new IntlDateFormatter(
        $icu_locale, // ICU uyumlu yerel ayarı kullan
        IntlDateFormatter::FULL,
        IntlDateFormatter::NONE,
        'Europe/Istanbul',
        IntlDateFormatter::GREGORIAN,
        'EEEE' // Sadece haftanın günü
    );
    echo "<p><b>Haftanın günü:</b> " . $formatter_weekday->format($test_date_datetime) . "</p>";

    // Sadece ay adı (örn: Temmuz)
    $formatter_month = new IntlDateFormatter(
        $icu_locale, // ICU uyumlu yerel ayarı kullan
        IntlDateFormatter::FULL,
        IntlDateFormatter::NONE,
        'Europe/Istanbul',
        IntlDateFormatter::GREGORIAN,
        'MMMM' // Sadece ay adı
    );
    echo "<p><b>Ay adı:</b> " . $formatter_month->format($test_date_datetime) . "</p>";

} else {
    echo "<p style='color: orange;'><b>Uyarı:</b> 'IntlDateFormatter' sınıfı bulunamadı. PHP 'intl' eklentisi yüklü değil.</p>";
    echo "<p>Bu durumda, `strftime()` fonksiyonu kullanımdan kaldırıldığı için tarih biçimlendirme beklendiği gibi çalışmayabilir.</p>";
    // Fallback olarak date() kullanabiliriz, ancak bu yerel ayar bağımlı değildir.
    echo "<p><b>date('l, d F Y', \$test_date_timestamp) (Yerel ayar olmadan):</b> " . date('l, d F Y', $test_date_timestamp) . "</p>";
}


echo "<h2>PHP Bilgisi (Locale Bölümü):</h2>";
echo "<pre>";
// Windows'ta 'locale -a' komutu çalışmayabilir, bu yüzden hata vermemesi için kontrol ekledik
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    echo "Windows sistemlerinde 'locale -a' komutu genellikle bulunmaz. Lütfen Bölge ve Dil Ayarlarınızı kontrol edin.";
    echo "\nWindows için yerel ayar isimleri genellikle 'Turkish' veya 'tr_TR' gibi olur.";
} else {
    echo shell_exec('locale -a'); // Linux/macOS için yüklü lokalleri listeler
    echo "\nYukarıdaki liste sunucunuzda yüklü olan tüm yerel ayarları gösterir.";
}
echo "</pre>";
echo "<p>Yukarıdaki komut çalışmazsa veya boş dönerse, sunucunuzda 'locale -a' komutu desteklenmiyor veya Linux/macOS tabanlı değildir.</p>";

?>
