-- Masakan Nusantara — starter data untuk phpMyAdmin (u758001970_nusantara)
-- Setara dengan database/seed.php, dibuat manual karena hosting ini tidak punya akses CLI/SSH.
-- Aman dijalankan di database kosong (baru selesai import schema.hostinger.sql).

INSERT INTO staff_users (name, email, password_hash, role) VALUES
  ('Admin Resto', 'admin@masakannusantara.co.id', '$2y$12$xr6lVCXQl5pD8frkX1aEsOdkQXrxfRSkSI2HSBo3belLBwS9AGq8C', 'admin');
-- Login: admin@masakannusantara.co.id / admin123 — WAJIB ganti setelah login pertama (menu Ganti Sandi).

INSERT INTO dining_tables (table_number, capacity_label, qr_token) VALUES
  (1, '1-2', '30dd263fe06d514d0e05c94bd2da7534'),
  (2, '1-2', '465fcd0eab54066323922609410ef899'),
  (3, '1-2', '2197f1ed809d33c15bdb7d92f0e4bb6f'),
  (4, '1-2', '07097646c9bf55f83f4c7cd9b49efbe4'),
  (5, '1-2', '186334ec015d16bdadbde1400d82a84a'),
  (6, '1-2', '2c419d1e562f6f639f77b00daaf1356f'),
  (7, '1-2', '28ffd92c5a48e86d905501a8186f8625'),
  (8, '1-2', 'cdbd063cc7ce84388e41ab948b8f9ee1'),
  (9, '1-2', 'd20cb7dcddb544b992c13ca3c52a110d'),
  (10, '1-2', 'b51fa6dbafac2eb400c3ec765fa1fd47'),
  (11, '1-2', '73cf5fb00031dd6dd242557eab6a9d28'),
  (12, '1-2', '2cdc1d42838db8564a77d535bd4079a1'),
  (13, '1-2', '7efccdda436914352e87d58f8d4fbc7c'),
  (14, '1-2', '8705b5f48a9b736eb9dbd71de4431da4'),
  (15, '1-2', 'd88cb83f3917383e9a1eeca5528297cf'),
  (16, '2-4', '71e736871713466b7c308d09f44099f8'),
  (17, '2-4', '7d43076f37b4dbd30dfcbe2f118ad57a'),
  (18, '2-4', 'c5b8342263d164b9050906fd63bb297e'),
  (19, '2-4', '86aa29facd9cf74820beed03a2f0541d'),
  (20, '2-4', '20fec448366cfbb480436b368d2e6837'),
  (21, '2-4', 'b57e351b3fb09cfc7f1c1f890d43c279'),
  (22, '2-4', '8c4c47102914c7d9c455d8cf11d8065d'),
  (23, '2-4', '50c6d4fff96aaadbdfe708471ca1c4a3'),
  (24, '2-4', '6426d60556c032cec749ae1d3fbb13b2'),
  (25, '2-4', '2e55c098f93e942a3f7cfda603329298'),
  (26, '2-4', '5ed87bf5f10c666bb11a006da89810b1'),
  (27, '2-4', 'a4ac6b94c501f9ff552499c31524df5c'),
  (28, '2-4', 'ed2e6d7ce74b10f012fe6534735a3488'),
  (29, '2-4', 'e080f2455b9472199b28238eca6eedf1'),
  (30, '2-4', '357c71f81aa6451353bc14ee5ae0199c'),
  (31, '2-4', '3da64172049f89ea8d58c795a1a6d632'),
  (32, '2-4', 'c9c3a714a365eab9e9edcd1f70ebaf1e'),
  (33, '2-4', '72bea1f80bed18c1ccc2a4ecae5cfa65'),
  (34, '2-4', '8b1513d3c01d19ecadd6ba427a873b3d'),
  (35, '2-4', '1e494a8dd18a8498e83521fda1ef126d');

