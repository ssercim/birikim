-- Bu SQL script'i, "Asset" veritabanını oluşturur (veya varsa silip yeniden oluşturur)
-- ve ardından tablolara Excel'den alınan, filtrelenmiş ve tüm hassasiyet/dil/içerik
-- düzeltmeleri yapılmış örnek verileri ekler.
-- Yeni 'properties' tablosu ve kira gelirlerinin mülklere göre ayrımı eklenmiştir.
-- Bu script'i çalıştırmadan önce Adım 1'deki tablo oluşturma script'ini çalıştırmış olmalısınız.

-- Foreign key kontrollerini geçici olarak devre dışı bırak, bu sayede işlemler sorunsuz çalışır.
SET FOREIGN_KEY_CHECKS = 0;

-- Veritabanını sil (eğer varsa)
DROP DATABASE IF EXISTS `Asset`;

-- Yeni veritabanını oluştur
CREATE DATABASE `Asset` CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;

-- Yeni oluşturulan veritabanını kullan (BU KOMUT ÇOK ÖNEMLİ!)
USE `Asset`;

-- 1. "users" Tablosu : Kullanıcı bilgilerini tutar.
-- Bu, uygulamanın sadece lokalde çalışacağı varsayımıyla yapılmıştır.
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) UNIQUE NULL, -- E-posta adresi, benzersiz
    `phone_number` VARCHAR(20) NULL, -- Telefon numarası
    `profile_image_path` VARCHAR(255) NULL, -- Profil resmi yolu
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Kullanıcı verilerini ekleme (Örnek profil resim yolları ve yeni sütunlar ile)
INSERT INTO `users` (`id`, `name`, `email`, `phone_number`, `profile_image_path`) VALUES
(1, 'Serbülent', 'serbulent_@hotmail.com', '05324160376', 'tools/images/profile/serbulent.jpg'),
(2, 'Aylin', 'aylin@example.com', '05326470034', 'tools/images/profile/aylin.jpg'),
(3, 'Meral', 'meral@example.com', '05326449926', 'tools/images/profile/meral.jpg');

-- 2. "asset_definitions" Tablosu : Varlık türlerinin tanımlarını tutar.
-- ENUM değerleri Türkçe'ye çevrildi ve sadece kullanılan/takip edilen varlıklar eklendi.
-- Parasal hassasiyetler 2, altın gram hassasiyeti 4 olarak ayarlandı.
-- Kripto, hisse, gayrimenkul gibi ilgilenilmeyen varlıklar çıkarıldı.
CREATE TABLE `asset_definitions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `type` ENUM('Altın', 'Döviz', 'Diğer') NOT NULL, -- ENUM Türkçe'ye çevrildi
    `symbol` VARCHAR(10) UNIQUE NULL, -- Sembol (örn: USD, EUR, XAU)
    `description` TEXT NULL,
    `icon_path` VARCHAR(255) NULL, -- Varlık ikonunun yolu
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Sadece Serbülent, Aylin ve Meral'in Excel sayfalarında geçen veya işlem gören varlıklar eklendi.
-- Kullanılmayan dövizler ve altın/maden türleri çıkarıldı.
INSERT INTO `asset_definitions` (`id`, `name`, `type`, `symbol`, `description`, `icon_path`) VALUES
(1, 'Türk Lirası', 'Döviz', 'TRY', NULL, 'tools/images/icons/try.png'),
(2, 'ABD Doları', 'Döviz', 'USD', NULL, 'tools/images/icons/usd.png'),
(3, 'Euro', 'Döviz', 'EUR', NULL, 'tools/images/icons/eur.png'),
(4, 'Suudi Arabistan Riyali', 'Döviz', 'SAR', NULL, 'tools/images/icons/sar.png'),
(5, 'Ons Altın', 'Altın', 'XAUUSD', NULL, 'tools/images/icons/ons_altin.png'),
(6, 'Gram Altın', 'Altın', 'XAU', NULL, 'tools/images/icons/gram_altin.png'),
(7, 'Gram Has Altın', 'Altın', 'HASXAU', NULL, 'tools/images/icons/gram_has_altin.png'),
(8, 'Çeyrek Altın', 'Altın', 'CEYREK', NULL, 'tools/images/icons/ceyrek_altin.png'),
(9, 'Yarım Altın', 'Altın', 'YARIM', NULL, 'tools/images/icons/yarim_altin.png'),
(10, 'Tam Altın', 'Altın', 'TAM', NULL, 'tools/images/icons/tam_altin.png'),
(11, 'Ata Altın', 'Altın', 'ATA', NULL, 'tools/images/icons/ata_altin.png'),
(12, '22 Ayar Bilezik', 'Altın', 'BLZ22', NULL, 'tools/images/icons/bilezik22.png');


-- 3. "bank_accounts" Tablosuna Örnek Veri Ekleme
-- 'balance' hassasiyeti 2 olarak ayarlandı.
CREATE TABLE `bank_accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `account_name` VARCHAR(100) NOT NULL, -- Hesap adı (örn: Garanti TL Hesabı)
    `bank_name` VARCHAR(100) NOT NULL,
    `account_number` VARCHAR(50) UNIQUE NOT NULL, -- IBAN veya hesap numarası
    `currency` VARCHAR(3) NOT NULL, -- Hesap para birimi (örn: TRY, USD, EUR)
    `balance` DECIMAL(18, 2) NOT NULL DEFAULT 0.00, -- Hesap bakiyesi (virgülden sonra 2 basamak)
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

