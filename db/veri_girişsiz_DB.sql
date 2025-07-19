-- Bu SQL script'i, "my_assets" veritabanınız için tüm tabloları oluşturur.
-- Herhangi bir örnek veri eklemez, sadece tablo yapılarını oluşturur.
-- MySQL Workbench'te bu script'i tek seferde çalıştırabilirsiniz.
-- Tabloların silinip yeniden oluşturulacağını unutmayın, mevcut veriler kaybolacaktır.

-- Foreign key kontrollerini geçici olarak devre dışı bırak, bu sayede DROP TABLE sorunsuz çalışır.
SET FOREIGN_KEY_CHECKS = 0;

-- Veritabanını sil (eğer varsa)
DROP DATABASE IF EXISTS `my_assets`;

-- Yeni veritabanını oluştur
CREATE DATABASE `my_assets` CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;

-- Yeni oluşturulan veritabanını kullan (BU KOMUT ÇOK ÖNEMLİ!)
USE `my_assets`;

-- Tabloları doğru sırada silme (Yabancı anahtar bağımlılıklarını çözmek için)
-- En çok bağımlılığı olan (diğer tablolara referans veren) tablolar en son silinmeli,
-- ancak DROP TABLE yaparken referans alanlar önce silinmelidir.
-- Bu yüzden DROP TABLE sıralaması CREATE TABLE sıralamasının tersi olmalıdır.
-- NOT: Bu DROP TABLE komutları artık 'USE my_assets;' komutundan sonra çalışacağı için
-- veritabanı seçili olacak ve hata vermeyecektir.
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `user_settings`;
DROP TABLE IF EXISTS `bank_accounts`;
DROP TABLE IF EXISTS `assets`;
DROP TABLE IF EXISTS `market_prices`;
DROP TABLE IF EXISTS `transaction_categories`;
DROP TABLE IF EXISTS `exchange_rates`;
DROP TABLE IF EXISTS `asset_definitions`;
DROP TABLE IF EXISTS `users`;


-- Foreign key kontrollerini tekrar etkinleştir.
SET FOREIGN_KEY_CHECKS = 1;

-- 1. "users" Tablosu : Kullanıcı bilgilerini tutar.
-- Bu, uygulamanın sadece lokalde çalışacağı varsayımıyla yapılmıştır.
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) UNIQUE NULL, -- E-posta adresi, benzersiz ve NULL yapılabilir olarak ayarlandı
    `phone_number` VARCHAR(20) NULL, -- Telefon numarası
    `profile_image_path` VARCHAR(255) NULL, -- Profil resmi yolu
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. "asset_definitions" Tablosu : Varlık türlerinin tanımlarını tutar.
CREATE TABLE `asset_definitions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    -- ENUM değerleri senin isteğin üzerine güncellendi: 'Altın', 'Döviz', 'diğer'
    `type` ENUM('Altın', 'Döviz', 'diğer') NOT NULL,
    `symbol` VARCHAR(10) UNIQUE NULL, -- Sembol (örn: USD, EUR, XAU)
    `description` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. "assets" Tablosu : Kullanıcıların sahip olduğu varlıkları tutar.
CREATE TABLE `assets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `asset_definition_id` INT NOT NULL,
    `quantity` DECIMAL(18, 4) NOT NULL, -- Varlık miktarı (hassasiyet 4 basamağa düşürüldü)
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

-- 4. "bank_accounts" Tablosu : Kullanıcıların banka hesap bilgilerini tutar.
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

-- 5. "exchange_rates" Tablosu : Para birimleri arasındaki döviz kurlarını tutar.
CREATE TABLE `exchange_rates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `base_currency` VARCHAR(3) NOT NULL, -- Baz para birimi (örn: USD)
    `target_currency` VARCHAR(3) NOT NULL, -- Hedef para birimi (örn: TRY)
    `rate` DECIMAL(18, 2) NOT NULL, -- Kur değeri (virgülden sonra 2 basamak)
    `rate_date` DATE NOT NULL, -- Kurun geçerli olduğu tarih
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (`base_currency`, `target_currency`, `rate_date`) -- Aynı gün aynı kurlar tekrarlanamaz
);

-- 6. "market_prices" Tablosu : Varlıkların piyasa fiyatlarını tutar.
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

-- 7. "transaction_categories" Tablosu: İşlem kategorilerini tutar.
CREATE TABLE `transaction_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL, -- NULL olabilir, genel kategoriler için
    `name` VARCHAR(100) NOT NULL,
    -- Bu ENUM değerleri senin isteğin üzerine tamamen Türkçe'ye çevrildi
    `type` ENUM('Gelir', 'Gider', 'Transfer', 'Yatırım') NOT NULL,
    `description` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE (`user_id`, `name`) -- Her kullanıcı için kategori adı benzersiz olmalı, genel kategoriler için name benzersiz olmalı
);

-- 8. "transactions" Tablosu : Kullanıcıların yaptığı tüm finansal işlemleri tutar.
CREATE TABLE `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NULL, -- İşlem kategorisi (opsiyonel)
    -- ENUM değerleri senin isteğin üzerine Türkçe'ye çevrildi
    `transaction_type` ENUM('Gelir', 'Gider', 'Transfer', 'Yatırım Alış', 'Yatırım Satış', 'Para Yatırma', 'Para Çekme') NOT NULL,
    `amount` DECIMAL(18, 2) NOT NULL, -- İşlem miktarı (virgülden sonra 2 basamak)
    `currency` VARCHAR(3) NOT NULL, -- İşlem para birimi
    `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `description` TEXT NULL,
    `from_account_id` INT NULL, -- Transferlerde veya çekimlerde kaynak hesap
    `to_account_id` INT NULL, -- Transferlerde veya yatırımlarda hedef hesap
    `asset_id` INT NULL, -- Varlık alım/satım işlemlerinde ilgili varlık
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `transaction_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`from_account_id`) REFERENCES `bank_accounts`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`to_account_id`) REFERENCES `bank_accounts`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`asset_id`) REFERENCES `assets`(`id`) ON DELETE SET NULL
);

-- 9. "user_settings" Tablosu : Kullanıcıya özel ayarları tutar.
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