INSERT INTO menu_items (category, name, description, price, image, sort_order, serve_temp) VALUES
  ('Makanan', 'Nasi Goreng Spesial', 'Nasi goreng dengan ayam, telur, dan kerupuk', 28000, 'assets/images/dishes/nasi-goreng.jpg', 0, 'keduanya'),
  ('Makanan', 'Rendang Sapi', 'Daging sapi masak rempah khas Padang', 35000, 'assets/images/dishes/rendang.jpg', 1, 'keduanya'),
  ('Makanan', 'Sate Ayam (5 tusuk)', 'Sate ayam bakar bumbu kecap, bumbu kacang', 20000, 'assets/images/dishes/sate-ayam.jpg', 2, 'keduanya'),
  ('Makanan', 'Gado-Gado', 'Sayuran rebus dengan siraman bumbu kacang', 18000, 'assets/images/dishes/gado-gado.jpg', 3, 'keduanya'),
  ('Makanan', 'Ayam Penyet', 'Ayam goreng geprek, sambal terasi, lalapan', 25000, 'assets/images/dishes/ayam-penyet.jpg', 4, 'keduanya'),
  ('Makanan', 'Soto Ayam', 'Sup ayam bening kuah kunyit dengan soun, telur, dan koya', 22000, 'assets/images/dishes/soto-ayam.jpg', 5, 'keduanya'),
  ('Makanan', 'Mie Goreng Jawa', 'Mie goreng bumbu kecap khas Jawa dengan telur dan sayuran', 20000, 'assets/images/dishes/mie-goreng.jpg', 6, 'keduanya'),
  ('Makanan', 'Ikan Bakar', 'Ikan bakar bumbu kecap pedas manis, disajikan dengan sambal', 32000, 'assets/images/dishes/ikan-bakar.jpg', 7, 'keduanya'),
  ('Makanan', 'Cap Cai', 'Tumis aneka sayuran segar dengan bakso dan udang', 22000, 'assets/images/dishes/capcay.jpg', 8, 'keduanya'),
  ('Makanan', 'Sop Buntut', 'Sup buntut sapi kaya rempah, gurih dan hangat', 38000, 'assets/images/dishes/sop-buntut.jpg', 9, 'keduanya'),
  ('Minuman', 'Teh Manis', 'Teh manis khas Nusantara — pilih dingin atau panas', 6000, 'assets/images/dishes/teh-manis.jpg', 10, 'keduanya'),
  ('Minuman', 'Teh Tawar', 'Teh tawar hangat/dingin tanpa gula', 5000, 'assets/images/dishes/teh-tawar.jpg', 11, 'keduanya'),
  ('Minuman', 'Es Jeruk', 'Perasan jeruk segar dengan es', 8000, 'assets/images/dishes/es-jeruk.jpg', 12, 'dingin'),
  ('Minuman', 'Es Cendol', 'Cendol, santan, dan gula merah', 12000, 'assets/images/dishes/es-cendol.jpg', 13, 'dingin'),
  ('Minuman', 'Kopi Tubruk', 'Kopi hitam khas Nusantara', 10000, 'assets/images/dishes/kopi-tubruk.jpg', 14, 'panas'),
  ('Minuman', 'Teh Tarik', 'Teh susu ditarik khas warung', 10000, 'assets/images/dishes/teh-tarik.jpg', 15, 'keduanya'),
  ('Minuman', 'Es Kelapa Muda', 'Kelapa muda segar dengan sirup', 15000, 'assets/images/dishes/es-kelapa-muda.jpg', 16, 'dingin'),
  ('Minuman', 'Jus Alpukat', 'Jus alpukat kental dengan susu cokelat', 15000, 'assets/images/dishes/jus-alpukat.jpg', 17, 'dingin'),
  ('Dimsum', 'Siomay Udang', 'Siomay udang kukus, disajikan dengan saus', 15000, 'assets/images/dishes/siomay.jpg', 18, 'keduanya'),
  ('Dimsum', 'Hakau Udang', 'Dumpling udang kukus kulit transparan', 18000, 'assets/images/dishes/hakau.jpg', 19, 'keduanya'),
  ('Dimsum', 'Bakpao Ayam', 'Bakpao kukus isi ayam', 12000, 'assets/images/dishes/bakpao.jpg', 20, 'keduanya'),
  ('Dimsum', 'Pangsit Goreng', 'Pangsit renyah isi ayam dan udang, disajikan dengan saus', 14000, 'assets/images/dishes/pangsit-goreng.jpg', 21, 'keduanya'),
  ('Dimsum', 'Lumpia Semarang', 'Lumpia goreng isi rebung dan ayam, khas Semarang', 15000, 'assets/images/dishes/lumpia.jpg', 22, 'keduanya'),
  ('Dimsum', 'Xiao Long Bao', 'Dumpling kukus isi kuah dan daging ayam cincang ala Shanghai', 20000, 'assets/images/dishes/xiao-long-bao.jpg', 23, 'keduanya'),
  ('Dimsum', 'Kuotie', 'Dumpling goreng-kukus isi ayam dan sayuran, gurih dan renyah', 17000, 'assets/images/dishes/kuotie.jpg', 24, 'keduanya');