INSERT INTO `bank_accounts` (`id`, `user_id`, `account_name`, `bank_name`, `account_number`, `currency`, `balance`) VALUES
(1, 1, 'Ana Hesap', 'Garanti BBVA', 'TR120006200000012345678901', 'TRY', 15000.50),
(2, 1, 'Dolar Hesabı', 'Garanti BBVA', 'TR120006200000012345678902', 'USD', 2500.75),
(3, 2, 'Birikim Hesabı', 'Akbank', 'TR340008700000098765432101', 'TRY', 50000.00),
(4, 1, 'Kira Geliri Hesabı', 'Garanti BBVA', 'TR120006200000012345678903', 'TRY', 0.00),
(5, 1, 'Maaş Hesabı', 'Ziraat Bankası', 'TR120001000000012345678904', 'TRY', 0.00);

-- 4. "properties" Tablosu: Kullanıcıların sahip olduğu kiradaki mülkleri tutar.
CREATE TABLE `properties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL, -- Mülkün adı (örn: "Bahçelievler Daire 1")
    `address` TEXT NULL, -- Mülkün adresi
    `current_rent_amount` DECIMAL(18, 2) NOT NULL, -- Güncel kira miktarı
    `rent_currency` VARCHAR(3) NOT NULL DEFAULT 'TRY', -- Kira para birimi
    `last_rent_increase_date` DATE NULL, -- Son kira artış tarihi
    `next_rent_increase_date` DATE NULL, -- Sonraki kira artış tarihi
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Mülk verilerini ekleme (Serbülent'in iki kiradaki dairesi)
INSERT INTO `properties` (`id`, `user_id`, `name`, `address`, `current_rent_amount`, `rent_currency`, `last_rent_increase_date`, `next_rent_increase_date`, `notes`) VALUES
(1, 1, 'Bahçelievler Daire', 'Bahçelievler, İstanbul', 30000.00, 'TRY', '2024-01-01', '2025-01-01', 'Serbülent''in kirada olan Bahçelievler dairesi.'),
(2, 1, 'Kadıköy Daire', 'Kadıköy, İstanbul', 25000.00, 'TRY', '2024-03-01', '2025-03-01', 'Serbülent''in kirada olan Kadıköy dairesi.');


-- 5. "exchange_rates" Tablosuna Örnek Veri Ekleme (Sadece kullanılan dövizler)
-- 'rate' hassasiyeti 2 olarak ayarlandı.
CREATE TABLE `exchange_rates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `base_currency` VARCHAR(3) NOT NULL, -- Baz para birimi (örn: USD)
    `target_currency` VARCHAR(3) NOT NULL, -- Hedef para birimi (örn: TRY)
    `rate` DECIMAL(18, 2) NOT NULL, -- Kur değeri (örn: 1 USD = X TRY) (virgülden sonra 2 basamak)
    `rate_date` DATE NOT NULL, -- Kurun geçerli olduğu tarih
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (`base_currency`, `target_currency`, `rate_date`) -- Aynı gün aynı kurlar tekrarlanamaz
);

