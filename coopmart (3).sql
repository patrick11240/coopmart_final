-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 06, 2026 at 12:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `coopmart`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `full_name`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'admin@system.com', '$2y$10$FrVSoSSFNcYiaSjM734xeuUiLwIgreASDHidgBHUlzuefap3X.Mgu', '2025-11-24 09:21:33', '2025-11-24 09:25:16');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`cart_id`, `user_id`, `created_at`, `updated_at`) VALUES
(5, 2, '2025-09-14 02:31:22', '2025-09-14 02:31:22'),
(6, 1, '2025-09-17 10:14:36', '2025-09-17 10:14:36'),
(7, 36, '2025-11-17 10:15:45', '2025-11-17 10:15:45'),
(8, 24, '2025-11-17 12:29:53', '2025-11-17 12:29:53'),
(9, 42, '2025-11-17 06:55:54', '2025-11-17 06:55:54'),
(10, 47, '2025-11-20 06:38:24', '2025-11-20 06:38:24'),
(11, 49, '2025-11-20 16:39:33', '2025-11-20 16:39:33'),
(12, 2, '2025-11-18 02:25:00', '2025-11-18 02:25:00'),
(13, 1, '2025-11-19 06:40:00', '2025-11-19 06:40:00'),
(14, 24, '2025-11-20 03:15:00', '2025-11-20 03:15:00'),
(15, 36, '2025-11-21 08:10:00', '2025-11-21 08:10:00'),
(16, 47, '2025-11-22 01:25:00', '2025-11-22 01:25:00'),
(19, 254, '2025-12-04 02:51:03', '2025-12-04 02:51:03'),
(20, 43, '2026-01-03 04:03:54', '2026-01-03 04:03:54');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`cart_item_id`, `cart_id`, `product_id`, `quantity`, `added_at`) VALUES
(181, 6, 23, 1, '2025-10-14 15:28:47'),
(182, 6, 25, 2, '2025-10-14 15:28:47'),
(183, 6, 28, 1, '2025-10-14 15:28:48'),
(184, 6, 13, 2, '2025-10-14 15:28:48'),
(188, 6, 14, 1, '2025-10-27 07:29:36'),
(218, 10, 53, 1, '2025-11-20 06:38:24'),
(219, 10, 23, 1, '2025-11-20 06:38:27'),
(225, 5, 25, 1, '2025-11-24 04:11:42'),
(226, 5, 23, 1, '2025-11-24 04:11:44'),
(227, 5, 57, 1, '2025-11-24 04:11:46'),
(228, 12, 15, 2, '2025-11-18 02:25:30'),
(229, 12, 23, 3, '2025-11-18 02:26:00'),
(230, 12, 28, 2, '2025-11-18 02:26:30'),
(231, 13, 13, 1, '2025-11-19 06:41:00'),
(232, 13, 14, 1, '2025-11-19 06:41:30'),
(233, 13, 57, 1, '2025-11-19 06:42:00'),
(234, 14, 29, 2, '2025-11-20 03:16:00'),
(235, 14, 30, 1, '2025-11-20 03:16:30'),
(236, 14, 31, 1, '2025-11-20 03:17:00'),
(241, 16, 16, 2, '2025-11-22 01:26:00'),
(242, 16, 20, 2, '2025-11-22 01:26:30'),
(243, 16, 54, 1, '2025-11-22 01:27:00'),
(250, 7, 25, 1, '2026-01-03 02:44:17'),
(251, 7, 15, 1, '2026-01-03 02:44:19'),
(252, 7, 13, 1, '2026-01-03 02:44:20');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `created_at`) VALUES
(6, 'Fresh Meat / Livestock Products', '2025-09-14 02:30:51'),
(7, 'Fruits', '2025-09-14 03:51:05'),
(9, 'Hair Shampo', '2025-09-14 03:54:44'),
(10, 'Snacks', '2025-09-14 04:01:34'),
(11, 'Beverages', '2025-09-14 04:01:34'),
(12, 'Instant Noodles', '2025-09-14 04:01:34'),
(13, 'Canned Goods', '2025-09-14 04:01:34'),
(14, 'Baked Goods', '2025-09-14 04:01:34'),
(15, 'Dairy Products', '2025-09-14 04:01:34'),
(16, 'Frozen Foods', '2025-09-14 04:01:34'),
(17, 'Chocolates & Candies', '2025-09-14 04:01:34'),
(18, 'Chips', '2025-09-14 04:01:34'),
(19, 'Sodas', '2025-09-14 04:01:34'),
(20, 'Penoy', '2025-10-27 07:33:44');

-- --------------------------------------------------------

--
-- Table structure for table `model_predictions`
--

CREATE TABLE `model_predictions` (
  `prediction_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `prediction_date` date DEFAULT NULL,
  `model_type` enum('linear_regression','random_forest','xgboost') DEFAULT NULL,
  `predicted_probability` decimal(5,4) DEFAULT NULL,
  `predicted_label` tinyint(4) DEFAULT NULL,
  `actual_label` tinyint(4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `cart_discount_amount` decimal(10,2) DEFAULT 0.00,
  `retention_discount_amount` decimal(10,2) DEFAULT 0.00,
  `cart_discount` decimal(10,2) DEFAULT 0.00,
  `packing_fee` decimal(10,2) NOT NULL DEFAULT 20.00,
  `status` enum('pending_payment','paid','processing_purchased_product','ready_to_pick_the_purchased_product','completed','canceled') DEFAULT 'pending_payment',
  `receipt_image` varchar(255) DEFAULT NULL,
  `receipt_proof` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `spin_discount_percent` int(11) DEFAULT 0,
  `points_discount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `total_price`, `discount_amount`, `cart_discount_amount`, `retention_discount_amount`, `cart_discount`, `packing_fee`, `status`, `receipt_image`, `receipt_proof`, `created_at`, `spin_discount_percent`, `points_discount`) VALUES
