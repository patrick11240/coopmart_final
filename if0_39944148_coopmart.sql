-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql312.infinityfree.com
-- Generation Time: Nov 23, 2025 at 10:53 PM
-- Server version: 11.4.7-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_39944148_coopmart`
--

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
(5, 2, '2025-09-14 10:31:22', '2025-09-14 10:31:22'),
(6, 1, '2025-09-17 18:14:36', '2025-09-17 18:14:36'),
(7, 36, '2025-11-17 18:15:45', '2025-11-17 18:15:45'),
(8, 24, '2025-11-17 20:29:53', '2025-11-17 20:29:53'),
(9, 42, '2025-11-17 14:55:54', '2025-11-17 14:55:54'),
(10, 47, '2025-11-20 14:38:24', '2025-11-20 14:38:24'),
(11, 49, '2025-11-21 00:39:33', '2025-11-21 00:39:33');

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
(181, 6, 23, 1, '2025-10-14 23:28:47'),
(182, 6, 25, 2, '2025-10-14 23:28:47'),
(183, 6, 28, 1, '2025-10-14 23:28:48'),
(184, 6, 13, 2, '2025-10-14 23:28:48'),
(188, 6, 14, 1, '2025-10-27 15:29:36'),
(211, 7, 23, 9, '2025-11-20 02:25:00'),
(212, 7, 25, 1, '2025-11-20 02:29:02'),
(213, 7, 28, 1, '2025-11-20 02:29:09'),
(214, 7, 20, 1, '2025-11-20 02:29:13'),
(215, 7, 57, 2, '2025-11-20 02:29:18'),
(216, 7, 15, 1, '2025-11-20 02:29:20'),
(217, 7, 14, 1, '2025-11-20 02:29:21'),
(218, 10, 53, 1, '2025-11-20 14:38:24'),
(219, 10, 23, 1, '2025-11-20 14:38:27');

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
(6, 'Fresh Meat / Livestock Products', '2025-09-14 10:30:51'),
(7, 'Fruits', '2025-09-14 11:51:05'),
(9, 'Hair Shampo', '2025-09-14 11:54:44'),
(10, 'Snacks', '2025-09-14 12:01:34'),
(11, 'Beverages', '2025-09-14 12:01:34'),
(12, 'Instant Noodles', '2025-09-14 12:01:34'),
(13, 'Canned Goods', '2025-09-14 12:01:34'),
(14, 'Baked Goods', '2025-09-14 12:01:34'),
(15, 'Dairy Products', '2025-09-14 12:01:34'),
(16, 'Frozen Foods', '2025-09-14 12:01:34'),
(17, 'Chocolates & Candies', '2025-09-14 12:01:34'),
(18, 'Chips', '2025-09-14 12:01:34'),
(19, 'Sodas', '2025-09-14 12:01:34'),
(20, 'sheesh!', '2025-10-27 15:33:44');

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
(16, 2, '450.00', '45.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_16.jpg', 'proof_16.jpg', '2025-10-02 08:15:00', 15, '30.00'),
(17, 4, '180.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_17.jpg', 'proof_17.jpg', '2025-10-05 13:30:00', 0, '0.00'),
(18, 6, '320.00', '25.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_18.jpg', 'proof_18.jpg', '2025-10-08 10:45:00', 10, '15.00'),
(19, 8, '95.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'canceled', NULL, NULL, '2025-10-12 15:20:00', 0, '0.00'),
(20, 10, '275.00', '20.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_20.jpg', 'proof_20.jpg', '2025-10-15 09:10:00', 8, '12.00'),
(21, 11, '420.00', '40.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_21.jpg', 'proof_21.jpg', '2025-10-18 12:25:00', 12, '28.00'),
(22, 13, '150.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_22.jpg', 'proof_22.jpg', '2025-10-22 07:50:00', 0, '0.00'),
(23, 15, '380.00', '35.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_23.jpg', 'proof_23.jpg', '2025-10-25 14:40:00', 10, '25.00'),
(24, 17, '220.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_24.jpg', 'proof_24.jpg', '2025-10-28 11:15:00', 8, '7.00'),
(25, 1, '195.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_25.jpg', 'proof_25.jpg', '2025-11-03 09:30:00', 10, '5.00'),
(26, 3, '510.00', '50.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_26.jpg', 'proof_26.jpg', '2025-11-07 14:55:00', 15, '35.00'),
(27, 5, '120.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_27.jpg', 'proof_27.jpg', '2025-11-10 11:20:00', 0, '0.00'),
(28, 7, '340.00', '30.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_28.jpg', 'proof_28.jpg', '2025-11-14 16:45:00', 10, '20.00'),
(29, 9, '180.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_29.jpg', 'proof_29.jpg', '2025-11-18 10:30:00', 10, '5.00'),
(30, 12, '290.00', '25.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_30.jpg', 'proof_30.jpg', '2025-11-22 13:10:00', 10, '15.00'),
(31, 14, '680.00', '65.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_31.jpg', 'proof_31.jpg', '2025-12-05 09:45:00', 15, '50.00'),
(32, 16, '420.00', '40.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_32.jpg', 'proof_32.jpg', '2025-12-10 15:20:00', 12, '28.00'),
(33, 19, '550.00', '50.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_33.jpg', 'proof_33.jpg', '2025-12-15 11:35:00', 15, '35.00'),
(34, 21, '320.00', '25.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_34.jpg', 'proof_34.jpg', '2025-12-20 14:50:00', 10, '15.00'),
(35, 23, '450.00', '40.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_35.jpg', 'proof_35.jpg', '2025-12-23 10:15:00', 12, '28.00'),
(36, 2, '190.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'processing_purchased_product', NULL, NULL, '2026-01-05 08:40:00', 10, '5.00'),
(37, 4, '275.00', '20.00', '0.00', '0.00', '0.00', '20.00', 'ready_to_pick_the_purchased_product', NULL, NULL, '2026-01-08 12:25:00', 8, '12.00'),
(38, 6, '180.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'completed', NULL, NULL, '2026-01-12 16:10:00', 0, '0.00'),
(39, 8, '420.00', '40.00', '0.00', '0.00', '0.00', '20.00', 'paid', 'receipt_39.jpg', 'proof_39.jpg', '2026-01-15 09:55:00', 12, '28.00'),
(40, 10, '290.00', '25.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_40.jpg', 'proof_40.jpg', '2026-01-18 14:30:00', 10, '15.00'),
(41, 11, '150.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_41.jpg', 'proof_41.jpg', '2026-01-22 11:05:00', 0, '0.00'),
(42, 13, '380.00', '35.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_42.jpg', 'proof_42.jpg', '2026-01-25 15:50:00', 10, '25.00'),
(43, 15, '220.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'ready_to_pick_the_purchased_product', NULL, NULL, '2026-01-28 10:25:00', 8, '7.00'),
(44, 17, '195.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'processing_purchased_product', NULL, NULL, '2026-01-30 13:40:00', 10, '5.00'),
(45, 1, '510.00', '50.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_45.jpg', 'proof_45.jpg', '2026-02-02 09:15:00', 15, '35.00'),
(46, 1, '120.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_46.jpg', 'proof_46.jpg', '2023-01-10 09:20:00', 0, '0.00'),
(47, 3, '85.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_47.jpg', 'proof_47.jpg', '2023-01-15 14:35:00', 0, '0.00'),
(48, 5, '200.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_48.jpg', 'proof_48.jpg', '2023-01-22 11:10:00', 0, '0.00'),
(49, 7, '150.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_49.jpg', 'proof_49.jpg', '2023-02-05 16:45:00', 0, '0.00'),
(50, 9, '95.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_50.jpg', 'proof_50.jpg', '2023-02-18 10:30:00', 0, '0.00'),
(51, 12, '180.00', '10.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_51.jpg', 'proof_51.jpg', '2023-03-12 12:15:00', 5, '5.00'),
(52, 14, '250.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_52.jpg', 'proof_52.jpg', '2023-03-25 07:50:00', 8, '7.00'),
(53, 16, '320.00', '20.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_53.jpg', 'proof_53.jpg', '2023-04-08 14:20:00', 8, '12.00'),
(54, 19, '190.00', '10.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_54.jpg', 'proof_54.jpg', '2023-04-20 11:05:00', 5, '5.00'),
(55, 21, '275.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_55.jpg', 'proof_55.jpg', '2023-05-05 08:40:00', 8, '7.00'),
(56, 23, '420.00', '25.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_56.jpg', 'proof_56.jpg', '2023-06-15 13:25:00', 8, '17.00'),
(57, 1, '180.00', '10.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_57.jpg', 'proof_57.jpg', '2023-06-28 10:50:00', 5, '5.00'),
(58, 3, '350.00', '20.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_58.jpg', 'proof_58.jpg', '2023-07-12 15:35:00', 8, '12.00'),
(59, 5, '220.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_59.jpg', 'proof_59.jpg', '2023-07-25 09:10:00', 8, '7.00'),
(60, 7, '480.00', '30.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_60.jpg', 'proof_60.jpg', '2023-08-08 12:45:00', 8, '22.00'),
(61, 9, '380.00', '25.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_61.jpg', 'proof_61.jpg', '2023-09-18 08:20:00', 8, '17.00'),
(62, 12, '520.00', '35.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_62.jpg', 'proof_62.jpg', '2023-10-05 14:55:00', 10, '25.00'),
(63, 14, '290.00', '20.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_63.jpg', 'proof_63.jpg', '2023-10-22 11:30:00', 8, '12.00'),
(64, 16, '650.00', '45.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_64.jpg', 'proof_64.jpg', '2023-11-10 08:15:00', 10, '35.00'),
(65, 19, '420.00', '30.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_65.jpg', 'proof_65.jpg', '2023-11-28 14:40:00', 10, '20.00'),
(66, 21, '580.00', '40.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_66.jpg', 'proof_66.jpg', '2023-12-15 11:25:00', 10, '30.00'),
(67, 23, '720.00', '55.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_67.jpg', 'proof_67.jpg', '2023-12-23 16:50:00', 12, '43.00'),
(68, 1, '195.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_68.jpg', 'proof_68.jpg', '2024-01-08 09:35:00', 8, '7.00'),
(69, 3, '280.00', '20.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_69.jpg', 'proof_69.jpg', '2024-01-15 14:20:00', 8, '12.00'),
(70, 5, '180.00', '10.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_70.jpg', 'proof_70.jpg', '2024-01-25 11:45:00', 5, '5.00'),
(71, 7, '420.00', '30.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_71.jpg', 'proof_71.jpg', '2024-02-05 16:30:00', 10, '20.00'),
(72, 9, '320.00', '25.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_72.jpg', 'proof_72.jpg', '2024-02-18 10:15:00', 8, '17.00'),
(73, 12, '250.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_73.jpg', 'proof_73.jpg', '2024-03-10 12:40:00', 8, '7.00'),
(74, 14, '380.00', '25.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_74.jpg', 'proof_74.jpg', '2024-03-22 07:25:00', 8, '17.00'),
(75, 16, '190.00', '10.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_75.jpg', 'proof_75.jpg', '2024-04-05 14:50:00', 5, '5.00'),
(76, 19, '520.00', '35.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_76.jpg', 'proof_76.jpg', '2024-04-18 11:35:00', 10, '25.00'),
(77, 21, '290.00', '20.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_77.jpg', 'proof_77.jpg', '2024-05-03 08:10:00', 8, '12.00'),
(78, 23, '650.00', '45.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_78.jpg', 'proof_78.jpg', '2024-06-12 13:55:00', 10, '35.00'),
(79, 1, '420.00', '30.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_79.jpg', 'proof_79.jpg', '2024-06-25 10:20:00', 10, '20.00'),
(80, 3, '580.00', '40.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_80.jpg', 'proof_80.jpg', '2024-07-08 15:45:00', 10, '30.00'),
(81, 5, '350.00', '25.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_81.jpg', 'proof_81.jpg', '2024-07-20 09:30:00', 8, '17.00'),
(82, 7, '480.00', '35.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_82.jpg', 'proof_82.jpg', '2024-08-05 12:15:00', 10, '25.00'),
(83, 9, '220.00', '15.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_83.jpg', 'proof_83.jpg', '2024-08-18 07:50:00', 8, '7.00'),
(84, 12, '380.00', '25.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_84.jpg', 'proof_84.jpg', '2024-09-02 14:25:00', 8, '17.00'),
(85, 14, '520.00', '35.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_85.jpg', 'proof_85.jpg', '2024-09-15 11:00:00', 10, '25.00'),
(86, 16, '290.00', '20.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_86.jpg', 'proof_86.jpg', '2024-09-28 08:35:00', 8, '12.00'),
(87, 19, '650.00', '45.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_87.jpg', 'proof_87.jpg', '2024-10-10 13:10:00', 10, '35.00'),
(88, 21, '420.00', '30.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_88.jpg', 'proof_88.jpg', '2024-10-25 10:45:00', 10, '20.00'),
(89, 23, '720.00', '55.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_89.jpg', 'proof_89.jpg', '2024-11-08 16:20:00', 12, '43.00'),
(90, 1, '850.00', '70.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_90.jpg', 'proof_90.jpg', '2024-11-22 13:55:00', 15, '55.00'),
(91, 3, '580.00', '45.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_91.jpg', 'proof_91.jpg', '2024-12-05 10:30:00', 12, '33.00'),
(92, 5, '680.00', '55.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_92.jpg', 'proof_92.jpg', '2024-12-18 15:05:00', 12, '43.00'),
(93, 7, '950.00', '85.00', '0.00', '0.00', '0.00', '20.00', 'completed', 'receipt_93.jpg', 'proof_93.jpg', '2024-12-24 11:40:00', 15, '70.00'),
(188, 2, '479.00', '51.00', '25.00', '0.00', '0.00', '20.00', 'pending_payment', 'uploads/receipts/receipt_68ef820c9a5ea6.10994625.png', NULL, '2025-10-15 18:14:20', 0, '0.00'),
(189, 2, '237.80', '24.20', '15.00', '0.00', '0.00', '20.00', 'pending_payment', 'uploads/receipts/receipt_68ff73fe5c9752.53324763.jpg', NULL, '2025-10-27 20:30:38', 0, '0.00'),
(190, 2, '47.00', '3.00', '0.00', '0.00', '0.00', '20.00', 'pending_payment', 'uploads/receipts/receipt_68ff7411cdac55.38560152.jpg', NULL, '2025-10-27 20:30:57', 0, '0.00'),
(191, 36, '20.00', '0.00', '0.00', '5.40', '0.00', '20.00', 'pending_payment', 'uploads/receipts/receipt_691b095fa15351.26528112.jpg', NULL, '2025-11-17 19:39:11', 0, '21.60'),
(192, 36, '275.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'pending_payment', 'uploads/receipts/receipt_691b09be4f75c2.71798473.jpg', NULL, '2025-11-17 19:40:46', 0, '25.00'),
(193, 24, '136.00', '0.00', '0.00', '29.00', '0.00', '20.00', 'paid', 'uploads/receipts/receipt_691b15501288c6.64839553.jpg', NULL, '2025-11-17 20:30:08', 0, '0.00'),
(194, 42, '35.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'pending_payment', 'uploads/receipts/receipt_691b37b1e8d8b9.87165996.jpg', NULL, '2025-11-17 14:56:49', 0, '0.00'),
(195, 42, '35.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'pending_payment', 'uploads/receipts/receipt_691b38f93d03c4.07831360.jpg', NULL, '2025-11-17 15:02:17', 0, '0.00'),
(196, 42, '35.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'pending_payment', 'uploads/receipts/receipt_691c5a4807e829.06007613.jpg', NULL, '2025-11-18 11:36:40', 0, '0.00'),
(197, 2, '93.80', '8.20', '20.00', '0.00', '0.00', '20.00', 'pending_payment', NULL, NULL, '2025-11-20 02:04:32', 0, '0.00'),
(198, 49, '245.00', '0.00', '0.00', '0.00', '0.00', '20.00', 'pending_payment', NULL, NULL, '2025-11-21 00:41:01', 0, '15.00');

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
(46, 16, 13, 1, '230.00', 0),
(47, 16, 14, 1, '130.00', 0),
(48, 16, 15, 1, '60.00', 0),
(49, 17, 23, 4, '15.00', 0),
(50, 17, 25, 2, '12.00', 0),
(51, 18, 17, 2, '85.00', 0),
(52, 18, 20, 2, '35.00', 0),
(53, 18, 33, 2, '25.00', 0),
(54, 19, 36, 1, '65.00', 0),
(55, 19, 37, 1, '75.00', 0),
(56, 20, 19, 2, '55.00', 0),
(57, 20, 21, 3, '35.00', 0),
(58, 20, 46, 1, '65.00', 0),
(59, 21, 25, 8, '12.00', 0),
(60, 21, 23, 6, '15.00', 0),
(61, 21, 27, 4, '18.00', 0),
(62, 22, 28, 3, '15.00', 0),
(63, 22, 54, 2, '35.00', 0),
(64, 23, 29, 1, '85.00', 0),
(65, 23, 30, 2, '35.00', 0),
(66, 23, 31, 1, '180.00', 0),
(67, 24, 33, 4, '25.00', 0),
(68, 24, 34, 2, '35.00', 0),
(69, 24, 35, 2, '15.00', 0),
(70, 25, 16, 1, '45.00', 0),
(71, 25, 22, 2, '35.00', 0),
(72, 25, 47, 1, '55.00', 0),
(73, 26, 41, 1, '220.00', 0),
(74, 26, 42, 1, '180.00', 0),
(75, 26, 44, 1, '95.00', 0),
(76, 27, 26, 2, '25.00', 0),
(77, 27, 37, 1, '75.00', 0),
(78, 28, 32, 3, '45.00', 0),
(79, 28, 38, 1, '120.00', 0),
(80, 28, 40, 1, '95.00', 0),
(81, 29, 45, 1, '150.00', 0),
(82, 29, 48, 1, '120.00', 0),
(83, 30, 49, 2, '85.00', 0),
(84, 30, 53, 2, '40.00', 0),
(85, 30, 52, 1, '45.00', 0),
(86, 31, 13, 2, '230.00', 0),
(87, 31, 45, 1, '150.00', 0),
(88, 31, 46, 2, '65.00', 0),
(89, 31, 48, 1, '120.00', 0),
(90, 32, 41, 1, '220.00', 0),
(91, 32, 42, 1, '180.00', 0),
(92, 33, 17, 3, '85.00', 0),
(93, 33, 18, 2, '120.00', 0),
(94, 33, 19, 2, '55.00', 0),
(95, 34, 25, 5, '12.00', 0),
(96, 34, 26, 3, '25.00', 0),
(97, 34, 27, 2, '18.00', 0),
(98, 35, 29, 2, '85.00', 0),
(99, 35, 30, 3, '35.00', 0),
(100, 35, 31, 1, '180.00', 0),
(101, 36, 33, 3, '25.00', 0),
(102, 36, 34, 2, '35.00', 0),
(103, 36, 37, 1, '75.00', 0),
(104, 37, 16, 2, '45.00', 0),
(105, 37, 20, 3, '35.00', 0),
(106, 37, 46, 1, '65.00', 0),
(107, 38, 23, 4, '15.00', 0),
(108, 38, 25, 2, '12.00', 0),
(109, 39, 41, 1, '220.00', 0),
(110, 39, 42, 1, '180.00', 0),
(111, 40, 49, 1, '85.00', 0),
(112, 40, 53, 2, '40.00', 0),
(113, 40, 52, 2, '45.00', 0),
(114, 46, 23, 3, '15.00', 0),
(115, 46, 25, 2, '12.00', 0),
(116, 47, 33, 2, '25.00', 0),
(117, 47, 37, 1, '75.00', 0),
(118, 48, 16, 2, '45.00', 0),
(119, 48, 20, 2, '35.00', 0),
(120, 49, 29, 1, '85.00', 0),
(121, 49, 30, 1, '35.00', 0),
(122, 50, 34, 1, '35.00', 0),
(123, 50, 35, 1, '15.00', 0),
(124, 51, 17, 1, '85.00', 0),
(125, 51, 19, 1, '55.00', 0),
(126, 52, 25, 3, '12.00', 0),
(127, 52, 26, 2, '25.00', 0),
(128, 53, 41, 1, '220.00', 0),
(129, 53, 42, 1, '180.00', 0),
(130, 54, 45, 1, '150.00', 0),
(131, 54, 46, 1, '65.00', 0),
(132, 55, 31, 1, '180.00', 0),
(133, 55, 32, 1, '45.00', 0),
(134, 56, 13, 1, '230.00', 0),
(135, 56, 14, 1, '130.00', 0),
(136, 57, 16, 2, '45.00', 0),
(137, 57, 18, 1, '120.00', 0),
(138, 58, 25, 5, '12.00', 0),
(139, 58, 23, 4, '15.00', 0),
(140, 59, 33, 3, '25.00', 0),
(141, 59, 34, 2, '35.00', 0),
(142, 60, 41, 1, '220.00', 0),
(143, 60, 42, 1, '180.00', 0),
(144, 61, 29, 2, '85.00', 0),
(145, 61, 30, 2, '35.00', 0),
(146, 62, 45, 2, '150.00', 0),
(147, 62, 46, 2, '65.00', 0),
(148, 63, 17, 2, '85.00', 0),
(149, 63, 19, 2, '55.00', 0),
(150, 64, 13, 2, '230.00', 0),
(151, 64, 14, 1, '130.00', 0),
(152, 65, 41, 1, '220.00', 0),
(153, 65, 42, 1, '180.00', 0),
(154, 66, 31, 2, '180.00', 0),
(155, 66, 32, 3, '45.00', 0),
(156, 67, 45, 3, '150.00', 0),
(157, 67, 46, 2, '65.00', 0),
(158, 68, 25, 4, '12.00', 0),
(159, 68, 23, 3, '15.00', 0),
(160, 69, 33, 3, '25.00', 0),
(161, 69, 34, 2, '35.00', 0),
(162, 70, 16, 2, '45.00', 0),
(163, 70, 20, 2, '35.00', 0),
(164, 71, 29, 2, '85.00', 0),
(165, 71, 30, 3, '35.00', 0),
(166, 72, 17, 2, '85.00', 0),
(167, 72, 19, 2, '55.00', 0),
(168, 73, 41, 1, '220.00', 0),
(169, 73, 44, 1, '95.00', 0),
(170, 74, 45, 1, '150.00', 0),
(171, 74, 48, 1, '120.00', 0),
(172, 75, 25, 3, '12.00', 0),
(173, 75, 26, 2, '25.00', 0),
(174, 76, 13, 1, '230.00', 0),
(175, 76, 14, 1, '130.00', 0),
(176, 77, 31, 1, '180.00', 0),
(177, 77, 32, 1, '45.00', 0),
(178, 78, 41, 2, '220.00', 0),
(179, 78, 42, 1, '180.00', 0),
(180, 79, 25, 6, '12.00', 0),
(181, 79, 23, 5, '15.00', 0),
(182, 80, 29, 3, '85.00', 0),
(183, 80, 30, 3, '35.00', 0),
(184, 81, 33, 4, '25.00', 0),
(185, 81, 34, 3, '35.00', 0),
(186, 82, 45, 2, '150.00', 0),
(187, 82, 46, 2, '65.00', 0),
(188, 83, 16, 2, '45.00', 0),
(189, 83, 18, 1, '120.00', 0),
(190, 84, 41, 1, '220.00', 0),
(191, 84, 42, 1, '180.00', 0),
(192, 85, 13, 1, '230.00', 0),
(193, 85, 14, 1, '130.00', 0),
(194, 86, 17, 2, '85.00', 0),
(195, 86, 19, 2, '55.00', 0),
(196, 87, 31, 2, '180.00', 0),
(197, 87, 32, 3, '45.00', 0),
(198, 88, 45, 2, '150.00', 0),
(199, 88, 46, 2, '65.00', 0),
(200, 89, 13, 2, '230.00', 0),
(201, 89, 14, 2, '130.00', 0),
(202, 90, 41, 3, '220.00', 0),
(203, 90, 42, 2, '180.00', 0),
(204, 91, 45, 3, '150.00', 0),
(205, 91, 48, 1, '120.00', 0),
(206, 92, 13, 2, '230.00', 0),
(207, 92, 14, 1, '130.00', 0),
(208, 93, 45, 4, '150.00', 0),
(209, 93, 46, 3, '65.00', 0),
(210, 93, 48, 2, '120.00', 0),
(211, 188, 15, 2, '55.00', 1),
(212, 188, 23, 2, '10.00', 1),
(213, 188, 14, 1, '125.00', 1),
(214, 188, 13, 1, '225.00', 1),
(215, 188, 54, 1, '30.00', 1),
(216, 189, 13, 1, '225.00', 1),
(217, 189, 25, 1, '7.00', 1),
(218, 189, 28, 1, '10.00', 1),
(219, 190, 28, 1, '15.00', 0),
(220, 190, 23, 1, '15.00', 0),
(221, 191, 25, 1, '12.00', 0),
(222, 191, 28, 1, '15.00', 0),
(223, 192, 15, 3, '60.00', 0),
(224, 192, 57, 1, '100.00', 0),
(225, 193, 14, 1, '130.00', 0),
(226, 193, 28, 1, '15.00', 0),
(227, 194, 23, 1, '15.00', 0),
(228, 195, 23, 1, '15.00', 0),
(229, 196, 23, 1, '15.00', 0),
(230, 197, 15, 1, '55.00', 1),
(231, 197, 23, 1, '10.00', 1),
(232, 197, 25, 1, '7.00', 1),
(233, 197, 28, 1, '10.00', 1),
(234, 198, 57, 1, '100.00', 0),
(235, 198, 55, 1, '35.00', 0),
(236, 198, 53, 1, '40.00', 0),
(237, 198, 36, 1, '65.00', 0);

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
(13, 'Tiekn', '230.00', 'https://th.bing.com/th/id/OIP.jq1lq70igKAfOQOkN8UYiQHaFa?w=244&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 6, '2025-09-14 10:31:18', 5),
(14, 'Manggo', '130.00', 'https://th.bing.com/th/id/OIP.X8V71omvwb7QQRnmwZbAhQHaHa?w=193&h=193&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 7, '2025-09-14 11:52:40', 5),
(15, 'Beauty Care Shampoo', '60.00', 'https://vega.am/image/cache/catalog/Angel/household/405986-2000x1500.jpg', 9, '2025-09-14 11:55:03', 6),
(16, 'Lay\'s Classic Chips', '45.00', 'https://th.bing.com/th/id/OIP.rQJ5KJzOqY7qOe8-7CKm8AHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 10, '2025-09-14 04:00:00', 25),
(17, 'Oreo Cookies', '85.00', 'https://th.bing.com/th/id/OIP.wKzV5BvP7qGLjQrVp8cNHAHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 10, '2025-09-14 04:01:00', 18),
(18, 'Pringles Original', '120.00', 'https://th.bing.com/th/id/OIP.mJ8k3Q7YvRzEeJ6Q9v8S5QHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 10, '2025-09-14 04:02:00', 12),
(19, 'Cheetos Crunchy', '55.00', 'https://th.bing.com/th/id/OIP.X8V71omvwb7QQRnmwZbAhQHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 10, '2025-09-14 04:03:00', 20),
(20, 'Coca-Cola 500ml', '35.00', 'https://th.bing.com/th/id/OIP.cpJPe6sdZvTSda1luQvyowHaLH?w=115&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 11, '2025-09-14 04:04:00', 45),
(21, 'Pepsi 500ml', '35.00', 'https://th.bing.com/th/id/OIP.jRt6Y8uI4pL9vB5n7cMzFgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 11, '2025-09-14 04:05:00', 38),
(22, 'Sprite 500ml', '35.00', 'https://th.bing.com/th/id/OIP.qWe4R9tY2kM8vN6x5bLzHgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 11, '2025-09-14 04:06:00', 30),
(23, 'Bottled Water 500ml', '15.00', 'https://th.bing.com/th/id/OIP.N934F5CunqzIwQ-krs2n0gHaHa?w=200&h=200&c=10&o=6&dpr=1.1&pid=genserp&rm=2', 11, '2025-09-14 04:07:00', 70),
(24, 'Orange Juice 1L', '95.00', 'https://th.bing.com/th/id/OIP.aEr5T8yU2iP7vM9k6fQnHgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 11, '2025-09-14 04:08:00', 15),
(25, 'Lucky Me! Pancit Canton', '12.00', 'https://th.bing.com/th/id/OIP.Fh3dF08GQ0ddxE729n26VAHaF-?w=218&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 12, '2025-09-14 04:09:00', 59),
(26, 'Nissin Cup Noodles', '25.00', 'https://th.bing.com/th/id/OIP.cWh9I3yV5mQ8wO8l7gSnDgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 12, '2025-09-14 04:10:00', 40),
(27, 'Maggi 2-Minute Noodles', '18.00', 'https://th.bing.com/th/id/OIP.dXi0J4zW6nR9xP9m8hToEgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 12, '2025-09-14 04:11:00', 35),
(28, 'Payless Xtra Big', '15.00', 'https://tse3.mm.bing.net/th/id/OIP.OyQRSRyo7DWP4H2pWQ99qgHaHa?pid=ImgDet&w=184&h=184&c=7&dpr=1.1&o=7&rm=3', 12, '2025-09-14 04:12:00', 53),
(29, 'Corned Beef 150g', '85.00', 'https://th.bing.com/th/id/OIP.fZk2L6bY8pT1zR1o0jVqGgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 13, '2025-09-14 04:13:00', 22),
(30, 'Sardines in Tomato Sauce', '35.00', 'https://th.bing.com/th/id/OIP.gAl3M7cZ9qU2aS2p1kWrHgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 13, '2025-09-14 04:14:00', 28),
(31, 'Spam Classic 340g', '180.00', 'https://th.bing.com/th/id/OIP.hBm4N8dA0rV3bT3q2lXsIgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 13, '2025-09-14 04:15:00', 15),
(32, 'Tuna Flakes in Oil', '45.00', 'https://th.bing.com/th/id/OIP.iCn5O9eB1sW4cU4r3mYtJgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 13, '2025-09-14 04:16:00', 33),
(33, 'Pandesal (6pcs)', '25.00', 'https://th.bing.com/th/id/OIP.jDo6P0fC2tX5dV5s4nZuKgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 14, '2025-09-14 04:17:00', 40),
(34, 'Ensaymada', '35.00', 'https://th.bing.com/th/id/OIP.kEp7Q1gD3uY6eW6t5oAvLgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 14, '2025-09-14 04:18:00', 25),
(35, 'Monay Bread', '15.00', 'https://th.bing.com/th/id/OIP.lFq8R2hE4vZ7fX7u6pBwMgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 14, '2025-09-14 04:19:00', 30),
(36, 'Chocolate Cake Slice', '65.00', 'https://th.bing.com/th/id/OIP.mGr9S3iF5wA8gY8v7qCxNgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 14, '2025-09-14 04:20:00', 13),
(37, 'Fresh Milk 1L', '75.00', 'https://th.bing.com/th/id/OIP.nHs0T4jG6xB9hZ9w8rDyOgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 15, '2025-09-14 04:21:00', 35),
(38, 'Cheese Slices 200g', '120.00', 'https://th.bing.com/th/id/OIP.oIt1U5kH7yC0iA0x9sEzPgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 15, '2025-09-14 04:22:00', 18),
(39, 'Greek Yogurt 150g', '85.00', 'https://th.bing.com/th/id/OIP.pJu2V6lI8zD1jB1y0tF0QgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 15, '2025-09-14 04:23:00', 22),
(40, 'ButterDuck 200g', '95.00', 'https://kitchenconvenienceph.com/cdn/shop/products/DAIRYCREMEBUTTERMILK200G_1024x1024@2x.jpg?v=1622724030', 15, '2025-09-14 04:24:00', 15),
(41, 'Frozen Chicken Wings 1kg', '220.00', 'https://th.bing.com/th/id/OIP.rLw4X8nK0BF3lD3A2vH2SgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 16, '2025-09-14 04:25:00', 12),
(42, 'Ice Cream Tub 1L', '180.00', 'https://th.bing.com/th/id/OIP.sMx5Y9oL1CG4mE4B3wI3TgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 16, '2025-09-14 04:26:00', 20),
(43, 'Frozen Fish Fillet 500g', '150.00', 'https://th.bing.com/th/id/OIP.tNy6Z0pM2DH5nF5C4xJ4UgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 16, '2025-09-14 04:27:00', 8),
(44, 'Frozen Vegetables Mix 400g', '95.00', 'https://th.bing.com/th/id/OIP.uOz7A1qN3EI6oG6D5yK5VgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 16, '2025-09-14 04:28:00', 14),
(45, 'Toblerone 100g', '150.00', 'https://th.bing.com/th/id/OIP.vPA8B2rO4FJ7pH7E6zL6WgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 17, '2025-09-14 04:29:00', 16),
(46, 'Kit Kat 4-Finger', '65.00', 'https://th.bing.com/th/id/OIP.wQB9C3sP5GK8qI8F7AM7XgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 17, '2025-09-14 04:30:00', 24),
(47, 'M&M\'s Peanut 45g', '55.00', 'https://th.bing.com/th/id/OIP.xRC0D4tQ6HL9rJ9G8BN8YgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 17, '2025-09-14 04:31:00', 28),
(48, 'Hershey\'s Kisses 150g', '120.00', 'https://th.bing.com/th/id/OIP.ySD1E5uR7IM0sK0H9CO9ZgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 17, '2025-09-14 04:32:00', 21),
(49, 'Doritos Nacho Cheese', '85.00', 'https://th.bing.com/th/id/OIP.zTE2F6vS8JN1tL1I0DP0agHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 18, '2025-09-14 04:33:00', 24),
(50, 'Ruffles Original', '75.00', 'https://th.bing.com/th/id/OIP.AUF3G7wT9KO2uM2J1EQ1bgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 18, '2025-09-14 04:34:00', 19),
(51, 'Tortillos Chili Cheese', '65.00', 'https://th.bing.com/th/id/OIP.BVG4H8xU0LP3vN3K2FR2cgHaHa?w=200&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 18, '2025-09-14 04:35:00', 16),
(52, 'Potato Corner Fries', '45.00', 'https://th.bing.com/th/id/OIP.NM9nklKUs4fF2HUo0rQw7gHaHa?w=173&h=180&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 18, '2025-09-14 04:36:00', 25),
(53, 'Mountain Dew 500ml', '40.00', 'https://th.bing.com/th/id/OIP.-zbDClE5xNau3uTFwuerpAHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 19, '2025-09-14 04:37:00', 33),
(54, 'Royal Tru-Orange 500ml', '35.00', 'https://th.bing.com/th/id/OIP.7JNzHSmzj7e4GenMCX_GmgHaHa?w=120&h=108&c=7&qlt=90&bgcl=d48f1e&r=0&o=6&dpr=1.1&pid=13.1', 19, '2025-09-14 04:38:00', 29),
(55, 'Sarsi 500ml', '35.00', 'https://th.bing.com/th/id/OIP.DOSiV9BYWlY39DeVy3wzNQHaRH?w=115&h=200&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 19, '2025-09-14 04:39:00', 23),
(56, '7UP 500ml', '35.00', 'https://th.bing.com/th/id/OIP.zi5wt0ZPTioEfYi_Ue-3JgHaNu?w=124&h=220&c=7&r=0&o=7&dpr=1.1&pid=1.7&rm=3', 19, '2025-09-14 04:40:00', 24),
(57, 'Bearbrand', '100.00', 'https://filebroker-cdn.lazada.co.th/kf/S6c7f210a87d74f85bb3444932d54878el.jpg', 15, '2025-10-27 15:33:19', 2);

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
(1, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 16:08:01', '2025-11-24 16:08:01', 2, '2025-11-17 16:08:01', NULL, NULL, 0),
(2, 18, 'welcome', '20.00', 100, 'Welcome to CoopMart, Mark Snow! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-17 16:15:18', '2025-11-24 16:15:18', 2, '2025-11-17 16:15:18', NULL, NULL, 0),
(3, 35, 'welcome', '20.00', 100, 'Welcome to CoopMart, Amanda Taylor! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-17 16:39:25', '2025-11-24 16:39:25', 2, '2025-11-17 16:39:25', NULL, NULL, 0),
(4, 35, 'welcome', '20.00', 100, 'Welcome to CoopMart, Amanda Taylor! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-17 16:39:26', '2025-11-24 16:39:26', 2, '2025-11-17 16:39:26', NULL, NULL, 0),
(5, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 16:39:32', '2025-11-24 16:39:32', 2, '2025-11-17 16:39:32', NULL, NULL, 0),
(6, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 16:39:33', '2025-11-24 16:39:33', 2, '2025-11-17 16:39:33', NULL, NULL, 0),
(7, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 16:40:14', '2025-11-24 16:40:14', 2, '2025-11-17 16:40:14', NULL, NULL, 0),
(8, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 16:40:15', '2025-11-24 16:40:15', 2, '2025-11-17 16:40:15', NULL, NULL, 0),
(9, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 16:42:20', '2025-11-24 16:42:20', 2, '2025-11-17 16:42:20', NULL, NULL, 0),
(10, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 16:42:21', '2025-11-24 16:42:21', 2, '2025-11-17 16:42:21', NULL, NULL, 0),
(11, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 16:45:42', '2025-11-24 16:45:42', 2, '2025-11-17 16:45:42', NULL, NULL, 0),
(12, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 16:45:45', '2025-11-24 16:45:45', 2, '2025-11-17 16:45:45', NULL, NULL, 0),
(13, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 17:27:29', '2025-11-24 17:27:29', 2, '2025-11-17 17:27:29', NULL, NULL, 0),
(14, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 17:27:30', '2025-11-24 17:27:30', 2, '2025-11-17 17:27:30', NULL, NULL, 0),
(15, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 18:00:08', '2025-11-24 18:00:08', 2, '2025-11-17 18:00:08', NULL, '2025-11-17 11:00:28', 0),
(16, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 18:00:09', '2025-11-24 18:00:09', 2, '2025-11-17 18:00:09', NULL, '2025-11-17 11:00:28', 0),
(17, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 18:00:53', '2025-11-24 18:00:53', 2, '2025-11-17 18:00:53', NULL, '2025-11-17 11:01:10', 0),
(18, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'used', '2025-11-17 18:00:54', '2025-11-24 18:00:54', 2, '2025-11-17 18:00:54', NULL, '2025-11-17 11:01:10', 0),
(19, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 19:36:08', '2025-11-24 19:36:08', 2, '2025-11-17 19:36:08', '2025-11-17 19:39:11', NULL, 0),
(20, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'used', '2025-11-17 19:36:10', '2025-11-24 19:36:10', 2, '2025-11-17 19:36:10', NULL, NULL, 0),
(21, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'used', '2025-11-17 19:36:56', '2025-11-24 19:36:56', 2, '2025-11-17 19:36:56', NULL, NULL, 0),
(22, 36, 'welcome', '20.00', 100, 'Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'used', '2025-11-17 19:36:57', '2025-11-24 19:36:57', 2, '2025-11-17 19:36:57', NULL, NULL, 0),
(23, 33, 'welcome', '20.00', 100, 'Welcome to CoopMart, Jennifer Lee! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-17 20:29:10', '2025-11-24 20:29:10', 2, '2025-11-17 20:29:10', NULL, NULL, 0),
(24, 33, 'welcome', '20.00', 100, 'Welcome to CoopMart, Jennifer Lee! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-17 20:29:10', '2025-11-24 20:29:10', 2, '2025-11-17 20:29:10', NULL, NULL, 0),
(25, 24, 'welcome', '20.00', 100, 'Welcome to CoopMart, Arnel Brucal! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', '', '2025-11-17 20:29:18', '2025-11-24 20:29:18', 2, '2025-11-17 20:29:18', '2025-11-17 20:30:08', NULL, 0),
(26, 24, 'welcome', '20.00', 100, 'Welcome to CoopMart, Arnel Brucal! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'used', '2025-11-17 20:29:19', '2025-11-24 20:29:19', 2, '2025-11-17 20:29:19', NULL, NULL, 0),
(27, 37, 'welcome', '20.00', 100, 'Welcome to CoopMart, Maria Santos! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-20 03:44:22', '2025-11-26 19:44:22', 2, '2025-11-20 03:44:22', NULL, NULL, 0),
(28, 37, 'welcome', '20.00', 100, 'Welcome to CoopMart, Maria Santos! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!', 'sent', '2025-11-20 03:44:24', '2025-11-26 19:44:24', 2, '2025-11-20 03:44:24', NULL, NULL, 0);

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
(1, 2, 20, 0, '2025-09-14 10:21:21'),
(2, 2, 20, 0, '2025-09-14 10:21:31'),
(3, 2, 10, 0, '2025-09-14 10:21:46'),
(4, 1, 10, 0, '2025-10-09 10:21:59'),
(5, 1, 20, 0, '2025-10-09 10:22:04'),
(6, 1, 20, 0, '2025-10-09 10:22:08'),
(7, 1, 10, 0, '2025-10-27 15:30:42'),
(8, 1, 50, 0, '2025-10-27 15:30:49'),
(9, 1, 50, 0, '2025-10-27 15:31:00'),
(10, 2, 20, 0, '2025-10-27 20:37:03'),
(11, 2, 30, 0, '2025-11-05 17:28:19'),
(12, 2, 50, 0, '2025-11-05 17:28:45'),
(13, 2, 30, 0, '2025-11-06 21:15:27'),
(14, 1, 20, 0, '2025-11-07 10:29:23'),
(15, 1, 50, 0, '2025-11-07 10:29:31'),
(16, 1, 30, 0, '2025-11-07 10:29:38'),
(17, 2, 20, 0, '2025-11-07 10:41:01'),
(18, 2, 10, 0, '2025-11-07 10:41:07'),
(19, 2, 50, 0, '2025-11-07 10:41:13'),
(20, 1, 30, 0, '2025-11-17 14:08:22'),
(21, 1, 20, 0, '2025-11-17 14:08:28'),
(22, 1, 10, 0, '2025-11-17 14:08:34'),
(23, 2, 20, 0, '2025-11-20 02:01:18'),
(24, 2, 30, 0, '2025-11-20 02:01:24'),
(25, 2, 20, 0, '2025-11-20 02:01:29'),
(26, 47, 50, 0, '2025-11-20 14:37:00'),
(27, 47, 30, 0, '2025-11-20 14:37:09'),
(28, 47, 50, 0, '2025-11-20 14:37:19'),
(29, 49, 30, 0, '2025-11-21 00:38:46'),
(30, 49, 10, 0, '2025-11-21 00:38:53'),
(31, 49, 20, 0, '2025-11-21 00:39:00');

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
  `login_streak` int(11) DEFAULT 0,
  `last_login_date` date DEFAULT NULL,
  `streak_updated_today` tinyint(1) DEFAULT 0,
  `points` int(11) DEFAULT 0,
  `daily_spins` int(11) DEFAULT 0,
  `last_spin_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `phone`, `gender`, `birth_date`, `address`, `city`, `province`, `zip_code`, `membership_type`, `role`, `created_at`, `updated_at`, `login_streak`, `last_login_date`, `streak_updated_today`, `points`, `daily_spins`, `last_spin_date`) VALUES