INSERT INTO `exchange_rates` (`base_currency`, `target_currency`, `rate`, `rate_date`) VALUES
('USD', 'TRY', 40.38, CURDATE()), -- Excel'deki 40,3784 yuvarlandı
('EUR', 'TRY', 46.96, CURDATE()), -- Excel'deki 46,956 yuvarlandı
('SAR', 'TRY', 10.78, CURDATE()); -- Excel'deki 10,7768 yuvarlandı

-- 6. "market_prices" Tablosuna Örnek Veri Ekleme (Sadece kullanılan altınlar)
-- 'price' hassasiyeti 2 olarak ayarlandı.
CREATE TABLE `market_prices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_definition_id` INT NOT NULL,
    `price` DECIMAL(18, 2) NOT NULL, -- Varlığın fiyatı (virgülden sonra 2 basamak)
    `currency` VARCHAR(3) NOT NULL, -- Fiyatın tutulduğu para birimi (örn: USD)
    `price_date` DATE NOT NULL, -- Fiyatın geçerli olduğu tarih
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (`asset_definition_id`, `currency`, `price_date`), -- Aynı gün aynı varlık için aynı para biriminde fiyat tekrarlanamaz
    FOREIGN KEY (`asset_definition_id`) REFERENCES `asset_definitions`(`id`) ON DELETE CASCADE
);

INSERT INTO `market_prices` (`asset_definition_id`, `price`, `currency`, `price_date`) VALUES
(5, 3339.53, 'USD', CURDATE()), -- Ons Altın (ID 5)
(6, 4332.82, 'TRY', CURDATE()), -- Gram Altın (ID 6)
(7, 4311.15, 'TRY', CURDATE()), -- Gram Has Altın (ID 7)
(8, 7056.71, 'TRY', CURDATE()), -- Çeyrek Altın (ID 8)
(9, 14113.42, 'TRY', CURDATE()), -- Yarım Altın (ID 9)
(10, 28140.52, 'TRY', CURDATE()), -- Tam Altın (ID 10)
(11, 29176.37, 'TRY', CURDATE()), -- Ata Altın (ID 11)
(12, 3936.22, 'TRY', CURDATE()); -- 22 Ayar Bilezik (ID 12)

-- 7. "transaction_categories" Tablosuna Örnek Veri Ekleme
-- 'type' ENUM değerleri Türkçe olarak kaldı.
CREATE TABLE `transaction_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL, -- NULL olabilir, genel kategoriler için
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('Gelir', 'Gider', 'Transfer', 'Yatırım') NOT NULL,
    `description` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE (`user_id`, `name`) -- Her kullanıcı için kategori adı benzersiz olmalı, genel kategoriler için name benzersiz olmalı
);

INSERT INTO `transaction_categories` (`id`, `user_id`, `name`, `type`, `description`) VALUES
(1, NULL, 'Maaş', 'Gelir', 'Aylık düzenli gelir.'),
(2, NULL, 'Kira', 'Gider', 'Ev kirası ödemesi.'),
(3, NULL, 'Gıda', 'Gider', 'Market ve restoran harcamaları.'),
(4, NULL, 'Ulaşım', 'Gider', 'Toplu taşıma veya yakıt giderleri.'),
(5, NULL, 'Yatırım', 'Yatırım', 'Varlık alım/satım işlemleri.'),
(6, NULL, 'Transfer', 'Transfer', 'Hesaplar arası para transferi.'),
(7, 1, 'Ek İş Geliri', 'Gelir', 'Serbülent''in freelance işlerinden kazandığı gelir.'),
(8, 1, 'Altın Alım', 'Yatırım', 'Altın alım işlemleri.'),
(9, NULL, 'Kira Geliri', 'Gelir', 'Gayrimenkulden gelen kira geliri.');

-- 8. "assets" Tablosuna Örnek Veri Ekleme (Serbülent, Aylin, Meral sayfalarından)
-- 'quantity' hassasiyeti 4, 'purchase_price' ve 'current_value' hassasiyeti 2 olarak ayarlandı.
CREATE TABLE `assets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `asset_definition_id` INT NOT NULL,
    `quantity` DECIMAL(18, 4) NOT NULL, -- Varlık miktarı (altın gramı için 4 basamak, diğerleri için yeterli)
    `purchase_price` DECIMAL(18, 2) NULL, -- Satın alma fiyatı (virgülden sonra 2 basamak)
    `purchase_date` DATE NULL, -- Satın alma tarihi (isteğe bağlı)
    `current_value` DECIMAL(18, 2) NULL, -- Varlığın anlık değeri (virgülden sonra 2 basamak, market_prices ile güncellenebilir)
    `currency` VARCHAR(3) NOT NULL DEFAULT 'TRY', -- Varlığın değerinin tutulduğu para birimi (örn: TRY, USD, EUR)
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`asset_definition_id`) REFERENCES `asset_definitions`(`id`) ON DELETE RESTRICT
);

