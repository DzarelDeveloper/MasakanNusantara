-- Masakan Nusantara — QR table ordering system
-- Fase 1 schema. See /opt/lampp/htdocs/OneDine/task.txt for the full concept.
-- Fresh database, deliberately not reusing the unrelated `onedine` DB
-- (that one belongs to an old, separate Laravel project at htdocs/Food).

CREATE DATABASE IF NOT EXISTS masakan_nusantara
    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE masakan_nusantara;

CREATE TABLE IF NOT EXISTS dining_tables (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_number   INT UNSIGNED NOT NULL UNIQUE,
    capacity_label VARCHAR(20)  NOT NULL,   -- e.g. "1-2", "2-4"
    qr_token       CHAR(32)     NOT NULL UNIQUE,
    -- Pembersihan meja dikerjakan langsung offline oleh staf, tidak dilacak
    -- sebagai status terpisah di aplikasi — "Tutup Sesi" langsung
    -- mengosongkan meja lagi (lihat owner/tables.php aksi close_session).
    status         ENUM('kosong', 'terisi') NOT NULL DEFAULT 'kosong',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menu_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category     VARCHAR(50)  NOT NULL,
    name         VARCHAR(100) NOT NULL,
    description  VARCHAR(255) NOT NULL DEFAULT '',
    price        INT UNSIGNED NOT NULL,     -- Rupiah, whole units
    image        VARCHAR(255) NOT NULL DEFAULT '',
    stock_status ENUM('tersedia', 'habis') NOT NULL DEFAULT 'tersedia',
    sort_order   INT UNSIGNED NOT NULL DEFAULT 0,
    -- Only meaningful for kategori Minuman: apakah item ini benar-benar bisa
    -- disajikan dingin maupun panas, atau cuma salah satu (mis. Es Jeruk
    -- selalu dingin, jadi pelanggan tidak perlu ditanya pilihan suhu lagi).
    serve_temp   ENUM('keduanya', 'dingin', 'panas') NOT NULL DEFAULT 'keduanya',
    -- Analog serve_temp tapi untuk kategori Makanan: apakah pelanggan bisa
    -- pilih tingkat pedas, atau hidangan ini disajikan dengan level tetap
    -- (mis. memang tidak ada versi pedas dari resepnya) sehingga picker-nya
    -- tidak perlu ditampilkan.
    spice_option ENUM('ada', 'tidak_ada') NOT NULL DEFAULT 'ada'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS table_sessions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_id   INT UNSIGNED NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at   TIMESTAMP NULL DEFAULT NULL,
    status     ENUM('aktif', 'selesai') NOT NULL DEFAULT 'aktif',
    FOREIGN KEY (table_id) REFERENCES dining_tables(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    -- Kode 6 digit (dicari manual pelanggan di kasir) + token QR Order
    -- (dipindai kasir). Unik dijaga di kode aplikasi (includes/order-code.php),
    -- bukan constraint DB keras, supaya bisa disesuaikan tanpa migrasi lagi.
    order_code        CHAR(6) NULL,
    qr_lookup_token   CHAR(32) NULL UNIQUE,
    table_id          INT UNSIGNED NOT NULL,
    table_session_id  INT UNSIGNED NULL,
    customer_name     VARCHAR(100) NOT NULL,
    customer_email    VARCHAR(150) NOT NULL,
    notes             VARCHAR(255) NOT NULL DEFAULT '',
    subtotal          INT UNSIGNED NOT NULL DEFAULT 0,
    status            ENUM('menunggu_bayar', 'baru_masuk', 'diproses', 'siap_disajikan', 'selesai', 'dibatalkan')
                      NOT NULL DEFAULT 'menunggu_bayar',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES dining_tables(id),
    FOREIGN KEY (table_session_id) REFERENCES table_sessions(id),
    INDEX idx_orders_order_code (order_code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        INT UNSIGNED NOT NULL,
    menu_item_id    INT UNSIGNED NOT NULL,
    item_name       VARCHAR(100) NOT NULL,  -- snapshot, survives menu edits
    price_at_order  INT UNSIGNED NOT NULL,  -- snapshot
    quantity        INT UNSIGNED NOT NULL,
    notes           VARCHAR(255) NOT NULL DEFAULT '',
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS staff_users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until  DATETIME NULL,
    role          ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id             INT UNSIGNED NOT NULL UNIQUE,
    method               VARCHAR(20) NOT NULL DEFAULT 'QRIS',  -- 'QRIS' atau 'Tunai'
    status               ENUM('pending', 'sukses', 'gagal') NOT NULL DEFAULT 'pending',
    cashier_id           INT UNSIGNED NULL,  -- diisi staf yang konfirmasi bayar tunai
    fake_transaction_id  VARCHAR(40) DEFAULT NULL,
    paid_at              TIMESTAMP NULL DEFAULT NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (cashier_id) REFERENCES staff_users(id)
) ENGINE=InnoDB;
