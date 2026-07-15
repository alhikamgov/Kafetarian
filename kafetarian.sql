-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 15 Jul 2026 pada 20.40
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kafetarian`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) DEFAULT NULL,
  `menu_id` int(11) DEFAULT NULL,
  `jumlah` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id`, `pesanan_id`, `menu_id`, `jumlah`, `subtotal`) VALUES
(1, 1, 1, 1, 15000.00),
(2, 1, 6, 1, 5000.00),
(3, 2, 2, 1, 15000.00),
(4, 2, 6, 1, 5000.00),
(5, 3, 4, 1, 15000.00),
(6, 3, 5, 1, 3000.00),
(9, 5, 1, 1, 15000.00),
(10, 5, 5, 1, 3000.00),
(11, 6, 2, 1, 15000.00),
(12, 6, 5, 1, 3000.00),
(13, 7, 1, 1, 15000.00),
(14, 7, 6, 1, 5000.00),
(15, 8, 5, 1, 3000.00),
(16, 9, 1, 3, 45000.00),
(17, 9, 6, 2, 10000.00),
(18, 10, 1, 1, 15000.00),
(19, 10, 6, 1, 5000.00),
(20, 11, 2, 1, 15000.00),
(21, 11, 5, 1, 3000.00),
(22, 11, 6, 1, 5000.00),
(23, 11, 1, 1, 15000.00),
(24, 12, 3, 1, 15000.00),
(25, 13, 5, 1, 3000.00),
(26, 14, 3, 1, 15000.00),
(31, 17, 1, 1, 15000.00),
(32, 17, 2, 1, 15000.00),
(33, 17, 5, 1, 3000.00),
(34, 17, 6, 1, 5000.00),
(35, 18, 1, 1, 15000.00),
(36, 18, 6, 1, 5000.00),
(37, 19, 1, 2, 30000.00),
(38, 19, 6, 2, 10000.00),
(39, 20, 1, 2, 30000.00),
(40, 20, 4, 1, 15000.00),
(41, 20, 5, 1, 3000.00),
(42, 20, 6, 2, 10000.00),
(43, 21, 4, 1, 15000.00),
(44, 21, 1, 2, 30000.00),
(45, 21, 6, 1, 5000.00),
(46, 21, 5, 2, 6000.00),
(47, 22, 1, 1, 15000.00),
(48, 22, 2, 1, 15000.00),
(49, 22, 6, 2, 10000.00),
(50, 22, 5, 2, 6000.00),
(51, 23, 1, 2, 30000.00),
(52, 23, 2, 1, 15000.00),
(53, 23, 5, 2, 6000.00),
(54, 23, 6, 1, 5000.00),
(55, 24, 1, 2, 30000.00),
(56, 24, 2, 1, 15000.00),
(57, 24, 5, 1, 3000.00),
(58, 24, 6, 2, 10000.00),
(59, 25, 1, 2, 30000.00),
(60, 25, 2, 1, 15000.00),
(61, 25, 6, 2, 10000.00),
(62, 25, 5, 2, 6000.00),
(63, 26, 1, 2, 30000.00),
(64, 26, 2, 1, 15000.00),
(65, 26, 5, 2, 6000.00),
(66, 26, 6, 2, 10000.00),
(67, 27, 1, 2, 30000.00),
(68, 27, 2, 2, 30000.00),
(69, 27, 6, 2, 10000.00),
(70, 27, 5, 3, 9000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kategori` enum('makanan','minuman') NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `menu`
--

INSERT INTO `menu` (`id`, `nama`, `kategori`, `harga`, `thumbnail`, `created_at`) VALUES
(1, 'Nasi Goreng', 'makanan', 15000.00, '1783685374_268165.jpg', '2026-07-10 12:09:34'),
(2, 'Mie Goreng', 'makanan', 15000.00, '1783685450_591095.jpg', '2026-07-10 12:10:50'),
(3, 'Kwetiau Goreng', 'makanan', 15000.00, '1783685584_618947.jpg', '2026-07-10 12:13:04'),
(4, 'Bihun Goreng', 'makanan', 15000.00, '1783685741_409164.jpeg', '2026-07-10 12:15:41'),
(5, 'Es Teh', 'minuman', 3000.00, '1783685772_811252.jpg', '2026-07-10 12:16:12'),
(6, 'Es Jeruk', 'minuman', 5000.00, '1783685793_561396.jpg', '2026-07-10 12:16:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `key_name` varchar(50) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `key_name`, `value`, `created_at`, `updated_at`) VALUES
(1, 'nama_toko', 'Kafetarian', '2026-07-09 23:02:36', '2026-07-09 23:08:07'),
(2, 'alamat', 'Jl. Raya Contoh No. 123, Kota Contoh 12345', '2026-07-09 23:02:36', '2026-07-09 23:02:36'),
(3, 'telepon', '0812-3456-7890', '2026-07-09 23:02:36', '2026-07-09 23:02:36'),
(4, 'email', 'info@kafetamin.com', '2026-07-09 23:02:36', '2026-07-09 23:02:36'),
(5, 'jam_buka', '08:00', '2026-07-09 23:02:36', '2026-07-09 23:02:36'),
(6, 'jam_tutup', '21:00', '2026-07-09 23:02:36', '2026-07-09 23:02:36'),
(7, 'hari_buka', 'Senin - Jumat', '2026-07-09 23:02:36', '2026-07-09 23:02:36'),
(8, 'hari_akhir_pekan', 'Sabtu - Minggu: 09:00 - 22:00', '2026-07-09 23:02:36', '2026-07-09 23:02:36'),
(9, 'deskripsi', 'Tempat terbaik untuk menikmati hidangan lezat dan minuman segar', '2026-07-09 23:02:36', '2026-07-09 23:02:36'),
(10, 'footer_text', '© All rights reserved.', '2026-07-09 23:02:36', '2026-07-14 00:53:49'),
(11, 'banner', 'banner_1783674060.jpg', '2026-07-10 08:59:01', '2026-07-10 09:01:00'),
(12, 'tema', 'hijau', '2026-07-14 00:25:28', '2026-07-15 18:34:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `trx_id` varchar(20) NOT NULL,
  `nama_pemesan` varchar(100) NOT NULL,
  `tipe` enum('dine-in','take-away') NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `status` enum('dikirim','dibayar','dibuat','selesai') DEFAULT 'dikirim',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id`, `trx_id`, `nama_pemesan`, `tipe`, `total_harga`, `status`, `created_at`) VALUES