-- Serbülent (ID: 1)
INSERT INTO `assets` (`user_id`, `asset_definition_id`, `quantity`, `purchase_price`, `purchase_date`, `current_value`, `currency`, `notes`) VALUES
(1, 2, 7400.00, NULL, NULL, 298774.26, 'USD', 'Serbülent''in Dolar varlığı.'), -- USD (ID 2)
(1, 1, 485000.00, NULL, NULL, 485000.00, 'TRY', 'Serbülent''in Türk Lirası varlığı.'), -- TRY (ID 1)
(1, 6, 155.8320, NULL, NULL, 675307.32, 'TRY', 'Serbülent''in Gram Altın varlığı.'), -- Gram Altın (ID 6)
(1, 11, 30.0000, NULL, NULL, 857700.00, 'TRY', 'Serbülent''in Ata Altın varlığı.'); -- Ata Altın (ID 11)

-- Aylin (ID: 2)
INSERT INTO `assets` (`user_id`, `asset_definition_id`, `quantity`, `purchase_price`, `purchase_date`, `current_value`, `currency`, `notes`) VALUES
(2, 6, 25.0000, NULL, NULL, 108339.00, 'TRY', 'Aylin''in Gram Altın varlığı.'), -- Gram Altın (ID 6)
(2, 8, 25.0000, NULL, NULL, 107797.25, 'TRY', 'Aylin''in Çeyrek Altın varlığı.'), -- Çeyrek Altın (ID 8)
(2, 9, 10.0000, NULL, NULL, 68989.80, 'TRY', 'Aylin''in Yarım Altın varlığı.'), -- Yarım Altın (ID 9)
(2, 10, 1.0000, NULL, NULL, 13754.85, 'TRY', 'Aylin''in Tam Altın varlığı.'), -- Tam Altın (ID 10)
(2, 11, 11.0000, NULL, NULL, 314490.00, 'TRY', 'Aylin''in Ata Altın varlığı.'), -- Ata Altın (ID 11)
(2, 12, 105.0000, NULL, NULL, 330504.30, 'TRY', 'Aylin''in 22 Ayar Bilezik varlığı.'); -- 22 Ayar Bilezik (ID 12)

-- Meral (ID: 3)
INSERT INTO `assets` (`user_id`, `asset_definition_id`, `quantity`, `purchase_price`, `purchase_date`, `current_value`, `currency`, `notes`) VALUES
(3, 2, 350.00, NULL, NULL, 14131.22, 'USD', 'Meral''in Dolar varlığı.'), -- USD (ID 2)
(3, 3, 395.00, NULL, NULL, 18542.88, 'EUR', 'Meral''in Euro varlığı.'), -- EUR (ID 3)
(3, 4, 1550.00, NULL, NULL, 16665.45, 'SAR', 'Meral''in Suudi Arabistan Riyali varlığı.'), -- SAR (ID 4)
(3, 6, 15.0000, NULL, NULL, 65003.40, 'TRY', 'Meral''in Gram Altın varlığı.'), -- Gram Altın (ID 6)
(3, 11, 1.0000, NULL, NULL, 28590.00, 'TRY', 'Meral''in Ata Altın varlığı.'); -- Ata Altın (ID 11)


