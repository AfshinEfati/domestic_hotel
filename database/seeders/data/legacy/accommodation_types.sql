-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 10.0.0.4
-- Generation Time: Sep 17, 2026 at 07:45 AM
-- Server version: 8.1.0
-- PHP Version: 8.2.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `newhotel`
--

-- --------------------------------------------------------

--
-- Table structure for table `accommodation_types`
--

CREATE TABLE `accommodation_types` (
  `id` bigint UNSIGNED NOT NULL,
  `fa_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `en_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accommodation_types`
--

INSERT INTO `accommodation_types` (`id`, `fa_name`, `en_name`, `created_at`, `updated_at`) VALUES
(1, 'اقامتگاه سنتی', 'traditional residence', '2026-02-21 12:02:09', '2026-02-22 10:42:22'),
(2, 'خانه مسافر', 'traveler house', '2026-02-21 12:02:09', '2026-02-22 10:42:38'),
(3, 'اقامتگاه بوم گردی', 'ecotourism resorts', '2026-02-21 12:02:09', '2026-02-22 10:42:53'),
(4, 'هتل', 'hotel', '2026-02-21 12:02:09', '2026-02-22 10:43:03'),
(5, 'هتل آپارتمان', 'apartment hotel', '2026-02-21 12:02:10', '2026-02-22 10:41:30'),
(6, 'مجتمع اقامتی', 'residential complex', '2026-02-21 12:02:10', '2026-02-22 10:41:47'),
(7, 'مجتمع گردشگری', 'tourism complex', '2026-02-21 12:02:10', '2026-02-22 10:42:08'),
(8, 'مهمانسرای ممتاز', 'privilege inn', '2026-02-21 12:02:11', '2026-02-22 10:41:05'),
(9, 'مهمانسرا', 'inn', '2026-02-21 12:02:11', '2026-02-22 10:41:17'),
(10, 'هتل بوتیک', 'boutique', '2026-02-21 12:02:12', '2026-02-22 10:40:24'),
(11, 'متل', 'motel', '2026-02-21 12:02:12', '2026-02-22 10:40:42'),
(12, 'سوئیت آپارتمانی', 'apartment suite', '2026-02-21 12:42:17', '2026-02-22 10:40:05'),
(13, 'هاستل', 'hostel', '2026-02-21 13:31:20', '2026-02-22 10:39:51'),
(14, 'مجتمع اقامتی ساحلی', 'beach residential complex', '2026-02-21 13:31:21', '2026-02-22 10:39:33'),
(15, 'واحد اقامتی', 'residential unit', '2026-02-21 13:31:25', '2026-02-22 10:39:04'),
(16, 'پانسیون', 'pension', '2026-02-21 13:31:38', '2026-02-22 10:38:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accommodation_types`
--
ALTER TABLE `accommodation_types`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accommodation_types`
--
ALTER TABLE `accommodation_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
