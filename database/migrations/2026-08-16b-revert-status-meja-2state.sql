-- Revert status meja dari 3-tingkat (kosong/terisi/dibersihkan) balik ke
-- 2-tingkat (kosong/terisi). Pembersihan meja dikerjakan langsung offline
-- oleh staf, tidak perlu dilacak sebagai status terpisah di aplikasi —
-- "Tutup Sesi" sekarang langsung mengosongkan meja lagi.
-- Aman dijalankan: tidak ada baris berstatus 'dibersihkan' saat ini.

UPDATE dining_tables SET status = 'kosong' WHERE status = 'dibersihkan';

ALTER TABLE dining_tables
  MODIFY COLUMN status ENUM('kosong', 'terisi') NOT NULL DEFAULT 'kosong';
