-- Kasir (bayar di kasir) + kode order 6 digit + QR Order + status meja
-- 3-state (kosong/terisi/dibersihkan). Additive only, aman dijalankan di DB
-- live tanpa truncate/data loss. Lihat plan di
-- /home/dzarel/.claude/plans/purrfect-conjuring-dewdrop.md

ALTER TABLE dining_tables
  MODIFY COLUMN status ENUM('kosong', 'terisi', 'dibersihkan') NOT NULL DEFAULT 'kosong';

ALTER TABLE orders
  ADD COLUMN order_code CHAR(6) NULL AFTER id,
  ADD COLUMN qr_lookup_token CHAR(32) NULL AFTER order_code,
  ADD INDEX idx_orders_order_code (order_code),
  ADD UNIQUE KEY uq_orders_qr_lookup_token (qr_lookup_token);

ALTER TABLE payments
  ADD COLUMN cashier_id INT UNSIGNED NULL AFTER status,
  ADD CONSTRAINT fk_payments_cashier FOREIGN KEY (cashier_id) REFERENCES staff_users(id);
