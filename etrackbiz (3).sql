-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2026 at 07:08 PM
-- Server version: 8.0.33
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `etrackbiz`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `password` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `full_name`, `email`, `role`, `password`) VALUES
(5, 'test', 'test', 'test@gmail.com', 'admin', '$2y$10$Ok3RpRph82ogxEe3QLYVCeA3yM5DSQstfmD5IscdUgiqYwuC/w5LS'),
(6, 'Sachintha', 'Sachintha ETrack Biz Admin', 'admin@etrackbiz.com', 'super_admin', '$2y$10$nuADq.P6jdcATFV.dWJhwOmLW/nNrGevJOqTyrEWR57YSXORJmUZG');

-- --------------------------------------------------------

--
-- Table structure for table `admin_remember_tokens`
--

CREATE TABLE `admin_remember_tokens` (
  `token_id` int NOT NULL,
  `admin_id` int NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'Tharuka Kalansuriya', '0775651243', 'Welterfreedon Gardens, Ganegoda, Akmeemana', '2025-08-12 08:52:51', '2025-08-12 08:52:51'),
(2, 'test 01', '0112 332', 'temple road, colombo 07', '2025-08-12 09:31:24', '2025-08-12 09:31:24'),
(3, 'test', '12345', 'temple road, maharagama, colombo 11', '2025-08-12 09:34:22', '2025-08-12 09:34:22'),
(4, 'updated', '12345', 'updated', '2025-08-13 06:59:36', '2025-08-13 06:59:36'),
(5, 'tt', '11', 'tt', '2025-08-13 07:10:13', '2025-08-13 07:10:13'),
(6, 'aa', '22', 'aa', '2025-08-13 07:29:23', '2025-08-13 07:29:23'),
(7, 'dd', '55', 'dd', '2025-08-13 07:29:47', '2025-08-13 07:29:47'),
(8, 'Sachintha', '077 1234567', 'No 02, Temple Road, Colombo', '2025-09-15 08:35:16', '2025-09-15 08:35:16'),
(9, 'tk', '01123345678', 'dcdadad', '2025-10-01 06:42:53', '2025-10-01 06:42:53'),
(10, 'qq', '12121121212', 'eererewr', '2025-10-02 04:51:35', '2025-10-02 04:51:35'),
(11, 'test', '0771234567', 'test', '2025-12-29 06:05:19', '2025-12-29 06:05:19');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int NOT NULL,
  `due_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `service_discounts` text,
  `global_discount_type` varchar(20) DEFAULT NULL,
  `global_discount_value` decimal(10,2) DEFAULT NULL,
  `global_discount_name` varchar(255) DEFAULT NULL,
  `global_discount_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','Paid','Cancelled') DEFAULT 'Pending',
  `is_proforma` tinyint(1) DEFAULT '0',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `subtotal_amount` decimal(10,2) DEFAULT '0.00',
  `invoice_date` date DEFAULT (curdate())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`invoice_id`, `invoice_number`, `customer_id`, `due_date`, `total_amount`, `service_discounts`, `global_discount_type`, `global_discount_value`, `global_discount_name`, `global_discount_amount`, `status`, `is_proforma`, `notes`, `created_at`, `updated_at`, `subtotal_amount`, `invoice_date`) VALUES
(69, 'PRO-0001', 1, '2026-01-28', 2210.00, '[{\"service_index\":0,\"service_name\":\"coller T Shirt\",\"discount_name\":\"Discount for coller T Shirt\",\"discount_type\":\"fixed\",\"discount_value\":100,\"discount_amount\":100},{\"service_index\":1,\"service_name\":\"crop top\",\"discount_name\":\"Discount for crop top\",\"discount_type\":\"percentage\",\"discount_value\":10,\"discount_amount\":90}]', 'percentage', 0.00, 'Global Discount', 0.00, 'Pending', 1, NULL, '2025-12-29 08:01:53', '2025-12-29 08:01:53', 2400.00, '2025-12-29'),
(70, 'INV-0001', 1, '2026-01-28', 2210.00, '[{\"service_index\":0,\"service_name\":\"coller T Shirt\",\"discount_name\":\"Discount for coller T Shirt\",\"discount_type\":\"fixed\",\"discount_value\":100,\"discount_amount\":100},{\"service_index\":1,\"service_name\":\"crop top\",\"discount_name\":\"Discount for crop top\",\"discount_type\":\"percentage\",\"discount_value\":10,\"discount_amount\":90}]', 'percentage', 0.00, 'Global Discount', 0.00, 'Pending', 0, NULL, '2025-12-29 08:01:53', '2025-12-29 08:01:53', 2400.00, '2025-12-29'),
(71, 'PRO-0002', 1, '2026-01-28', 2400.00, NULL, 'percentage', 0.00, 'Global Discount', 0.00, 'Pending', 1, NULL, '2025-12-29 08:31:38', '2025-12-29 08:31:38', 2400.00, '2025-12-29'),
(72, 'INV-0002', 1, '2026-01-28', 2400.00, NULL, 'percentage', 0.00, 'Global Discount', 0.00, 'Pending', 0, NULL, '2025-12-29 08:31:38', '2025-12-29 08:31:38', 2400.00, '2025-12-29');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `item_id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `item_number` varchar(50) DEFAULT NULL,
  `service_name` varchar(255) NOT NULL,
  `quantity` decimal(8,2) NOT NULL DEFAULT '1.00',
  `rate` decimal(10,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`item_id`, `invoice_id`, `item_number`, `service_name`, `quantity`, `rate`, `amount`) VALUES
(177, 69, 'HK-010', 'coller T Shirt', 1.00, 1500.00, 1500.00),
(178, 69, 'HK-013', 'crop top', 1.00, 900.00, 900.00),
(179, 70, 'HK-010', 'coller T Shirt', 1.00, 1500.00, 1500.00),
(180, 70, 'HK-013', 'crop top', 1.00, 900.00, 900.00),
(181, 71, 'HK-010', 'coller T Shirt', 1.00, 1500.00, 1500.00),
(182, 71, 'HK-013', 'crop top', 1.00, 900.00, 900.00),
(183, 72, 'HK-010', 'coller T Shirt', 1.00, 1500.00, 1500.00),
(184, 72, 'HK-013', 'crop top', 1.00, 900.00, 900.00);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int NOT NULL,
  `item_number` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `default_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `item_number`, `name`, `default_price`, `created_at`, `updated_at`) VALUES
(10, 'HK-010', 'coller T Shirt', 1500.00, '2025-12-24 07:52:45', '2025-12-29 08:13:29'),
(11, 'HK-011', 'shorts', 750.00, '2025-12-24 07:52:56', '2025-12-29 08:13:29'),
(12, 'HK-012', 'denims', 2800.00, '2025-12-24 07:53:12', '2025-12-29 08:13:29'),
(13, 'HK-013', 'crop top', 900.00, '2025-12-24 07:53:22', '2025-12-29 08:13:29'),
(14, '1233', 'test', 1000.00, '2025-12-29 08:22:04', '2025-12-29 08:22:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_remember_tokens`
--
ALTER TABLE `admin_remember_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `unique_admin_token` (`admin_id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `fk_customer` (`customer_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `fk_invoice` (`invoice_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD UNIQUE KEY `unique_service_name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admin_remember_tokens`
--
ALTER TABLE `admin_remember_tokens`
  MODIFY `token_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=185;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_remember_tokens`
--
ALTER TABLE `admin_remember_tokens`
  ADD CONSTRAINT `admin_remember_tokens_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