-- 9. "transactions" Tablosu : Kullanıcıların yaptığı tüm finansal işlemleri tutar.
-- 'amount' hassasiyeti 4, 'unit_price' ve 'total_try_value' hassasiyeti 2 olarak ayarlandı.
-- 'transaction_type' ENUM değerleri 'Alış' ve 'Satış' olarak güncellendi.
-- Yeni 'property_id' sütunu eklendi.
CREATE TABLE `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NULL, -- İşlem kategorisi (opsiyonel)
    `transaction_type` ENUM('Gelir', 'Gider', 'Transfer', 'Alış', 'Satış', 'Para Yatırma', 'Para Çekme') NOT NULL,
    `amount` DECIMAL(18, 4) NOT NULL, -- Miktar (varlık adedi veya para miktarı) (virgülden sonra 4 basamak)
    `transaction_currency` VARCHAR(3) NOT NULL, -- İşlemin yapıldığı para birimi (örn: TRY, USD)
    `unit_price` DECIMAL(18, 2) NULL, -- Varlık işlemlerinde birim fiyatı (virgülden sonra 2 basamak)
    `total_try_value` DECIMAL(18, 2) NOT NULL, -- İşlemin TL karşılığı (virgülden sonra 2 basamak)
    `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `description` TEXT NULL,
    `from_account_id` INT NULL, -- Transferlerde veya çekimlerde kaynak hesap
    `to_account_id` INT NULL, -- Transferlerde veya yatırımlarda hedef hesap
    `asset_id` INT NULL, -- Varlık alım/satım işlemlerinde ilgili varlık (asset_definitions.id)
    `property_id` INT NULL, -- Kira gelirleri için ilgili mülk (properties.id)
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `transaction_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`from_account_id`) REFERENCES `bank_accounts`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`to_account_id`) REFERENCES `bank_accounts`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`asset_id`) REFERENCES `asset_definitions`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE SET NULL -- Yeni yabancı anahtar
);

-- İşlem verilerini ekleme (Excel İşlemler sayfasından ve yeni sütunlarla uyumlu)
-- asset_id'ler yeni asset_definitions ID'lerine göre güncellendi.
-- transaction_type 'Alış' ve 'Satış' olarak güncellendi.
-- Kira gelirleri artık mülk ID'lerine göre ayrıldı.
INSERT INTO `transactions` (`user_id`, `category_id`, `transaction_type`, `amount`, `transaction_currency`, `unit_price`, `total_try_value`, `transaction_date`, `description`, `from_account_id`, `to_account_id`, `asset_id`, `property_id`) VALUES
(1, 5, 'Satış', 4100.00, 'USD', 7.48, 30668.00, '2020-09-08 00:00:00', NULL, 2, 1, 2, NULL), -- USD Satışı (asset_id 2)
(1, 5, 'Alış', 23.00, 'TRY', 3100.00, 71300.00, '2020-09-08 00:00:00', NULL, 1, NULL, 11, NULL), -- Ata Lira Alışı (asset_id 11)
(1, 5, 'Alış', 155.8283, 'TRY', 468.70, 73036.72, '2020-11-12 00:00:00', NULL, 1, NULL, 7, NULL), -- 24 Ayar Has AU Alışı (asset_id 7)
(1, 5, 'Alış', 7.00, 'TRY', 3137.95, 21965.63, '2020-11-12 00:00:00', NULL, 1, NULL, 11, NULL), -- Ata Lira Alışı (asset_id 11)
(1, 5, 'Alış', 500.00, 'USD', 7.76, 3879.00, '2020-11-12 00:00:00', NULL, 1, 2, 2, NULL), -- USD Alışı (asset_id 2)
(1, 5, 'Alış', 150.00, 'USD', 27.80, 4170.00, '2023-10-13 00:00:00', NULL, 1, 2, 2, NULL), -- USD Alışı (asset_id 2)
(1, 5, 'Alış', 1385.00, 'USD', 27.80, 38503.00, '2023-10-13 00:00:00', 'MELİH', 1, 2, 2, NULL), -- USD Alışı (asset_id 2)
(1, 5, 'Alış', 2050.00, 'USD', 30.70, 62935.00, '2024-02-09 00:00:00', 'MELİH', 1, 2, 2, NULL), -- USD Alışı (asset_id 2)
(1, 5, 'Alış', 1050.00, 'USD', 32.55, 34177.50, '2024-04-18 00:00:00', 'MELİH', 1, 2, 2, NULL), -- USD Alışı (asset_id 2)
(1, 5, 'Alış', 450.00, 'USD', 32.55, 14647.50, '2024-04-18 00:00:00', NULL, 1, 2, 2, NULL), -- USD Alışı (asset_id 2)
(1, 5, 'Alış', 250.00, 'USD', 32.55, 8137.50, '2024-04-18 00:00:00', 'ANNEM', 1, 2, 2, NULL), -- USD Alışı (asset_id 2)
-- Yeni eklenen maaş ve kira geliri işlemleri (son 4 ay)
(1, 1, 'Gelir', 25000.00, 'TRY', NULL, 25000.00, '2024-04-25 09:00:00', 'Nisan ayı maaş ödemesi.', NULL, 5, 1, NULL), -- TRY (ID 1)
(1, 1, 'Gelir', 25000.00, 'TRY', NULL, 25000.00, '2024-05-25 09:00:00', 'Mayıs ayı maaş ödemesi.', NULL, 5, 1, NULL), -- TRY (ID 1)
(1, 1, 'Gelir', 25000.00, 'TRY', NULL, 25000.00, '2024-06-25 09:00:00', 'Haziran ayı maaş ödemesi.', NULL, 5, 1, NULL), -- TRY (ID 1)
(1, 1, 'Gelir', 25000.00, 'TRY', NULL, 25000.00, '2024-07-25 09:00:00', 'Temmuz ayı maaş ödemesi.', NULL, 5, 1, NULL), -- TRY (ID 1)
-- Kira gelirleri artık mülk ID'lerine göre ayrıldı
(1, 9, 'Gelir', 30000.00, 'TRY', NULL, 30000.00, '2024-04-20 10:00:00', 'Nisan ayı Bahçelievler kira geliri.', NULL, 4, 1, 1), -- Bahçelievler Daire (ID 1)
(1, 9, 'Gelir', 25000.00, 'TRY', NULL, 25000.00, '2024-04-20 10:00:00', 'Nisan ayı Kadıköy kira geliri.', NULL, 4, 1, 2), -- Kadıköy Daire (ID 2)
(1, 9, 'Gelir', 30000.00, 'TRY', NULL, 30000.00, '2024-05-20 10:00:00', 'Mayıs ayı Bahçelievler kira geliri.', NULL, 4, 1, 1), -- Bahçelievler Daire (ID 1)
(1, 9, 'Gelir', 25000.00, 'TRY', NULL, 25000.00, '2024-05-20 10:00:00', 'Mayıs ayı Kadıköy kira geliri.', NULL, 4, 1, 2), -- Kadıköy Daire (ID 2)
(1, 9, 'Gelir', 30000.00, 'TRY', NULL, 30000.00, '2024-06-20 10:00:00', 'Haziran ayı Bahçelievler kira geliri.', NULL, 4, 1, 1), -- Bahçelievler Daire (ID 1)
(1, 9, 'Gelir', 25000.00, 'TRY', NULL, 25000.00, '2024-06-20 10:00:00', 'Haziran ayı Kadıköy kira geliri.', NULL, 4, 1, 2), -- Kadıköy Daire (ID 2)
(1, 9, 'Gelir', 30000.00, 'TRY', NULL, 30000.00, '2024-07-20 10:00:00', 'Temmuz ayı Bahçelievler kira geliri.', NULL, 4, 1, 1), -- Bahçelievler Daire (ID 1)
(1, 9, 'Gelir', 25000.00, 'TRY', NULL, 25000.00, '2024-07-20 10:00:00', 'Temmuz ayı Kadıköy kira geliri.', NULL, 4, 1, 2); -- Kadıköy Daire (ID 2)


-- 10. "user_settings" Tablosu : Kullanıcıya özel ayarları tutar.
CREATE TABLE `user_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `currency_preference` VARCHAR(3) NOT NULL DEFAULT 'TRY', -- Varsayılan para birimi
    `notification_enabled` BOOLEAN NOT NULL DEFAULT TRUE,
    `theme_preference` VARCHAR(50) NOT NULL DEFAULT 'light', -- Tema tercihi (örn: light, dark)
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Kullanıcı ayarları verilerini ekleme
INSERT INTO `user_settings` (`id`, `user_id`, `currency_preference`, `notification_enabled`, `theme_preference`) VALUES
(1, 1, 'TRY', TRUE, 'dark'),
(2, 2, 'USD', TRUE, 'light'),
(3, 3, 'EUR', FALSE, 'light');

-- Foreign key kontrollerini tekrar etkinleştir.
SET FOREIGN_KEY_CHECKS = 1;