(16, 2, 450.00, 45.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_16.jpg', 'proof_16.jpg', '2025-10-02 00:15:00', 15, 30.00),
(17, 4, 180.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_17.jpg', 'proof_17.jpg', '2025-10-05 05:30:00', 0, 0.00),
(18, 6, 320.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_18.jpg', 'proof_18.jpg', '2025-10-08 02:45:00', 10, 15.00),
(19, 8, 95.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'canceled', NULL, NULL, '2025-10-12 07:20:00', 0, 0.00),
(20, 10, 275.00, 20.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_20.jpg', 'proof_20.jpg', '2025-10-15 01:10:00', 8, 12.00),
(21, 11, 420.00, 40.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_21.jpg', 'proof_21.jpg', '2025-10-18 04:25:00', 12, 28.00),
(22, 13, 150.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_22.jpg', 'proof_22.jpg', '2025-10-21 23:50:00', 0, 0.00),
(23, 15, 380.00, 35.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_23.jpg', 'proof_23.jpg', '2025-10-25 06:40:00', 10, 25.00),
(24, 17, 220.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_24.jpg', 'proof_24.jpg', '2025-10-28 03:15:00', 8, 7.00),
(25, 1, 195.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_25.jpg', 'proof_25.jpg', '2025-11-03 01:30:00', 10, 5.00),
(26, 3, 510.00, 50.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_26.jpg', 'proof_26.jpg', '2025-11-07 06:55:00', 15, 35.00),
(27, 5, 120.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_27.jpg', 'proof_27.jpg', '2025-11-10 03:20:00', 0, 0.00),
(28, 7, 340.00, 30.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_28.jpg', 'proof_28.jpg', '2025-11-14 08:45:00', 10, 20.00),
(29, 9, 180.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_29.jpg', 'proof_29.jpg', '2025-11-18 02:30:00', 10, 5.00),
(30, 12, 290.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_30.jpg', 'proof_30.jpg', '2025-11-22 05:10:00', 10, 15.00),
(31, 14, 680.00, 65.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_31.jpg', 'proof_31.jpg', '2025-12-05 01:45:00', 15, 50.00),
(32, 16, 420.00, 40.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_32.jpg', 'proof_32.jpg', '2025-12-10 07:20:00', 12, 28.00),
(33, 19, 550.00, 50.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_33.jpg', 'proof_33.jpg', '2025-12-15 03:35:00', 15, 35.00),
(34, 21, 320.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_34.jpg', 'proof_34.jpg', '2025-12-20 06:50:00', 10, 15.00),
(35, 23, 450.00, 40.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_35.jpg', 'proof_35.jpg', '2025-12-23 02:15:00', 12, 28.00),
(36, 2, 190.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'processing_purchased_product', NULL, NULL, '2026-01-05 00:40:00', 10, 5.00),
(37, 4, 275.00, 20.00, 0.00, 0.00, 0.00, 20.00, 'ready_to_pick_the_purchased_product', NULL, NULL, '2026-01-08 04:25:00', 8, 12.00),
(38, 6, 180.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', NULL, NULL, '2026-01-12 08:10:00', 0, 0.00),
(39, 8, 420.00, 40.00, 0.00, 0.00, 0.00, 20.00, 'paid', 'receipt_39.jpg', 'proof_39.jpg', '2026-01-15 01:55:00', 12, 28.00),
(40, 10, 290.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_40.jpg', 'proof_40.jpg', '2026-01-18 06:30:00', 10, 15.00),
(41, 11, 150.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_41.jpg', 'proof_41.jpg', '2026-01-22 03:05:00', 0, 0.00),
(42, 13, 380.00, 35.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_42.jpg', 'proof_42.jpg', '2026-01-25 07:50:00', 10, 25.00),
(43, 15, 220.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'ready_to_pick_the_purchased_product', NULL, NULL, '2026-01-28 02:25:00', 8, 7.00),
(44, 17, 195.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'processing_purchased_product', NULL, NULL, '2026-01-30 05:40:00', 10, 5.00),
(45, 1, 510.00, 50.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_45.jpg', 'proof_45.jpg', '2026-02-02 01:15:00', 15, 35.00),
(46, 1, 120.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_46.jpg', 'proof_46.jpg', '2023-01-10 01:20:00', 0, 0.00),
(47, 3, 85.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_47.jpg', 'proof_47.jpg', '2023-01-15 06:35:00', 0, 0.00),
(48, 5, 200.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_48.jpg', 'proof_48.jpg', '2023-01-22 03:10:00', 0, 0.00),
(49, 7, 150.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_49.jpg', 'proof_49.jpg', '2023-02-05 08:45:00', 0, 0.00),
(50, 9, 95.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_50.jpg', 'proof_50.jpg', '2023-02-18 02:30:00', 0, 0.00),
(51, 12, 180.00, 10.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_51.jpg', 'proof_51.jpg', '2023-03-12 04:15:00', 5, 5.00),
(52, 14, 250.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_52.jpg', 'proof_52.jpg', '2023-03-24 23:50:00', 8, 7.00),
(53, 16, 320.00, 20.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_53.jpg', 'proof_53.jpg', '2023-04-08 06:20:00', 8, 12.00),
(54, 19, 190.00, 10.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_54.jpg', 'proof_54.jpg', '2023-04-20 03:05:00', 5, 5.00),
(55, 21, 275.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_55.jpg', 'proof_55.jpg', '2023-05-05 00:40:00', 8, 7.00),
(56, 23, 420.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_56.jpg', 'proof_56.jpg', '2023-06-15 05:25:00', 8, 17.00),
(57, 1, 180.00, 10.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_57.jpg', 'proof_57.jpg', '2023-06-28 02:50:00', 5, 5.00),
(58, 3, 350.00, 20.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_58.jpg', 'proof_58.jpg', '2023-07-12 07:35:00', 8, 12.00),
(59, 5, 220.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_59.jpg', 'proof_59.jpg', '2023-07-25 01:10:00', 8, 7.00),
(60, 7, 480.00, 30.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_60.jpg', 'proof_60.jpg', '2023-08-08 04:45:00', 8, 22.00),
(61, 9, 380.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_61.jpg', 'proof_61.jpg', '2023-09-18 00:20:00', 8, 17.00),
(62, 12, 520.00, 35.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_62.jpg', 'proof_62.jpg', '2023-10-05 06:55:00', 10, 25.00),
(63, 14, 290.00, 20.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_63.jpg', 'proof_63.jpg', '2023-10-22 03:30:00', 8, 12.00),
(64, 16, 650.00, 45.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_64.jpg', 'proof_64.jpg', '2023-11-10 00:15:00', 10, 35.00),
(65, 19, 420.00, 30.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_65.jpg', 'proof_65.jpg', '2023-11-28 06:40:00', 10, 20.00),
(66, 21, 580.00, 40.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_66.jpg', 'proof_66.jpg', '2023-12-15 03:25:00', 10, 30.00),
(67, 23, 720.00, 55.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_67.jpg', 'proof_67.jpg', '2023-12-23 08:50:00', 12, 43.00),
(68, 1, 195.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_68.jpg', 'proof_68.jpg', '2024-01-08 01:35:00', 8, 7.00),
(69, 3, 280.00, 20.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_69.jpg', 'proof_69.jpg', '2024-01-15 06:20:00', 8, 12.00),
(70, 5, 180.00, 10.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_70.jpg', 'proof_70.jpg', '2024-01-25 03:45:00', 5, 5.00),
(71, 7, 420.00, 30.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_71.jpg', 'proof_71.jpg', '2024-02-05 08:30:00', 10, 20.00),
(72, 9, 320.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_72.jpg', 'proof_72.jpg', '2024-02-18 02:15:00', 8, 17.00),
(73, 12, 250.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_73.jpg', 'proof_73.jpg', '2024-03-10 04:40:00', 8, 7.00),
(74, 14, 380.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_74.jpg', 'proof_74.jpg', '2024-03-21 23:25:00', 8, 17.00),
(75, 16, 190.00, 10.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_75.jpg', 'proof_75.jpg', '2024-04-05 06:50:00', 5, 5.00),
(76, 19, 520.00, 35.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_76.jpg', 'proof_76.jpg', '2024-04-18 03:35:00', 10, 25.00),
(77, 21, 290.00, 20.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_77.jpg', 'proof_77.jpg', '2024-05-03 00:10:00', 8, 12.00),
(78, 23, 650.00, 45.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_78.jpg', 'proof_78.jpg', '2024-06-12 05:55:00', 10, 35.00),
(79, 1, 420.00, 30.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_79.jpg', 'proof_79.jpg', '2024-06-25 02:20:00', 10, 20.00),
(80, 3, 580.00, 40.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_80.jpg', 'proof_80.jpg', '2024-07-08 07:45:00', 10, 30.00),
(81, 5, 350.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_81.jpg', 'proof_81.jpg', '2024-07-20 01:30:00', 8, 17.00),
(82, 7, 480.00, 35.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_82.jpg', 'proof_82.jpg', '2024-08-05 04:15:00', 10, 25.00),
(83, 9, 220.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_83.jpg', 'proof_83.jpg', '2024-08-17 23:50:00', 8, 7.00),
(84, 12, 380.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_84.jpg', 'proof_84.jpg', '2024-09-02 06:25:00', 8, 17.00),
(85, 14, 520.00, 35.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_85.jpg', 'proof_85.jpg', '2024-09-15 03:00:00', 10, 25.00),
(86, 16, 290.00, 20.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_86.jpg', 'proof_86.jpg', '2024-09-28 00:35:00', 8, 12.00),
(87, 19, 650.00, 45.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_87.jpg', 'proof_87.jpg', '2024-10-10 05:10:00', 10, 35.00),
(88, 21, 420.00, 30.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_88.jpg', 'proof_88.jpg', '2024-10-25 02:45:00', 10, 20.00),
(89, 23, 720.00, 55.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_89.jpg', 'proof_89.jpg', '2024-11-08 08:20:00', 12, 43.00),
(90, 1, 850.00, 70.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_90.jpg', 'proof_90.jpg', '2024-11-22 05:55:00', 15, 55.00),
(91, 3, 580.00, 45.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_91.jpg', 'proof_91.jpg', '2024-12-05 02:30:00', 12, 33.00),
(92, 5, 680.00, 55.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_92.jpg', 'proof_92.jpg', '2024-12-18 07:05:00', 12, 43.00),
(93, 7, 950.00, 85.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'receipt_93.jpg', 'proof_93.jpg', '2024-12-24 03:40:00', 15, 70.00),
(188, 2, 479.00, 51.00, 25.00, 0.00, 0.00, 20.00, 'pending_payment', 'uploads/receipts/receipt_68ef820c9a5ea6.10994625.png', NULL, '2025-10-15 10:14:20', 0, 0.00),
(189, 2, 237.80, 24.20, 15.00, 0.00, 0.00, 20.00, 'pending_payment', 'uploads/receipts/receipt_68ff73fe5c9752.53324763.jpg', NULL, '2025-10-27 12:30:38', 0, 0.00),
(190, 2, 47.00, 3.00, 0.00, 0.00, 0.00, 20.00, 'pending_payment', 'uploads/receipts/receipt_68ff7411cdac55.38560152.jpg', NULL, '2025-10-27 12:30:57', 0, 0.00),
(191, 36, 20.00, 0.00, 0.00, 5.40, 0.00, 20.00, 'pending_payment', 'uploads/receipts/receipt_691b095fa15351.26528112.jpg', NULL, '2025-11-17 11:39:11', 0, 21.60),
(192, 36, 275.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'pending_payment', 'uploads/receipts/receipt_691b09be4f75c2.71798473.jpg', NULL, '2025-11-17 11:40:46', 0, 25.00),
(193, 24, 136.00, 0.00, 0.00, 29.00, 0.00, 20.00, 'paid', 'uploads/receipts/receipt_691b15501288c6.64839553.jpg', NULL, '2025-11-17 12:30:08', 0, 0.00),
(194, 42, 35.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'pending_payment', 'uploads/receipts/receipt_691b37b1e8d8b9.87165996.jpg', NULL, '2025-11-17 06:56:49', 0, 0.00),
(195, 42, 35.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'pending_payment', 'uploads/receipts/receipt_691b38f93d03c4.07831360.jpg', NULL, '2025-11-17 07:02:17', 0, 0.00),
(196, 42, 35.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'pending_payment', 'uploads/receipts/receipt_691c5a4807e829.06007613.jpg', NULL, '2025-11-18 03:36:40', 0, 0.00),
(197, 2, 93.80, 8.20, 20.00, 0.00, 0.00, 20.00, 'pending_payment', NULL, NULL, '2025-11-19 18:04:32', 0, 0.00),
(198, 49, 245.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'pending_payment', NULL, NULL, '2025-11-20 16:41:01', 0, 15.00),
(199, 2, 185.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654321.jpg', 'proof_199.jpg', '2025-11-18 02:30:00', 10, 5.00),
(200, 1, 320.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654322.jpg', 'proof_200.jpg', '2025-11-19 06:45:00', 10, 15.00),
(201, 24, 275.00, 20.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654323.jpg', 'proof_201.jpg', '2025-11-20 03:20:00', 8, 12.00),
(202, 36, 420.00, 40.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654324.jpg', 'proof_202.jpg', '2025-11-21 08:15:00', 12, 28.00),
(203, 47, 150.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654325.jpg', 'proof_203.jpg', '2025-11-22 01:30:00', 0, 0.00),
(204, 50, 195.00, 10.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654326.jpg', 'proof_204.jpg', '2025-11-18 00:20:00', 5, 5.00),
(205, 51, 340.00, 30.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654327.jpg', 'proof_205.jpg', '2025-11-19 02:15:00', 10, 20.00),
(206, 52, 180.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654328.jpg', 'proof_206.jpg', '2025-11-19 23:30:00', 0, 0.00),
(207, 53, 225.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654329.jpg', 'proof_207.jpg', '2025-11-21 06:45:00', 8, 10.00),
(208, 54, 485.00, 45.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654330.jpg', 'proof_208.jpg', '2025-11-22 01:20:00', 12, 35.00),
(209, 55, 165.00, 5.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654331.jpg', 'proof_209.jpg', '2025-11-23 03:30:00', 3, 2.00),
(210, 81, 450.00, 40.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654332.jpg', 'proof_210.jpg', '2025-11-20 05:25:00', 10, 30.00),
(211, 56, 280.00, 20.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654333.jpg', 'proof_211.jpg', '2025-11-24 07:40:00', 8, 12.00),
(212, 57, 395.00, 35.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654334.jpg', 'proof_212.jpg', '2025-11-25 00:50:00', 10, 25.00),
(213, 58, 140.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654335.jpg', 'proof_213.jpg', '2025-11-26 04:15:00', 0, 0.00),
(214, 82, 210.00, 10.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654336.jpg', 'proof_214.jpg', '2025-11-21 08:20:00', 5, 5.00),
(215, 59, 520.00, 50.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654337.jpg', 'proof_215.jpg', '2025-11-27 02:30:00', 12, 40.00),
(216, 60, 310.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654338.jpg', 'proof_216.jpg', '2025-11-28 06:45:00', 10, 15.00),
(217, 61, 175.00, 5.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654339.jpg', 'proof_217.jpg', '2025-11-29 01:20:00', 3, 2.00),
(218, 83, 265.00, 18.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654340.jpg', 'proof_218.jpg', '2025-11-22 03:35:00', 8, 10.00),
(219, 62, 380.00, 30.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654341.jpg', 'proof_219.jpg', '2025-11-30 05:50:00', 10, 20.00),
(220, 63, 155.00, 5.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654342.jpg', 'proof_220.jpg', '2025-12-01 00:25:00', 3, 2.00),
(221, 64, 465.00, 42.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654343.jpg', 'proof_221.jpg', '2025-12-02 07:40:00', 12, 30.00),
(222, 84, 340.00, 28.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654344.jpg', 'proof_222.jpg', '2025-11-23 02:15:00', 10, 18.00),
(223, 65, 245.00, 15.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654345.jpg', 'proof_223.jpg', '2025-12-03 04:30:00', 8, 7.00),
(224, 66, 425.00, 38.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654346.jpg', 'proof_224.jpg', '2025-12-04 01:45:00', 10, 28.00),
(225, 67, 190.00, 8.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654347.jpg', 'proof_225.jpg', '2025-12-05 06:20:00', 5, 3.00),
(226, 85, 540.00, 52.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654348.jpg', 'proof_226.jpg', '2025-11-24 08:35:00', 12, 40.00),
(227, 68, 575.00, 55.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654349.jpg', 'proof_227.jpg', '2025-12-06 03:25:00', 12, 45.00),
(228, 69, 295.00, 22.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654350.jpg', 'proof_228.jpg', '2025-12-07 05:40:00', 8, 14.00),
(229, 70, 385.00, 32.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654351.jpg', 'proof_229.jpg', '2025-12-08 02:15:00', 10, 22.00),
(230, 86, 220.00, 12.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654352.jpg', 'proof_230.jpg', '2025-11-25 00:50:00', 6, 6.00),
(231, 71, 205.00, 10.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654353.jpg', 'proof_231.jpg', '2025-12-09 07:30:00', 5, 5.00),
(232, 72, 495.00, 47.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654354.jpg', 'proof_232.jpg', '2025-12-10 04:45:00', 12, 35.00),
(233, 73, 168.00, 7.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654355.jpg', 'proof_233.jpg', '2025-12-11 01:20:00', 5, 2.00),
(234, 87, 315.00, 25.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654356.jpg', 'proof_234.jpg', '2025-11-26 06:15:00', 10, 15.00),
(235, 74, 355.00, 28.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654357.jpg', 'proof_235.jpg', '2025-12-12 03:40:00', 10, 18.00),
(236, 75, 410.00, 35.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654358.jpg', 'proof_236.jpg', '2025-12-13 06:55:00', 10, 25.00),
(237, 76, 195.00, 8.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654359.jpg', 'proof_237.jpg', '2025-12-14 02:30:00', 5, 3.00),
(238, 88, 445.00, 40.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654360.jpg', 'proof_238.jpg', '2025-11-27 08:25:00', 10, 30.00),
(239, 77, 530.00, 50.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654361.jpg', 'proof_239.jpg', '2025-12-15 05:20:00', 12, 40.00),
(240, 78, 325.00, 26.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654362.jpg', 'proof_240.jpg', '2025-12-16 01:45:00', 10, 16.00),
(241, 79, 175.00, 6.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654363.jpg', 'proof_241.jpg', '2025-12-17 07:10:00', 3, 3.00),
(242, 89, 480.00, 45.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654364.jpg', 'proof_242.jpg', '2025-11-28 04:35:00', 12, 33.00),
(243, 80, 390.00, 32.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654365.jpg', 'proof_243.jpg', '2025-12-18 03:50:00', 10, 22.00),
(244, 90, 215.00, 11.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654366.jpg', 'proof_244.jpg', '2025-11-29 00:40:00', 6, 5.00),
(245, 91, 340.00, 28.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654367.jpg', 'proof_245.jpg', '2025-11-30 06:20:00', 10, 18.00),
(246, 92, 405.00, 36.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654368.jpg', 'proof_246.jpg', '2025-12-01 02:15:00', 10, 26.00),
(247, 93, 510.00, 48.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654369.jpg', 'proof_247.jpg', '2025-12-02 05:30:00', 12, 36.00),
(248, 94, 185.00, 8.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654370.jpg', 'proof_248.jpg', '2025-12-03 01:45:00', 5, 3.00),
(249, 95, 260.00, 18.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654371.jpg', 'proof_249.jpg', '2025-12-04 07:20:00', 8, 10.00),
(250, 96, 425.00, 38.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654372.jpg', 'proof_250.jpg', '2025-12-05 03:35:00', 10, 28.00),
(251, 97, 460.00, 42.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654373.jpg', 'proof_251.jpg', '2025-12-06 06:50:00', 12, 30.00),
(252, 98, 195.00, 9.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654374.jpg', 'proof_252.jpg', '2025-12-07 02:25:00', 5, 4.00),
(253, 99, 310.00, 24.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654375.jpg', 'proof_253.jpg', '2025-12-08 05:40:00', 8, 16.00),
(254, 100, 380.00, 32.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654376.jpg', 'proof_254.jpg', '2025-12-09 01:55:00', 10, 22.00),
(255, 101, 620.00, 60.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654377.jpg', 'proof_255.jpg', '2025-12-10 07:10:00', 12, 50.00),
(256, 102, 175.00, 7.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654378.jpg', 'proof_256.jpg', '2025-12-11 03:20:00', 5, 2.00),
(257, 103, 365.00, 30.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654379.jpg', 'proof_257.jpg', '2025-12-12 06:35:00', 10, 20.00),
(258, 104, 495.00, 45.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654380.jpg', 'proof_258.jpg', '2025-12-13 02:50:00', 12, 33.00),
(259, 105, 205.00, 10.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654381.jpg', 'proof_259.jpg', '2025-12-14 04:15:00', 6, 4.00),
(260, 106, 285.00, 22.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654382.jpg', 'proof_260.jpg', '2025-12-15 01:30:00', 8, 14.00),
(261, 107, 545.00, 52.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654383.jpg', 'proof_261.jpg', '2025-12-16 07:45:00', 12, 40.00),
(262, 108, 160.00, 5.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654384.jpg', 'proof_262.jpg', '2025-12-17 03:00:00', 3, 2.00),
(263, 109, 395.00, 34.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654385.jpg', 'proof_263.jpg', '2025-12-18 05:25:00', 10, 24.00),
(264, 110, 470.00, 43.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654386.jpg', 'proof_264.jpg', '2025-12-19 02:40:00', 12, 31.00),
(265, 111, 225.00, 13.00, 0.00, 0.00, 0.00, 20.00, 'completed', 'uploads/receipts/receipt_6987654387.jpg', 'proof_265.jpg', '2025-12-19 06:55:00', 6, 7.00),
(266, 45, 1280.75, 64.04, 25.62, 15.00, 38.42, 20.00, 'completed', 'uploads/receipts/receipt_266_20251219.jpg', 'proof_266.jpg', '2025-12-19 00:15:00', 5, 12.50),
(267, 89, 875.50, 43.78, 17.51, 0.00, 26.27, 20.00, 'paid', 'uploads/receipts/receipt_267_20251219.jpg', 'proof_267.jpg', '2025-12-19 01:45:00', 0, 25.00),
(268, 123, 542.25, 27.11, 10.85, 20.00, 16.27, 20.00, 'processing_purchased_product', 'uploads/receipts/receipt_268_20251219.jpg', 'proof_268.jpg', '2025-12-19 03:20:00', 3, 8.00),
(269, 67, 1890.00, 94.50, 37.80, 50.00, 56.70, 20.00, 'ready_to_pick_the_purchased_product', 'uploads/receipts/receipt_269_20251219.jpg', 'proof_269.jpg', '2025-12-19 05:05:00', 7, 35.00),
(270, 31, 325.80, 16.29, 6.52, 0.00, 9.77, 20.00, 'pending_payment', NULL, NULL, '2025-12-19 06:30:00', 0, 0.00),
(271, 156, 745.90, 37.30, 14.92, 25.00, 22.38, 20.00, 'completed', 'uploads/receipts/receipt_271_20251220.jpg', 'proof_271.jpg', '2025-12-20 01:10:00', 4, 15.00),
(272, 92, 1123.45, 56.17, 22.47, 0.00, 33.70, 20.00, 'canceled', NULL, NULL, '2025-12-20 02:55:00', 0, 0.00),
(273, 178, 458.30, 22.92, 9.17, 15.00, 13.75, 20.00, 'paid', 'uploads/receipts/receipt_273_20251220.jpg', 'proof_273.jpg', '2025-12-20 04:40:00', 2, 10.00),
(274, 45, 1689.75, 84.49, 33.80, 75.00, 50.69, 20.00, 'processing_purchased_product', 'uploads/receipts/receipt_274_20251220.jpg', 'proof_274.jpg', '2025-12-20 06:25:00', 8, 42.00),
(275, 112, 299.99, 15.00, 6.00, 0.00, 9.00, 20.00, 'pending_payment', NULL, NULL, '2025-12-20 08:15:00', 0, 5.00),
(276, 83, 987.60, 49.38, 19.75, 30.00, 29.63, 20.00, 'completed', 'uploads/receipts/receipt_276_20251221.jpg', 'proof_276.jpg', '2025-12-21 00:45:00', 6, 18.50),
(277, 134, 1250.25, 62.51, 25.01, 0.00, 37.51, 20.00, 'ready_to_pick_the_purchased_product', 'uploads/receipts/receipt_277_20251221.jpg', 'proof_277.jpg', '2025-12-21 02:30:00', 0, 30.00),
(278, 67, 423.80, 21.19, 8.48, 10.00, 12.71, 20.00, 'paid', 'uploads/receipts/receipt_278_20251221.jpg', 'proof_278.jpg', '2025-12-21 04:20:00', 3, 7.50),
(279, 189, 1756.40, 87.82, 35.13, 60.00, 52.69, 20.00, 'processing_purchased_product', 'uploads/receipts/receipt_279_20251221.jpg', 'proof_279.jpg', '2025-12-21 06:05:00', 9, 45.00),
(280, 56, 589.99, 29.50, 11.80, 0.00, 17.70, 20.00, 'canceled', NULL, NULL, '2025-12-21 07:50:00', 0, 12.00),
(281, 102, 834.75, 41.74, 16.70, 20.00, 25.04, 20.00, 'completed', 'uploads/receipts/receipt_281_20251222.jpg', 'proof_281.jpg', '2025-12-22 01:00:00', 5, 16.00),
(282, 145, 1499.99, 75.00, 30.00, 0.00, 45.00, 20.00, 'paid', 'uploads/receipts/receipt_282_20251222.jpg', 'proof_282.jpg', '2025-12-22 02:45:00', 0, 35.00),
(283, 78, 623.45, 31.17, 12.47, 25.00, 18.70, 20.00, 'ready_to_pick_the_purchased_product', 'uploads/receipts/receipt_283_20251222.jpg', 'proof_283.jpg', '2025-12-22 04:30:00', 4, 14.50),
(284, 23, 987.60, 49.38, 19.75, 0.00, 29.63, 20.00, 'pending_payment', NULL, NULL, '2025-12-22 06:15:00', 0, 22.00),
(285, 167, 275.80, 13.79, 5.52, 5.00, 8.27, 20.00, 'completed', 'uploads/receipts/receipt_285_20251222.jpg', 'proof_285.jpg', '2025-12-22 08:00:00', 2, 6.00),
(286, 91, 1120.25, 56.01, 22.41, 40.00, 33.61, 20.00, 'processing_purchased_product', 'uploads/receipts/receipt_286_20251223.jpg', 'proof_286.jpg', '2025-12-23 00:20:00', 7, 28.00),
(287, 132, 756.90, 37.85, 15.14, 0.00, 22.71, 20.00, 'paid', 'uploads/receipts/receipt_287_20251223.jpg', 'proof_287.jpg', '2025-12-23 02:10:00', 0, 15.00),
(288, 54, 1432.75, 71.64, 28.66, 50.00, 42.98, 20.00, 'completed', 'uploads/receipts/receipt_288_20251223.jpg', 'proof_288.jpg', '2025-12-23 03:55:00', 8, 36.00),
(289, 176, 498.60, 24.93, 9.97, 15.00, 14.96, 20.00, 'canceled', NULL, NULL, '2025-12-23 05:40:00', 3, 9.50),
(290, 88, 1899.99, 95.00, 38.00, 0.00, 57.00, 20.00, 'ready_to_pick_the_purchased_product', 'uploads/receipts/receipt_290_20251223.jpg', 'proof_290.jpg', '2025-12-23 07:25:00', 0, 50.00),
(291, 123, 654.80, 32.74, 13.10, 20.00, 19.64, 20.00, 'completed', 'uploads/receipts/receipt_291_20251224.jpg', 'proof_291.jpg', '2025-12-24 01:30:00', 5, 13.00),
(292, 67, 2450.50, 122.53, 49.01, 100.00, 73.52, 20.00, 'paid', 'uploads/receipts/receipt_292_20251224.jpg', 'proof_292.jpg', '2025-12-24 03:15:00', 10, 62.50),
(293, 145, 879.45, 43.97, 17.59, 0.00, 26.38, 20.00, 'processing_purchased_product', 'uploads/receipts/receipt_293_20251224.jpg', 'proof_293.jpg', '2025-12-24 05:00:00', 0, 22.00),
(294, 92, 599.99, 30.00, 12.00, 25.00, 18.00, 20.00, 'pending_payment', NULL, NULL, '2025-12-24 06:45:00', 4, 10.00),
(295, 178, 1345.75, 67.29, 26.92, 45.00, 40.37, 20.00, 'completed', 'uploads/receipts/receipt_295_20251224.jpg', 'proof_295.jpg', '2025-12-24 08:30:00', 7, 33.50),
(296, 31, 789.60, 39.48, 15.79, 30.00, 23.69, 20.00, 'paid', 'uploads/receipts/receipt_296_20251225.jpg', 'proof_296.jpg', '2025-12-25 02:00:00', 6, 20.00),
(297, 156, 432.25, 21.61, 8.65, 0.00, 12.97, 20.00, 'ready_to_pick_the_purchased_product', 'uploads/receipts/receipt_297_20251225.jpg', 'proof_297.jpg', '2025-12-25 03:45:00', 0, 11.00),
(298, 83, 956.80, 47.84, 19.14, 40.00, 28.70, 20.00, 'completed', 'uploads/receipts/receipt_298_20251225.jpg', 'proof_298.jpg', '2025-12-25 05:30:00', 8, 24.00),
(299, 134, 1678.90, 83.95, 33.58, 0.00, 50.37, 20.00, 'canceled', NULL, NULL, '2025-12-25 07:15:00', 0, 42.00),
(300, 67, 325.50, 16.28, 6.51, 10.00, 9.77, 20.00, 'pending_payment', NULL, NULL, '2025-12-25 09:00:00', 3, 8.00),
(301, 189, 1123.40, 56.17, 22.47, 50.00, 33.70, 20.00, 'completed', 'uploads/receipts/receipt_301_20251226.jpg', 'proof_301.jpg', '2025-12-26 01:15:00', 9, 28.00),
(302, 56, 745.99, 37.30, 14.92, 0.00, 22.38, 20.00, 'paid', 'uploads/receipts/receipt_302_20251226.jpg', 'proof_302.jpg', '2025-12-26 03:00:00', 0, 19.00),
(303, 102, 1890.25, 94.51, 37.80, 75.00, 56.70, 20.00, 'processing_purchased_product', 'uploads/receipts/receipt_303_20251227.jpg', 'proof_303.jpg', '2025-12-27 00:45:00', 10, 47.50),
(304, 145, 598.80, 29.94, 11.98, 25.00, 17.96, 20.00, 'ready_to_pick_the_purchased_product', 'uploads/receipts/receipt_304_20251227.jpg', 'proof_304.jpg', '2025-12-27 02:30:00', 5, 15.00),
(305, 78, 1345.60, 67.28, 26.91, 0.00, 40.37, 20.00, 'completed', 'uploads/receipts/receipt_305_20251227.jpg', 'proof_305.jpg', '2025-12-27 04:15:00', 0, 33.50),
(306, 23, 876.90, 43.85, 17.54, 30.00, 26.31, 20.00, 'paid', 'uploads/receipts/receipt_306_20251228.jpg', 'proof_306.jpg', '2025-12-28 01:30:00', 7, 22.00),
(307, 167, 432.25, 21.61, 8.65, 15.00, 12.97, 20.00, 'pending_payment', NULL, NULL, '2025-12-28 03:15:00', 3, 11.00),
(308, 91, 1654.75, 82.74, 33.10, 60.00, 49.64, 20.00, 'processing_purchased_product', 'uploads/receipts/receipt_308_20251228.jpg', 'proof_308.jpg', '2025-12-28 05:00:00', 8, 41.50),
(309, 132, 789.99, 39.50, 15.80, 0.00, 23.70, 20.00, 'canceled', NULL, NULL, '2025-12-28 06:45:00', 0, 20.00),
(310, 54, 1120.40, 56.02, 22.41, 40.00, 33.61, 20.00, 'completed', 'uploads/receipts/receipt_310_20251228.jpg', 'proof_310.jpg', '2025-12-28 08:30:00', 6, 28.00),
(311, 176, 598.75, 29.94, 11.98, 20.00, 17.96, 20.00, 'ready_to_pick_the_purchased_product', 'uploads/receipts/receipt_311_20251229.jpg', 'proof_311.jpg', '2025-12-29 02:00:00', 5, 15.00),
(312, 88, 2345.99, 117.30, 46.92, 100.00, 70.38, 20.00, 'paid', 'uploads/receipts/receipt_312_20251229.jpg', 'proof_312.jpg', '2025-12-29 03:45:00', 12, 58.50),
(313, 123, 876.50, 43.83, 17.53, 0.00, 26.30, 20.00, 'completed', 'uploads/receipts/receipt_313_20251230.jpg', 'proof_313.jpg', '2025-12-30 01:15:00', 0, 22.00),
(314, 67, 654.80, 32.74, 13.10, 25.00, 19.64, 20.00, 'processing_purchased_product', 'uploads/receipts/receipt_314_20251230.jpg', 'proof_314.jpg', '2025-12-30 03:00:00', 4, 16.50),
(315, 145, 1899.75, 94.99, 38.00, 80.00, 57.00, 20.00, 'pending_payment', NULL, NULL, '2025-12-31 15:45:00', 10, 47.50),
(317, 254, 147.00, 0.00, 0.00, 0.00, 0.00, 20.00, 'pending_payment', NULL, NULL, '2026-01-03 02:48:24', 0, 0.00),
(318, 254, 124.50, 0.00, 10.00, 0.00, 0.00, 20.00, 'pending_payment', NULL, NULL, '2026-01-03 03:52:21', 0, 27.50),
(319, 43, 41.60, 0.00, 0.00, 5.40, 0.00, 20.00, 'pending_payment', NULL, NULL, '2026-01-03 04:04:10', 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `was_discounted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`, `was_discounted`) VALUES
(46, 16, 13, 1, 230.00, 0),
(47, 16, 14, 1, 130.00, 0),
(48, 16, 15, 1, 60.00, 0),
(49, 17, 23, 4, 15.00, 0),
(50, 17, 25, 2, 12.00, 0),
(52, 18, 20, 2, 35.00, 0),
(54, 19, 36, 1, 65.00, 0),
(55, 19, 37, 1, 75.00, 0),
(56, 20, 19, 2, 55.00, 0),
(57, 20, 21, 3, 35.00, 0),
(58, 20, 46, 1, 65.00, 0),
(59, 21, 25, 8, 12.00, 0),
(60, 21, 23, 6, 15.00, 0),
(61, 21, 27, 4, 18.00, 0),
(62, 22, 28, 3, 15.00, 0),
(63, 22, 54, 2, 35.00, 0),
(64, 23, 29, 1, 85.00, 0),
(65, 23, 30, 2, 35.00, 0),
(66, 23, 31, 1, 180.00, 0),
(68, 24, 34, 2, 35.00, 0),
(70, 25, 16, 1, 45.00, 0),
(71, 25, 22, 2, 35.00, 0),
(72, 25, 47, 1, 55.00, 0),
(73, 26, 41, 1, 220.00, 0),
(74, 26, 42, 1, 180.00, 0),
(75, 26, 44, 1, 95.00, 0),
(76, 27, 26, 2, 25.00, 0),
(77, 27, 37, 1, 75.00, 0),
(78, 28, 32, 3, 45.00, 0),
(79, 28, 38, 1, 120.00, 0),
(80, 28, 40, 1, 95.00, 0),
(81, 29, 45, 1, 150.00, 0),
(82, 29, 48, 1, 120.00, 0),
(83, 30, 49, 2, 85.00, 0),
(84, 30, 53, 2, 40.00, 0),
(85, 30, 52, 1, 45.00, 0),
(86, 31, 13, 2, 230.00, 0),
(87, 31, 45, 1, 150.00, 0),
(88, 31, 46, 2, 65.00, 0),
(89, 31, 48, 1, 120.00, 0),
(90, 32, 41, 1, 220.00, 0),
(91, 32, 42, 1, 180.00, 0),
(93, 33, 18, 2, 120.00, 0),
(94, 33, 19, 2, 55.00, 0),
(95, 34, 25, 5, 12.00, 0),
(96, 34, 26, 3, 25.00, 0),
(97, 34, 27, 2, 18.00, 0),
(98, 35, 29, 2, 85.00, 0),
(99, 35, 30, 3, 35.00, 0),
(100, 35, 31, 1, 180.00, 0),
(102, 36, 34, 2, 35.00, 0),
(103, 36, 37, 1, 75.00, 0),
(104, 37, 16, 2, 45.00, 0),
(105, 37, 20, 3, 35.00, 0),
(106, 37, 46, 1, 65.00, 0),
(107, 38, 23, 4, 15.00, 0),
(108, 38, 25, 2, 12.00, 0),
(109, 39, 41, 1, 220.00, 0),
(110, 39, 42, 1, 180.00, 0),
(111, 40, 49, 1, 85.00, 0),
(112, 40, 53, 2, 40.00, 0),
(113, 40, 52, 2, 45.00, 0),
(114, 46, 23, 3, 15.00, 0),
(115, 46, 25, 2, 12.00, 0),
(117, 47, 37, 1, 75.00, 0),
(118, 48, 16, 2, 45.00, 0),
(119, 48, 20, 2, 35.00, 0),
(120, 49, 29, 1, 85.00, 0),
(121, 49, 30, 1, 35.00, 0),
(122, 50, 34, 1, 35.00, 0),
(125, 51, 19, 1, 55.00, 0),
(126, 52, 25, 3, 12.00, 0),
(127, 52, 26, 2, 25.00, 0),
(128, 53, 41, 1, 220.00, 0),
(129, 53, 42, 1, 180.00, 0),
(130, 54, 45, 1, 150.00, 0),
(131, 54, 46, 1, 65.00, 0),
(132, 55, 31, 1, 180.00, 0),
(133, 55, 32, 1, 45.00, 0),
(134, 56, 13, 1, 230.00, 0),
(135, 56, 14, 1, 130.00, 0),
(136, 57, 16, 2, 45.00, 0),
(137, 57, 18, 1, 120.00, 0),
(138, 58, 25, 5, 12.00, 0),
(139, 58, 23, 4, 15.00, 0),
(141, 59, 34, 2, 35.00, 0),
(142, 60, 41, 1, 220.00, 0),
(143, 60, 42, 1, 180.00, 0),
(144, 61, 29, 2, 85.00, 0),
(145, 61, 30, 2, 35.00, 0),
(146, 62, 45, 2, 150.00, 0),
(147, 62, 46, 2, 65.00, 0),
(149, 63, 19, 2, 55.00, 0),
(150, 64, 13, 2, 230.00, 0),
(151, 64, 14, 1, 130.00, 0),
(152, 65, 41, 1, 220.00, 0),
(153, 65, 42, 1, 180.00, 0),
(154, 66, 31, 2, 180.00, 0),
(155, 66, 32, 3, 45.00, 0),
(156, 67, 45, 3, 150.00, 0),
(157, 67, 46, 2, 65.00, 0),
(158, 68, 25, 4, 12.00, 0),
(159, 68, 23, 3, 15.00, 0),
(161, 69, 34, 2, 35.00, 0),
(162, 70, 16, 2, 45.00, 0),
(163, 70, 20, 2, 35.00, 0),
(164, 71, 29, 2, 85.00, 0),
(165, 71, 30, 3, 35.00, 0),
(167, 72, 19, 2, 55.00, 0),
(168, 73, 41, 1, 220.00, 0),
(169, 73, 44, 1, 95.00, 0),
(170, 74, 45, 1, 150.00, 0),
(171, 74, 48, 1, 120.00, 0),
(172, 75, 25, 3, 12.00, 0),
(173, 75, 26, 2, 25.00, 0),
(174, 76, 13, 1, 230.00, 0),
(175, 76, 14, 1, 130.00, 0),
(176, 77, 31, 1, 180.00, 0),
(177, 77, 32, 1, 45.00, 0),
(178, 78, 41, 2, 220.00, 0),
(179, 78, 42, 1, 180.00, 0),
(180, 79, 25, 6, 12.00, 0),
(181, 79, 23, 5, 15.00, 0),
(182, 80, 29, 3, 85.00, 0),
(183, 80, 30, 3, 35.00, 0),
(185, 81, 34, 3, 35.00, 0),
(186, 82, 45, 2, 150.00, 0),
(187, 82, 46, 2, 65.00, 0),
(188, 83, 16, 2, 45.00, 0),
(189, 83, 18, 1, 120.00, 0),
(190, 84, 41, 1, 220.00, 0),
(191, 84, 42, 1, 180.00, 0),
(192, 85, 13, 1, 230.00, 0),
(193, 85, 14, 1, 130.00, 0),
(195, 86, 19, 2, 55.00, 0),
(196, 87, 31, 2, 180.00, 0),
(197, 87, 32, 3, 45.00, 0),
(198, 88, 45, 2, 150.00, 0),
(199, 88, 46, 2, 65.00, 0),
(200, 89, 13, 2, 230.00, 0),
(201, 89, 14, 2, 130.00, 0),
(202, 90, 41, 3, 220.00, 0),
(203, 90, 42, 2, 180.00, 0),
(204, 91, 45, 3, 150.00, 0),
(205, 91, 48, 1, 120.00, 0),
(206, 92, 13, 2, 230.00, 0),
(207, 92, 14, 1, 130.00, 0),
(208, 93, 45, 4, 150.00, 0),
(209, 93, 46, 3, 65.00, 0),
(210, 93, 48, 2, 120.00, 0),
(211, 188, 15, 2, 55.00, 1),
(212, 188, 23, 2, 10.00, 1),
(213, 188, 14, 1, 125.00, 1),
(214, 188, 13, 1, 225.00, 1),
(215, 188, 54, 1, 30.00, 1),
(216, 189, 13, 1, 225.00, 1),
(217, 189, 25, 1, 7.00, 1),
(218, 189, 28, 1, 10.00, 1),
(219, 190, 28, 1, 15.00, 0),
(220, 190, 23, 1, 15.00, 0),
(221, 191, 25, 1, 12.00, 0),
(222, 191, 28, 1, 15.00, 0),
(223, 192, 15, 3, 60.00, 0),
(224, 192, 57, 1, 100.00, 0),
(225, 193, 14, 1, 130.00, 0),
(226, 193, 28, 1, 15.00, 0),
(227, 194, 23, 1, 15.00, 0),
(228, 195, 23, 1, 15.00, 0),
(229, 196, 23, 1, 15.00, 0),
(230, 197, 15, 1, 55.00, 1),
(231, 197, 23, 1, 10.00, 1),
(232, 197, 25, 1, 7.00, 1),
(233, 197, 28, 1, 10.00, 1),
(234, 198, 57, 1, 100.00, 0),
(235, 198, 55, 1, 35.00, 0),
(236, 198, 53, 1, 40.00, 0),
(237, 198, 36, 1, 65.00, 0),
(238, 199, 15, 2, 55.00, 1),
(239, 199, 23, 3, 10.00, 1),
(240, 199, 28, 2, 10.00, 1),
(241, 200, 13, 1, 225.00, 1),
(242, 200, 14, 1, 125.00, 1),
(243, 200, 57, 1, 100.00, 0),
(244, 201, 29, 2, 85.00, 0),
(245, 201, 30, 1, 35.00, 0),
(246, 201, 31, 1, 180.00, 0),
(247, 202, 25, 5, 12.00, 0),
(248, 202, 27, 3, 18.00, 0),
(249, 202, 28, 4, 15.00, 0),
(250, 202, 23, 6, 15.00, 0),
(251, 203, 16, 2, 45.00, 0),
(252, 203, 20, 2, 35.00, 0),
(253, 203, 54, 1, 35.00, 0),
(254, 199, 15, 2, 55.00, 1),
(255, 199, 23, 3, 10.00, 1),
(256, 199, 28, 2, 10.00, 1),
(257, 200, 13, 1, 225.00, 1),
(258, 200, 14, 1, 125.00, 1),
(259, 200, 57, 1, 100.00, 0),
(260, 201, 29, 2, 85.00, 0),
(261, 201, 30, 1, 35.00, 0),
(262, 201, 31, 1, 180.00, 0),
(263, 202, 25, 5, 12.00, 0),
(264, 202, 27, 3, 18.00, 0),
(265, 202, 28, 4, 15.00, 0),
(266, 202, 23, 6, 15.00, 0),
(267, 203, 16, 2, 45.00, 0),
(268, 203, 20, 2, 35.00, 0),
(269, 203, 54, 1, 35.00, 0),
(271, 317, 25, 1, 12.00, 0),
(272, 317, 28, 1, 15.00, 0),
(273, 317, 57, 1, 100.00, 0),
(274, 318, 14, 1, 125.00, 1),
(275, 318, 25, 1, 7.00, 1),
(276, 319, 25, 1, 12.00, 0),
(277, 319, 28, 1, 15.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `monthly_sold` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `price`, `image_path`, `category_id`, `created_at`, `monthly_sold`) VALUES
(13, 'Tiekn', 230.00, 'https://th.bing.com/th/id/OIP.jq1lq70igKAfOQOkN8UYiQHaFa?w=244&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 6, '2025-09-14 02:31:18', 5),
(14, 'Manggo', 130.00, 'https://th.bing.com/th/id/OIP.X8V71omvwb7QQRnmwZbAhQHaHa?w=193&h=193&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 7, '2025-09-14 03:52:40', 6),
(15, 'Beauty Care Shampoo', 60.00, 'https://vega.am/image/cache/catalog/Angel/household/405986-2000x1500.jpg', 9, '2025-09-14 03:55:03', 6),
(16, 'Lay\'s Classic Chips', 45.00, 'https://th.bing.com/th/id/OIP.8fJnkGtzwkmzoG2uIJjFaQHaFj?w=220&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 10, '2025-09-13 20:00:00', 25),
(18, 'Pringles Original', 120.00, 'https://th.bing.com/th/id/OIP.ZTXpyz4hJZv_Y0ow21IIjQHaHa?w=168&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 10, '2025-09-13 20:02:00', 12),
(19, 'Cheetos Crunchy', 55.00, 'https://th.bing.com/th/id/OIP.X8V71omvwb7QQRnmwZbAhQHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 10, '2025-09-13 20:03:00', 20),
(20, 'Coca-Cola 500ml', 35.00, 'https://th.bing.com/th/id/OIP.cpJPe6sdZvTSda1luQvyowHaLH?w=115&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 11, '2025-09-13 20:04:00', 45),
(21, 'Pepsi 500ml', 35.00, 'https://th.bing.com/th/id/OIP.8Xk7RaZVJhRMkLN_UX9SZQHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 11, '2025-09-13 20:05:00', 38),
(22, 'Sprite 500ml', 35.00, 'https://th.bing.com/th/id/OIP.aLGE6S5kBipZxIRULbwxSwAAAA?w=174&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 11, '2025-09-13 20:06:00', 30),
(23, 'Bottled Water 500ml', 15.00, 'https://th.bing.com/th/id/OIP.N934F5CunqzIwQ-krs2n0gHaHa?w=200&h=200&c=10&o=6&dpr=1.1&pid=genserp&rm=2', 11, '2025-09-13 20:07:00', 71),
(25, 'Lucky Me! Pancit Canton', 12.00, 'https://th.bing.com/th/id/OIP.Fh3dF08GQ0ddxE729n26VAHaF-?w=218&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 12, '2025-09-13 20:09:00', 62),
(26, 'Nissin Cup Noodles', 25.00, 'https://th.bing.com/th/id/OIP.ye_asawd6kQ8pljikFiHGwHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 12, '2025-09-13 20:10:00', 40),
(27, 'Maggi 2-Minute Noodles', 18.00, 'https://th.bing.com/th/id/OIP.CNLgzQhBdMlkQfhLfyEkjgHaHa?w=188&h=188&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 12, '2025-09-13 20:11:00', 35),
(28, 'Payless Xtra Big', 15.00, 'https://tse3.mm.bing.net/th/id/OIP.OyQRSRyo7DWP4H2pWQ99qgHaHa?pid=ImgDet&w=184&h=184&c=7&dpr=1.1&o=7&rm=3', 12, '2025-09-13 20:12:00', 55),
(29, 'Corned Beef 150g', 85.00, 'https://th.bing.com/th/id/OIP.7VTdctitUeBRMm0K9iWsYgHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 13, '2025-09-13 20:13:00', 22),
(30, 'Sardines in Tomato Sauce', 35.00, 'https://th.bing.com/th/id/OIP.RCNQ-lyAgzF3qggUFXMUvwHaHa?o=7rm=3&rs=1&pid=ImgDetMain&o=7&rm=3', 13, '2025-09-13 20:14:00', 28),
(31, 'Spam Classic 340g', 180.00, 'https://tse1.mm.bing.net/th/id/OIP.rkKrCCGDVm4X_s4o-ve11wHaHa?rs=1&pid=ImgDetMain&o=7&rm=3', 13, '2025-09-13 20:15:00', 15),
(32, 'Tuna Flakes in Oil', 45.00, 'https://cf.shopee.ph/file/4d968d8443c98035ad317a862059cce1', 13, '2025-09-13 20:16:00', 33),
(34, 'Ensaymada', 35.00, 'https://th.bing.com/th/id/OIP.k9A--0hbzPGrmoO_kd4oygHaEK?w=317&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 14, '2025-09-13 20:18:00', 25),
(36, 'Chocolate Cake Slice', 65.00, 'https://th.bing.com/th/id/OIP.E-gHTCwxMX_Kwk8e5Qj0lgHaHa?w=172&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 14, '2025-09-13 20:20:00', 13),
(37, 'Fresh Milk 1L', 75.00, 'https://th.bing.com/th/id/OIP.HwsUN1k3CZYz6SgkaOSFtAHaHa?w=196&h=196&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 15, '2025-09-13 20:21:00', 35),
(38, 'Cheese Slices 200g', 120.00, 'https://th.bing.com/th/id/OIP.etVr_q6VoRMAubKh3Nn8YwAAAA?w=343&h=187&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 15, '2025-09-13 20:22:00', 18),
(39, 'Greek Yogurt 150g', 85.00, 'https://th.bing.com/th/id/OIP.zbwwPx3m9EoyKYnY2fArPwHaHa?w=217&h=217&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 15, '2025-09-13 20:23:00', 22),
(40, 'ButterDuck 200g', 95.00, 'https://kitchenconvenienceph.com/cdn/shop/products/DAIRYCREMEBUTTERMILK200G_1024x1024@2x.jpg?v=1622724030', 15, '2025-09-13 20:24:00', 15),
(41, 'Frozen Chicken Wings 1kg', 220.00, 'https://tse1.mm.bing.net/th/id/OIP.JtOdA10CozM_NpNqactfiQHaHa?rs=1&pid=ImgDetMain&o=7&rm=3', 16, '2025-09-13 20:25:00', 12),
(42, 'Ice Cream Tub 1L', 180.00, 'https://th.bing.com/th/id/OIP.dIxUhwfjtOQYW-jdl-LV8QHaHa?w=181&h=181&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 16, '2025-09-13 20:26:00', 20),
(43, 'Frozen Fish Fillet 500g', 150.00, 'https://tse2.mm.bing.net/th/id/OIP.O6gWqletfUJKLq1wnu4SDQAAAA?pid=ImgDet&w=169&h=169&c=7&dpr=1.1&o=7&rm=3', 16, '2025-09-13 20:27:00', 8),
(44, 'Frozen Vegetables Mix 400g', 95.00, 'https://th.bing.com/th/id/OIP.N6JrFcZ3n_BoR0ug9N98pgHaHa?w=201&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 16, '2025-09-13 20:28:00', 14),
(45, 'Toblerone 100g', 150.00, 'https://th.bing.com/th/id/OIP.Q7B7jk9R1mrCYDOVbLP_IwHaHa?w=159&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 17, '2025-09-13 20:29:00', 16),
(46, 'Kit Kat 4-Finger', 65.00, 'https://th.bing.com/th/id/OIP.P6i47PnZEEwdMQsuO4k0QAHaEK?w=289&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 17, '2025-09-13 20:30:00', 24),
(47, 'M&M\'s Peanut 45g', 55.00, 'https://th.bing.com/th/id/OIP.9Ltxq4R4SJMijl6Aj0ADwgHaEK?w=289&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 17, '2025-09-13 20:31:00', 28),
(48, 'Hershey\'s Kisses 150g', 120.00, 'https://th.bing.com/th/id/OIP.CMFVqJjA-Pg4qOcCpNBxCAHaHZ?w=205&h=204&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 17, '2025-09-13 20:32:00', 21),
(49, 'Doritos Nacho Cheese', 85.00, 'https://th.bing.com/th/id/OIP.NTH5pBPEAVF3yeBO9yoiAAHaJY?w=142&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 18, '2025-09-13 20:33:00', 24),
(50, 'Ruffles Original', 75.00, 'https://th.bing.com/th/id/OIP.b2gjJ9_QLDxZ9njkbEZKxwHaHa?w=174&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 18, '2025-09-13 20:34:00', 19),
(51, 'Tortillos Chili Cheese', 65.00, 'https://tse2.mm.bing.net/th/id/OIP.-I67X7E_-qP2y7HqrKkidwAAAA?rs=1&pid=ImgDetMain&o=7&rm=3', 18, '2025-09-13 20:35:00', 16),
(52, 'Potato Corner Fries', 45.00, 'https://th.bing.com/th/id/OIP.NM9nklKUs4fF2HUo0rQw7gHaHa?w=173&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 18, '2025-09-13 20:36:00', 25),
(53, 'Mountain Dew 500ml', 40.00, 'https://th.bing.com/th/id/OIP.-zbDClE5xNau3uTFwuerpAHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 19, '2025-09-13 20:37:00', 33),
(54, 'Royal Tru-Orange 500ml', 35.00, 'https://th.bing.com/th/id/OIP.7JNzHSmzj7e4GenMCX_GmgHaHa?w=120&h=108&c=7&qlt=90&bgcl=d48f1e&r=0&o=6&dpr=1.1&pid=13.1', 19, '2025-09-13 20:38:00', 29),
(55, 'Sarsi 500ml', 35.00, 'https://th.bing.com/th/id/OIP.DOSiV9BYWlY39DeVy3wzNQHaRH?w=115&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 19, '2025-09-13 20:39:00', 23),
(56, '7UP 500ml', 35.00, 'https://th.bing.com/th/id/OIP.zi5wt0ZPTioEfYi_Ue-3JgHaNu?w=124&h=220&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 19, '2025-09-13 20:40:00', 24),
(57, 'Bearbrand', 100.00, 'https://filebroker-cdn.lazada.co.th/kf/S6c7f210a87d74f85bb3444932d54878el.jpg', 15, '2025-10-27 07:33:19', 3),
(58, 'Ground meat', 145.00, 'https://th.bing.com/th/id/OIP.GDA88Aq098NNAj5CXlXEOAHaEK?w=263&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 6, '2026-01-06 01:47:30', 0),
(59, 'Chicken Feet (1kg)', 100.00, 'https://th.bing.com/th/id/OIP.oB3Dv7cBV4YhLUV5anvRwgHaFj?w=252&h=189&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 6, '2026-01-06 01:48:22', 0),
(60, 'Lucky Me! Pancit Canton Sweet & Spicy (80g)', 15.00, 'https://th.bing.com/th/id/OIP.yd1xtArV8Uv9hyfJj-3OtQHaHa?w=192&h=192&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 12, '2026-01-06 01:51:10', 0),
(61, 'Jack ‘n Jill Piattos Spicy Cheese 85g', 47.00, 'https://th.bing.com/th/id/OIP.p4wXH5ZlGpC-knjhYf74LQAAAA?w=177&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 18, '2026-01-06 01:52:00', 0),
(62, 'Jack ‘n Jill Piattos Sour Cream & Onion 85g', 47.00, 'https://th.bing.com/th/id/OIP.B8J-aB2_cJlJ6vfMKj8UaAHaHa?w=193&h=193&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 18, '2026-01-06 01:52:26', 0),
(63, 'Cream O Choco / Vanilla 80g', 32.00, 'https://th.bing.com/th/id/OIP.ig1sozk53duUvfymBYavTQHaFg?w=250&h=186&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 10, '2026-01-06 01:52:48', 0),
(64, 'Presto Creams (Chocolate, Vanilla / Peanut Butter) 80g', 27.00, 'https://th.bing.com/th/id/OIP.cHe0Cjc70-H5NlqKkc9smgHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 10, '2026-01-06 01:53:27', 0),
(65, 'Kopiko Black 3-in-1 Coffee Singles 30g', 27.00, 'https://th.bing.com/th/id/OIP.vO_rBdxo13X03pnIBkNyAwHaHa?w=203&h=203&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 15, '2026-01-06 01:54:06', 0);

-- --------------------------------------------------------

--
-- Table structure for table `retention_offers`
--

CREATE TABLE `retention_offers` (
  `offer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `offer_type` enum('welcome','win_back','re_engagement','cart_recovery','loyalty_reward','membership_special','standard_retention') NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT NULL,
  `points_bonus` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('draft','sent','used','expired') DEFAULT 'sent',
  `sent_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `converted_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `opened_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retention_offers`
--

INSERT INTO `retention_offers` (`offer_id`, `user_id`, `offer_type`, `discount_percentage`, `points_bonus`, `message`, `status`, `sent_at`, `expires_at`, `created_by`, `created_at`, `converted_at`, `opened_at`, `opened_count`) VALUES
(1, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 08:08:01', '2025-11-24 16:08:01', 2, '2025-11-17 08:08:01', NULL, NULL, 0),
(2, 18, 'welcome', 20.00, 100, 'Welcome to CoopMart, Mark Snow! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-17 08:15:18', '2025-11-24 16:15:18', 2, '2025-11-17 08:15:18', NULL, NULL, 0),
(3, 35, 'welcome', 20.00, 100, 'Welcome to CoopMart, Amanda Taylor! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-17 08:39:25', '2025-11-24 16:39:25', 2, '2025-11-17 08:39:25', NULL, NULL, 0),
(4, 35, 'welcome', 20.00, 100, 'Welcome to CoopMart, Amanda Taylor! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-17 08:39:26', '2025-11-24 16:39:26', 2, '2025-11-17 08:39:26', NULL, NULL, 0),
(5, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 08:39:32', '2025-11-24 16:39:32', 2, '2025-11-17 08:39:32', NULL, NULL, 0),
(6, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 08:39:33', '2025-11-24 16:39:33', 2, '2025-11-17 08:39:33', NULL, NULL, 0),
(7, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 08:40:14', '2025-11-24 16:40:14', 2, '2025-11-17 08:40:14', NULL, NULL, 0),
(8, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 08:40:15', '2025-11-24 16:40:15', 2, '2025-11-17 08:40:15', NULL, NULL, 0),
(9, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 08:42:20', '2025-11-24 16:42:20', 2, '2025-11-17 08:42:20', NULL, NULL, 0),
(10, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 08:42:21', '2025-11-24 16:42:21', 2, '2025-11-17 08:42:21', NULL, NULL, 0),
(11, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 08:45:42', '2025-11-24 16:45:42', 2, '2025-11-17 08:45:42', NULL, NULL, 0),
(12, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 08:45:45', '2025-11-24 16:45:45', 2, '2025-11-17 08:45:45', NULL, NULL, 0),
(13, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 09:27:29', '2025-11-24 17:27:29', 2, '2025-11-17 09:27:29', NULL, NULL, 0),
(14, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 09:27:30', '2025-11-24 17:27:30', 2, '2025-11-17 09:27:30', NULL, NULL, 0),
(15, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 10:00:08', '2025-11-24 18:00:08', 2, '2025-11-17 10:00:08', NULL, '2025-11-17 03:00:28', 0),
(16, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 10:00:09', '2025-11-24 18:00:09', 2, '2025-11-17 10:00:09', NULL, '2025-11-17 03:00:28', 0),
(17, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 10:00:53', '2025-11-24 18:00:53', 2, '2025-11-17 10:00:53', NULL, '2025-11-17 03:01:10', 0),
(18, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'used', '2025-11-17 10:00:54', '2025-11-24 18:00:54', 2, '2025-11-17 10:00:54', NULL, '2025-11-17 03:01:10', 0),
(19, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 11:36:08', '2025-11-24 19:36:08', 2, '2025-11-17 11:36:08', '2025-11-17 11:39:11', NULL, 0),
(20, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'used', '2025-11-17 11:36:10', '2025-11-24 19:36:10', 2, '2025-11-17 11:36:10', NULL, NULL, 0),
(21, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'used', '2025-11-17 11:36:56', '2025-11-24 19:36:56', 2, '2025-11-17 11:36:56', NULL, NULL, 0),
(22, 36, 'welcome', 20.00, 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'used', '2025-11-17 11:36:57', '2025-11-24 19:36:57', 2, '2025-11-17 11:36:57', NULL, NULL, 0),
(23, 33, 'welcome', 20.00, 100, 'Welcome to CoopMart, Jennifer Lee! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-17 12:29:10', '2025-11-24 20:29:10', 2, '2025-11-17 12:29:10', NULL, NULL, 0),
(24, 33, 'welcome', 20.00, 100, 'Welcome to CoopMart, Jennifer Lee! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-17 12:29:10', '2025-11-24 20:29:10', 2, '2025-11-17 12:29:10', NULL, NULL, 0),
(25, 24, 'welcome', 20.00, 100, 'Welcome to CoopMart, Arnel Brucal! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 12:29:18', '2025-11-24 20:29:18', 2, '2025-11-17 12:29:18', '2025-11-17 12:30:08', NULL, 0),
(26, 24, 'welcome', 20.00, 100, 'Welcome to CoopMart, Arnel Brucal! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'used', '2025-11-17 12:29:19', '2025-11-24 20:29:19', 2, '2025-11-17 12:29:19', NULL, NULL, 0),
(27, 37, 'welcome', 20.00, 100, 'Welcome to CoopMart, Maria Santos! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-19 19:44:22', '2025-11-26 19:44:22', 2, '2025-11-19 19:44:22', NULL, NULL, 0),
(28, 37, 'welcome', 20.00, 100, 'Welcome to CoopMart, Maria Santos! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-19 19:44:24', '2025-11-26 19:44:24', 2, '2025-11-19 19:44:24', NULL, NULL, 0),
(29, 43, 'welcome', 20.00, 100, 'Welcome to CoopMart, Patrick Brcucal! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2026-01-03 04:02:40', '2026-01-10 12:02:40', 2, '2026-01-03 04:02:40', NULL, NULL, 0),
(30, 43, 'welcome', 20.00, 100, 'Welcome to CoopMart, Patrick Brcucal! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2026-01-03 04:02:40', '2026-01-10 12:02:40', 2, '2026-01-03 04:02:40', '2026-01-03 04:04:10', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `spin_discounts`
--

CREATE TABLE `spin_discounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `discount_percent` int(11) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spin_discounts`
--

INSERT INTO `spin_discounts` (`id`, `user_id`, `discount_percent`, `is_used`, `created_at`) VALUES
(1, 2, 20, 0, '2025-09-14 02:21:21'),
(2, 2, 20, 0, '2025-09-14 02:21:31'),
(3, 2, 10, 0, '2025-09-14 02:21:46'),
(4, 1, 10, 0, '2025-10-09 02:21:59'),
(5, 1, 20, 0, '2025-10-09 02:22:04'),
(6, 1, 20, 0, '2025-10-09 02:22:08'),
(7, 1, 10, 0, '2025-10-27 07:30:42'),
(8, 1, 50, 0, '2025-10-27 07:30:49'),
(9, 1, 50, 0, '2025-10-27 07:31:00'),
(10, 2, 20, 0, '2025-10-27 12:37:03'),
(11, 2, 30, 0, '2025-11-05 09:28:19'),
(12, 2, 50, 0, '2025-11-05 09:28:45'),
(13, 2, 30, 0, '2025-11-06 13:15:27'),
(14, 1, 20, 0, '2025-11-07 02:29:23'),
(15, 1, 50, 0, '2025-11-07 02:29:31'),
(16, 1, 30, 0, '2025-11-07 02:29:38'),
(17, 2, 20, 0, '2025-11-07 02:41:01'),
(18, 2, 10, 0, '2025-11-07 02:41:07'),
(19, 2, 50, 0, '2025-11-07 02:41:13'),
(20, 1, 30, 0, '2025-11-17 06:08:22'),
(21, 1, 20, 0, '2025-11-17 06:08:28'),
(22, 1, 10, 0, '2025-11-17 06:08:34'),
(23, 2, 20, 0, '2025-11-19 18:01:18'),
(24, 2, 30, 0, '2025-11-19 18:01:24'),
(25, 2, 20, 0, '2025-11-19 18:01:29'),
(26, 47, 50, 0, '2025-11-20 06:37:00'),
(27, 47, 30, 0, '2025-11-20 06:37:09'),
(28, 47, 50, 0, '2025-11-20 06:37:19'),
(29, 49, 30, 0, '2025-11-20 16:38:46'),
(30, 49, 10, 0, '2025-11-20 16:38:53'),
(31, 49, 20, 0, '2025-11-20 16:39:00'),
(32, 2, 10, 1, '2025-11-18 02:29:00'),
(33, 1, 10, 1, '2025-11-19 06:44:00'),
(34, 24, 8, 1, '2025-11-20 03:19:00'),
(35, 36, 12, 1, '2025-11-21 08:14:00'),
(42, 254, 50, 0, '2026-01-03 02:47:11'),
(43, 254, 50, 0, '2026-01-03 02:47:17'),
(44, 254, 10, 0, '2026-01-03 02:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `membership_type` enum('regular','sidc_member','non_member') DEFAULT 'regular',
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `login_streak` int(11) DEFAULT 0,
  `last_login_date` date DEFAULT NULL,
  `streak_updated_today` tinyint(1) DEFAULT 0,
  `points` int(11) DEFAULT 0,
  `daily_spins` int(11) DEFAULT 0,
  `last_spin_date` date DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `verified_at` datetime DEFAULT NULL,
  `verification_otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `otp_attempts` int(11) DEFAULT 0,
  `otp_requested_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `phone`, `gender`, `birth_date`, `address`, `city`, `province`, `zip_code`, `membership_type`, `role`, `created_at`, `updated_at`, `terms_accepted_at`, `login_streak`, `last_login_date`, `streak_updated_today`, `points`, `daily_spins`, `last_spin_date`, `email_verified`, `verified_at`, `verification_otp`, `otp_expiry`, `otp_attempts`, `otp_requested_at`) VALUES
(1, 'tam', 'tan@gmail.com', '$2y$10$bwqcMWVgslh7hypf12BBme/sJHtIXeo6C7j7if7zXKOkEsiJ1rxmu', '09977114098', 'male', '2003-06-11', 'Santa Veronica', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-09-13 05:55:52', '2025-12-18 16:29:57', NULL, 0, '2025-10-29', 0, 352, 3, '2025-11-17', 0, NULL, NULL, NULL, 0, NULL),
(2, 'Admin', 'admin@gmail.com', '$2y$10$GBhEk5a7bEawaqetr/U1L.Gd2Z.7.OSUZWHQhReELjYawq2HDHMIy', NULL, 'prefer_not_to_say', NULL, NULL, NULL, NULL, NULL, 'sidc_member', 'admin', '2025-09-13 06:01:19', '2026-01-06 01:37:57', NULL, 1, '2026-01-06', 1, 298, 3, '2025-11-19', 0, NULL, NULL, NULL, 0, NULL),
(3, 'Arvie', 'arvie@gmail.com', '$2y$10$Rtk9mAIqkxpW.O4hsOuQOOC/q1iq/JGLVhL6Gp/M0P6nSaARHFape', NULL, 'male', '2002-05-23', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-13 12:28:42', '2025-10-09 12:52:30', NULL, 2, '2025-09-14', 1, 80, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(4, 'John Doe', 'john.doe1@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000001', 'male', '1998-08-15', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 01:00:00', '2025-10-09 12:52:30', NULL, 0, NULL, 0, 10, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(5, 'Jane Smith', 'jane.smith@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000002', 'female', '2000-11-03', NULL, NULL, NULL, NULL, 'sidc_member', 'customer', '2025-09-14 01:05:00', '2025-10-09 12:52:31', NULL, 1, '2025-09-14', 1, 25, 1, '2025-09-14', 0, NULL, NULL, NULL, 0, NULL),
(6, 'Mike Tester', 'mike.tester@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000003', 'male', '2000-03-12', NULL, NULL, NULL, NULL, 'non_member', 'customer', '2025-09-14 01:10:00', '2025-10-09 12:52:30', NULL, 0, NULL, 0, 5, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(7, 'Anna Banana', 'anna.banana@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000004', 'female', '2002-02-14', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 01:15:00', '2025-10-09 12:52:31', NULL, 2, '2025-09-14', 1, 40, 2, '2025-09-14', 0, NULL, NULL, NULL, 0, NULL),
(8, 'Carlos Cruz', 'carlos.cruz@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000005', 'male', '1999-11-05', NULL, NULL, NULL, NULL, 'sidc_member', 'customer', '2025-09-14 01:20:00', '2025-10-09 12:52:30', NULL, 0, NULL, 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(9, 'Daisy Ray', 'daisy.ray@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000006', 'female', '2001-09-28', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 01:25:00', '2025-10-09 12:52:31', NULL, 3, '2025-09-14', 1, 60, 1, '2025-09-14', 0, NULL, NULL, NULL, 0, NULL),
(10, 'Eddie Brock', 'eddie.brock@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000007', 'male', '2001-02-18', NULL, NULL, NULL, NULL, 'non_member', 'customer', '2025-09-14 01:30:00', '2025-10-09 12:52:30', NULL, 1, '2025-09-14', 1, 15, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(11, 'Fiona Apple', 'fiona.apple@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000008', 'female', '1997-12-10', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 01:35:00', '2025-10-09 12:52:31', NULL, 0, NULL, 0, 20, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(12, 'George Lime', 'george.lime@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000009', 'male', '1997-09-09', NULL, NULL, NULL, NULL, 'sidc_member', 'customer', '2025-09-14 01:40:00', '2025-10-09 12:52:30', NULL, 1, '2025-09-14', 1, 30, 2, '2025-09-14', 0, NULL, NULL, NULL, 0, NULL),
(13, 'Holly Wood', 'holly.wood@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000010', 'female', '1999-07-26', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 01:45:00', '2025-10-09 12:52:31', NULL, 0, NULL, 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(14, 'Ian Bean', 'ian.bean@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000011', 'male', '2004-12-22', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 01:50:00', '2025-10-09 12:52:30', NULL, 4, '2025-09-14', 1, 70, 3, '2025-09-14', 0, NULL, NULL, NULL, 0, NULL),
(15, 'Jenny Lake', 'jenny.lake@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000012', 'female', '2001-04-05', NULL, NULL, NULL, NULL, 'non_member', 'customer', '2025-09-14 01:55:00', '2025-10-09 12:52:31', NULL, 0, NULL, 0, 5, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(16, 'Karl Marx', 'karl.marx@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000013', 'male', '1996-05-01', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 02:00:00', '2025-10-09 12:52:31', NULL, 1, '2025-09-14', 1, 10, 1, '2025-09-14', 0, NULL, NULL, NULL, 0, NULL),
(17, 'Lara Croft', 'lara.croft@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000014', 'female', '1998-10-21', NULL, NULL, NULL, NULL, 'sidc_member', 'customer', '2025-09-14 02:05:00', '2025-10-09 12:52:31', NULL, 2, '2025-09-14', 1, 50, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(18, 'Mark Snow', 'mark.snow@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000015', 'male', '2002-07-29', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 02:10:00', '2025-10-09 12:52:31', NULL, 0, NULL, 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(19, 'Nina Park', 'nina.park@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000016', 'female', '2003-01-09', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 02:15:00', '2025-10-09 12:52:31', NULL, 1, '2025-09-14', 1, 35, 1, '2025-09-14', 0, NULL, NULL, NULL, 0, NULL),
(20, 'Oscar Wild', 'oscar.wild@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000017', 'male', '1999-01-20', NULL, NULL, NULL, NULL, 'non_member', 'customer', '2025-09-14 02:20:00', '2025-10-09 12:52:31', NULL, 0, NULL, 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(21, 'Penny Wise', 'penny.wise@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000018', 'female', '2002-03-17', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 02:25:00', '2025-10-09 12:52:31', NULL, 2, '2025-09-14', 1, 45, 2, '2025-09-14', 0, NULL, NULL, NULL, 0, NULL),
(22, 'Quinn Fox', 'quinn.fox@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000019', 'male', '2003-10-11', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 02:30:00', '2025-10-09 12:52:31', NULL, 0, NULL, 0, 5, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(23, 'Rita Ora', 'rita.ora@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000020', 'female', '2000-06-02', NULL, NULL, NULL, NULL, 'sidc_member', 'customer', '2025-09-14 02:35:00', '2025-10-13 00:40:04', NULL, 0, '2025-09-14', 1, 0, 1, '2025-09-14', 0, NULL, NULL, NULL, 0, NULL),
(24, 'Arnel Brucal', 'arnel@gmail.com', '$2y$10$2B9M2UW1TbLUbHScshT7weYbgKCQ9U5vcZXR.mWxvAqp/OAJ00Ihy', NULL, 'male', '1980-03-17', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-30 12:07:12', '2026-01-03 02:38:25', NULL, 1, '2026-01-03', 0, 227, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(25, 'tanny', 'tanny@gmail.com', '$2y$10$wrt0XMGLQEy3a35OoPmtTekvBlWWJBmAQ8jUUFz.fP1is46js8rIa', NULL, 'female', '2004-08-13', NULL, NULL, NULL, NULL, 'regular', 'admin', '2025-09-30 12:28:56', '2025-10-09 12:52:31', NULL, 0, NULL, 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(26, 'John Smith', 'john.smith@email.com', 'password123', '555-0101', 'male', '1990-05-15', '123 Main St', 'Toronto', 'Ontario', 'M5V 2T6', 'regular', 'customer', '2023-06-01 00:00:00', '2023-11-15 06:30:00', NULL, 0, '2023-11-15', 0, 150, 1, '2023-11-15', 0, NULL, NULL, NULL, 0, NULL),
(27, 'Sarah Johnson', 'sarah.j@email.com', 'sarahpass456', '555-0102', 'female', '1988-08-22', '456 Oak Ave', 'Vancouver', 'British Columbia', 'V6B 4Y8', 'sidc_member', 'customer', '2023-07-10 02:20:00', '2023-11-10 08:45:00', NULL, 0, '2023-11-10', 0, 275, 1, '2023-11-10', 0, NULL, NULL, NULL, 0, NULL),
(28, 'Michael Chen', 'michael.chen@email.com', 'mike789pass', '555-0103', 'male', '1995-03-30', '789 King St', 'Calgary', 'Alberta', 'T2G 0B3', 'non_member', 'customer', '2023-08-05 05:15:00', '2023-12-15 02:20:00', NULL, 0, '2023-12-15', 0, 80, 1, '2023-12-15', 0, NULL, NULL, NULL, 0, NULL),
(29, 'Emily Davis', 'emily.davis@email.com', 'emily2024!', '555-0104', 'female', '1992-12-10', '321 Pine Rd', 'Montreal', 'Quebec', 'H3A 0G4', 'regular', 'customer', '2023-09-11 23:45:00', '2024-01-14 10:30:00', NULL, 5, '2024-01-14', 1, 420, 1, '2024-01-14', 0, NULL, NULL, NULL, 0, NULL),
(30, 'Robert Wilson', 'robert.w@email.com', 'bobwilson99', '555-0105', 'male', '1985-07-18', '654 Elm St', 'Ottawa', 'Ontario', 'K1P 5J7', 'sidc_member', 'customer', '2023-10-20 04:10:00', '2024-01-13 01:15:00', NULL, 3, '2024-01-13', 1, 190, 1, '2024-01-13', 0, NULL, NULL, NULL, 0, NULL),
(31, 'Lisa Anderson', 'lisa.anderson@email.com', 'lisaPass321', '555-0106', 'female', '1993-04-12', '789 Maple Dr', 'Edmonton', 'Alberta', 'T5J 2R7', 'sidc_member', 'customer', '2022-08-15 01:30:00', '2023-05-10 06:20:00', NULL, 0, '2023-05-10', 0, 850, 0, '2023-05-10', 0, NULL, NULL, NULL, 0, NULL),
(32, 'David Martinez', 'david.m@email.com', 'davidm888', '555-0107', 'male', '1987-11-03', '234 Birch Ln', 'Winnipeg', 'Manitoba', 'R3C 1S5', 'sidc_member', 'customer', '2022-05-10 07:45:00', '2023-04-15 02:10:00', NULL, 0, '2023-04-15', 0, 1200, 0, '2023-04-15', 0, NULL, NULL, NULL, 0, NULL),
(33, 'Jennifer Lee', 'jennifer.lee@email.com', 'jenLee2024!', '555-0108', 'female', '1991-09-17', '567 Cedar St', 'Halifax', 'Nova Scotia', 'B3J 2K9', 'non_member', 'customer', '2023-01-01 01:15:00', '2023-01-01 01:15:00', NULL, 0, NULL, 0, 45, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(34, 'Kevin Brown', 'kevin.brown@email.com', 'kbrownSecure1', '555-0109', 'male', '1982-12-25', '890 Spruce Ave', 'Quebec City', 'Quebec', 'G1R 2H7', 'sidc_member', 'customer', '2021-03-22 04:20:00', '2023-01-05 01:30:00', NULL, 0, '2023-01-05', 0, 1500, 0, '2023-01-05', 0, NULL, NULL, NULL, 0, NULL),
(35, 'Amanda Taylor', 'amanda.t@email.com', 'amandaT99#', '555-0110', 'female', '1994-06-08', '123 Willow Way', 'Victoria', 'British Columbia', 'V8W 1J6', 'sidc_member', 'customer', '2022-11-30 03:50:00', '2023-03-01 09:25:00', NULL, 0, '2023-03-01', 0, 950, 0, '2023-03-01', 0, NULL, NULL, NULL, 0, NULL),
(36, 'BOSS1', 'BOSS1@GMAIL.COM', '$2y$10$Z7uPH/1ew5WLe8FmGSu0oO7Rw0eqq3xmfZt9hrDVpTMqJuPVTBuGG', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-10-29 08:01:23', '2025-12-18 16:29:57', NULL, 1, '2025-11-24', 0, 42, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(37, 'Maria Santos', 'maria.santos@example.com', '$2y$10$WfR.2x1E3lf1M6MylsH3OeZgH3U3G6B4m1pufrSJi8mXZkbnZb0hy', '09171234567', 'female', '1995-06-15', '123 Mabini Street', 'San Pablo City', 'Laguna', '4000', 'regular', 'customer', '2025-06-05 15:00:00', NULL, NULL, 0, '2025-06-06', 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(38, 'High Risk Customer', 'highrisk.customer@example.com', '$2y$10$ChurnRisk99!hashedpassword123456789', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-06 11:57:18', NULL, NULL, 0, '2025-09-07', 0, 0, 0, '2025-09-22', 0, NULL, NULL, NULL, 0, NULL),
(40, 'Inactive User', 'inactive@test.com', '$2y$10$bwqcMWVgslh7hypf12BBme/sJHtIXeo6C7j7if7zXKOkEsiJ1rxmu', '09171111111', 'male', '1995-01-01', '123 Inactive Street', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2023-12-31 16:00:00', '2023-12-31 16:00:00', NULL, 0, '2024-01-01', 0, 0, 0, '2024-01-01', 0, NULL, NULL, NULL, 0, NULL),
(41, 'bsin', 'bsin@gmail.com', '$2y$10$NOpsHXU6dUig3VGRr2kkd.03LBIypICk8opXylUSW1D.UbhnjMR/i', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-07 03:46:30', NULL, NULL, 0, NULL, 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(42, 'Ariel Arvie Reyes', 'bembangtheory1234@gmail.com', '$2y$10$DSZnPHIv.mA5IfaGpmR7junJ5eoVWtQqTGIjjORIX/scBqEImKK9G', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-17 06:53:13', '2025-11-18 02:40:06', NULL, 1, '2025-11-18', 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(43, 'Patrick Brcucal', 'patty@gmail.com', '$2y$10$oRg2uCaCBT6AdLjwEwZKNO0blF1XhSTLO9VajfRKYqoTV.O5Ntzwq', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-17 21:33:29', '2026-01-03 04:04:10', NULL, 1, '2026-01-03', 0, 100, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(44, 'Dante Aguilar', 'dantejr9812@gmail.com', '$2y$10$fDX1/13z60PjrbHFmHzb2eDkjNBmQB0IbH2j4iav2aecaosD.Is56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-19 01:47:15', '2025-11-19 01:50:46', NULL, 1, '2025-11-19', 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(45, 'Nicko C. Albes', 'nickoalbes@gmail.com', '$2y$10$M9ZLXjzEW6133akI36kyIu34xoOEo22y8pPEChxL5pg5D9SMcsxki', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-19 18:49:02', NULL, NULL, 0, NULL, 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(46, 'Nicko C. Albes', 'nicko@gmail.com', '$2y$10$iUDhOvO7175ZEeKjqqutQu/dP6JFSwxsNIcS3/dW9JDwRdj4j8te6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-19 18:53:09', '2025-11-19 19:15:02', NULL, 1, '2025-11-19', 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(47, 'Chresianne Mae Zabala', 'chresianne987@gmail.com', '$2y$10$HHTND8ZL0WwMbsp99aWKYu/ucWIbG/cO5RHya.dqURcc1RkXiZA.G', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-20 06:36:27', '2025-12-18 16:29:57', NULL, 1, '2025-11-20', 0, 145, 3, '2025-11-20', 0, NULL, NULL, NULL, 0, NULL),
(48, 'hambri', 'hambriniltan@gmail.com', '$2y$10$yOdJg8QIut4jS0JEvGRG2O/lgpD1BtepAwwyuIFLxq7/I1kKysMzC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-20 06:37:01', '2025-11-20 06:45:02', NULL, 1, '2025-11-20', 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(49, 'Hannah Perona', 'hanzzelllp8@gmail.com', '$2y$10$nKj1MjiYKMbRlBVCwh0n5eu6knWvCJU4fqKa9MtaLjWvOA3Ni4H1.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-20 16:37:55', '2025-11-20 16:41:01', NULL, 0, NULL, 0, 0, 3, '2025-11-20', 0, NULL, NULL, NULL, 0, NULL),
(50, 'Sofia Mendoza', 'sofia.mendoza@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234568', 'female', '1996-03-14', '234 Rizal Ave', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-11-18 00:15:23', NULL, NULL, 0, NULL, 0, 45, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(51, 'Isabella Cruz', 'isabella.cruz@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234569', 'female', '1998-07-22', '456 Luna St', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-11-19 06:22:41', '2025-11-20 02:30:15', NULL, 2, '2025-11-20', 1, 180, 2, '2025-11-20', 0, NULL, NULL, NULL, 0, NULL),
(52, 'Miguel Reyes', 'miguel.reyes@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234570', 'male', '1994-05-18', '789 Del Pilar St', 'Biñan', 'Laguna', '4024', 'regular', 'customer', '2025-11-20 01:45:12', NULL, NULL, 0, NULL, 0, 0, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(53, 'Camila Santos', 'camila.santos@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234571', 'female', '2000-11-30', '321 Bonifacio Ave', 'Calamba', 'Laguna', '4027', 'non_member', 'customer', '2025-11-21 08:33:50', '2025-11-22 00:10:22', NULL, 1, '2025-11-22', 1, 95, 1, '2025-11-22', 0, NULL, NULL, NULL, 0, NULL),
(54, 'Valentina Garcia', 'valentina.garcia@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234572', 'female', '1997-08-25', '654 Quezon Blvd', 'Santa Rosa', 'Laguna', '4026', 'sidc_member', 'customer', '2025-11-22 03:20:35', '2025-11-23 07:45:18', NULL, 3, '2025-11-23', 1, 340, 3, '2025-11-23', 0, NULL, NULL, NULL, 0, NULL),
(55, 'Lucas Hernandez', 'lucas.hernandez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234573', 'male', '1999-02-14', '987 Aguinaldo St', 'Los Baños', 'Laguna', '4030', 'regular', 'customer', '2025-11-22 23:55:28', NULL, NULL, 0, NULL, 0, 20, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(56, 'Emma Rodriguez', 'emma.rodriguez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234574', 'female', '2001-04-19', '147 Mabini St', 'Cabuyao', 'Laguna', '4025', 'regular', 'customer', '2025-11-24 05:42:16', '2025-11-25 01:20:44', NULL, 1, '2025-11-25', 1, 125, 1, '2025-11-25', 0, NULL, NULL, NULL, 0, NULL),
(57, 'Mia Torres', 'mia.torres@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234575', 'female', '1995-09-07', '258 Burgos Ave', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-11-25 02:18:52', '2025-11-26 06:33:29', NULL, 2, '2025-11-26', 1, 265, 2, '2025-11-26', 0, NULL, NULL, NULL, 0, NULL),
(58, 'Diego Martinez', 'diego.martinez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234576', 'male', '1993-12-03', '369 Roxas St', 'Calamba', 'Laguna', '4027', 'non_member', 'customer', '2025-11-26 00:25:33', NULL, NULL, 0, NULL, 0, 15, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(59, 'Luna Castillo', 'luna.castillo@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234577', 'female', '2002-06-11', '741 Recto Ave', 'Biñan', 'Laguna', '4024', 'regular', 'customer', '2025-11-27 07:50:47', '2025-11-28 03:15:38', NULL, 4, '2025-11-28', 1, 420, 3, '2025-11-28', 0, NULL, NULL, NULL, 0, NULL),
(60, 'Sofia Flores', 'sofia.flores@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234578', 'female', '1998-01-28', '852 Gomez St', 'Santa Rosa', 'Laguna', '4026', 'sidc_member', 'customer', '2025-11-28 04:37:21', '2025-11-29 08:42:55', NULL, 1, '2025-11-29', 1, 150, 1, '2025-11-29', 0, NULL, NULL, NULL, 0, NULL),
(61, 'Mateo Ramos', 'mateo.ramos@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234579', 'male', '1996-10-16', '963 Luna St', 'Los Baños', 'Laguna', '4030', 'regular', 'customer', '2025-11-29 01:14:39', NULL, NULL, 0, NULL, 0, 75, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(62, 'Aria Morales', 'aria.morales@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234580', 'female', '2000-03-22', '159 Jacinto Ave', 'Cabuyao', 'Laguna', '4025', 'regular', 'customer', '2025-11-30 06:28:54', '2025-12-01 02:55:17', NULL, 2, '2025-12-01', 1, 220, 2, '2025-12-01', 0, NULL, NULL, NULL, 0, NULL),
(63, 'Elena Perez', 'elena.perez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234581', 'female', '1997-07-09', '357 Santos St', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-12-01 03:45:28', NULL, NULL, 0, NULL, 0, 35, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(64, 'Gabriel Lopez', 'gabriel.lopez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234582', 'male', '1994-11-14', '468 Magsaysay Blvd', 'Calamba', 'Laguna', '4027', 'sidc_member', 'customer', '2025-12-02 00:52:16', '2025-12-03 05:20:42', NULL, 3, '2025-12-03', 1, 385, 3, '2025-12-03', 0, NULL, NULL, NULL, 0, NULL),
(65, 'Victoria Diaz', 'victoria.diaz@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234583', 'female', '1999-05-26', '579 Laurel Ave', 'Biñan', 'Laguna', '4024', 'regular', 'customer', '2025-12-03 08:38:45', '2025-12-04 01:47:29', NULL, 1, '2025-12-04', 1, 105, 1, '2025-12-04', 0, NULL, NULL, NULL, 0, NULL),
(66, 'Olivia Navarro', 'olivia.navarro@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234584', 'female', '2001-08-31', '680 Osmeña St', 'Santa Rosa', 'Laguna', '4026', 'sidc_member', 'customer', '2025-12-04 05:15:32', '2025-12-05 07:28:51', NULL, 2, '2025-12-05', 1, 290, 2, '2025-12-05', 0, NULL, NULL, NULL, 0, NULL),
(67, 'Sebastian Rivera', 'sebastian.rivera@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234585', 'male', '1995-12-19', '791 Aquino Ave', 'Los Baños', 'Laguna', '4030', 'regular', 'customer', '2025-12-05 02:22:18', NULL, NULL, 0, NULL, 0, 50, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(68, 'Amelia Santiago', 'amelia.santiago@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234586', 'female', '1998-04-04', '802 Escoda St', 'Cabuyao', 'Laguna', '4025', 'non_member', 'customer', '2025-12-05 23:48:55', '2025-12-07 04:35:23', NULL, 4, '2025-12-07', 1, 460, 3, '2025-12-07', 0, NULL, NULL, NULL, 0, NULL),
(69, 'Chloe Gutierrez', 'chloe.gutierrez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234587', 'female', '2000-09-17', '913 Tandang Sora Ave', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-07 06:56:41', '2025-12-08 00:14:37', NULL, 1, '2025-12-08', 1, 135, 1, '2025-12-08', 0, NULL, NULL, NULL, 0, NULL),
(70, 'Adrian Mendez', 'adrian.mendez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234588', 'male', '1997-02-23', '124 Malvar St', 'Calamba', 'Laguna', '4027', 'sidc_member', 'customer', '2025-12-08 03:33:29', '2025-12-09 08:45:52', NULL, 2, '2025-12-09', 1, 245, 2, '2025-12-09', 0, NULL, NULL, NULL, 0, NULL),
(71, 'Harper Cruz', 'harper.cruz@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234589', 'female', '1996-06-08', '235 Hidalgo Ave', 'Biñan', 'Laguna', '4024', 'regular', 'customer', '2025-12-09 01:18:44', NULL, NULL, 0, NULL, 0, 85, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(72, 'Ava Fernandez', 'ava.fernandez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234590', 'female', '2002-01-15', '346 Abad Santos St', 'Santa Rosa', 'Laguna', '4026', 'non_member', 'customer', '2025-12-10 07:42:37', '2025-12-11 02:28:15', NULL, 3, '2025-12-11', 1, 365, 3, '2025-12-11', 0, NULL, NULL, NULL, 0, NULL),
(73, 'Ethan Aguilar', 'ethan.aguilar@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234591', 'male', '1994-07-29', '457 Evangelista Ave', 'Los Baños', 'Laguna', '4030', 'regular', 'customer', '2025-12-11 04:55:21', NULL, NULL, 0, NULL, 0, 30, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(74, 'Zoe Valdez', 'zoe.valdez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234592', 'female', '1999-11-02', '568 Matias St', 'Cabuyao', 'Laguna', '4025', 'sidc_member', 'customer', '2025-12-12 00:27:53', '2025-12-13 06:39:28', NULL, 1, '2025-12-13', 1, 170, 1, '2025-12-13', 0, NULL, NULL, NULL, 0, NULL),
(75, 'Lily Ortiz', 'lily.ortiz@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234593', 'female', '2001-03-18', '679 Tirona Blvd', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-13 08:14:46', '2025-12-14 03:52:34', NULL, 2, '2025-12-14', 1, 210, 2, '2025-12-14', 0, NULL, NULL, NULL, 0, NULL),
(76, 'Noah Jimenez', 'noah.jimenez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234594', 'male', '1995-08-24', '780 Soriano Ave', 'Calamba', 'Laguna', '4027', 'non_member', 'customer', '2025-12-14 05:38:19', NULL, NULL, 0, NULL, 0, 60, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(77, 'Ella Velasco', 'ella.velasco@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234595', 'female', '1998-12-06', '891 Faura St', 'Biñan', 'Laguna', '4024', 'regular', 'customer', '2025-12-15 02:45:32', '2025-12-16 01:17:45', NULL, 4, '2025-12-16', 1, 440, 3, '2025-12-16', 0, NULL, NULL, NULL, 0, NULL),
(78, 'Grace Miranda', 'grace.miranda@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234596', 'female', '2000-05-12', '102 Tolentino Ave', 'Santa Rosa', 'Laguna', '4026', 'sidc_member', 'customer', '2025-12-15 23:23:58', '2025-12-17 07:31:22', NULL, 1, '2025-12-17', 1, 155, 1, '2025-12-17', 0, NULL, NULL, NULL, 0, NULL),
(79, 'Liam Estrada', 'liam.estrada@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234597', 'male', '1993-09-28', '213 Heneral Luna St', 'Los Baños', 'Laguna', '4030', 'regular', 'customer', '2025-12-17 06:51:27', NULL, NULL, 0, NULL, 0, 25, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(80, 'Scarlett Ponce', 'scarlett.ponce@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234598', 'female', '1997-01-20', '324 Bonifacio St', 'Cabuyao', 'Laguna', '4025', 'non_member', 'customer', '2025-12-18 03:29:43', '2025-12-19 00:42:16', NULL, 2, '2025-12-19', 1, 235, 2, '2025-12-19', 0, NULL, NULL, NULL, 0, NULL),
(81, 'Maya Pascual', 'maya.pascual@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234599', 'female', '2001-06-14', '435 Quirino Ave', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-11-19 01:35:28', '2025-11-20 05:48:51', NULL, 3, '2025-11-20', 1, 320, 3, '2025-11-20', 0, NULL, NULL, NULL, 0, NULL),
(82, 'Oliver Bautista', 'oliver.bautista@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234600', 'male', '1996-10-27', '546 Mabuhay St', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-11-20 07:18:34', NULL, NULL, 0, NULL, 0, 70, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(83, 'Stella Vargas', 'stella.vargas@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234601', 'female', '1999-03-09', '657 Kalaw Ave', 'Biñan', 'Laguna', '4024', 'regular', 'customer', '2025-11-21 04:44:17', '2025-11-22 08:22:39', NULL, 1, '2025-11-22', 1, 115, 1, '2025-11-22', 0, NULL, NULL, NULL, 0, NULL),
(84, 'Aurora Del Rosario', 'aurora.delrosario@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234602', 'female', '2002-08-15', '768 Maceda St', 'Santa Rosa', 'Laguna', '4026', 'non_member', 'customer', '2025-11-22 00:56:42', '2025-11-23 03:35:28', NULL, 2, '2025-11-23', 1, 195, 2, '2025-11-23', 0, NULL, NULL, NULL, 0, NULL),
(85, 'William Reyes', 'william.reyes@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234603', 'male', '1994-12-21', '879 Legarda Ave', 'Los Baños', 'Laguna', '4030', 'sidc_member', 'customer', '2025-11-23 08:32:55', '2025-11-24 01:48:13', NULL, 4, '2025-11-24', 1, 410, 3, '2025-11-24', 0, NULL, NULL, NULL, 0, NULL),
(86, 'Violet Santos', 'violet.santos@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234604', 'female', '1998-04-30', '980 Balagtas St', 'Cabuyao', 'Laguna', '4025', 'regular', 'customer', '2025-11-24 05:27:38', NULL, NULL, 0, NULL, 0, 90, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(87, 'James Salazar', 'james.salazar@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234605', 'male', '1995-07-05', '191 Palma St', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-11-25 02:15:21', '2025-11-26 06:58:47', NULL, 1, '2025-11-26', 1, 140, 1, '2025-11-26', 0, NULL, NULL, NULL, 0, NULL),
(88, 'Ruby Gonzales', 'ruby.gonzales@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234606', 'female', '2000-11-11', '202 Mapa Ave', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-11-25 23:42:59', '2025-11-27 04:26:34', NULL, 2, '2025-11-27', 1, 275, 2, '2025-11-27', 0, NULL, NULL, NULL, 0, NULL),
(89, 'Penelope Cruz', 'penelope.cruz2@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234607', 'female', '1997-02-17', '313 Francisco St', 'Biñan', 'Laguna', '4024', 'sidc_member', 'customer', '2025-11-27 06:53:24', '2025-11-28 02:19:56', NULL, 3, '2025-11-28', 1, 355, 3, '2025-11-28', 0, NULL, NULL, NULL, 0, NULL),
(90, 'Isla Ramos', 'isla.ramos@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234608', 'female', '2001-05-23', '424 Herrera Ave', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-11-28 03:38:47', NULL, NULL, 0, NULL, 0, 55, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(91, 'Benjamin Torres', 'benjamin.torres@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234609', 'male', '1996-09-08', '535 Buendia Blvd', 'Los Baños', 'Laguna', '4030', 'non_member', 'customer', '2025-11-29 00:24:13', '2025-11-30 07:47:29', NULL, 1, '2025-11-30', 1, 165, 1, '2025-11-30', 0, NULL, NULL, NULL, 0, NULL),
(92, 'Hazel Moreno', 'hazel.moreno@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234610', 'female', '1999-01-26', '646 Ortigas St', 'Cabuyao', 'Laguna', '4025', 'regular', 'customer', '2025-11-30 08:51:35', '2025-12-01 01:33:48', NULL, 2, '2025-12-01', 1, 225, 2, '2025-12-01', 0, NULL, NULL, NULL, 0, NULL),
(93, 'Charlotte Villa', 'charlotte.villa@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234611', 'female', '2002-07-03', '757 Roxas Ave', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-01 05:19:52', '2025-12-02 03:45:27', NULL, 4, '2025-12-02', 1, 395, 3, '2025-12-02', 0, NULL, NULL, NULL, 0, NULL),
(94, 'Henry Castro', 'henry.castro@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234612', 'male', '1994-11-18', '868 Taft St', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-12-02 02:37:26', NULL, NULL, 0, NULL, 0, 40, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(95, 'Ivy Lopez', 'ivy.lopez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234613', 'female', '1998-03-25', '979 Espana Blvd', 'Biñan', 'Laguna', '4024', 'non_member', 'customer', '2025-12-02 23:58:41', '2025-12-04 06:22:15', NULL, 1, '2025-12-04', 1, 120, 1, '2025-12-04', 0, NULL, NULL, NULL, 0, NULL),
(96, 'Alice Mercado', 'alice.mercado@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234614', 'female', '2000-08-12', '180 Claro Recto Ave', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-12-04 07:46:33', '2025-12-05 00:51:49', NULL, 2, '2025-12-05', 1, 260, 2, '2025-12-05', 0, NULL, NULL, NULL, 0, NULL),
(97, 'Elijah Domingo', 'elijah.domingo@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234615', 'male', '1995-12-29', '291 Ayala Ave', 'Los Baños', 'Laguna', '4030', 'sidc_member', 'customer', '2025-12-05 04:33:28', '2025-12-06 08:18:52', NULL, 3, '2025-12-06', 1, 330, 3, '2025-12-06', 0, NULL, NULL, NULL, 0, NULL),
(98, 'Nora Bernal', 'nora.bernal@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234616', 'female', '1997-05-16', '302 Shaw Blvd', 'Cabuyao', 'Laguna', '4025', 'regular', 'customer', '2025-12-06 01:21:54', NULL, NULL, 0, NULL, 0, 80, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(99, 'Evelyn Cortez', 'evelyn.cortez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234617', 'female', '2001-09-22', '413 Aurora Blvd', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-12-07 08:48:37', '2025-12-08 05:25:19', NULL, 1, '2025-12-08', 1, 145, 1, '2025-12-08', 0, NULL, NULL, NULL, 0, NULL),
(100, 'Jackson Alvarez', 'jackson.alvarez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234618', 'male', '1996-01-08', '524 Marcos Highway', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-12-08 06:55:43', '2025-12-09 02:38:26', NULL, 2, '2025-12-09', 1, 215, 2, '2025-12-09', 0, NULL, NULL, NULL, 0, NULL),
(101, 'Roberto Manalansan', 'roberto.manalansan@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234619', 'male', '1980-05-14', '101 Maharlika Highway', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-09 01:15:22', '2025-12-10 06:30:18', NULL, 5, '2025-12-10', 1, 520, 3, '2025-12-10', 0, NULL, NULL, NULL, 0, NULL),
(102, 'Maricel Dela Paz', 'maricel.delapaz@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234620', 'female', '1981-08-23', '202 San Roque Street', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-10 03:45:33', NULL, NULL, 0, NULL, 0, 65, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(103, 'Jonathan Villanueva', 'jonathan.villanueva@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234621', 'male', '1979-03-17', '303 San Jose Village', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-12-11 06:20:45', '2025-12-12 02:15:28', NULL, 2, '2025-12-12', 1, 190, 2, '2025-12-12', 0, NULL, NULL, NULL, 0, NULL),
(104, 'Melissa Alcantara', 'melissa.alcantara@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234622', 'female', '1982-11-08', '404 Quezon Avenue Ext', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-12 00:30:16', '2025-12-13 08:45:39', NULL, 3, '2025-12-13', 1, 380, 3, '2025-12-13', 0, NULL, NULL, NULL, 0, NULL),
(105, 'Dennis Bautista', 'dennis.bautista@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234623', 'male', '1980-12-30', '505 M.L. Quezon Street', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-13 02:55:27', NULL, NULL, 0, NULL, 0, 45, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(106, 'Jennifer Tan', 'jennifer.tan@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234624', 'female', '1983-06-19', '606 Burgos Extension', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-14 05:40:38', '2025-12-15 01:25:47', NULL, 1, '2025-12-15', 1, 130, 1, '2025-12-15', 0, NULL, NULL, NULL, 0, NULL),
(107, 'Antonio Marquez', 'antonio.marquez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234625', 'male', '1979-09-12', '707 Luna Subdivision', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-15 07:20:52', '2025-12-16 03:35:29', NULL, 4, '2025-12-16', 1, 450, 3, '2025-12-16', 0, NULL, NULL, NULL, 0, NULL),
(108, 'Catherine Lim', 'catherine.lim@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234626', 'female', '1984-02-25', '808 Bonifacio Avenue', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-12-16 01:10:44', NULL, NULL, 0, NULL, 0, 25, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(109, 'Fernando Navarro', 'fernando.navarro@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234627', 'male', '1981-07-03', '909 Mabini Homes', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-17 04:35:18', '2025-12-18 06:20:55', NULL, 2, '2025-12-18', 1, 240, 2, '2025-12-18', 0, NULL, NULL, NULL, 0, NULL),
(110, 'Grace Evangelista', 'grace.evangelista@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234628', 'female', '1980-04-15', '1010 Gomezville Subd', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-18 00:45:32', '2025-12-19 02:30:41', NULL, 3, '2025-12-19', 1, 370, 3, '2025-12-19', 0, NULL, NULL, NULL, 0, NULL),
(111, 'Ricardo Samonte', 'ricardo.samonte@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234629', 'male', '1978-10-28', '1111 Del Pilar Village', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-19 06:15:47', NULL, NULL, 0, NULL, 0, 85, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(112, 'Monica De Leon', 'monica.deleon@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234630', 'female', '1985-01-14', '1212 Roxas Subdivision', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-20 02:25:39', '2025-12-21 00:45:26', NULL, 1, '2025-12-21', 1, 140, 1, '2025-12-21', 0, NULL, NULL, NULL, 0, NULL),
(113, 'Eduardo Cortez', 'eduardo.cortez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234631', 'male', '1979-11-22', '1313 Aguinaldo Residences', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-12-21 08:30:28', '2025-12-22 04:15:33', NULL, 5, '2025-12-22', 1, 510, 3, '2025-12-22', 0, NULL, NULL, NULL, 0, NULL),
(114, 'Patricia Gonzaga', 'patricia.gonzaga@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234632', 'female', '1982-03-08', '1414 Laurel Gardens', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-22 01:40:55', '2025-12-23 07:25:48', NULL, 2, '2025-12-23', 1, 220, 2, '2025-12-23', 0, NULL, NULL, NULL, 0, NULL),
(115, 'Carlos Macaraeg', 'carlos.macaraeg@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234633', 'male', '1980-06-29', '1515 Legarda Compound', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-23 03:55:17', NULL, NULL, 0, NULL, 0, 60, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(116, 'Susan Paglinawan', 'susan.paglinawan@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234634', 'female', '1983-09-05', '1616 Soriano Heights', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-24 05:20:44', '2025-12-25 01:10:37', NULL, 3, '2025-12-25', 1, 360, 3, '2025-12-25', 0, NULL, NULL, NULL, 0, NULL),
(117, 'Manuel Cordero', 'manuel.cordero@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234635', 'male', '1978-12-18', '1717 Taft Avenue Subd', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-25 07:45:29', '2025-12-26 03:30:22', NULL, 1, '2025-12-26', 1, 150, 1, '2025-12-26', 0, NULL, NULL, NULL, 0, NULL),
(118, 'Elizabeth Rosales', 'elizabeth.rosales@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234636', 'female', '1984-05-07', '1818 Recto Avenue Ext', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-12-26 02:35:18', NULL, NULL, 0, NULL, 0, 35, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(119, 'Jose Magtanggol', 'jose.magtanggol@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234637', 'male', '1981-02-11', '1919 Mapa Subdivision', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-27 00:50:46', '2025-12-28 06:40:53', NULL, 2, '2025-12-28', 1, 230, 2, '2025-12-28', 0, NULL, NULL, NULL, 0, NULL),
(120, 'Rosa Fernandez', 'rosa.fernandez@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234638', 'female', '1980-08-20', '2020 Osmeña Residences', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-28 04:15:37', '2025-12-29 02:25:44', NULL, 4, '2025-12-29', 1, 430, 3, '2025-12-29', 0, NULL, NULL, NULL, 0, NULL),
(121, 'Ramon San Jose', 'ramon.sanjose@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234639', 'male', '1979-01-25', '2121 Quirino Subdivision', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-29 06:40:52', NULL, NULL, 0, NULL, 0, 75, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(122, 'Teresa Malabanan', 'teresa.malabanan@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234640', 'female', '1985-07-12', '2222 Kalaw Street Ext', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-30 01:25:28', '2025-12-31 00:15:39', NULL, 1, '2025-12-31', 1, 125, 1, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(123, 'Alberto Delos Santos', 'alberto.delossantos@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234641', 'male', '1978-04-03', '2323 Evangelista Village', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-12-31 03:50:45', '2026-01-01 08:30:27', NULL, 3, '2026-01-01', 1, 340, 3, '2026-01-01', 0, NULL, NULL, NULL, 0, NULL),
(124, 'Carmen Dimaculangan', 'carmen.dimaculangan@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234642', 'female', '1982-10-17', '2424 Herrera Compound', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2026-01-01 05:35:19', '2026-01-02 01:45:31', NULL, 2, '2026-01-02', 1, 210, 2, '2026-01-02', 0, NULL, NULL, NULL, 0, NULL),
(125, 'Francisco Lagman', 'francisco.lagman@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09171234643', 'male', '1980-03-28', '2525 Ayala Subdivision', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2026-01-02 02:20:34', NULL, NULL, 0, NULL, 0, 50, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(126, 'Megan Rivera', 'megan.rivera126@example.com', '$2y$10$newhash001xyzabcdefghijk', '09171234644', 'female', '2000-08-22', '12 Sunrise Village', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-11-19 01:30:15', '2025-11-25 06:20:00', NULL, 3, '2025-11-25', 1, 185, 2, '2025-11-25', 0, NULL, NULL, NULL, 0, NULL),
(127, 'Daniel Kim', 'daniel.kim127@example.com', '$2y$10$newhash002xyzabcdefghijk', '09171234645', 'male', '1998-03-14', '45 Greenfield Subd', 'Calamba', 'Laguna', '4027', 'sidc_member', 'customer', '2025-11-20 03:45:30', '2025-11-28 08:10:00', NULL, 5, '2025-11-28', 1, 425, 3, '2025-11-28', 0, NULL, NULL, NULL, 0, NULL),
(128, 'Chloe Bennett', 'chloe.bennett128@example.com', '$2y$10$newhash003xyzabcdefghijk', '09171234646', 'female', '2002-07-30', '78 Sunset Blvd', 'San Pedro', 'Laguna', '4023', 'regular', 'customer', '2025-11-21 06:20:45', NULL, NULL, 0, NULL, 0, 50, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(129, 'Ryan Garcia', 'ryan.garcia129@example.com', '$2y$10$newhash004xyzabcdefghijk', '09171234647', 'male', '1995-12-05', '23 Moonlight St', 'Binan', 'Laguna', '4024', 'non_member', 'customer', '2025-11-22 08:35:20', '2025-11-29 02:15:00', NULL, 2, '2025-11-29', 1, 120, 1, '2025-11-29', 0, NULL, NULL, NULL, 0, NULL),
(130, 'Olivia Chen', 'olivia.chen130@example.com', '$2y$10$newhash005xyzabcdefghijk', '09171234648', 'female', '2001-04-18', '89 Starlight Ave', 'Santa Rosa', 'Laguna', '4026', 'sidc_member', 'customer', '2025-11-23 00:10:35', '2025-11-30 05:45:00', NULL, 4, '2025-11-30', 1, 380, 3, '2025-11-30', 0, NULL, NULL, NULL, 0, NULL),
(131, 'Ethan Wright', 'ethan.wright131@example.com', '$2y$10$newhash006xyzabcdefghijk', '09171234649', 'male', '1997-09-12', '34 Cloud Nine Subd', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-11-24 02:25:50', NULL, NULL, 0, NULL, 0, 75, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(132, 'Emma Lewis', 'emma.lewis132@example.com', '$2y$10$newhash007xyzabcdefghijk', '09171234650', 'female', '1999-11-25', '56 Rainbow Village', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-11-25 04:40:15', '2025-12-01 01:30:00', NULL, 1, '2025-12-01', 1, 155, 1, '2025-12-01', 0, NULL, NULL, NULL, 0, NULL),
(133, 'Noah Walker', 'noah.walker133@example.com', '$2y$10$newhash008xyzabcdefghijk', '09171234651', 'male', '2000-01-17', '67 Dreamland Subd', 'San Pedro', 'Laguna', '4023', 'non_member', 'customer', '2025-11-26 07:55:40', '2025-12-02 03:20:00', NULL, 6, '2025-12-02', 1, 510, 3, '2025-12-02', 0, NULL, NULL, NULL, 0, NULL),
(134, 'Ava Hall', 'ava.hall134@example.com', '$2y$10$newhash009xyzabcdefghijk', '09171234652', 'female', '1996-06-08', '78 Paradise St', 'Binan', 'Laguna', '4024', 'sidc_member', 'customer', '2025-11-27 10:10:25', NULL, NULL, 0, NULL, 0, 95, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(135, 'Liam Young', 'liam.young135@example.com', '$2y$10$newhash010xyzabcdefghijk', '09171234653', 'male', '2003-02-14', '90 Harmony Ave', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-11-27 23:45:30', '2025-12-03 06:10:00', NULL, 2, '2025-12-03', 1, 210, 2, '2025-12-03', 0, NULL, NULL, NULL, 0, NULL),
(136, 'Isabella King', 'isabella.king136@example.com', '$2y$10$newhash011xyzabcdefghijk', '09171234654', 'female', '1994-05-29', '12 Serenity Village', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-11-29 01:20:45', '2025-12-04 08:35:00', NULL, 3, '2025-12-04', 1, 295, 2, '2025-12-04', 0, NULL, NULL, NULL, 0, NULL),
(137, 'Mason Scott', 'mason.scott137@example.com', '$2y$10$newhash012xyzabcdefghijk', '09171234655', 'male', '1998-08-03', '34 Tranquility St', 'Calamba', 'Laguna', '4027', 'non_member', 'customer', '2025-11-30 03:35:20', NULL, NULL, 0, NULL, 0, 60, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(138, 'Sophia Green', 'sophia.green138@example.com', '$2y$10$newhash013xyzabcdefghijk', '09171234656', 'female', '2002-10-19', '56 Peaceful Ave', 'San Pedro', 'Laguna', '4023', 'sidc_member', 'customer', '2025-12-01 05:50:35', '2025-12-06 00:25:00', NULL, 4, '2025-12-06', 1, 365, 3, '2025-12-06', 0, NULL, NULL, NULL, 0, NULL),
(139, 'Jacob Adams', 'jacob.adams139@example.com', '$2y$10$newhash014xyzabcdefghijk', '09171234657', 'male', '1995-12-27', '78 Calm Street', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-12-02 08:05:50', '2025-12-07 02:40:00', NULL, 1, '2025-12-07', 1, 140, 1, '2025-12-07', 0, NULL, NULL, NULL, 0, NULL),
(140, 'Mia Nelson', 'mia.nelson140@example.com', '$2y$10$newhash015xyzabcdefghijk', '09171234658', 'female', '1999-03-11', '90 Quiet Village', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-12-03 10:20:15', NULL, NULL, 0, NULL, 0, 85, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(141, 'William Carter', 'william.carter141@example.com', '$2y$10$newhash016xyzabcdefghijk', '09171234659', 'male', '2001-07-24', '23 Silent Subd', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-04 00:35:30', '2025-12-09 04:55:00', NULL, 5, '2025-12-09', 1, 445, 3, '2025-12-09', 0, NULL, NULL, NULL, 0, NULL),
(142, 'Charlotte Mitchell', 'charlotte.mitchell142@example.com', '$2y$10$newhash017xyzabcdefghijk', '09171234660', 'female', '1997-04-05', '45 Gentle Ave', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-12-05 02:50:45', '2025-12-10 07:10:00', NULL, 2, '2025-12-10', 1, 225, 2, '2025-12-10', 0, NULL, NULL, NULL, 0, NULL),
(143, 'Elijah Perez', 'elijah.perez143@example.com', '$2y$10$newhash018xyzabcdefghijk', '09171234661', 'male', '1993-11-18', '67 Mild Street', 'San Pedro', 'Laguna', '4023', 'non_member', 'customer', '2025-12-06 05:05:20', NULL, NULL, 0, NULL, 0, 110, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(144, 'Amelia Roberts', 'amelia.roberts144@example.com', '$2y$10$newhash019xyzabcdefghijk', '09171234662', 'female', '2000-09-02', '89 Soft Village', 'Binan', 'Laguna', '4024', 'sidc_member', 'customer', '2025-12-07 07:20:35', '2025-12-12 09:25:00', NULL, 3, '2025-12-12', 1, 310, 2, '2025-12-12', 0, NULL, NULL, NULL, 0, NULL),
(145, 'James Turner', 'james.turner145@example.com', '$2y$10$newhash020xyzabcdefghijk', '09171234663', 'male', '1996-02-15', '12 Gentle Breeze', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-12-08 09:35:50', '2025-12-13 11:40:00', NULL, 1, '2025-12-13', 1, 165, 1, '2025-12-13', 0, NULL, NULL, NULL, 0, NULL),
(146, 'Harper Phillips', 'harper.phillips146@example.com', '$2y$10$newhash021xyzabcdefghijk', '09171234664', 'female', '2004-12-28', '34 Light Whisper', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-09 11:50:15', NULL, NULL, 0, NULL, 0, 70, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(147, 'Benjamin Campbell', 'benjamin.campbell147@example.com', '$2y$10$newhash022xyzabcdefghijk', '09171234665', 'male', '1998-06-09', '56 Quiet Wind', 'Calamba', 'Laguna', '4027', 'non_member', 'customer', '2025-12-10 00:05:30', '2025-12-15 02:15:00', NULL, 4, '2025-12-15', 1, 395, 3, '2025-12-15', 0, NULL, NULL, NULL, 0, NULL),
(148, 'Evelyn Parker', 'evelyn.parker148@example.com', '$2y$10$newhash023xyzabcdefghijk', '09171234666', 'female', '1995-01-22', '78 Soft Breeze', 'San Pedro', 'Laguna', '4023', 'sidc_member', 'customer', '2025-12-11 02:20:45', '2025-12-16 04:30:00', NULL, 2, '2025-12-16', 1, 240, 2, '2025-12-16', 0, NULL, NULL, NULL, 0, NULL),
(149, 'Lucas Edwards', 'lucas.edwards149@example.com', '$2y$10$newhash024xyzabcdefghijk', '09171234667', 'male', '2002-03-07', '90 Calm Air', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-12-12 04:35:20', NULL, NULL, 0, NULL, 0, 125, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(150, 'Abigail Collins', 'abigail.collins150@example.com', '$2y$10$newhash025xyzabcdefghijk', '09171234668', 'female', '1999-10-14', '23 Stillness St', 'Santa Rosa', 'Laguna', '4026', 'regular', 'admin', '2025-12-13 06:50:35', '2025-12-31 15:59:59', NULL, 7, '2025-12-31', 1, 520, 3, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(151, 'Henry Stewart', 'henry.stewart151@example.com', '$2y$10$newhash026xyzabcdefghijk', '09171234669', 'male', '1994-07-19', '45 Quiet Corner', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-14 08:05:50', '2025-12-19 10:20:00', NULL, 3, '2025-12-19', 1, 335, 2, '2025-12-19', 0, NULL, NULL, NULL, 0, NULL),
(152, 'Ella Murphy', 'ella.murphy152@example.com', '$2y$10$newhash027xyzabcdefghijk', '09171234670', 'female', '2001-12-03', '56 Silent Grove', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-12-15 10:20:15', NULL, NULL, 0, NULL, 0, 90, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(153, 'Alexander Cook', 'alexander.cook153@example.com', '$2y$10$newhash028xyzabcdefghijk', '09171234671', 'male', '1997-05-16', '67 Peaceful Haven', 'San Pedro', 'Laguna', '4023', 'non_member', 'customer', '2025-12-16 00:35:30', '2025-12-21 06:45:00', NULL, 5, '2025-12-21', 1, 465, 3, '2025-12-21', 0, NULL, NULL, NULL, 0, NULL),
(154, 'Scarlett Morgan', 'scarlett.morgan154@example.com', '$2y$10$newhash029xyzabcdefghijk', '09171234672', 'female', '2000-02-28', '78 Tranquil View', 'Binan', 'Laguna', '4024', 'sidc_member', 'customer', '2025-12-17 02:50:45', '2025-12-22 08:55:00', NULL, 2, '2025-12-22', 1, 250, 2, '2025-12-22', 0, NULL, NULL, NULL, 0, NULL),
(155, 'Jack Bell', 'jack.bell155@example.com', '$2y$10$newhash030xyzabcdefghijk', '09171234673', 'male', '1995-09-11', '89 Calm Retreat', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-12-18 05:05:20', NULL, NULL, 0, NULL, 0, 145, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(156, 'Grace Howard', 'grace.howard156@example.com', '$2y$10$newhash031xyzabcdefghijk', '09171234674', 'female', '2003-04-24', '12 Serene Place', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-19 07:20:35', '2025-12-24 11:10:00', NULL, 4, '2025-12-24', 1, 375, 3, '2025-12-24', 0, NULL, NULL, NULL, 0, NULL),
(157, 'Owen Ward', 'owen.ward157@example.com', '$2y$10$newhash032xyzabcdefghijk', '09171234675', 'male', '1998-11-07', '34 Quiet Nook', 'Calamba', 'Laguna', '4027', 'non_member', 'customer', '2025-12-20 09:35:50', '2025-12-25 13:25:00', NULL, 1, '2025-12-25', 1, 180, 1, '2025-12-25', 0, NULL, NULL, NULL, 0, NULL),
(158, 'Chloe Cox', 'chloe.cox158@example.com', '$2y$10$newhash033xyzabcdefghijk', '09171234676', 'female', '1996-06-20', '56 Peaceful Spot', 'San Pedro', 'Laguna', '4023', 'sidc_member', 'customer', '2025-12-21 11:50:15', NULL, NULL, 0, NULL, 0, 105, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(159, 'Luke Diaz', 'luke.diaz159@example.com', '$2y$10$newhash034xyzabcdefghijk', '09171234677', 'male', '2001-01-02', '78 Silent Meadow', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-12-22 00:05:30', '2025-12-27 02:40:00', NULL, 6, '2025-12-27', 1, 535, 3, '2025-12-27', 0, NULL, NULL, NULL, 0, NULL),
(160, 'Zoe Richardson', 'zoe.richardson160@example.com', '$2y$10$newhash035xyzabcdefghijk', '09171234678', 'female', '1999-08-15', '90 Calm Garden', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-12-23 02:20:45', '2025-12-28 04:55:00', NULL, 2, '2025-12-28', 1, 265, 2, '2025-12-28', 0, NULL, NULL, NULL, 0, NULL),
(161, 'Grayson Wood', 'grayson.wood161@example.com', '$2y$10$newhash036xyzabcdefghijk', '09171234679', 'male', '1994-03-28', '23 Quiet Orchard', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-12-24 04:35:20', NULL, NULL, 0, NULL, 0, 130, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(162, 'Victoria Watson', 'victoria.watson162@example.com', '$2y$10$newhash037xyzabcdefghijk', '09171234680', 'female', '2002-10-11', '45 Peaceful Field', 'Calamba', 'Laguna', '4027', 'sidc_member', 'customer', '2025-12-25 06:50:35', '2025-12-30 07:10:00', NULL, 3, '2025-12-30', 1, 355, 2, '2025-12-30', 0, NULL, NULL, NULL, 0, NULL),
(163, 'Leo Brooks', 'leo.brooks163@example.com', '$2y$10$newhash038xyzabcdefghijk', '09171234681', 'male', '1997-05-24', '56 Silent Park', 'San Pedro', 'Laguna', '4023', 'regular', 'customer', '2025-12-26 08:05:50', '2025-12-31 09:25:00', NULL, 1, '2025-12-31', 1, 195, 1, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL);
INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `phone`, `gender`, `birth_date`, `address`, `city`, `province`, `zip_code`, `membership_type`, `role`, `created_at`, `updated_at`, `terms_accepted_at`, `login_streak`, `last_login_date`, `streak_updated_today`, `points`, `daily_spins`, `last_spin_date`, `email_verified`, `verified_at`, `verification_otp`, `otp_expiry`, `otp_attempts`, `otp_requested_at`) VALUES
(164, 'Layla Bennett', 'layla.bennett164@example.com', '$2y$10$newhash039xyzabcdefghijk', '09171234682', 'female', '2000-12-06', '67 Calm Woods', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-12-27 10:20:15', NULL, NULL, 0, NULL, 0, 115, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(165, 'Julian Barnes', 'julian.barnes165@example.com', '$2y$10$newhash040xyzabcdefghijk', '09171234683', 'male', '1995-07-19', '78 Quiet Forest', 'Santa Rosa', 'Laguna', '4026', 'non_member', 'customer', '2025-12-28 00:35:30', '2025-12-31 11:40:00', NULL, 4, '2025-12-31', 1, 415, 3, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(166, 'Nora Ross', 'nora.ross166@example.com', '$2y$10$newhash041xyzabcdefghijk', '09171234684', 'female', '2003-02-01', '89 Peaceful Hill', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-29 02:50:45', '2025-12-31 13:55:00', NULL, 2, '2025-12-31', 1, 280, 2, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(167, 'Carter Henderson', 'carter.henderson167@example.com', '$2y$10$newhash042xyzabcdefghijk', '09171234685', 'male', '1998-09-14', '12 Silent Mountain', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-12-30 05:05:20', NULL, NULL, 0, NULL, 0, 155, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(168, 'Ellie Coleman', 'ellie.coleman168@example.com', '$2y$10$newhash043xyzabcdefghijk', '09171234686', 'female', '1996-04-27', '34 Calm Valley', 'San Pedro', 'Laguna', '4023', 'regular', 'customer', '2025-12-31 07:20:35', NULL, NULL, 0, NULL, 0, 70, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(169, 'Ryan Gray', 'ryan.gray169@example.com', '$2y$10$newhash044xyzabcdefghijk', '09171234687', 'male', '2001-11-09', '56 Quiet River', 'Binan', 'Laguna', '4024', 'non_member', 'customer', '2025-11-18 09:35:50', '2025-11-25 01:15:00', NULL, 5, '2025-11-25', 1, 485, 3, '2025-11-25', 0, NULL, NULL, NULL, 0, NULL),
(170, 'Hannah James', 'hannah.james170@example.com', '$2y$10$newhash045xyzabcdefghijk', '09171234688', 'female', '1999-06-22', '78 Peaceful Lake', 'Santa Rosa', 'Laguna', '4026', 'sidc_member', 'customer', '2025-11-19 11:50:15', '2025-11-26 03:30:00', NULL, 3, '2025-11-26', 1, 370, 2, '2025-11-26', 0, NULL, NULL, NULL, 0, NULL),
(171, 'Nathan Foster', 'nathan.foster171@example.com', '$2y$10$newhash046xyzabcdefghijk', '09171234689', 'male', '1994-01-05', '90 Silent Ocean', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-11-20 00:05:30', NULL, NULL, 0, NULL, 0, 125, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(172, 'Lily Simmons', 'lily.simmons172@example.com', '$2y$10$newhash047xyzabcdefghijk', '09171234690', 'female', '2002-08-18', '23 Calm Sea', 'Calamba', 'Laguna', '4027', 'regular', 'admin', '2025-11-21 02:20:45', '2025-11-28 06:45:00', NULL, 2, '2025-11-28', 1, 300, 2, '2025-11-28', 0, NULL, NULL, NULL, 0, NULL),
(173, 'Isaac Long', 'isaac.long173@example.com', '$2y$10$newhash048xyzabcdefghijk', '09171234691', 'male', '1997-03-31', '45 Quiet Bay', 'San Pedro', 'Laguna', '4023', 'non_member', 'customer', '2025-11-22 04:35:20', '2025-11-29 09:00:00', NULL, 1, '2025-11-29', 1, 205, 1, '2025-11-29', 0, NULL, NULL, NULL, 0, NULL),
(174, 'Aubrey Foster', 'aubrey.foster174@example.com', '$2y$10$newhash049xyzabcdefghijk', '09171234692', 'female', '2000-10-13', '56 Peaceful Cove', 'Binan', 'Laguna', '4024', 'sidc_member', 'customer', '2025-11-23 06:50:35', NULL, NULL, 0, NULL, 0, 135, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(175, 'Caleb Russell', 'caleb.russell175@example.com', '$2y$10$newhash050xyzabcdefghijk', '09171234693', 'male', '1995-05-26', '67 Silent Harbor', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-11-24 08:05:50', '2025-11-30 11:15:00', NULL, 4, '2025-11-30', 1, 430, 3, '2025-11-30', 0, NULL, NULL, NULL, 0, NULL),
(176, 'Madison Hughes', 'madison.hughes176@example.com', '$2y$10$newhash051xyzabcdefghijk', '09171234694', 'female', '2003-12-09', '78 Calm Marina', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-11-25 10:20:15', '2025-12-01 00:30:00', NULL, 3, '2025-12-01', 1, 320, 2, '2025-12-01', 0, NULL, NULL, NULL, 0, NULL),
(177, 'Hunter Patterson', 'hunter.patterson177@example.com', '$2y$10$newhash052xyzabcdefghijk', '09171234695', 'male', '1998-07-22', '90 Quiet Port', 'Calamba', 'Laguna', '4027', 'non_member', 'customer', '2025-11-26 00:35:30', NULL, NULL, 0, NULL, 0, 165, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(178, 'Addison Bryant', 'addison.bryant178@example.com', '$2y$10$newhash053xyzabcdefghijk', '09171234696', 'female', '1996-02-04', '12 Peaceful Dock', 'San Pedro', 'Laguna', '4023', 'sidc_member', 'customer', '2025-11-27 02:50:45', '2025-12-02 02:45:00', NULL, 2, '2025-12-02', 1, 275, 2, '2025-12-02', 0, NULL, NULL, NULL, 0, NULL),
(179, 'Levi Alexander', 'levi.alexander179@example.com', '$2y$10$newhash054xyzabcdefghijk', '09171234697', 'male', '2001-09-17', '34 Silent Wharf', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-11-28 05:05:20', '2025-12-03 05:00:00', NULL, 1, '2025-12-03', 1, 190, 1, '2025-12-03', 0, NULL, NULL, NULL, 0, NULL),
(180, 'Natalie Griffin', 'natalie.griffin180@example.com', '$2y$10$newhash055xyzabcdefghijk', '09171234698', 'female', '1999-04-30', '56 Calm Pier', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-11-29 07:20:35', NULL, NULL, 0, NULL, 0, 110, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(181, 'Christian West', 'christian.west181@example.com', '$2y$10$newhash056xyzabcdefghijk', '09171234699', 'male', '1994-11-12', '67 Quiet Anchorage', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-11-30 09:35:50', '2025-12-05 07:15:00', NULL, 6, '2025-12-05', 1, 550, 3, '2025-12-05', 0, NULL, NULL, NULL, 0, NULL),
(182, 'Savannah Hayes', 'savannah.hayes182@example.com', '$2y$10$newhash057xyzabcdefghijk', '09171234700', 'female', '2002-06-25', '78 Peaceful Mooring', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-12-01 11:50:15', '2025-12-06 09:30:00', NULL, 3, '2025-12-06', 1, 340, 2, '2025-12-06', 0, NULL, NULL, NULL, 0, NULL),
(183, 'Isaiah Myers', 'isaiah.myers183@example.com', '$2y$10$newhash058xyzabcdefghijk', '09171234701', 'male', '1997-01-08', '89 Silent Jetty', 'San Pedro', 'Laguna', '4023', 'non_member', 'customer', '2025-12-02 00:05:30', NULL, NULL, 0, NULL, 0, 175, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(184, 'Brooklyn Ford', 'brooklyn.ford184@example.com', '$2y$10$newhash059xyzabcdefghijk', '09171234702', 'female', '2000-08-21', '90 Calm Quay', 'Binan', 'Laguna', '4024', 'sidc_member', 'customer', '2025-12-03 02:20:45', '2025-12-08 11:45:00', NULL, 4, '2025-12-08', 1, 400, 3, '2025-12-08', 0, NULL, NULL, NULL, 0, NULL),
(185, 'Aaron Sanders', 'aaron.sanders185@example.com', '$2y$10$newhash060xyzabcdefghijk', '09171234703', 'male', '1995-03-05', '12 Quiet Berth', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-12-04 04:35:20', '2025-12-09 13:00:00', NULL, 2, '2025-12-09', 1, 285, 2, '2025-12-09', 0, NULL, NULL, NULL, 0, NULL),
(186, 'Claire Reed', 'claire.reed186@example.com', '$2y$10$newhash061xyzabcdefghijk', '09171234704', 'female', '2003-10-18', '34 Peaceful Slip', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-05 06:50:35', NULL, NULL, 0, NULL, 0, 120, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(187, 'Thomas Kelly', 'thomas.kelly187@example.com', '$2y$10$newhash062xyzabcdefghijk', '09171234705', 'male', '1998-05-31', '56 Silent Harbor', 'Calamba', 'Laguna', '4027', 'non_member', 'customer', '2025-12-06 08:05:50', '2025-12-11 14:15:00', NULL, 1, '2025-12-11', 1, 210, 1, '2025-12-11', 0, NULL, NULL, NULL, 0, NULL),
(188, 'Skylar Price', 'skylar.price188@example.com', '$2y$10$newhash063xyzabcdefghijk', '09171234706', 'female', '1996-12-13', '67 Calm Landing', 'San Pedro', 'Laguna', '4023', 'sidc_member', 'customer', '2025-12-07 10:20:15', '2025-12-12 15:30:00', NULL, 5, '2025-12-12', 1, 470, 3, '2025-12-12', 0, NULL, NULL, NULL, 0, NULL),
(189, 'Eli Bailey', 'eli.bailey189@example.com', '$2y$10$newhash064xyzabcdefghijk', '09171234707', 'male', '2001-07-26', '78 Quiet Terminal', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-12-08 00:35:30', NULL, NULL, 0, NULL, 0, 140, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(190, 'Audrey Bennett', 'audrey.bennett190@example.com', '$2y$10$newhash065xyzabcdefghijk', '09171234708', 'female', '1999-02-08', '89 Peaceful Station', 'Santa Rosa', 'Laguna', '4026', 'regular', 'admin', '2025-12-09 02:50:45', '2025-12-14 00:45:00', NULL, 3, '2025-12-14', 1, 360, 2, '2025-12-14', 0, NULL, NULL, NULL, 0, NULL),
(191, 'Connor Cooper', 'connor.cooper191@example.com', '$2y$10$newhash066xyzabcdefghijk', '09171234709', 'male', '1994-09-21', '12 Silent Depot', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-12-10 05:05:20', '2025-12-15 02:00:00', NULL, 2, '2025-12-15', 1, 295, 2, '2025-12-15', 0, NULL, NULL, NULL, 0, NULL),
(192, 'Bella Howard', 'bella.howard192@example.com', '$2y$10$newhash067xyzabcdefghijk', '09171234710', 'female', '2002-04-04', '34 Calm Platform', 'Calamba', 'Laguna', '4027', 'sidc_member', 'customer', '2025-12-11 07:20:35', NULL, NULL, 0, NULL, 0, 155, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(193, 'Jaxon Torres', 'jaxon.torres193@example.com', '$2y$10$newhash068xyzabcdefghijk', '09171234711', 'male', '1997-11-17', '56 Quiet Stop', 'San Pedro', 'Laguna', '4023', 'regular', 'customer', '2025-12-12 09:35:50', '2025-12-17 04:15:00', NULL, 4, '2025-12-17', 1, 425, 3, '2025-12-17', 0, NULL, NULL, NULL, 0, NULL),
(194, 'Paisley Peterson', 'paisley.peterson194@example.com', '$2y$10$newhash069xyzabcdefghijk', '09171234712', 'female', '2000-06-30', '67 Peaceful Junction', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-12-13 11:50:15', '2025-12-18 06:30:00', NULL, 1, '2025-12-18', 1, 220, 1, '2025-12-18', 0, NULL, NULL, NULL, 0, NULL),
(195, 'Dominic Ramirez', 'dominic.ramirez195@example.com', '$2y$10$newhash070xyzabcdefghijk', '09171234713', 'male', '1995-01-13', '78 Silent Crossing', 'Santa Rosa', 'Laguna', '4026', 'non_member', 'customer', '2025-12-14 00:05:30', NULL, NULL, 0, NULL, 0, 180, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(196, 'Anna Flores', 'anna.flores196@example.com', '$2y$10$newhash071xyzabcdefghijk', '09171234714', 'female', '2003-08-26', '89 Calm Intersection', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-15 02:20:45', '2025-12-20 08:45:00', NULL, 2, '2025-12-20', 1, 305, 2, '2025-12-20', 0, NULL, NULL, NULL, 0, NULL),
(197, 'Josiah Washington', 'josiah.washington197@example.com', '$2y$10$newhash072xyzabcdefghijk', '09171234715', 'male', '1998-03-10', '90 Quiet Corner', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-12-16 04:35:20', '2025-12-21 10:00:00', NULL, 5, '2025-12-21', 1, 490, 3, '2025-12-21', 0, NULL, NULL, NULL, 0, NULL),
(198, 'Kylie Butler', 'kylie.butler198@example.com', '$2y$10$newhash073xyzabcdefghijk', '09171234716', 'female', '1996-10-23', '12 Peaceful Roundabout', 'San Pedro', 'Laguna', '4023', 'regular', 'customer', '2025-12-17 06:50:35', NULL, NULL, 0, NULL, 0, 135, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(199, 'Austin Foster', 'austin.foster199@example.com', '$2y$10$newhash074xyzabcdefghijk', '09171234717', 'male', '2001-05-06', '34 Silent Circle', 'Binan', 'Laguna', '4024', 'non_member', 'customer', '2025-12-18 08:05:50', '2025-12-23 11:15:00', NULL, 3, '2025-12-23', 1, 375, 2, '2025-12-23', 0, NULL, NULL, NULL, 0, NULL),
(200, 'Hailey Gonzales', 'hailey.gonzales200@example.com', '$2y$10$newhash075xyzabcdefghijk', '09171234718', 'female', '1999-12-19', '56 Calm Square', 'Santa Rosa', 'Laguna', '4026', 'sidc_member', 'customer', '2025-12-19 10:20:15', '2025-12-31 15:59:59', NULL, 8, '2025-12-31', 1, 560, 3, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(201, 'Gabriel Reyes', 'gabriel.reyes201@example.com', '$2y$10$hash201xyzabcdefghijklmn', '09171234719', 'male', '1998-05-14', '123 Sunrise Avenue', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-20 01:30:00', '2025-12-25 06:20:00', NULL, 4, '2025-12-25', 1, 220, 2, '2025-12-25', 0, NULL, NULL, NULL, 0, NULL),
(202, 'Isabelle Cruz', 'isabelle.cruz202@example.com', '$2y$10$hash202xyzabcdefghijklmn', '09171234720', 'female', '2000-11-28', '456 Sunset Boulevard', 'Calamba', 'Laguna', '4027', 'sidc_member', 'customer', '2025-12-20 03:45:00', NULL, NULL, 0, NULL, 0, 85, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(203, 'Marcus Tan', 'marcus.tan203@example.com', '$2y$10$hash203xyzabcdefghijklmn', '09171234721', 'male', '1995-03-17', '789 Moonlight Street', 'San Pedro', 'Laguna', '4023', 'non_member', 'customer', '2025-12-21 06:10:00', '2025-12-26 08:30:00', NULL, 3, '2025-12-26', 1, 175, 1, '2025-12-26', 0, NULL, NULL, NULL, 0, NULL),
(204, 'Sophia Lim', 'sophia.lim204@example.com', '$2y$10$hash204xyzabcdefghijklmn', '09171234722', 'female', '2002-07-03', '101 Starlight Village', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-12-21 08:25:00', '2025-12-28 03:15:00', NULL, 5, '2025-12-28', 1, 410, 3, '2025-12-28', 0, NULL, NULL, NULL, 0, NULL),
(205, 'Julian Gomez', 'julian.gomez205@example.com', '$2y$10$hash205xyzabcdefghijklmn', '09171234723', 'male', '1997-12-19', '202 Rainbow Subdivision', 'Santa Rosa', 'Laguna', '4026', 'sidc_member', 'customer', '2025-12-22 00:40:00', NULL, NULL, 0, NULL, 0, 120, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(206, 'Valerie Chen', 'valerie.chen206@example.com', '$2y$10$hash206xyzabcdefghijklmn', '09171234724', 'female', '1999-09-05', '303 Cloud Nine Avenue', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-22 02:55:00', '2025-12-27 05:45:00', NULL, 2, '2025-12-27', 1, 195, 2, '2025-12-27', 0, NULL, NULL, NULL, 0, NULL),
(207, 'Nathaniel Ong', 'nathaniel.ong207@example.com', '$2y$10$hash207xyzabcdefghijklmn', '09171234725', 'male', '1994-02-21', '404 Dreamland Street', 'Calamba', 'Laguna', '4027', 'non_member', 'customer', '2025-12-23 05:20:00', '2025-12-29 01:30:00', NULL, 1, '2025-12-29', 1, 140, 1, '2025-12-29', 0, NULL, NULL, NULL, 0, NULL),
(208, 'Camille Sy', 'camille.sy208@example.com', '$2y$10$hash208xyzabcdefghijklmn', '09171234726', 'female', '2001-06-12', '505 Paradise Village', 'San Pedro', 'Laguna', '4023', 'sidc_member', 'customer', '2025-12-23 07:35:00', NULL, NULL, 0, NULL, 0, 75, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(209, 'Adrian Yu', 'adrian.yu209@example.com', '$2y$10$hash209xyzabcdefghijklmn', '09171234727', 'male', '1996-10-30', '606 Harmony Avenue', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-12-24 01:00:00', '2025-12-30 07:20:00', NULL, 4, '2025-12-30', 1, 335, 2, '2025-12-30', 0, NULL, NULL, NULL, 0, NULL),
(210, 'Diana Wong', 'diana.wong210@example.com', '$2y$10$hash210xyzabcdefghijklmn', '09171234728', 'female', '2003-01-15', '707 Serenity Street', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-12-24 03:15:00', '2025-12-31 09:35:00', NULL, 6, '2025-12-31', 1, 480, 3, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(211, 'Sebastian Lim', 'sebastian.lim211@example.com', '$2y$10$hash211xyzabcdefghijklmn', '09171234729', 'male', '1998-04-02', '808 Tranquility Village', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-12-25 05:40:00', NULL, NULL, 0, NULL, 0, 95, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(212, 'Gabrielle Tan', 'gabrielle.tan212@example.com', '$2y$10$hash212xyzabcdefghijklmn', '09171234730', 'female', '2000-08-18', '909 Peaceful Avenue', 'Calamba', 'Laguna', '4027', 'sidc_member', 'customer', '2025-12-25 07:55:00', '2025-12-29 04:10:00', NULL, 3, '2025-12-29', 1, 280, 2, '2025-12-29', 0, NULL, NULL, NULL, 0, NULL),
(213, 'Brandon Chua', 'brandon.chua213@example.com', '$2y$10$hash213xyzabcdefghijklmn', '09171234731', 'male', '1995-12-04', '111 Calm Street', 'San Pedro', 'Laguna', '4023', 'regular', 'customer', '2025-12-26 00:20:00', '2025-12-31 06:25:00', NULL, 2, '2025-12-31', 1, 215, 1, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(214, 'Francesca Go', 'francesca.go214@example.com', '$2y$10$hash214xyzabcdefghijklmn', '09171234732', 'female', '2002-03-21', '222 Quiet Village', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-12-26 02:35:00', NULL, NULL, 0, NULL, 0, 130, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(215, 'Vincent Lee', 'vincent.lee215@example.com', '$2y$10$hash215xyzabcdefghijklmn', '09171234733', 'male', '1997-07-07', '333 Silent Avenue', 'Santa Rosa', 'Laguna', '4026', 'non_member', 'customer', '2025-12-27 04:50:00', '2025-12-30 08:40:00', NULL, 1, '2025-12-30', 1, 165, 1, '2025-12-30', 0, NULL, NULL, NULL, 0, NULL),
(216, 'Beatrice Lim', 'beatrice.lim216@example.com', '$2y$10$hash216xyzabcdefghijklmn', '09171234734', 'female', '1999-11-23', '444 Gentle Street', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-12-27 07:05:00', '2025-12-31 10:55:00', NULL, 5, '2025-12-31', 1, 425, 3, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(217, 'Christopher Sy', 'christopher.sy217@example.com', '$2y$10$hash217xyzabcdefghijklmn', '09171234735', 'male', '1994-05-09', '555 Mild Village', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-12-28 01:30:00', NULL, NULL, 0, NULL, 0, 110, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(218, 'Genevieve Ong', 'genevieve.ong218@example.com', '$2y$10$hash218xyzabcdefghijklmn', '09171234736', 'female', '2001-09-25', '666 Soft Avenue', 'San Pedro', 'Laguna', '4023', 'regular', 'customer', '2025-12-28 03:45:00', '2025-12-31 12:10:00', NULL, 3, '2025-12-31', 1, 310, 2, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(219, 'Maximilian Tan', 'maximilian.tan219@example.com', '$2y$10$hash219xyzabcdefghijklmn', '09171234737', 'male', '1996-02-10', '777 Light Whisper', 'Binan', 'Laguna', '4024', 'non_member', 'customer', '2025-12-29 06:00:00', '2025-12-31 14:25:00', NULL, 2, '2025-12-31', 1, 245, 1, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(220, 'Seraphina Gomez', 'seraphina.gomez220@example.com', '$2y$10$hash220xyzabcdefghijklmn', '09171234738', 'female', '2003-06-27', '888 Quiet Wind', 'Santa Rosa', 'Laguna', '4026', 'sidc_member', 'customer', '2025-12-29 08:15:00', NULL, NULL, 0, NULL, 0, 155, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(221, 'Raphael Chua', 'raphael.chua221@example.com', '$2y$10$hash221xyzabcdefghijklmn', '09171234739', 'male', '1998-10-13', '999 Peaceful Breeze', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-30 00:40:00', '2025-12-31 11:40:00', NULL, 4, '2025-12-31', 1, 380, 3, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(222, 'Arabella Yu', 'arabella.yu222@example.com', '$2y$10$hash222xyzabcdefghijklmn', '09171234740', 'female', '2000-01-29', '111 Calm Air', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-12-30 02:55:00', NULL, NULL, 0, NULL, 0, 90, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(223, 'Dominic Lim', 'dominic.lim223@example.com', '$2y$10$hash223xyzabcdefghijklmn', '09171234741', 'male', '1995-05-16', '222 Stillness Street', 'San Pedro', 'Laguna', '4023', 'non_member', 'customer', '2025-12-31 05:20:00', NULL, NULL, 0, NULL, 0, 205, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(224, 'Evangeline Sy', 'evangeline.sy224@example.com', '$2y$10$hash224xyzabcdefghijklmn', '09171234742', 'female', '2002-08-01', '333 Quiet Corner', 'Binan', 'Laguna', '4024', 'sidc_member', 'customer', '2025-12-31 07:35:00', NULL, NULL, 0, NULL, 0, 125, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(225, 'Theodore Ong', 'theodore.ong225@example.com', '$2y$10$hash225xyzabcdefghijklmn', '09171234743', 'male', '1997-12-17', '444 Silent Grove', 'Santa Rosa', 'Laguna', '4026', 'regular', 'admin', '2025-11-18 01:50:00', '2025-12-25 03:30:00', NULL, 7, '2025-12-25', 1, 550, 3, '2025-12-25', 0, NULL, NULL, NULL, 0, NULL),
(226, 'Juliette Tan', 'juliette.tan226@example.com', '$2y$10$hash226xyzabcdefghijklmn', '09171234744', 'female', '1999-03-04', '555 Peaceful Haven', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-11-19 04:05:00', '2025-12-20 06:45:00', NULL, 3, '2025-12-20', 1, 265, 2, '2025-12-20', 0, NULL, NULL, NULL, 0, NULL),
(227, 'Alexander Gomez', 'alexander.gomez227@example.com', '$2y$10$hash227xyzabcdefghijklmn', '09171234745', 'male', '1994-07-20', '666 Tranquil View', 'Calamba', 'Laguna', '4027', 'non_member', 'customer', '2025-11-20 06:20:00', NULL, NULL, 0, NULL, 0, 115, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(228, 'Giselle Chua', 'giselle.chua228@example.com', '$2y$10$hash228xyzabcdefghijklmn', '09171234746', 'female', '2001-11-05', '777 Calm Retreat', 'San Pedro', 'Laguna', '4023', 'sidc_member', 'customer', '2025-11-21 08:35:00', '2025-12-22 09:00:00', NULL, 2, '2025-12-22', 1, 230, 1, '2025-12-22', 0, NULL, NULL, NULL, 0, NULL),
(229, 'Emmanuel Sy', 'emmanuel.sy229@example.com', '$2y$10$hash229xyzabcdefghijklmn', '09171234747', 'male', '1996-04-22', '888 Serene Place', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-11-22 10:50:00', '2025-12-24 11:15:00', NULL, 5, '2025-12-24', 1, 395, 3, '2025-12-24', 0, NULL, NULL, NULL, 0, NULL),
(230, 'Celeste Ong', 'celeste.ong230@example.com', '$2y$10$hash230xyzabcdefghijklmn', '09171234748', 'female', '2003-08-08', '999 Quiet Nook', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-11-23 13:05:00', NULL, NULL, 0, NULL, 0, 140, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(231, 'Xavier Lim', 'xavier.lim231@example.com', '$2y$10$hash231xyzabcdefghijklmn', '09171234749', 'male', '1998-12-24', '111 Peaceful Spot', 'San Pablo', 'Laguna', '4000', 'non_member', 'customer', '2025-11-24 01:30:00', '2025-12-26 02:30:00', NULL, 1, '2025-12-26', 1, 180, 1, '2025-12-26', 0, NULL, NULL, NULL, 0, NULL),
(232, 'Vivienne Tan', 'vivienne.tan232@example.com', '$2y$10$hash232xyzabcdefghijklmn', '09171234750', 'female', '2000-05-10', '222 Silent Meadow', 'Calamba', 'Laguna', '4027', 'sidc_member', 'customer', '2025-11-25 03:45:00', '2025-12-28 04:45:00', NULL, 4, '2025-12-28', 1, 350, 2, '2025-12-28', 0, NULL, NULL, NULL, 0, NULL),
(233, 'Zachary Gomez', 'zachary.gomez233@example.com', '$2y$10$hash233xyzabcdefghijklmn', '09171234751', 'male', '1995-09-26', '333 Calm Garden', 'San Pedro', 'Laguna', '4023', 'regular', 'customer', '2025-11-26 06:00:00', NULL, NULL, 0, NULL, 0, 95, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(234, 'Liliana Chua', 'liliana.chua234@example.com', '$2y$10$hash234xyzabcdefghijklmn', '09171234752', 'female', '2002-02-11', '444 Quiet Orchard', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-11-27 08:15:00', '2025-12-30 07:00:00', NULL, 3, '2025-12-30', 1, 295, 2, '2025-12-30', 0, NULL, NULL, NULL, 0, NULL),
(235, 'Benjamin Sy', 'benjamin.sy235@example.com', '$2y$10$hash235xyzabcdefghijklmn', '09171234753', 'male', '1997-06-28', '555 Peaceful Field', 'Santa Rosa', 'Laguna', '4026', 'non_member', 'customer', '2025-11-28 10:30:00', '2025-12-31 09:15:00', NULL, 2, '2025-12-31', 1, 220, 1, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(236, 'Rosalie Ong', 'rosalie.ong236@example.com', '$2y$10$hash236xyzabcdefghijklmn', '09171234754', 'female', '1999-10-14', '666 Silent Park', 'San Pablo', 'Laguna', '4000', 'sidc_member', 'customer', '2025-11-29 00:45:00', '2025-12-31 11:30:00', NULL, 6, '2025-12-31', 1, 460, 3, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(237, 'Caleb Lim', 'caleb.lim237@example.com', '$2y$10$hash237xyzabcdefghijklmn', '09171234755', 'male', '1994-01-30', '777 Calm Woods', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-11-30 03:00:00', NULL, NULL, 0, NULL, 0, 135, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(238, 'Magnolia Tan', 'magnolia.tan238@example.com', '$2y$10$hash238xyzabcdefghijklmn', '09171234756', 'female', '2001-05-17', '888 Quiet Forest', 'San Pedro', 'Laguna', '4023', 'regular', 'customer', '2025-12-01 05:15:00', '2025-12-25 08:45:00', NULL, 4, '2025-12-25', 1, 370, 2, '2025-12-25', 0, NULL, NULL, NULL, 0, NULL),
(239, 'Elijah Gomez', 'elijah.gomez239@example.com', '$2y$10$hash239xyzabcdefghijklmn', '09171234757', 'male', '1996-09-02', '999 Peaceful Hill', 'Binan', 'Laguna', '4024', 'non_member', 'customer', '2025-12-02 07:30:00', '2025-12-28 10:00:00', NULL, 1, '2025-12-28', 1, 155, 1, '2025-12-28', 0, NULL, NULL, NULL, 0, NULL),
(240, 'Penelope Chua', 'penelope.chua240@example.com', '$2y$10$hash240xyzabcdefghijklmn', '09171234758', 'female', '2003-12-19', '111 Silent Mountain', 'Santa Rosa', 'Laguna', '4026', 'sidc_member', 'customer', '2025-12-03 09:45:00', NULL, NULL, 0, NULL, 0, 85, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(241, 'Atticus Sy', 'atticus.sy241@example.com', '$2y$10$hash241xyzabcdefghijklmn', '09171234759', 'male', '1998-04-05', '222 Calm Valley', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-04 12:00:00', '2025-12-29 12:30:00', NULL, 3, '2025-12-29', 1, 305, 2, '2025-12-29', 0, NULL, NULL, NULL, 0, NULL),
(242, 'Octavia Ong', 'octavia.ong242@example.com', '$2y$10$hash242xyzabcdefghijklmn', '09171234760', 'female', '2000-07-21', '333 Quiet River', 'Calamba', 'Laguna', '4027', 'regular', 'customer', '2025-12-05 01:15:00', NULL, NULL, 0, NULL, 0, 120, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(243, 'Jasper Lim', 'jasper.lim243@example.com', '$2y$10$hash243xyzabcdefghijklmn', '09171234761', 'male', '1995-11-06', '444 Peaceful Lake', 'San Pedro', 'Laguna', '4023', 'non_member', 'customer', '2025-12-06 03:30:00', '2025-12-30 14:45:00', NULL, 2, '2025-12-30', 1, 240, 1, '2025-12-30', 0, NULL, NULL, NULL, 0, NULL),
(244, 'Clementine Tan', 'clementine.tan244@example.com', '$2y$10$hash244xyzabcdefghijklmn', '09171234762', 'female', '2002-02-22', '555 Silent Ocean', 'Binan', 'Laguna', '4024', 'sidc_member', 'customer', '2025-12-07 05:45:00', '2025-12-31 15:59:59', NULL, 5, '2025-12-31', 1, 415, 3, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(245, 'Silas Gomez', 'silas.gomez245@example.com', '$2y$10$hash245xyzabcdefghijklmn', '09171234763', 'male', '1997-06-09', '666 Calm Sea', 'Santa Rosa', 'Laguna', '4026', 'regular', 'customer', '2025-12-08 08:00:00', NULL, NULL, 0, NULL, 0, 170, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(246, 'Elowen Chua', 'elowen.chua246@example.com', '$2y$10$hash246xyzabcdefghijklmn', '09171234764', 'female', '1999-09-25', '777 Quiet Bay', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-12-09 10:15:00', '2025-12-26 13:15:00', NULL, 4, '2025-12-26', 1, 330, 2, '2025-12-26', 0, NULL, NULL, NULL, 0, NULL),
(247, 'Leander Sy', 'leander.sy247@example.com', '$2y$10$hash247xyzabcdefghijklmn', '09171234765', 'male', '1994-01-11', '888 Peaceful Cove', 'Calamba', 'Laguna', '4027', 'non_member', 'customer', '2025-12-10 12:30:00', '2025-12-27 14:30:00', NULL, 1, '2025-12-27', 1, 190, 1, '2025-12-27', 0, NULL, NULL, NULL, 0, NULL),
(248, 'Lavender Ong', 'lavender.ong248@example.com', '$2y$10$hash248xyzabcdefghijklmn', '09171234766', 'female', '2001-04-28', '999 Silent Harbor', 'San Pedro', 'Laguna', '4023', 'sidc_member', 'customer', '2025-12-11 14:45:00', NULL, NULL, 0, NULL, 0, 105, 0, NULL, 0, NULL, NULL, NULL, 0, NULL),
(249, 'Orion Lim', 'orion.lim249@example.com', '$2y$10$hash249xyzabcdefghijklmn', '09171234767', 'male', '1996-08-14', '111 Calm Marina', 'Binan', 'Laguna', '4024', 'regular', 'customer', '2025-12-12 01:00:00', '2025-12-28 02:45:00', NULL, 3, '2025-12-28', 1, 285, 2, '2025-12-28', 0, NULL, NULL, NULL, 0, NULL),
(250, 'Sylvia Tan', 'sylvia.tan250@example.com', '$2y$10$hash250xyzabcdefghijklmn', '09171234768', 'female', '2003-12-01', '222 Quiet Port', 'Santa Rosa', 'Laguna', '4026', 'regular', 'admin', '2025-12-13 03:15:00', '2025-12-31 04:00:00', NULL, 8, '2025-12-31', 1, 580, 3, '2025-12-31', 0, NULL, NULL, NULL, 0, NULL),
(254, 'minay', 'mimayamiral@gmail.com', '$2y$10$pZJRl0XiXQyIOf5T5d6V.erMFU4srU02GyB17RqoN90nsIR4Xplxi', '094161248123', 'female', '2004-07-31', 'Pinagdanlayan', 'Dolores', 'Quezon', '3093', 'regular', 'customer', '2026-01-03 02:46:45', '2026-01-03 03:52:21', '2026-01-03 02:47:59', 1, '2026-01-03', 0, 0, 3, '2026-01-03', 1, NULL, NULL, NULL, 0, '2026-01-03 10:46:45');

-- --------------------------------------------------------

--
-- Table structure for table `user_discounts`
--

CREATE TABLE `user_discounts` (
  `user_discount_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cart_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `original_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 5.00,
  `discount_type` enum('inactivity','promotional','loyalty') DEFAULT 'inactivity',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT NULL,
  `points_bonus` int(11) DEFAULT 0,
  `discount_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_discounts`
--

INSERT INTO `user_discounts` (`user_discount_id`, `user_id`, `cart_item_id`, `product_id`, `original_price`, `discount_amount`, `discount_type`, `applied_at`, `expires_at`, `is_used`, `used_at`, `discount_percentage`, `points_bonus`, `discount_code`, `description`) VALUES
(23, 2, 37, 15, 60.00, 5.00, 'inactivity', '2025-10-14 16:15:19', '2025-10-15 16:15:19', 1, '2025-10-15 10:14:20', NULL, 0, NULL, NULL),
(24, 2, 38, 23, 15.00, 5.00, 'inactivity', '2025-10-14 16:15:19', '2025-10-15 16:15:19', 1, '2025-10-15 10:14:20', NULL, 0, NULL, NULL),
(25, 2, 39, 14, 130.00, 5.00, 'inactivity', '2025-10-14 16:15:19', '2025-10-15 16:15:19', 1, '2025-10-15 10:14:20', NULL, 0, NULL, NULL),
(26, 2, 40, 13, 230.00, 5.00, 'inactivity', '2025-10-14 16:15:19', '2025-10-15 16:15:19', 1, '2025-10-15 10:14:20', NULL, 0, NULL, NULL),
(27, 2, 41, 54, 35.00, 5.00, 'inactivity', '2025-10-14 16:15:19', '2025-10-15 16:15:19', 1, '2025-10-15 10:14:20', NULL, 0, NULL, NULL),
(47, 2, 185, 25, 12.00, 5.00, 'inactivity', '2025-10-27 11:25:30', '2025-10-28 11:25:30', 1, '2025-10-27 12:30:38', NULL, 0, NULL, NULL),
(48, 2, 186, 28, 15.00, 5.00, 'inactivity', '2025-10-27 11:25:30', '2025-10-28 11:25:30', 1, '2025-10-27 12:30:38', NULL, 0, NULL, NULL),
(49, 2, 187, 13, 230.00, 5.00, 'inactivity', '2025-10-27 11:25:30', '2025-10-28 11:25:30', 1, '2025-10-27 12:30:38', NULL, 0, NULL, NULL),
(73, 36, 0, 0, 0.00, 0.00, '', '2025-11-17 10:15:25', '2025-11-24 10:15:25', 1, '2025-11-17 04:35:37', 20.00, 0, 'RETENTION96128B', 'Retention offer: Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(74, 36, 0, 0, 0.00, 0.00, '', '2025-11-17 10:15:31', '2025-11-24 10:15:31', 1, '2025-11-17 03:18:25', 20.00, 0, 'RETENTION9FC1AD', 'Retention offer: Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(75, 36, 0, 0, 0.00, 0.00, '', '2025-11-17 11:37:14', '2025-11-24 11:37:14', 1, '2025-11-17 04:39:58', 20.00, 0, 'RETENTIONE4489E', 'Retention offer: Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(76, 36, 0, 0, 0.00, 0.00, '', '2025-11-17 11:38:12', '2025-11-24 11:38:12', 1, '2025-11-17 04:39:56', 20.00, 0, 'RETENTIONC18F2B', 'Retention offer: Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(77, 36, 0, 0, 0.00, 0.00, '', '2025-11-17 11:38:13', '2025-11-24 11:38:13', 1, '2025-11-17 04:39:55', 20.00, 0, 'RETENTIONC60502', 'Retention offer: Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(82, 2, 199, 15, 60.00, 5.00, 'inactivity', '2025-11-19 18:02:34', '2025-11-20 18:02:34', 1, '2025-11-19 18:04:32', NULL, 0, NULL, NULL),
(83, 2, 198, 23, 15.00, 5.00, 'inactivity', '2025-11-19 18:02:34', '2025-11-20 18:02:34', 1, '2025-11-19 18:04:32', NULL, 0, NULL, NULL),
(84, 2, 204, 25, 12.00, 5.00, 'inactivity', '2025-11-19 18:02:34', '2025-11-20 18:02:34', 1, '2025-11-19 18:04:32', NULL, 0, NULL, NULL),
(85, 2, 210, 28, 15.00, 5.00, 'inactivity', '2025-11-19 18:02:34', '2025-11-20 18:02:34', 1, '2025-11-19 18:04:32', NULL, 0, NULL, NULL),
(86, 50, 301, 16, 45.00, 5.00, 'inactivity', '2025-11-18 02:20:15', '2025-11-19 02:20:15', 1, '2025-11-18 04:30:22', NULL, 0, NULL, NULL),
(87, 50, 302, 25, 12.00, 5.00, 'inactivity', '2025-11-18 02:20:15', '2025-11-19 02:20:15', 1, '2025-11-18 04:30:22', NULL, 0, NULL, NULL),
(88, 51, 303, 20, 35.00, 5.00, 'inactivity', '2025-11-19 00:15:30', '2025-11-20 00:15:30', 1, '2025-11-19 02:15:00', NULL, 0, NULL, NULL),
(89, 51, 304, 37, 75.00, 5.00, 'inactivity', '2025-11-19 00:15:30', '2025-11-20 00:15:30', 1, '2025-11-19 02:15:00', NULL, 0, NULL, NULL),
(90, 51, 305, 46, 65.00, 5.00, 'inactivity', '2025-11-19 00:15:30', '2025-11-20 00:15:30', 1, '2025-11-19 02:15:00', NULL, 0, NULL, NULL),
(91, 52, 0, 0, 0.00, 0.00, '', '2025-11-19 18:45:12', '2025-11-26 18:45:12', 1, '2025-11-19 23:30:00', 20.00, 100, 'RETENTION52A1B3', 'Retention offer: Welcome to CoopMart, Miguel Reyes! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(92, 53, 0, 0, 0.00, 0.00, '', '2025-11-21 01:33:50', '2025-11-28 01:33:50', 1, '2025-11-21 06:45:00', 20.00, 100, 'RETENTION53C2D4', 'Retention offer: Welcome to CoopMart, Camila Santos! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(93, 55, 0, 0, 0.00, 0.00, '', '2025-11-22 16:55:28', '2025-11-29 16:55:28', 1, '2025-11-23 03:30:00', 20.00, 100, 'RETENTION55E3F5', 'Retention offer: Welcome to CoopMart, Lucas Hernandez! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(94, 54, 306, 42, 180.00, 5.00, 'inactivity', '2025-11-21 21:20:35', '2025-11-22 21:20:35', 1, '2025-11-22 01:20:00', NULL, 0, NULL, NULL),
(95, 54, 307, 31, 180.00, 5.00, 'inactivity', '2025-11-21 21:20:35', '2025-11-22 21:20:35', 1, '2025-11-22 01:20:00', NULL, 0, NULL, NULL),
(96, 56, 308, 17, 85.00, 5.00, 'inactivity', '2025-11-23 23:42:16', '2025-11-24 23:42:16', 1, '2025-11-24 07:40:00', NULL, 0, NULL, NULL),
(97, 56, 309, 26, 25.00, 5.00, 'inactivity', '2025-11-23 23:42:16', '2025-11-24 23:42:16', 1, '2025-11-24 07:40:00', NULL, 0, NULL, NULL),
(98, 57, 310, 38, 120.00, 5.00, 'inactivity', '2025-11-24 20:18:52', '2025-11-25 20:18:52', 1, '2025-11-25 00:50:00', NULL, 0, NULL, NULL),
(99, 57, 311, 45, 150.00, 5.00, 'inactivity', '2025-11-24 20:18:52', '2025-11-25 20:18:52', 1, '2025-11-25 00:50:00', NULL, 0, NULL, NULL),
(100, 58, 0, 0, 0.00, 0.00, '', '2025-11-25 17:25:33', '2025-12-02 17:25:33', 1, '2025-11-26 04:15:00', 20.00, 100, 'RETENTION58G4H6', 'Retention offer: Welcome to CoopMart, Diego Martinez! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(101, 61, 0, 0, 0.00, 0.00, '', '2025-11-28 18:14:39', '2025-12-05 18:14:39', 1, '2025-11-29 01:20:00', 20.00, 100, 'RETENTION61I5J7', 'Retention offer: Welcome to CoopMart, Mateo Ramos! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(102, 59, 312, 30, 35.00, 5.00, 'inactivity', '2025-11-27 01:50:47', '2025-11-28 01:50:47', 1, '2025-11-27 02:30:00', NULL, 0, NULL, NULL),
(103, 59, 313, 32, 45.00, 5.00, 'inactivity', '2025-11-27 01:50:47', '2025-11-28 01:50:47', 1, '2025-11-27 02:30:00', NULL, 0, NULL, NULL),
(104, 60, 314, 39, 85.00, 5.00, 'inactivity', '2025-11-27 22:37:21', '2025-11-28 22:37:21', 1, '2025-11-28 06:45:00', NULL, 0, NULL, NULL),
(105, 60, 315, 50, 75.00, 5.00, 'inactivity', '2025-11-27 22:37:21', '2025-11-28 22:37:21', 1, '2025-11-28 06:45:00', NULL, 0, NULL, NULL),
(106, 62, 316, 21, 35.00, 5.00, 'inactivity', '2025-11-30 00:28:54', '2025-12-01 00:28:54', 1, '2025-11-30 05:50:00', NULL, 0, NULL, NULL),
(107, 62, 317, 49, 85.00, 5.00, 'inactivity', '2025-11-30 00:28:54', '2025-12-01 00:28:54', 1, '2025-11-30 05:50:00', NULL, 0, NULL, NULL),
(108, 63, 0, 0, 0.00, 0.00, '', '2025-11-30 20:45:28', '2025-12-07 20:45:28', 1, '2025-12-01 00:25:00', 20.00, 100, 'RETENTION63K6L8', 'Retention offer: Welcome to CoopMart, Elena Perez! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(109, 67, 0, 0, 0.00, 0.00, '', '2025-12-04 19:22:18', '2025-12-11 19:22:18', 1, '2025-12-05 06:20:00', 20.00, 100, 'RETENTION67M7N9', 'Retention offer: Welcome to CoopMart, Sebastian Rivera! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(110, 64, 318, 29, 85.00, 5.00, 'inactivity', '2025-12-01 18:52:16', '2025-12-02 18:52:16', 1, '2025-12-02 07:40:00', NULL, 0, NULL, NULL),
(111, 64, 319, 41, 220.00, 5.00, 'inactivity', '2025-12-01 18:52:16', '2025-12-02 18:52:16', 1, '2025-12-02 07:40:00', NULL, 0, NULL, NULL),
(112, 65, 320, 18, 120.00, 5.00, 'inactivity', '2025-12-03 02:38:45', '2025-12-04 02:38:45', 1, '2025-12-03 04:30:00', NULL, 0, NULL, NULL),
(113, 66, 321, 22, 35.00, 5.00, 'inactivity', '2025-12-03 23:15:32', '2025-12-04 23:15:32', 1, '2025-12-04 01:45:00', NULL, 0, NULL, NULL),
(114, 66, 322, 48, 120.00, 5.00, 'inactivity', '2025-12-03 23:15:32', '2025-12-04 23:15:32', 1, '2025-12-04 01:45:00', NULL, 0, NULL, NULL),
(115, 68, 323, 33, 25.00, 5.00, 'inactivity', '2025-12-05 17:48:55', '2025-12-06 17:48:55', 1, '2025-12-06 03:25:00', NULL, 0, NULL, NULL),
(116, 68, 324, 40, 95.00, 5.00, 'inactivity', '2025-12-05 17:48:55', '2025-12-06 17:48:55', 1, '2025-12-06 03:25:00', NULL, 0, NULL, NULL),
(117, 73, 0, 0, 0.00, 0.00, '', '2025-12-10 21:55:21', '2025-12-17 21:55:21', 1, '2025-12-11 01:20:00', 20.00, 100, 'RETENTION73O8P0', 'Retention offer: Welcome to CoopMart, Ethan Aguilar! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(118, 76, 0, 0, 0.00, 0.00, '', '2025-12-13 22:38:19', '2025-12-20 22:38:19', 1, '2025-12-14 02:30:00', 20.00, 100, 'RETENTION76Q9R1', 'Retention offer: Welcome to CoopMart, Noah Jimenez! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(119, 69, 325, 27, 18.00, 5.00, 'inactivity', '2025-12-07 00:56:41', '2025-12-08 00:56:41', 1, '2025-12-07 05:40:00', NULL, 0, NULL, NULL),
(120, 69, 326, 53, 40.00, 5.00, 'inactivity', '2025-12-07 00:56:41', '2025-12-08 00:56:41', 1, '2025-12-07 05:40:00', NULL, 0, NULL, NULL),
(121, 70, 327, 34, 35.00, 5.00, 'inactivity', '2025-12-07 21:33:29', '2025-12-08 21:33:29', 1, '2025-12-08 02:15:00', NULL, 0, NULL, NULL),
(122, 70, 328, 51, 65.00, 5.00, 'inactivity', '2025-12-07 21:33:29', '2025-12-08 21:33:29', 1, '2025-12-08 02:15:00', NULL, 0, NULL, NULL),
(123, 71, 329, 19, 55.00, 5.00, 'inactivity', '2025-12-08 19:18:44', '2025-12-09 19:18:44', 1, '2025-12-09 07:30:00', NULL, 0, NULL, NULL),
(124, 72, 330, 24, 95.00, 5.00, 'inactivity', '2025-12-10 01:42:37', '2025-12-11 01:42:37', 1, '2025-12-10 04:45:00', NULL, 0, NULL, NULL),
(125, 72, 331, 43, 150.00, 5.00, 'inactivity', '2025-12-10 01:42:37', '2025-12-11 01:42:37', 1, '2025-12-10 04:45:00', NULL, 0, NULL, NULL),
(126, 79, 0, 0, 0.00, 0.00, '', '2025-12-16 23:51:27', '2025-12-23 23:51:27', 1, '2025-12-17 07:10:00', 20.00, 100, 'RETENTION79S0T2', 'Retention offer: Welcome to CoopMart, Liam Estrada! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(127, 90, 0, 0, 0.00, 0.00, '', '2025-11-27 20:38:47', '2025-12-04 20:38:47', 1, '2025-11-29 00:40:00', 20.00, 100, 'RETENTION90U1V3', 'Retention offer: Welcome to CoopMart, Isla Ramos! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(128, 74, 332, 35, 15.00, 5.00, 'inactivity', '2025-12-11 18:27:53', '2025-12-12 18:27:53', 1, '2025-12-12 03:40:00', NULL, 0, NULL, NULL),
(129, 74, 333, 52, 45.00, 5.00, 'inactivity', '2025-12-11 18:27:53', '2025-12-12 18:27:53', 1, '2025-12-12 03:40:00', NULL, 0, NULL, NULL),
(130, 75, 334, 36, 65.00, 5.00, 'inactivity', '2025-12-13 02:14:46', '2025-12-14 02:14:46', 1, '2025-12-13 06:55:00', NULL, 0, NULL, NULL),
(131, 75, 335, 47, 55.00, 5.00, 'inactivity', '2025-12-13 02:14:46', '2025-12-14 02:14:46', 1, '2025-12-13 06:55:00', NULL, 0, NULL, NULL),
(132, 77, 336, 44, 95.00, 5.00, 'inactivity', '2025-12-14 20:45:32', '2025-12-15 20:45:32', 1, '2025-12-15 05:20:00', NULL, 0, NULL, NULL),
(133, 77, 337, 54, 35.00, 5.00, 'inactivity', '2025-12-14 20:45:32', '2025-12-15 20:45:32', 1, '2025-12-15 05:20:00', NULL, 0, NULL, NULL),
(134, 78, 338, 55, 35.00, 5.00, 'inactivity', '2025-12-15 17:23:58', '2025-12-16 17:23:58', 1, '2025-12-16 01:45:00', NULL, 0, NULL, NULL),
(135, 78, 339, 56, 35.00, 5.00, 'inactivity', '2025-12-15 17:23:58', '2025-12-16 17:23:58', 1, '2025-12-16 01:45:00', NULL, 0, NULL, NULL),
(136, 102, 0, 0, 0.00, 0.00, '', '2025-12-09 20:45:33', '2025-12-16 20:45:33', 1, '2025-12-11 03:20:00', 20.00, 100, 'RETENTION102W2X4', 'Retention offer: Welcome to CoopMart, Maricel Dela Paz! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(137, 105, 0, 0, 0.00, 0.00, '', '2025-12-12 19:55:27', '2025-12-19 19:55:27', 1, '2025-12-14 04:15:00', 20.00, 100, 'RETENTION105Y3Z5', 'Retention offer: Welcome to CoopMart, Dennis Bautista! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(138, 108, 0, 0, 0.00, 0.00, '', '2025-12-15 18:10:44', '2025-12-22 18:10:44', 1, '2025-12-17 03:00:00', 20.00, 100, 'RETENTION108A4B6', 'Retention offer: Welcome to CoopMart, Catherine Lim! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(140, 80, 340, 28, 15.00, 5.00, 'inactivity', '2025-12-17 21:29:43', '2025-12-18 21:29:43', 1, '2025-12-18 03:50:00', NULL, 0, NULL, NULL),
(141, 80, 341, 37, 75.00, 5.00, 'inactivity', '2025-12-17 21:29:43', '2025-12-18 21:29:43', 1, '2025-12-18 03:50:00', NULL, 0, NULL, NULL),
(142, 81, 342, 16, 45.00, 5.00, 'inactivity', '2025-11-18 19:35:28', '2025-11-19 19:35:28', 1, '2025-11-20 05:25:00', NULL, 0, NULL, NULL),
(143, 81, 343, 46, 65.00, 5.00, 'inactivity', '2025-11-18 19:35:28', '2025-11-19 19:35:28', 1, '2025-11-20 05:25:00', NULL, 0, NULL, NULL),
(144, 82, 344, 25, 12.00, 5.00, 'inactivity', '2025-11-20 01:18:34', '2025-11-21 01:18:34', 1, '2025-11-21 08:20:00', NULL, 0, NULL, NULL),
(145, 83, 345, 17, 85.00, 5.00, 'inactivity', '2025-11-20 22:44:17', '2025-11-21 22:44:17', 1, '2025-11-22 03:35:00', NULL, 0, NULL, NULL),
(146, 84, 346, 20, 35.00, 5.00, 'inactivity', '2025-11-21 18:56:42', '2025-11-22 18:56:42', 1, '2025-11-23 02:15:00', NULL, 0, NULL, NULL),
(147, 85, 347, 42, 180.00, 5.00, 'inactivity', '2025-11-23 02:32:55', '2025-11-24 02:32:55', 1, '2025-11-24 08:35:00', NULL, 0, NULL, NULL),
(148, 86, 348, 23, 15.00, 5.00, 'inactivity', '2025-11-23 23:27:38', '2025-11-24 23:27:38', 1, '2025-11-25 00:50:00', NULL, 0, NULL, NULL),
(149, 87, 349, 21, 35.00, 5.00, 'inactivity', '2025-11-24 20:15:21', '2025-11-25 20:15:21', 1, '2025-11-26 06:15:00', NULL, 0, NULL, NULL),
(150, 88, 350, 38, 120.00, 5.00, 'inactivity', '2025-11-25 17:42:59', '2025-11-26 17:42:59', 1, '2025-11-27 08:25:00', NULL, 0, NULL, NULL),
(151, 45, 412, 25, 89.99, 8.99, 'inactivity', '2025-11-26 01:15:00', '2025-11-28 01:15:00', 1, '2025-11-27 02:30:00', 10.00, 0, NULL, 'Welcome back discount after 30 days inactivity'),
(153, 123, 418, 18, 75.00, 7.50, 'inactivity', '2025-11-27 06:20:00', '2025-11-30 06:20:00', 1, '2025-11-28 01:45:00', 10.00, 0, NULL, 'Returning customer discount'),
(154, 67, 420, 55, 299.99, 30.00, 'promotional', '2025-11-28 02:45:00', '2025-12-05 15:59:59', 1, '2025-12-01 06:20:00', 10.00, 25, 'CHRISTMAS2025', 'Christmas early bird promotion'),
(156, 78, 425, 22, 249.99, 37.50, 'promotional', '2025-11-30 08:35:00', '2025-12-15 15:59:59', 1, '2025-12-03 03:15:00', 15.00, 20, NULL, 'Year-end clearance sale'),
(157, 23, 428, 47, 120.50, 24.10, 'promotional', '2025-12-01 00:50:00', '2025-12-08 15:59:59', 1, '2025-12-05 08:40:00', 20.00, 15, 'DECEMBER20', 'December promotional discount'),
(159, 83, 432, 28, 179.50, 26.93, 'loyalty', '2025-12-03 07:40:00', '2025-12-10 07:40:00', 1, '2025-12-06 02:20:00', 15.00, 40, NULL, 'Gold member loyalty discount'),
(160, 134, 435, 51, 359.99, 71.99, 'loyalty', '2025-12-04 01:15:00', '2025-12-11 01:15:00', 1, '2025-12-07 05:45:00', 20.00, 75, 'LOYALTY20', '20% off for loyal customers'),
(161, 189, 438, 16, 99.99, 5.00, 'inactivity', '2025-12-05 03:30:00', '2025-12-07 03:30:00', 1, '2025-12-06 01:10:00', 5.00, 0, NULL, 'Welcome back discount'),
(163, 102, 442, 29, 159.75, 31.95, 'loyalty', '2025-12-07 02:20:00', '2025-12-14 02:20:00', 1, '2025-12-09 07:30:00', 20.00, 35, NULL, 'Silver member discount'),
(165, 91, 448, 53, 289.50, 43.43, 'promotional', '2025-12-09 08:50:00', '2025-12-16 15:59:59', 1, '2025-12-12 03:45:00', 15.00, 30, 'MIDDEC15', 'Mid-December sale'),
(166, 132, 450, 24, 129.99, 25.99, 'promotional', '2025-12-10 01:25:00', '2025-12-20 15:59:59', 1, '2025-12-15 06:20:00', 20.00, 20, 'XMAS20', 'Christmas special 20% off'),
(168, 176, 455, 19, 89.50, 8.95, 'inactivity', '2025-12-12 07:55:00', '2025-12-14 07:55:00', 1, '2025-12-13 02:15:00', 10.00, 0, NULL, 'Returning user discount'),
(169, 88, 458, 56, 399.99, 80.00, 'promotional', '2025-12-13 03:10:00', '2025-12-23 15:59:59', 1, '2025-12-18 08:30:00', 20.00, 100, 'BIGSALE20', 'Holiday big sale discount'),
(170, 31, 460, 31, 149.25, 22.39, 'loyalty', '2025-12-14 06:25:00', '2025-12-21 06:25:00', 1, '2025-12-17 01:40:00', 15.00, 30, NULL, 'Frequent buyer discount'),
(172, 45, 465, 48, 269.50, 40.43, 'promotional', '2025-12-16 02:05:00', '2025-12-26 15:59:59', 1, '2025-12-20 05:25:00', 15.00, 35, 'WINTER15', 'Winter season promotion'),
(173, 112, 468, 34, 189.99, 37.99, 'loyalty', '2025-12-17 05:20:00', '2025-12-24 05:20:00', 1, '2025-12-20 07:50:00', 20.00, 40, NULL, 'Premium member discount'),
(175, 145, 472, 52, 329.99, 49.50, 'promotional', '2025-12-19 01:50:00', '2025-12-29 15:59:59', 1, '2025-12-23 03:15:00', 15.00, 50, 'LASTCHANCE15', 'Last chance holiday sale'),
(176, 123, 475, 37, 159.00, 15.90, 'inactivity', '2025-12-20 04:05:00', '2025-12-22 04:05:00', 1, '2025-12-21 06:40:00', 10.00, 0, NULL, 'Holiday return discount'),
(178, 83, 480, 26, 119.99, 23.99, 'promotional', '2025-12-22 10:35:00', '2025-12-31 15:59:59', 1, '2025-12-26 02:30:00', 20.00, 25, 'NYE2025', 'New Year Eve special'),
(179, 134, 482, 49, 199.75, 19.98, 'inactivity', '2025-12-23 00:00:00', '2025-12-25 00:00:00', 1, '2025-12-24 08:45:00', 10.00, 0, NULL, 'Christmas return discount'),
(180, 189, 485, 32, 239.99, 47.99, 'loyalty', '2025-12-24 03:15:00', '2025-12-31 03:15:00', 1, '2025-12-27 01:20:00', 20.00, 55, NULL, 'VIP member year-end discount'),
(181, 92, 488, 21, 89.99, 13.50, 'promotional', '2025-12-25 06:30:00', '2026-01-04 15:59:59', 0, NULL, 15.00, 20, 'YE2025', 'Year-end clearance'),
(182, 78, 490, 54, 349.50, 69.90, 'loyalty', '2025-12-26 09:45:00', '2026-01-02 09:45:00', 1, '2025-12-29 05:10:00', 20.00, 70, NULL, 'Diamond member exclusive'),
(184, 167, 495, 46, 189.99, 28.50, 'promotional', '2025-12-28 05:25:00', '2026-01-07 15:59:59', 1, '2025-12-30 07:35:00', 15.00, 30, 'NEWYEAR15', 'New Year promotion'),
(185, 91, 498, 20, 109.50, 21.90, 'loyalty', '2025-12-29 08:40:00', '2026-01-05 08:40:00', 0, NULL, 20.00, 25, NULL, 'Regular customer discount'),
(186, 132, 500, 57, 459.99, 45.99, 'inactivity', '2025-12-30 01:55:00', '2026-01-01 01:55:00', 1, '2025-12-31 03:50:00', 10.00, 0, NULL, 'End of month return discount'),
(187, 54, 502, 35, 169.75, 33.95, 'loyalty', '2025-12-30 04:10:00', '2026-01-06 04:10:00', 1, '2026-01-02 06:25:00', 20.00, 35, NULL, 'New Year loyalty bonus'),
(188, 176, 505, 50, 299.99, 44.99, 'promotional', '2025-12-30 07:25:00', '2026-01-09 15:59:59', 0, NULL, 15.00, 45, 'JAN2026', 'January coming soon sale'),
(189, 88, 508, 30, 139.00, 13.90, 'inactivity', '2025-12-31 10:40:00', '2026-01-02 10:40:00', 1, '2026-01-01 02:15:00', 10.00, 0, NULL, 'New Year return discount'),
(190, 31, 510, 43, 249.50, 49.90, 'loyalty', '2025-12-31 13:55:00', '2026-01-07 13:55:00', 0, NULL, 20.00, 50, NULL, 'Year-end loyalty reward'),
(192, 145, 515, 40, 219.00, 43.80, 'loyalty', '2025-12-31 05:20:00', '2026-01-07 05:20:00', 1, '2026-01-03 08:40:00', 20.00, 40, NULL, 'Frequent shopper discount'),
(193, 123, 518, 17, 79.99, 11.99, 'promotional', '2025-12-31 08:35:00', '2026-01-10 15:59:59', 0, NULL, 15.00, 15, 'HAPPYNY', 'Happy New Year promotion'),
(194, 156, 520, 59, 389.50, 38.95, 'inactivity', '2025-12-31 11:50:00', '2026-01-02 11:50:00', 1, '2026-01-01 04:30:00', 10.00, 0, NULL, 'New Year welcome back'),
(195, 83, 522, 60, 159.75, 23.96, 'loyalty', '2025-12-31 14:05:00', '2026-01-07 14:05:00', 0, NULL, 15.00, 30, NULL, 'Year-end thank you discount'),
(196, 134, 525, 61, 129.99, 25.99, 'promotional', '2025-12-31 03:15:00', '2026-01-08 15:59:59', 1, '2026-01-02 01:45:00', 20.00, 20, 'START2026', 'Start of year promotion'),
(198, 92, 530, 63, 279.99, 55.99, 'loyalty', '2025-12-31 09:45:00', '2026-01-07 09:45:00', 1, '2026-01-03 06:20:00', 20.00, 55, NULL, 'Premium loyalty discount'),
(199, 78, 532, 64, 149.50, 22.43, 'promotional', '2025-12-31 12:00:00', '2026-01-06 15:59:59', 0, NULL, 15.00, 25, 'WEEKEND25', 'Weekend special offer'),
(200, 23, 535, 65, 89.99, 8.99, 'inactivity', '2025-12-31 15:59:59', '2026-01-02 15:59:59', 1, '2026-01-01 00:00:00', 10.00, 0, NULL, 'New Year first login discount'),
(201, 45, 540, 66, 199.99, 20.00, 'promotional', '2026-01-01 01:00:00', '2026-01-10 15:59:59', 1, '2026-01-03 03:20:00', 10.00, 25, 'NEWYEAR2026', 'New Year promotional discount'),
(202, 112, 542, 67, 159.50, 15.95, 'inactivity', '2026-01-01 04:15:00', '2026-01-03 04:15:00', 0, NULL, 10.00, 0, NULL, 'New Year return discount'),
(203, 176, 545, 68, 289.99, 43.50, 'loyalty', '2026-01-02 06:30:00', '2026-01-09 06:30:00', 1, '2026-01-04 07:45:00', 15.00, 40, NULL, 'January loyalty bonus'),
(204, 91, 548, 69, 129.75, 25.95, 'promotional', '2026-01-02 09:45:00', '2026-01-12 15:59:59', 0, NULL, 20.00, 20, 'JAN20OFF', 'January promotional discount'),
(205, 132, 550, 70, 219.00, 10.95, 'inactivity', '2026-01-03 02:00:00', '2026-01-05 02:00:00', 1, '2026-01-04 01:30:00', 5.00, 0, NULL, 'Monthly inactivity discount'),
(206, 54, 552, 71, 179.99, 35.99, 'loyalty', '2026-01-04 05:15:00', '2026-01-11 05:15:00', 1, '2026-01-06 06:20:00', 20.00, 35, NULL, 'Silver member discount'),
(207, 189, 555, 72, 349.50, 52.43, 'promotional', '2026-01-05 08:30:00', '2026-01-15 15:59:59', 0, NULL, 15.00, 45, 'MIDJAN15', 'Mid-January sale'),
(208, 67, 558, 73, 99.99, 9.99, 'inactivity', '2026-01-06 11:45:00', '2026-01-08 11:45:00', 1, '2026-01-07 03:10:00', 10.00, 0, NULL, 'Post-holiday return discount'),
(209, 145, 560, 74, 259.99, 38.99, 'loyalty', '2026-01-07 00:20:00', '2026-01-14 00:20:00', 1, '2026-01-09 08:35:00', 15.00, 50, NULL, 'Regular customer discount'),
(210, 123, 562, 75, 149.50, 29.90, 'promotional', '2026-01-08 03:35:00', '2026-01-18 15:59:59', 0, NULL, 20.00, 25, 'WEEKEND20', 'Weekend special promotion'),
(211, 156, 565, 76, 189.75, 9.49, 'inactivity', '2026-01-09 06:50:00', '2026-01-11 06:50:00', 0, NULL, 5.00, 0, NULL, 'Welcome back discount'),
(212, 83, 568, 77, 319.99, 48.00, 'loyalty', '2026-01-10 10:05:00', '2026-01-17 10:05:00', 1, '2026-01-12 02:25:00', 15.00, 60, NULL, 'Gold member loyalty discount'),
(213, 134, 570, 78, 89.99, 17.99, 'promotional', '2026-01-11 01:30:00', '2026-01-21 15:59:59', 1, '2026-01-14 05:40:00', 20.00, 15, 'SALE2026', 'New Year sale'),
(214, 167, 572, 79, 139.00, 6.95, 'inactivity', '2026-01-12 04:45:00', '2026-01-14 04:45:00', 1, '2026-01-13 07:50:00', 5.00, 0, NULL, 'Monthly inactivity promotion'),
(215, 92, 575, 80, 279.50, 55.90, 'loyalty', '2026-01-13 08:00:00', '2026-01-20 08:00:00', 0, NULL, 20.00, 55, NULL, 'VIP member discount'),
(216, 78, 578, 81, 199.99, 19.99, 'inactivity', '2026-01-14 11:15:00', '2026-01-16 11:15:00', 1, '2026-01-15 04:05:00', 10.00, 0, NULL, 'Returning customer discount'),
(217, 23, 580, 82, 169.75, 25.46, 'promotional', '2026-01-15 00:40:00', '2026-01-25 15:59:59', 1, '2026-01-17 06:30:00', 15.00, 30, 'JANSAVE15', 'January savings promotion'),
(218, 45, 582, 83, 119.99, 23.99, 'loyalty', '2026-01-15 03:55:00', '2026-01-22 03:55:00', 0, NULL, 20.00, 25, NULL, 'Frequent buyer discount'),
(219, 112, 585, 84, 239.00, 11.95, 'inactivity', '2026-01-15 07:10:00', '2026-01-17 07:10:00', 1, '2026-01-16 01:45:00', 5.00, 0, NULL, 'Inactivity welcome back offer'),
(220, 176, 588, 85, 359.99, 71.99, 'promotional', '2026-01-15 10:25:00', '2026-01-25 15:59:59', 1, '2026-01-18 08:20:00', 20.00, 70, 'BIGSAVE20', 'Big savings promotion'),
(221, 31, 590, 86, 89.99, 13.50, 'loyalty', '2026-01-01 02:05:00', '2026-01-08 02:05:00', 1, '2026-01-03 03:30:00', 15.00, 20, NULL, 'New member loyalty discount'),
(222, 178, 592, 87, 179.50, 8.98, 'inactivity', '2026-01-02 05:20:00', '2026-01-04 05:20:00', 0, NULL, 5.00, 0, NULL, 'Monthly return discount'),
(223, 102, 595, 88, 299.99, 59.99, 'promotional', '2026-01-03 08:35:00', '2026-01-13 15:59:59', 1, '2026-01-05 07:40:00', 20.00, 60, 'SAVE20NOW', 'Save 20% now promotion'),
(224, 189, 598, 89, 129.00, 12.90, 'inactivity', '2026-01-04 11:50:00', '2026-01-06 11:50:00', 1, '2026-01-05 02:15:00', 10.00, 0, NULL, 'Returning user discount'),
(225, 56, 600, 90, 219.75, 32.96, 'loyalty', '2026-01-05 01:15:00', '2026-01-12 01:15:00', 0, NULL, 15.00, 35, NULL, 'Regular shopper discount'),
(226, 145, 602, 91, 159.99, 31.99, 'promotional', '2026-01-06 04:30:00', '2026-01-16 15:59:59', 1, '2026-01-08 06:25:00', 20.00, 30, 'WINTER20', 'Winter sale 20% off'),
(227, 83, 605, 92, 89.50, 4.48, 'inactivity', '2026-01-07 07:45:00', '2026-01-09 07:45:00', 0, NULL, 5.00, 0, NULL, 'Inactivity promotion'),
(228, 134, 608, 93, 269.99, 40.50, 'loyalty', '2026-01-08 11:00:00', '2026-01-15 11:00:00', 1, '2026-01-10 09:35:00', 15.00, 45, NULL, 'Valued customer discount'),
(229, 167, 610, 94, 139.99, 27.99, 'promotional', '2026-01-09 02:25:00', '2026-01-19 15:59:59', 1, '2026-01-12 01:50:00', 20.00, 25, 'JANUARY20', 'January special offer'),
(230, 92, 612, 95, 189.00, 9.45, 'inactivity', '2026-01-10 05:40:00', '2026-01-12 05:40:00', 0, NULL, 5.00, 0, NULL, 'Monthly welcome back'),
(231, 78, 615, 96, 329.50, 65.90, 'loyalty', '2026-01-11 08:55:00', '2026-01-18 08:55:00', 1, '2026-01-13 05:15:00', 20.00, 65, NULL, 'Premium loyalty discount'),
(232, 23, 618, 97, 119.75, 11.98, 'inactivity', '2026-01-12 12:10:00', '2026-01-14 12:10:00', 1, '2026-01-13 03:40:00', 10.00, 0, NULL, 'Return discount'),
(233, 45, 620, 98, 249.99, 37.50, 'promotional', '2026-01-13 01:35:00', '2026-01-23 15:59:59', 0, NULL, 15.00, 40, 'SAVE15TODAY', 'Save 15% today'),
(234, 112, 622, 99, 179.00, 17.90, 'inactivity', '2026-01-14 04:50:00', '2026-01-16 04:50:00', 1, '2026-01-15 07:05:00', 10.00, 0, NULL, 'Monthly return offer'),
(235, 176, 625, 100, 399.99, 79.99, 'loyalty', '2026-01-15 08:05:00', '2026-01-22 08:05:00', 0, NULL, 20.00, 80, NULL, 'Top customer discount'),
(236, 91, 628, 101, 149.99, 22.50, 'promotional', '2026-01-01 06:20:00', '2026-01-11 15:59:59', 1, '2026-01-03 08:45:00', 15.00, 30, 'NEWSTART15', 'New start promotion'),
(237, 132, 630, 102, 99.50, 4.98, 'inactivity', '2026-01-02 09:35:00', '2026-01-04 09:35:00', 0, NULL, 5.00, 0, NULL, 'Welcome back discount'),
(238, 54, 632, 103, 229.99, 45.99, 'loyalty', '2026-01-03 12:50:00', '2026-01-10 12:50:00', 1, '2026-01-05 06:30:00', 20.00, 45, NULL, 'Regular member discount'),
(239, 189, 635, 104, 169.75, 25.46, 'promotional', '2026-01-04 02:15:00', '2026-01-14 15:59:59', 1, '2026-01-06 03:55:00', 15.00, 35, 'MIDMONTH15', 'Mid-month promotion'),
(240, 67, 638, 105, 89.99, 8.99, 'inactivity', '2026-01-05 05:30:00', '2026-01-07 05:30:00', 0, NULL, 10.00, 0, NULL, 'Monthly inactivity discount'),
(241, 145, 640, 106, 279.50, 55.90, 'loyalty', '2026-01-06 08:45:00', '2026-01-13 08:45:00', 1, '2026-01-08 10:20:00', 20.00, 55, NULL, 'Loyal customer reward'),
(242, 123, 642, 107, 139.99, 27.99, 'promotional', '2026-01-07 12:00:00', '2026-01-17 15:59:59', 0, NULL, 20.00, 25, 'SAVE20JAN', 'Save 20% in January'),
(243, 156, 645, 108, 119.00, 5.95, 'inactivity', '2026-01-08 01:25:00', '2026-01-10 01:25:00', 1, '2026-01-09 02:50:00', 5.00, 0, NULL, 'Returning user offer'),
(244, 83, 648, 109, 349.99, 52.50, 'loyalty', '2026-01-09 04:40:00', '2026-01-16 04:40:00', 1, '2026-01-11 07:05:00', 15.00, 70, NULL, 'VIP loyalty discount'),
(245, 134, 650, 110, 199.75, 19.98, 'inactivity', '2026-01-10 07:55:00', '2026-01-12 07:55:00', 0, NULL, 10.00, 0, NULL, 'Monthly return discount'),
(246, 167, 652, 111, 159.99, 31.99, 'promotional', '2026-01-11 11:10:00', '2026-01-21 15:59:59', 1, '2026-01-13 04:25:00', 20.00, 30, 'JANSPECIAL', 'January special promotion'),
(247, 92, 655, 112, 239.50, 11.98, 'inactivity', '2026-01-12 00:35:00', '2026-01-14 00:35:00', 1, '2026-01-13 06:40:00', 5.00, 0, NULL, 'Welcome back offer'),
(248, 78, 658, 113, 189.99, 28.50, 'loyalty', '2026-01-13 03:50:00', '2026-01-20 03:50:00', 0, NULL, 15.00, 40, NULL, 'Regular shopper discount'),
(249, 23, 660, 114, 129.00, 25.80, 'promotional', '2026-01-14 07:05:00', '2026-01-24 15:59:59', 1, '2026-01-16 01:30:00', 20.00, 20, 'LASTCHANCE20', 'Last chance January sale'),
(250, 45, 662, 115, 299.99, 14.99, 'inactivity', '2026-01-15 10:20:00', '2026-01-17 10:20:00', 0, NULL, 5.00, 0, NULL, 'Monthly inactivity promotion'),
(251, 24, 234, 29, 85.00, 5.00, 'inactivity', '2026-01-03 02:38:15', '2026-01-04 02:38:15', 0, NULL, NULL, 0, NULL, NULL),
(252, 24, 235, 30, 35.00, 5.00, 'inactivity', '2026-01-03 02:38:15', '2026-01-04 02:38:15', 0, NULL, NULL, 0, NULL, NULL),
(253, 24, 236, 31, 180.00, 5.00, 'inactivity', '2026-01-03 02:38:15', '2026-01-04 02:38:15', 0, NULL, NULL, 0, NULL, NULL),
(254, 2, 228, 15, 60.00, 5.00, 'inactivity', '2026-01-03 02:41:40', '2026-01-04 02:41:40', 0, NULL, NULL, 0, NULL, NULL),
(255, 2, 226, 23, 15.00, 5.00, 'inactivity', '2026-01-03 02:41:40', '2026-01-04 02:41:40', 0, NULL, NULL, 0, NULL, NULL),
(256, 2, 229, 23, 15.00, 5.00, 'inactivity', '2026-01-03 02:41:40', '2026-01-04 02:41:40', 0, NULL, NULL, 0, NULL, NULL),
(257, 2, 225, 25, 12.00, 5.00, 'inactivity', '2026-01-03 02:41:40', '2026-01-04 02:41:40', 0, NULL, NULL, 0, NULL, NULL),
(258, 2, 230, 28, 15.00, 5.00, 'inactivity', '2026-01-03 02:41:40', '2026-01-04 02:41:40', 0, NULL, NULL, 0, NULL, NULL),
(259, 2, 227, 57, 100.00, 5.00, 'inactivity', '2026-01-03 02:41:40', '2026-01-04 02:41:40', 0, NULL, NULL, 0, NULL, NULL),
(286, 254, 267, 14, 130.00, 5.00, 'inactivity', '2026-01-03 03:51:56', '2026-01-03 03:52:56', 1, '2026-01-03 03:52:21', NULL, 0, NULL, NULL),
(287, 254, 266, 25, 12.00, 5.00, 'inactivity', '2026-01-03 03:51:56', '2026-01-03 03:52:56', 1, '2026-01-03 03:52:21', NULL, 0, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `idx_carts_user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD UNIQUE KEY `uc_cart_item` (`cart_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_cart_items_added_at` (`added_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `model_predictions`
--
ALTER TABLE `model_predictions`
  ADD PRIMARY KEY (`prediction_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_orders_status_date` (`status`,`created_at`),
  ADD KEY `idx_orders_user_date` (`user_id`,`created_at`),
  ADD KEY `idx_orders_user_id` (`user_id`),
  ADD KEY `idx_orders_created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_products_monthly_sold` (`monthly_sold`);

--
-- Indexes for table `retention_offers`
--
ALTER TABLE `retention_offers`
  ADD PRIMARY KEY (`offer_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_retention_offers_user_id` (`user_id`),
  ADD KEY `idx_retention_offers_status` (`status`);

--
-- Indexes for table `spin_discounts`
--
ALTER TABLE `spin_discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role_membership` (`role`,`membership_type`);

--
-- Indexes for table `user_discounts`
--
ALTER TABLE `user_discounts`
  ADD PRIMARY KEY (`user_discount_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `cart_item_id` (`cart_item_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=270;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `model_predictions`
--
ALTER TABLE `model_predictions`
  MODIFY `prediction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=320;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `retention_offers`
--
ALTER TABLE `retention_offers`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `spin_discounts`
--
ALTER TABLE `spin_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT for table `user_discounts`
--
ALTER TABLE `user_discounts`
  MODIFY `user_discount_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=288;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `retention_offers`
--
ALTER TABLE `retention_offers`
  ADD CONSTRAINT `retention_offers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `retention_offers_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `spin_discounts`
--
ALTER TABLE `spin_discounts`
  ADD CONSTRAINT `spin_discounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_discounts`
--
ALTER TABLE `user_discounts`
  ADD CONSTRAINT `user_discounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
