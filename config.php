<?php
// Sayfanın ve içeriğin UTF-8 olarak gönderildiğini tarayıcıya bildir.
// BU SATIR VE PHP AÇILIŞ ETİKETİNDEN (<?php) ÖNCE HİÇBİR KARAKTER (BOŞLUK, YENİ SATIR DAHİL) OLMAMALIDIR.
header('Content-Type: text/html; charset=utf-8');

// Veritabanı bağlantı bilgileri
define('DB_SERVER', 'localhost'); // Genellikle localhost
define('DB_USERNAME', 'root');    // MySQL kullanıcı adınız (XAMPP/WAMP için varsayılan root)
define('DB_PASSWORD', '0976');        // MySQL şifreniz (XAMPP/WAMP için varsayılan boş)
define('DB_NAME', 'Asset');   // Veritabanı adınız 'Asset' olarak güncellendi!

try {
    // PDO DSN (Data Source Name) içine charset=utf8mb4 ekleyerek
    // veritabanı bağlantısının karakter setini belirtiyoruz.
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);

    // Hata modunu PDO istisnalarına ayarla.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Bağlantı kurulduktan sonra da sunucu tarafında karakter setini ayarlamak için
    // SET NAMES komutunu çalıştırabiliriz. Bu, ek bir güvenlik katmanı sağlar.
    $pdo->exec("SET NAMES 'utf8mb4'");
    $pdo->exec("SET CHARACTER SET utf8mb4");

} catch (PDOException $e) {
    // Veritabanı bağlantısı kurulamadığında hata mesajı göster ve script'i durdur.
    die("HATA: Veritabanı bağlantısı kurulamadı. Lütfen veritabanı ayarlarınızı kontrol edin. Detay: " . $e->getMessage());
}
?>