(1, 'tam', 'tan@gmail.com', '$2y$10$bwqcMWVgslh7hypf12BBme/sJHtIXeo6C7j7if7zXKOkEsiJ1rxmu', '09977114098', 'male', '2003-06-11', 'Santa Veronica', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2025-09-13 13:55:52', '2025-11-17 14:08:34', 0, '2025-10-29', 0, 320, 3, '2025-11-17'),
(2, 'Admin', 'admin@gmail.com', '$2y$10$GBhEk5a7bEawaqetr/U1L.Gd2Z.7.OSUZWHQhReELjYawq2HDHMIy', NULL, 'prefer_not_to_say', NULL, NULL, NULL, NULL, NULL, 'sidc_member', 'admin', '2025-09-13 14:01:19', '2025-11-21 02:58:17', 3, '2025-11-20', 1, 280, 3, '2025-11-19'),
(3, 'Arvie', 'arvie@gmail.com', '$2y$10$Rtk9mAIqkxpW.O4hsOuQOOC/q1iq/JGLVhL6Gp/M0P6nSaARHFape', NULL, 'male', '2002-05-23', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-13 20:28:42', '2025-10-09 20:52:30', 2, '2025-09-14', 1, 80, 0, NULL),
(4, 'John Doe', 'john.doe1@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000001', 'male', '1998-08-15', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 09:00:00', '2025-10-09 20:52:30', 0, NULL, 0, 10, 0, NULL),
(5, 'Jane Smith', 'jane.smith@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000002', 'female', '2000-11-03', NULL, NULL, NULL, NULL, 'sidc_member', 'customer', '2025-09-14 09:05:00', '2025-10-09 20:52:31', 1, '2025-09-14', 1, 25, 1, '2025-09-14'),
(6, 'Mike Tester', 'mike.tester@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000003', 'male', '2000-03-12', NULL, NULL, NULL, NULL, 'non_member', 'customer', '2025-09-14 09:10:00', '2025-10-09 20:52:30', 0, NULL, 0, 5, 0, NULL),
(7, 'Anna Banana', 'anna.banana@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000004', 'female', '2002-02-14', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 09:15:00', '2025-10-09 20:52:31', 2, '2025-09-14', 1, 40, 2, '2025-09-14'),
(8, 'Carlos Cruz', 'carlos.cruz@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000005', 'male', '1999-11-05', NULL, NULL, NULL, NULL, 'sidc_member', 'customer', '2025-09-14 09:20:00', '2025-10-09 20:52:30', 0, NULL, 0, 0, 0, NULL),
(9, 'Daisy Ray', 'daisy.ray@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000006', 'female', '2001-09-28', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 09:25:00', '2025-10-09 20:52:31', 3, '2025-09-14', 1, 60, 1, '2025-09-14'),
(10, 'Eddie Brock', 'eddie.brock@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000007', 'male', '2001-02-18', NULL, NULL, NULL, NULL, 'non_member', 'customer', '2025-09-14 09:30:00', '2025-10-09 20:52:30', 1, '2025-09-14', 1, 15, 0, NULL),
(11, 'Fiona Apple', 'fiona.apple@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000008', 'female', '1997-12-10', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 09:35:00', '2025-10-09 20:52:31', 0, NULL, 0, 20, 0, NULL),
(12, 'George Lime', 'george.lime@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000009', 'male', '1997-09-09', NULL, NULL, NULL, NULL, 'sidc_member', 'customer', '2025-09-14 09:40:00', '2025-10-09 20:52:30', 1, '2025-09-14', 1, 30, 2, '2025-09-14'),
(13, 'Holly Wood', 'holly.wood@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000010', 'female', '1999-07-26', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 09:45:00', '2025-10-09 20:52:31', 0, NULL, 0, 0, 0, NULL),
(14, 'Ian Bean', 'ian.bean@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000011', 'male', '2004-12-22', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 09:50:00', '2025-10-09 20:52:30', 4, '2025-09-14', 1, 70, 3, '2025-09-14'),
(15, 'Jenny Lake', 'jenny.lake@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000012', 'female', '2001-04-05', NULL, NULL, NULL, NULL, 'non_member', 'customer', '2025-09-14 09:55:00', '2025-10-09 20:52:31', 0, NULL, 0, 5, 0, NULL),
(16, 'Karl Marx', 'karl.marx@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000013', 'male', '1996-05-01', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 10:00:00', '2025-10-09 20:52:31', 1, '2025-09-14', 1, 10, 1, '2025-09-14'),
(17, 'Lara Croft', 'lara.croft@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000014', 'female', '1998-10-21', NULL, NULL, NULL, NULL, 'sidc_member', 'customer', '2025-09-14 10:05:00', '2025-10-09 20:52:31', 2, '2025-09-14', 1, 50, 0, NULL),
(18, 'Mark Snow', 'mark.snow@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000015', 'male', '2002-07-29', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 10:10:00', '2025-10-09 20:52:31', 0, NULL, 0, 0, 0, NULL),
(19, 'Nina Park', 'nina.park@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000016', 'female', '2003-01-09', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 10:15:00', '2025-10-09 20:52:31', 1, '2025-09-14', 1, 35, 1, '2025-09-14'),
(20, 'Oscar Wild', 'oscar.wild@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000017', 'male', '1999-01-20', NULL, NULL, NULL, NULL, 'non_member', 'customer', '2025-09-14 10:20:00', '2025-10-09 20:52:31', 0, NULL, 0, 0, 0, NULL),
(21, 'Penny Wise', 'penny.wise@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000018', 'female', '2002-03-17', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 10:25:00', '2025-10-09 20:52:31', 2, '2025-09-14', 1, 45, 2, '2025-09-14'),
(22, 'Quinn Fox', 'quinn.fox@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000019', 'male', '2003-10-11', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-14 10:30:00', '2025-10-09 20:52:31', 0, NULL, 0, 5, 0, NULL),
(23, 'Rita Ora', 'rita.ora@example.com', '$2y$10$abcdefghijklmnopqrstuv', '09170000020', 'female', '2000-06-02', NULL, NULL, NULL, NULL, 'sidc_member', 'customer', '2025-09-14 10:35:00', '2025-10-13 08:40:04', 0, '2025-09-14', 1, 0, 1, '2025-09-14'),
(24, 'Arnel Brucal', 'arnel@gmail.com', '$2y$10$2B9M2UW1TbLUbHScshT7weYbgKCQ9U5vcZXR.mWxvAqp/OAJ00Ihy', NULL, 'male', '1980-03-17', NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-09-30 20:07:12', '2025-11-17 20:30:08', 1, '2025-11-17', 0, 200, 0, NULL),
(25, 'tanny', 'tanny@gmail.com', '$2y$10$wrt0XMGLQEy3a35OoPmtTekvBlWWJBmAQ8jUUFz.fP1is46js8rIa', NULL, 'female', '2004-08-13', NULL, NULL, NULL, NULL, 'regular', 'admin', '2025-09-30 20:28:56', '2025-10-09 20:52:31', 0, NULL, 0, 0, 0, NULL),
(26, 'John Smith', 'john.smith@email.com', 'password123', '555-0101', 'male', '1990-05-15', '123 Main St', 'Toronto', 'Ontario', 'M5V 2T6', 'regular', 'customer', '2023-06-01 08:00:00', '2023-11-15 14:30:00', 0, '2023-11-15', 0, 150, 1, '2023-11-15'),
(27, 'Sarah Johnson', 'sarah.j@email.com', 'sarahpass456', '555-0102', 'female', '1988-08-22', '456 Oak Ave', 'Vancouver', 'British Columbia', 'V6B 4Y8', 'sidc_member', 'customer', '2023-07-10 10:20:00', '2023-11-10 16:45:00', 0, '2023-11-10', 0, 275, 1, '2023-11-10'),
(28, 'Michael Chen', 'michael.chen@email.com', 'mike789pass', '555-0103', 'male', '1995-03-30', '789 King St', 'Calgary', 'Alberta', 'T2G 0B3', 'non_member', 'customer', '2023-08-05 13:15:00', '2023-12-15 10:20:00', 0, '2023-12-15', 0, 80, 1, '2023-12-15'),
(29, 'Emily Davis', 'emily.davis@email.com', 'emily2024!', '555-0104', 'female', '1992-12-10', '321 Pine Rd', 'Montreal', 'Quebec', 'H3A 0G4', 'regular', 'customer', '2023-09-12 07:45:00', '2024-01-14 18:30:00', 5, '2024-01-14', 1, 420, 1, '2024-01-14'),
(30, 'Robert Wilson', 'robert.w@email.com', 'bobwilson99', '555-0105', 'male', '1985-07-18', '654 Elm St', 'Ottawa', 'Ontario', 'K1P 5J7', 'sidc_member', 'customer', '2023-10-20 12:10:00', '2024-01-13 09:15:00', 3, '2024-01-13', 1, 190, 1, '2024-01-13'),
(31, 'Lisa Anderson', 'lisa.anderson@email.com', 'lisaPass321', '555-0106', 'female', '1993-04-12', '789 Maple Dr', 'Edmonton', 'Alberta', 'T5J 2R7', 'sidc_member', 'customer', '2022-08-15 09:30:00', '2023-05-10 14:20:00', 0, '2023-05-10', 0, 850, 0, '2023-05-10'),
(32, 'David Martinez', 'david.m@email.com', 'davidm888', '555-0107', 'male', '1987-11-03', '234 Birch Ln', 'Winnipeg', 'Manitoba', 'R3C 1S5', 'sidc_member', 'customer', '2022-05-10 15:45:00', '2023-04-15 10:10:00', 0, '2023-04-15', 0, 1200, 0, '2023-04-15'),
(33, 'Jennifer Lee', 'jennifer.lee@email.com', 'jenLee2024!', '555-0108', 'female', '1991-09-17', '567 Cedar St', 'Halifax', 'Nova Scotia', 'B3J 2K9', 'non_member', 'customer', '2023-01-01 09:15:00', '2023-01-01 09:15:00', 0, NULL, 0, 45, 0, NULL),
(34, 'Kevin Brown', 'kevin.brown@email.com', 'kbrownSecure1', '555-0109', 'male', '1982-12-25', '890 Spruce Ave', 'Quebec City', 'Quebec', 'G1R 2H7', 'sidc_member', 'customer', '2021-03-22 12:20:00', '2023-01-05 09:30:00', 0, '2023-01-05', 0, 1500, 0, '2023-01-05'),
(35, 'Amanda Taylor', 'amanda.t@email.com', 'amandaT99#', '555-0110', 'female', '1994-06-08', '123 Willow Way', 'Victoria', 'British Columbia', 'V8W 1J6', 'sidc_member', 'customer', '2022-11-30 11:50:00', '2023-03-01 17:25:00', 0, '2023-03-01', 0, 950, 0, '2023-03-01'),
(36, 'BOSS1', 'BOSS1@GMAIL.COM', '$2y$10$Z7uPH/1ew5WLe8FmGSu0oO7Rw0eqq3xmfZt9hrDVpTMqJuPVTBuGG', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-10-29 16:01:23', '2025-11-20 02:08:32', 1, '2025-11-19', 0, 0, 0, NULL),
(37, 'Maria Santos', 'maria.santos@example.com', '$2y$10$WfR.2x1E3lf1M6MylsH3OeZgH3U3G6B4m1pufrSJi8mXZkbnZb0hy', '09171234567', 'female', '1995-06-15', '123 Mabini Street', 'San Pablo City', 'Laguna', '4000', 'regular', 'customer', '2025-06-05 23:00:00', NULL, 0, '2025-06-06', 0, 0, 0, NULL),
(38, 'High Risk Customer', 'highrisk.customer@example.com', '$2y$10$ChurnRisk99!hashedpassword123456789', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-06 19:57:18', NULL, 0, '2025-09-07', 0, 0, 0, '2025-09-22'),
(40, 'Inactive User', 'inactive@test.com', '$2y$10$bwqcMWVgslh7hypf12BBme/sJHtIXeo6C7j7if7zXKOkEsiJ1rxmu', '09171111111', 'male', '1995-01-01', '123 Inactive Street', 'San Pablo', 'Laguna', '4000', 'regular', 'customer', '2024-01-01 00:00:00', '2024-01-01 00:00:00', 0, '2024-01-01', 0, 0, 0, '2024-01-01'),
(41, 'bsin', 'bsin@gmail.com', '$2y$10$NOpsHXU6dUig3VGRr2kkd.03LBIypICk8opXylUSW1D.UbhnjMR/i', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-07 11:46:30', NULL, 0, NULL, 0, 0, 0, NULL),
(42, 'Ariel Arvie Reyes', 'bembangtheory1234@gmail.com', '$2y$10$DSZnPHIv.mA5IfaGpmR7junJ5eoVWtQqTGIjjORIX/scBqEImKK9G', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-17 14:53:13', '2025-11-18 10:40:06', 1, '2025-11-18', 0, 0, 0, NULL),
(43, 'Patrick Brcucal', 'patty@gmail.com', '$2y$10$oRg2uCaCBT6AdLjwEwZKNO0blF1XhSTLO9VajfRKYqoTV.O5Ntzwq', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-18 05:33:29', '2025-11-18 05:34:24', 1, '2025-11-18', 0, 0, 0, NULL),
(44, 'Dante Aguilar', 'dantejr9812@gmail.com', '$2y$10$fDX1/13z60PjrbHFmHzb2eDkjNBmQB0IbH2j4iav2aecaosD.Is56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-19 09:47:15', '2025-11-19 09:50:46', 1, '2025-11-19', 0, 0, 0, NULL),
(45, 'Nicko C. Albes', 'nickoalbes@gmail.com', '$2y$10$M9ZLXjzEW6133akI36kyIu34xoOEo22y8pPEChxL5pg5D9SMcsxki', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-20 02:49:02', NULL, 0, NULL, 0, 0, 0, NULL),
(46, 'Nicko C. Albes', 'nicko@gmail.com', '$2y$10$iUDhOvO7175ZEeKjqqutQu/dP6JFSwxsNIcS3/dW9JDwRdj4j8te6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-20 02:53:09', '2025-11-20 03:15:02', 1, '2025-11-19', 0, 0, 0, NULL),
(47, 'Chresianne Mae Zabala', 'chresianne987@gmail.com', '$2y$10$HHTND8ZL0WwMbsp99aWKYu/ucWIbG/cO5RHya.dqURcc1RkXiZA.G', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-20 14:36:27', '2025-11-20 14:39:09', 1, '2025-11-20', 0, 130, 3, '2025-11-20'),
(48, 'hambri', 'hambriniltan@gmail.com', '$2y$10$yOdJg8QIut4jS0JEvGRG2O/lgpD1BtepAwwyuIFLxq7/I1kKysMzC', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-20 14:37:01', '2025-11-20 14:45:02', 1, '2025-11-20', 0, 0, 0, NULL),
(49, 'Hannah Perona', 'hanzzelllp8@gmail.com', '$2y$10$nKj1MjiYKMbRlBVCwh0n5eu6knWvCJU4fqKa9MtaLjWvOA3Ni4H1.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'regular', 'customer', '2025-11-21 00:37:55', '2025-11-21 00:41:01', 0, NULL, 0, 0, 3, '2025-11-20');

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
(23, 2, 37, 15, '60.00', '5.00', 'inactivity', '2025-10-15 00:15:19', '2025-10-16 00:15:19', 1, '2025-10-15 18:14:20', NULL, 0, NULL, NULL),
(24, 2, 38, 23, '15.00', '5.00', 'inactivity', '2025-10-15 00:15:19', '2025-10-16 00:15:19', 1, '2025-10-15 18:14:20', NULL, 0, NULL, NULL),
(25, 2, 39, 14, '130.00', '5.00', 'inactivity', '2025-10-15 00:15:19', '2025-10-16 00:15:19', 1, '2025-10-15 18:14:20', NULL, 0, NULL, NULL),
(26, 2, 40, 13, '230.00', '5.00', 'inactivity', '2025-10-15 00:15:19', '2025-10-16 00:15:19', 1, '2025-10-15 18:14:20', NULL, 0, NULL, NULL),
(27, 2, 41, 54, '35.00', '5.00', 'inactivity', '2025-10-15 00:15:19', '2025-10-16 00:15:19', 1, '2025-10-15 18:14:20', NULL, 0, NULL, NULL),
(47, 2, 185, 25, '12.00', '5.00', 'inactivity', '2025-10-27 19:25:30', '2025-10-28 19:25:30', 1, '2025-10-27 20:30:38', NULL, 0, NULL, NULL),
(48, 2, 186, 28, '15.00', '5.00', 'inactivity', '2025-10-27 19:25:30', '2025-10-28 19:25:30', 1, '2025-10-27 20:30:38', NULL, 0, NULL, NULL),
(49, 2, 187, 13, '230.00', '5.00', 'inactivity', '2025-10-27 19:25:30', '2025-10-28 19:25:30', 1, '2025-10-27 20:30:38', NULL, 0, NULL, NULL),
(73, 36, 0, 0, '0.00', '0.00', '', '2025-11-17 18:15:25', '2025-11-24 18:15:25', 1, '2025-11-17 12:35:37', '20.00', 0, 'RETENTION96128B', 'Retention offer: Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(74, 36, 0, 0, '0.00', '0.00', '', '2025-11-17 18:15:31', '2025-11-24 18:15:31', 1, '2025-11-17 11:18:25', '20.00', 0, 'RETENTION9FC1AD', 'Retention offer: Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(75, 36, 0, 0, '0.00', '0.00', '', '2025-11-17 19:37:14', '2025-11-24 19:37:14', 1, '2025-11-17 12:39:58', '20.00', 0, 'RETENTIONE4489E', 'Retention offer: Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(76, 36, 0, 0, '0.00', '0.00', '', '2025-11-17 19:38:12', '2025-11-24 19:38:12', 1, '2025-11-17 12:39:56', '20.00', 0, 'RETENTIONC18F2B', 'Retention offer: Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(77, 36, 0, 0, '0.00', '0.00', '', '2025-11-17 19:38:13', '2025-11-24 19:38:13', 1, '2025-11-17 12:39:55', '20.00', 0, 'RETENTIONC60502', 'Retention offer: Welcome to CoopMart, BOSS1! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(78, 24, 0, 0, '0.00', '0.00', '', '2025-11-17 20:29:42', '2025-11-24 20:29:42', 0, NULL, '20.00', 0, 'RETENTIONECBB43', 'Retention offer: Welcome to CoopMart, Arnel Brucal! We\'d love for you to try us out. Enjoy 20% off your first order plus 100 bonus points to get you started!'),
(82, 2, 199, 15, '60.00', '5.00', 'inactivity', '2025-11-20 02:02:34', '2025-11-21 02:02:34', 1, '2025-11-20 02:04:32', NULL, 0, NULL, NULL),
(83, 2, 198, 23, '15.00', '5.00', 'inactivity', '2025-11-20 02:02:34', '2025-11-21 02:02:34', 1, '2025-11-20 02:04:32', NULL, 0, NULL, NULL),
(84, 2, 204, 25, '12.00', '5.00', 'inactivity', '2025-11-20 02:02:34', '2025-11-21 02:02:34', 1, '2025-11-20 02:04:32', NULL, 0, NULL, NULL),
(85, 2, 210, 28, '15.00', '5.00', 'inactivity', '2025-11-20 02:02:34', '2025-11-21 02:02:34', 1, '2025-11-20 02:04:32', NULL, 0, NULL, NULL);

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=225;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=238;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `retention_offers`
--
ALTER TABLE `retention_offers`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `spin_discounts`
--
ALTER TABLE `spin_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `user_discounts`
--
ALTER TABLE `user_discounts`
  MODIFY `user_discount_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

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
