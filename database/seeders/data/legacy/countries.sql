-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 10.0.0.4
-- Generation Time: Sep 17, 2026 at 09:10 AM
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
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` bigint UNSIGNED NOT NULL,
  `fa_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `en_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iso2` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iso3` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `fa_name`, `en_name`, `iso2`, `iso3`, `created_at`, `updated_at`) VALUES
(1, 'ایران', 'Iran', 'IR', 'IRN', '2026-02-21 11:59:48', '2026-02-21 11:59:48'),
(2, 'عراق', 'Iran', 'IQ', 'IRQ', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(3, 'ترکیه', 'Iran', 'TR', 'TUR', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(4, 'امارات متحده عربی', 'Iran', 'AE', 'ARE', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(5, 'تایلند', 'Iran', 'TH', 'THA', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(6, 'گرجستان', 'Iran', 'GE', 'GEO', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(7, 'ارمنستان', 'Iran', 'AM', 'ARM', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(8, 'آذربایجان', 'Iran', 'AZ', 'AZE', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(9, 'عمان', 'Iran', 'OM', 'OMN', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(10, 'چین', 'China', 'CN', 'CHN', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(11, 'افغانستان', 'Iran', 'AF', 'AFG', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(12, 'روسیه', 'Russia', 'RU', 'RUS', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(13, 'سوریه', 'Syria', 'SY', 'SYR', '2026-02-21 11:59:51', '2026-02-21 11:59:51'),
(14, 'ازبکستان', 'Uzbekistan', 'UZ', 'UZB', '2026-02-21 11:59:52', '2026-02-21 11:59:52'),
(15, 'لبنان', 'Lebanon', 'LB', 'IRN', '2026-02-21 11:59:52', '2026-02-21 11:59:52'),
(16, 'بحرین', 'Bahrain', 'BA', 'BAH', '2026-02-21 11:59:52', '2026-02-21 11:59:52'),
(17, 'کویت', 'Iran', 'KW', 'KWT', '2026-02-21 11:59:52', '2026-02-21 11:59:52'),
(18, 'پاکستان', 'Pakestan', 'OP', 'IRN', '2026-02-21 11:59:52', '2026-02-21 11:59:52'),
(19, 'تاجیکستان', 'Iran', 'TJ', 'TJK', '2026-02-21 11:59:52', '2026-02-21 11:59:52'),
(20, 'تانزانیا', 'Iran', 'TZ', 'TZA', '2026-02-21 11:59:53', '2026-02-21 11:59:53'),
(21, 'مصر', 'Iran', 'EG', 'EGY', '2026-02-21 11:59:53', '2026-02-21 11:59:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