(1, 'TRX-20260710-9175', 'Ikam', 'dine-in', 20000.00, 'selesai', '2026-07-10 12:17:38'),
(2, 'TRX-20260710-8643', 'Ikam', 'take-away', 20000.00, 'selesai', '2026-07-10 12:29:07'),
(3, 'TRX-20260710-4178', 'Ikam', 'dine-in', 18000.00, 'selesai', '2026-07-10 12:32:51'),
(5, 'TRX-20260710-1349', 'Ikam', 'dine-in', 18000.00, 'selesai', '2026-07-10 17:44:00'),
(6, 'TRX-20260710-5092', 'Ikam', 'dine-in', 18000.00, 'selesai', '2026-07-10 18:03:23'),
(7, 'TRX-20260710-7126', 'Ikam', 'take-away', 20000.00, 'selesai', '2026-07-10 18:40:22'),
(8, 'TRX-20260711-5444', 'a', 'dine-in', 3000.00, 'selesai', '2026-07-11 02:44:52'),
(9, 'TRX-20260711-6596', 'sz', 'dine-in', 55000.00, 'selesai', '2026-07-11 02:52:44'),
(10, 'TRX-20260713-2038', 'Ikam', 'dine-in', 20000.00, 'selesai', '2026-07-13 17:17:56'),
(11, 'POS-20260713-5050', 'Ikam', 'dine-in', 38000.00, 'selesai', '2026-07-13 17:25:53'),
(12, 'POS-20260713-9612', 'Ikam', 'dine-in', 15000.00, 'selesai', '2026-07-13 17:31:39'),
(13, 'POS-20260713-6616', 'Ikam', 'dine-in', 3000.00, 'selesai', '2026-07-13 17:32:22'),
(14, 'POS-20260713-7032', 'Ikam', 'dine-in', 15000.00, 'selesai', '2026-07-13 17:32:59'),
(17, 'TRX-20260714-2351', 'Ikam', 'dine-in', 38000.00, 'selesai', '2026-07-14 00:10:24'),
(18, 'TRX-20260714-9585', 'Ikam', 'dine-in', 20000.00, 'selesai', '2026-07-14 00:34:23'),
(19, 'TRX-20260714-2137', 'Ikam', 'take-away', 40000.00, 'selesai', '2026-07-14 01:08:50'),
(20, 'TRX-20260714-1096', 'Ikam', 'take-away', 58000.00, 'selesai', '2026-07-14 21:24:45'),
(21, 'POS-20260714-6046', 'Ikam', 'dine-in', 56000.00, 'selesai', '2026-07-14 21:26:53'),
(22, 'POS-20260714-3262', 'Ikam', 'dine-in', 46000.00, 'selesai', '2026-07-14 21:28:01'),
(23, 'TRX-20260715-6672', 'Ikam', 'take-away', 56000.00, 'selesai', '2026-07-15 01:09:43'),
(24, 'POS-20260715-7145', 'Ikam', 'take-away', 58000.00, 'selesai', '2026-07-15 01:11:27'),
(25, 'POS-20260715-6352', 'Ikam', 'take-away', 61000.00, 'selesai', '2026-07-15 01:12:29'),
(26, 'POS-20260715-2779', 'Ikam', 'dine-in', 61000.00, 'selesai', '2026-07-15 01:22:40'),
(27, 'POS-20260715-7666', 'Ikam', 'dine-in', 79000.00, 'selesai', '2026-07-15 01:25:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('admin','kasir') DEFAULT 'kasir',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$6/6VBzPKnjQLKRMyIWao/.oItruglAR2Dy1v1dF6cI5GmfyHieEyq', 'admin', '2026-07-09 21:56:35');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indeks untuk tabel `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trx_id` (`trx_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT untuk tabel `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
