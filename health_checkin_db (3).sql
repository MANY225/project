-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 11, 2025 at 11:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `health_checkin_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `checkins`
--

CREATE TABLE `checkins` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `place` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checkins`
--

INSERT INTO `checkins` (`id`, `name`, `note`, `lat`, `lng`, `created_at`, `place`) VALUES
(1, 'เชียงใหม่', '', 0, 0, '2025-05-29 09:08:04', NULL),
(2, 'เชียงใหม่', '', 0, 0, '2025-05-29 09:09:52', NULL),
(3, 'user', '00', 0, 0, '2025-06-02 10:06:26', NULL),
(4, 'user', '', 0, 0, '2025-06-04 15:09:31', NULL),
(5, 'user', '', 0, 0, '2025-06-04 15:10:10', NULL),
(6, 'user', '', 0, 0, '2025-06-04 15:10:25', NULL),
(7, 'กรุงเทพ', '', 0, 0, '2025-06-04 15:13:46', NULL),
(8, 'user', '', 0, 0, '2025-06-04 15:24:37', NULL),
(9, 'user', 'ออกกำลังกาย', 0, 0, '2025-06-04 15:34:40', NULL),
(10, 'user', 'ดูลิง', 0, 0, '2025-06-04 15:39:38', 'ศาลพระกาฬ'),
(11, 'user', '', 13.688640209853004, 100.66341984624141, '2025-06-05 08:34:14', 'สวนหลวง ร.9 (Suan Luang Rama IX)'),
(12, 'comman', '', 14.802490090268, 100.61504499563, '2025-06-10 09:30:36', 'ศาลพระกาฬ');

-- --------------------------------------------------------

--
-- Table structure for table `places`
--

CREATE TABLE `places` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `places`
--

INSERT INTO `places` (`id`, `name`, `description`, `province`, `tags`, `lat`, `lng`, `image`) VALUES
(1, 'ศาลพระกาฬ', 'ลิงเยอะ', 'ลพบุรี', 'พักผ่อน', 14.802490090268, 100.61504499563, 'place_6840f87540b90_Lopbsanpkan0306a.jpg'),
(2, 'สวนหลวง ร.9 (Suan Luang Rama IX)', 'บริเวณเฉลิมพระเกียรติ ประกอบด้วยหอรัชมงคล และอุทยานมหาราช ภายในหอรัชมงคลจัดแสดงเรื่องราวเกี่ยวกับพระราชกรณียกิจ และเครื่องใช้ส่วนพระองค์ สวนพฤกษศาสตร์ เป็นวัตถุประสงค์สำคัญในการจัดสร้างสวนหลวง ร.9 แห่งนี้ เนื้อที่รวม 150 ไร่ มีการจัดพันธุ์ไม้หลักอนุกรมวิธานและนิเวศวิทยา และยังเป็นที่รวบรวมไม้พันธุ์ต่าง ๆ ของไทย รวมทั้งไม้ที่หายาก และสมุนไพรต่าง ๆ พร้อมทั้งสวนนานาชาติ เช่น สเปน ฝรั่งเศส อิตาลี ญี่ปุ่น และอังกฤษ เปิดให้เข้าชมตั้งแต่วันจันทร์-เสาร์ บริเวณนี้มีอาคารต่าง ๆ', 'กรุงเทพ', 'สมาธิ เดินเล่น ออกกำลังกาย', 13.688640209853, 100.66341984624, 'place_6840f8706bb16_009-20190305-091530.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `fullname` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `fullname`, `email`, `phone`) VALUES
(1, 'admin', '$2y$10$/iYNge6ZYKINC1OsHmkjAuhPB/UPnp5ygJifA9WvVSct9WS627Yoq', 'admin', NULL, NULL, NULL),
(2, 'user', '$2y$10$anYVff.z3aMEhq4dtmdCSul4zo21lF2IB1GZzHSR0SzWacryqMEvq', 'user', NULL, NULL, NULL),
(3, 'comman', '$2y$10$71zUKinhCIlKAjLjhVrHTufEduwCD4nwx8WwrAs94yQ82Xh1Dg7qW', 'user', 'ชัชวาล เมฆอยู่', 'inspot0926727150@gmail.com', '0613411730');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `checkins`
--
ALTER TABLE `checkins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `places`
--
ALTER TABLE `places`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `checkins`
--
ALTER TABLE `checkins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `places`
--
ALTER TABLE `places`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
