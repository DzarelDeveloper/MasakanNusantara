-- Analog dari serve_temp (Minuman) tapi untuk Makanan: sebagian hidangan
-- bisa diatur level pedasnya oleh pelanggan, sebagian lagi disajikan
-- dengan level tetap sesuai resep (tidak perlu ditanya). Default 'ada'
-- (semua Makanan existing tetap tampilkan picker seperti sebelumnya).
ALTER TABLE menu_items
  ADD COLUMN spice_option ENUM('ada', 'tidak_ada') NOT NULL DEFAULT 'ada' AFTER serve_temp;
