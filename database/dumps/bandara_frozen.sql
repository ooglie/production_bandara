-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Apr 12, 2026 at 02:11 AM
-- Server version: 8.0.40
-- PHP Version: 8.4.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bandara_frozen`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cta_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cta_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `show_on_home` tinyint(1) NOT NULL DEFAULT '1',
  `is_dismissible` tinyint(1) NOT NULL DEFAULT '1',
  `priority` int UNSIGNED NOT NULL DEFAULT '0',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `background_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attributes`
--

CREATE TABLE `attributes` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frontend_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'select',
  `is_filterable` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attribute_values`
--

CREATE TABLE `attribute_values` (
  `id` bigint UNSIGNED NOT NULL,
  `attribute_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `position` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `b2b_customer_products`
--

CREATE TABLE `b2b_customer_products` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `min_order_quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by_id` bigint UNSIGNED DEFAULT NULL,
  `updated_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bandara_credit_transactions`
--

CREATE TABLE `bandara_credit_transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `amount` int NOT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'posted',
  `idempotency_key` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bandara_credit_wallets`
--

CREATE TABLE `bandara_credit_wallets` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `balance` int UNSIGNED NOT NULL DEFAULT '0',
  `tier` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'silver',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('bandara-by-maytira-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:22:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:15:\"manage products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:7;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"view products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:7;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:13:\"manage orders\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:11:\"view orders\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:15:\"manage invoices\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:13:\"view invoices\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:16:\"manage customers\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:14:\"view customers\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:14:\"manage vendors\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:7;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:12:\"view vendors\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:7;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:14:\"manage tickets\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:12:\"view tickets\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:15:\"manage settings\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:12:\"view reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:14:\"manage coupons\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:12:\"manage users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:16:\"manage marketing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:22:\"manage vendor payments\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:21:\"create vendor invoice\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:12:\"manage sales\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:13:\"view payments\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:13:\"manage stores\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:7;}}}s:5:\"roles\";a:5:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"Admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:7:\"Manager\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:6:\"Stores\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:13:\"CA-Accountant\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:7:\"Support\";s:1:\"c\";s:3:\"web\";}}}', 1776046071);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `session_id`, `coupon_id`, `created_at`, `updated_at`) VALUES
(318, NULL, '5ykRHjpiSzxkMu4YyxhqmOrHcf24XBYQ53DGSKhB', NULL, '2026-03-24 05:32:33', '2026-03-24 07:36:36'),
(322, NULL, 'nXeUcvzxDxItSbOwERqhaWHGWaPuapQhbuxk97kJ', NULL, '2026-04-09 01:13:02', '2026-04-09 01:13:02');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint UNSIGNED NOT NULL,
  `cart_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `item_weight` decimal(10,3) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `position` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `is_active`, `position`, `created_at`, `updated_at`, `deleted_at`) VALUES
(27, NULL, 'Seafood', 'seafood', 'Prawns, fish and everything related to fish local and international', 1, 0, '2026-03-06 10:07:53', '2026-03-06 10:07:53', NULL),
(28, NULL, 'Lamb', 'lamb', 'Lamb', 1, 0, '2026-03-06 10:08:16', '2026-03-06 10:08:16', NULL),
(29, NULL, 'Mutton', 'mutton', 'Mutton', 1, 0, '2026-03-06 10:08:23', '2026-03-06 10:08:23', NULL),
(30, NULL, 'Pork', 'pork', 'pork', 1, 0, '2026-03-06 10:08:30', '2026-03-06 10:08:30', NULL),
(31, NULL, 'Processed meat', 'processed-meat', 'Processed', 1, 0, '2026-03-06 10:09:09', '2026-03-06 10:09:09', NULL),
(32, NULL, 'Dairy', 'dairy', NULL, 1, 0, '2026-03-17 09:20:03', '2026-03-17 09:20:03', NULL),
(33, NULL, 'Vegetarian', 'vegetarian', NULL, 1, 0, '2026-03-17 09:20:27', '2026-03-17 09:20:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `category_product`
--

CREATE TABLE `category_product` (
  `category_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` bigint UNSIGNED NOT NULL,
  `country_code` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IN',
  `state_code` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `country_code`, `state_code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'IN', 'MH', 'Mumbai', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(2, 'IN', 'MH', 'Pune', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(3, 'IN', 'MH', 'Nagpur', 1, 3, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(4, 'IN', 'MH', 'Nashik', 1, 4, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(5, 'IN', 'MH', 'Thane', 1, 5, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(6, 'IN', 'DL', 'New Delhi', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(7, 'IN', 'DL', 'Delhi', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(8, 'IN', 'KA', 'Bengaluru', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(9, 'IN', 'KA', 'Mysuru', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(10, 'IN', 'KA', 'Mangaluru', 1, 3, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(11, 'IN', 'TN', 'Chennai', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(12, 'IN', 'TN', 'Coimbatore', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(13, 'IN', 'TN', 'Madurai', 1, 3, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(14, 'IN', 'TS', 'Hyderabad', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(15, 'IN', 'TS', 'Warangal', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(16, 'IN', 'GJ', 'Ahmedabad', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(17, 'IN', 'GJ', 'Surat', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(18, 'IN', 'GJ', 'Vadodara', 1, 3, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(19, 'IN', 'GJ', 'Rajkot', 1, 4, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(20, 'IN', 'WB', 'Kolkata', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(21, 'IN', 'WB', 'Siliguri', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(22, 'IN', 'UP', 'Lucknow', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(23, 'IN', 'UP', 'Kanpur', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(24, 'IN', 'UP', 'Noida', 1, 3, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(25, 'IN', 'UP', 'Varanasi', 1, 4, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(26, 'IN', 'RJ', 'Jaipur', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(27, 'IN', 'RJ', 'Jodhpur', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(28, 'IN', 'RJ', 'Udaipur', 1, 3, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(29, 'IN', 'KL', 'Kochi', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(30, 'IN', 'KL', 'Thiruvananthapuram', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(31, 'IN', 'KL', 'Kozhikode', 1, 3, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(32, 'IN', 'GA', 'Panaji', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(33, 'IN', 'GA', 'Margao', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(34, 'IN', 'PB', 'Ludhiana', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(35, 'IN', 'PB', 'Amritsar', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(36, 'IN', 'PB', 'Jalandhar', 1, 3, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(37, 'IN', 'HR', 'Gurugram', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(38, 'IN', 'HR', 'Faridabad', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(39, 'IN', 'MP', 'Bhopal', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(40, 'IN', 'MP', 'Indore', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(41, 'IN', 'OD', 'Bhubaneswar', 1, 1, '2026-02-02 19:15:05', '2026-02-02 19:15:05'),
(42, 'IN', 'OD', 'Cuttack', 1, 2, '2026-02-02 19:15:05', '2026-02-02 19:15:05');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `code` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`code`, `name`) VALUES
('AD', 'Andorra'),
('AE', 'United Arab Emirates'),
('AF', 'Afghanistan'),
('AG', 'Antigua and Barbuda'),
('AI', 'Anguilla'),
('AL', 'Albania'),
('AM', 'Armenia'),
('AO', 'Angola'),
('AQ', 'Antarctica'),
('AR', 'Argentina'),
('AS', 'American Samoa'),
('AT', 'Austria'),
('AU', 'Australia'),
('AW', 'Aruba'),
('AX', 'Åland Islands'),
('AZ', 'Azerbaijan'),
('BA', 'Bosnia and Herzegovina'),
('BB', 'Barbados'),
('BD', 'Bangladesh'),
('BE', 'Belgium'),
('BF', 'Burkina Faso'),
('BG', 'Bulgaria'),
('BH', 'Bahrain'),
('BI', 'Burundi'),
('BJ', 'Benin'),
('BL', 'Saint Barthélemy'),
('BM', 'Bermuda'),
('BN', 'Brunei Darussalam'),
('BO', 'Bolivia (Plurinational State of)'),
('BQ', 'Bonaire, Sint Eustatius and Saba'),
('BR', 'Brazil'),
('BS', 'Bahamas'),
('BT', 'Bhutan'),
('BV', 'Bouvet Island'),
('BW', 'Botswana'),
('BY', 'Belarus'),
('BZ', 'Belize'),
('CA', 'Canada'),
('CC', 'Cocos (Keeling) Islands'),
('CD', 'Congo (Democratic Republic of the)'),
('CF', 'Central African Republic'),
('CG', 'Congo'),
('CH', 'Switzerland'),
('CI', 'Côte d\'Ivoire'),
('CK', 'Cook Islands'),
('CL', 'Chile'),
('CM', 'Cameroon'),
('CN', 'China'),
('CO', 'Colombia'),
('CR', 'Costa Rica'),
('CU', 'Cuba'),
('CV', 'Cabo Verde'),
('CW', 'Curaçao'),
('CX', 'Christmas Island'),
('CY', 'Cyprus'),
('CZ', 'Czechia'),
('DE', 'Germany'),
('DJ', 'Djibouti'),
('DK', 'Denmark'),
('DM', 'Dominica'),
('DO', 'Dominican Republic'),
('DZ', 'Algeria'),
('EC', 'Ecuador'),
('EE', 'Estonia'),
('EG', 'Egypt'),
('EH', 'Western Sahara'),
('ER', 'Eritrea'),
('ES', 'Spain'),
('ET', 'Ethiopia'),
('FI', 'Finland'),
('FJ', 'Fiji'),
('FK', 'Falkland Islands (Malvinas)'),
('FM', 'Micronesia (Federated States of)'),
('FO', 'Faroe Islands'),
('FR', 'France'),
('GA', 'Gabon'),
('GB', 'United Kingdom of Great Britain and Northern Ireland'),
('GD', 'Grenada'),
('GE', 'Georgia'),
('GF', 'French Guiana'),
('GG', 'Guernsey'),
('GH', 'Ghana'),
('GI', 'Gibraltar'),
('GL', 'Greenland'),
('GM', 'Gambia'),
('GN', 'Guinea'),
('GP', 'Guadeloupe'),
('GQ', 'Equatorial Guinea'),
('GR', 'Greece'),
('GS', 'South Georgia and the South Sandwich Islands'),
('GT', 'Guatemala'),
('GU', 'Guam'),
('GW', 'Guinea-Bissau'),
('GY', 'Guyana'),
('HK', 'Hong Kong'),
('HM', 'Heard Island and McDonald Islands'),
('HN', 'Honduras'),
('HR', 'Croatia'),
('HT', 'Haiti'),
('HU', 'Hungary'),
('ID', 'Indonesia'),
('IE', 'Ireland'),
('IL', 'Israel'),
('IM', 'Isle of Man'),
('IN', 'India'),
('IO', 'British Indian Ocean Territory'),
('IQ', 'Iraq'),
('IR', 'Iran (Islamic Republic of)'),
('IS', 'Iceland'),
('IT', 'Italy'),
('JE', 'Jersey'),
('JM', 'Jamaica'),
('JO', 'Jordan'),
('JP', 'Japan'),
('KE', 'Kenya'),
('KG', 'Kyrgyzstan'),
('KH', 'Cambodia'),
('KI', 'Kiribati'),
('KM', 'Comoros'),
('KN', 'Saint Kitts and Nevis'),
('KP', 'Korea (Democratic People\'s Republic of)'),
('KR', 'Korea (Republic of)'),
('KW', 'Kuwait'),
('KY', 'Cayman Islands'),
('KZ', 'Kazakhstan'),
('LA', 'Lao People\'s Democratic Republic'),
('LB', 'Lebanon'),
('LC', 'Saint Lucia'),
('LI', 'Liechtenstein'),
('LK', 'Sri Lanka'),
('LR', 'Liberia'),
('LS', 'Lesotho'),
('LT', 'Lithuania'),
('LU', 'Luxembourg'),
('LV', 'Latvia'),
('LY', 'Libya'),
('MA', 'Morocco'),
('MC', 'Monaco'),
('MD', 'Moldova (Republic of)'),
('ME', 'Montenegro'),
('MF', 'Saint Martin (French part)'),
('MG', 'Madagascar'),
('MH', 'Marshall Islands'),
('MK', 'North Macedonia'),
('ML', 'Mali'),
('MM', 'Myanmar'),
('MN', 'Mongolia'),
('MO', 'Macao'),
('MP', 'Northern Mariana Islands'),
('MQ', 'Martinique'),
('MR', 'Mauritania'),
('MS', 'Montserrat'),
('MT', 'Malta'),
('MU', 'Mauritius'),
('MV', 'Maldives'),
('MW', 'Malawi'),
('MX', 'Mexico'),
('MY', 'Malaysia'),
('MZ', 'Mozambique'),
('NA', 'Namibia'),
('NC', 'New Caledonia'),
('NE', 'Niger'),
('NF', 'Norfolk Island'),
('NG', 'Nigeria'),
('NI', 'Nicaragua'),
('NL', 'Netherlands'),
('NO', 'Norway'),
('NP', 'Nepal'),
('NR', 'Nauru'),
('NU', 'Niue'),
('NZ', 'New Zealand'),
('OM', 'Oman'),
('PA', 'Panama'),
('PE', 'Peru'),
('PF', 'French Polynesia'),
('PG', 'Papua New Guinea'),
('PH', 'Philippines'),
('PK', 'Pakistan'),
('PL', 'Poland'),
('PM', 'Saint Pierre and Miquelon'),
('PN', 'Pitcairn'),
('PR', 'Puerto Rico'),
('PS', 'Palestine, State of'),
('PT', 'Portugal'),
('PW', 'Palau'),
('PY', 'Paraguay'),
('QA', 'Qatar'),
('RE', 'Réunion'),
('RO', 'Romania'),
('RS', 'Serbia'),
('RU', 'Russian Federation'),
('RW', 'Rwanda'),
('SA', 'Saudi Arabia'),
('SB', 'Solomon Islands'),
('SC', 'Seychelles'),
('SD', 'Sudan'),
('SE', 'Sweden'),
('SG', 'Singapore'),
('SH', 'Saint Helena, Ascension and Tristan da Cunha'),
('SI', 'Slovenia'),
('SJ', 'Svalbard and Jan Mayen'),
('SK', 'Slovakia'),
('SL', 'Sierra Leone'),
('SM', 'San Marino'),
('SN', 'Senegal'),
('SO', 'Somalia'),
('SR', 'Suriname'),
('SS', 'South Sudan'),
('ST', 'Sao Tome and Principe'),
('SV', 'El Salvador'),
('SX', 'Sint Maarten (Dutch part)'),
('SY', 'Syrian Arab Republic'),
('SZ', 'Eswatini'),
('TC', 'Turks and Caicos Islands'),
('TD', 'Chad'),
('TF', 'French Southern Territories'),
('TG', 'Togo'),
('TH', 'Thailand'),
('TJ', 'Tajikistan'),
('TK', 'Tokelau'),
('TL', 'Timor-Leste'),
('TM', 'Turkmenistan'),
('TN', 'Tunisia'),
('TO', 'Tonga'),
('TR', 'Türkiye'),
('TT', 'Trinidad and Tobago'),
('TV', 'Tuvalu'),
('TW', 'Taiwan (Province of China)'),
('TZ', 'Tanzania, United Republic of'),
('UA', 'Ukraine'),
('UG', 'Uganda'),
('UM', 'United States Minor Outlying Islands'),
('US', 'United States of America'),
('UY', 'Uruguay'),
('UZ', 'Uzbekistan'),
('VA', 'Holy See'),
('VC', 'Saint Vincent and the Grenadines'),
('VE', 'Venezuela (Bolivarian Republic of)'),
('VG', 'Virgin Islands (British)'),
('VI', 'Virgin Islands (U.S.)'),
('VN', 'Viet Nam'),
('VU', 'Vanuatu'),
('WF', 'Wallis and Futuna'),
('WS', 'Samoa'),
('XK', 'Kosovo'),
('YE', 'Yemen'),
('YT', 'Mayotte'),
('ZA', 'South Africa'),
('ZM', 'Zambia'),
('ZW', 'Zimbabwe');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_type` enum('flat','percent') COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `usage_limit_per_user` int DEFAULT NULL,
  `usage_count` int NOT NULL DEFAULT '0',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_by_id` bigint NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `name`, `description`, `discount_type`, `discount_value`, `max_discount_amount`, `min_order_amount`, `usage_limit`, `usage_limit_per_user`, `usage_count`, `starts_at`, `ends_at`, `is_active`, `created_by_id`, `created_at`, `updated_by_id`, `updated_at`, `deleted_at`) VALUES
(2, 'save10', NULL, '10% discount on all products', 'percent', 10.00, NULL, NULL, NULL, 2, 1, NULL, NULL, 1, 1, '2026-03-24 14:10:20', 1, '2026-03-24 14:10:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `coupon_redemptions`
--

CREATE TABLE `coupon_redemptions` (
  `id` bigint UNSIGNED NOT NULL,
  `coupon_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `redeemed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupon_redemptions`
--

INSERT INTO `coupon_redemptions` (`id`, `coupon_id`, `user_id`, `order_id`, `discount_amount`, `redeemed_at`, `created_at`, `updated_at`) VALUES
(5, 2, 40, NULL, 47.62, '2026-04-02 16:12:20', '2026-04-02 16:12:20', '2026-04-02 16:12:20');

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `pincode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gstin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default_shipping` tinyint(1) NOT NULL DEFAULT '0',
  `is_default_billing` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_addresses`
--

INSERT INTO `customer_addresses` (`id`, `user_id`, `label`, `full_name`, `phone`, `address_line1`, `address_line2`, `city`, `state`, `state_code`, `country`, `pincode`, `gstin`, `is_default_shipping`, `is_default_billing`, `created_at`, `updated_at`, `deleted_at`) VALUES
(7, 33, NULL, 'kaustubh', '9082916969', 'talli,lane 7, koregaon prak', NULL, 'Pune', 'Maharashtra', 'MH', 'India', '411001', NULL, 1, 0, '2026-03-18 15:31:08', '2026-03-18 15:31:08', NULL),
(8, 40, NULL, 'Disha Barve', '9823170102', 'Nityanand Complex', 'Narangi Baug Road', 'Pune', 'Maharashtra', 'MH', 'India', '411001', NULL, 1, 1, '2026-03-21 05:30:34', '2026-03-22 05:19:22', '2026-03-22 05:19:22'),
(9, 40, NULL, 'Disha Barve', '9823170102', '303B, Nityanand Complex', 'Narangi Baug Road', 'Pune', 'Maharashtra', 'MH', 'India', '411001', NULL, 1, 1, '2026-03-22 06:07:14', '2026-03-22 06:24:02', '2026-03-22 06:24:02'),
(10, 40, NULL, 'Disha Barve', '9823170102', '303B, Nityanand Complex', 'BG Road', 'Pune', 'Maharashtra', 'MH', 'India', '411001', NULL, 1, 1, '2026-03-22 06:24:53', '2026-03-22 06:26:39', '2026-03-22 06:26:39'),
(11, 40, NULL, 'Disha Barve', '9823170102', 'Nityanand', 'BG Road', 'Pune', 'Maharashtra', 'MH', 'India', '411001', NULL, 1, 1, '2026-03-22 06:26:59', '2026-03-22 06:28:46', '2026-03-22 06:28:46'),
(12, 40, NULL, 'Disha Barve', '9823170102', 'BG Road', 'Narangi', 'Pune', 'Maharashtra', 'MH', 'India', '411001', NULL, 1, 1, '2026-03-22 06:30:29', '2026-03-22 06:31:24', '2026-03-22 06:31:24'),
(13, 40, NULL, 'Disha Barve', '9823170102', 'BG Rd', 'Nityanandc', 'Pune', 'Maharashtra', 'MH', 'India', '411001', NULL, 1, 1, '2026-03-22 06:31:50', '2026-03-22 06:32:17', '2026-03-22 06:32:17'),
(14, 40, NULL, 'Disha Barve', '9823170102', 'BG Rd', 'Nityanand', 'Pune', 'Maharashtra', 'MH', 'India', '411001', NULL, 1, 1, '2026-03-22 06:33:23', '2026-03-22 06:40:36', '2026-03-22 06:40:36'),
(15, 40, NULL, 'Disha Barve', '9823170102', 'BG Road', 'Nityanandc', 'Pune', 'Maharashtra', 'MH', 'India', '411001', NULL, 1, 1, '2026-03-22 06:40:59', '2026-03-22 06:41:22', '2026-03-22 06:41:22'),
(16, 40, NULL, 'Disha Barve', '9823170102', 'Nityanand', 'BG Road', 'Pune', 'Maharashtra', 'MH', 'India', '411001', NULL, 1, 1, '2026-03-22 06:41:41', '2026-03-22 06:41:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_product_prices`
--

CREATE TABLE `customer_product_prices` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INR',
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by_id` bigint UNSIGNED DEFAULT NULL,
  `updated_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hsn_codes`
--

CREATE TABLE `hsn_codes` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gst_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hsn_codes`
--

INSERT INTO `hsn_codes` (`id`, `code`, `gst_rate`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(2, '04069000', 5.00, 'Cheese and curd - Other cheese', 'Specific types of cheese, such as matured, ripened, or other processed cheeses that are not fresh, grated, powdered, or blue-veined.', 1, '2026-02-01 06:48:14', '2026-02-01 06:48:14'),
(3, '03048100', 5.00, 'Frozen fillets of Pacific salmon, Atlantic salmon, and Danube salmon', 'It covers various salmon species like Oncorhynchus nerka, Salmo salar, and Hucho hucho', 1, '2026-02-01 06:49:46', '2026-02-01 06:49:46'),
(4, '16010000', 12.00, 'Sausages, sausages-like products, and, and in some interpretations, products based on insects', 'Sausages and similar products, of meat, meat offal, or blood; food preparations based on these products.  It covers processed meat items and not just raw meat products', 1, '2026-02-01 06:51:10', '2026-02-01 07:12:36'),
(5, '02032900', 5.00, 'Frozen meat of swine (other than carcasses, hams, and shoulders)', 'Boneless pork, pork pieces, and other frozen cuts, whether pre-packaged/labelled or not', 1, '2026-02-01 06:52:48', '2026-02-01 06:52:48'),
(6, '16024900', 12.00, 'Other Prepared Or Preserved Pork', 'It falls under Chapter 16 (Preparations of Meat, Fish, or Aquatic Invertebrates). This code covers products like prepared pork mixtures not classified elsewhere', 1, '2026-02-01 06:54:07', '2026-03-17 04:26:12'),
(7, '02031900', 5.00, 'Meat of swine, fresh or chilled, other.', 'Fresh or chilled meat of swine (pork), specifically excluding hams, shoulders, and their cuts with bone-in, falling under \"Other\" in the 0203 chapter of the Harmonized System of Nomenclature', 1, '2026-02-01 06:55:42', '2026-02-01 06:55:42'),
(8, '02072500', 5.00, 'Frozen whole turkey (not cut into pieces)', 'Meat and edible offal, of the poultry of heading 0105, fresh, chilled or frozen: Of turkeys: Not cut in pieces, frozen', 1, '2026-02-01 06:56:47', '2026-02-01 06:56:47'),
(9, '16043200', 5.00, 'Caviar substitutes prepared from fish eggs', 'Prepared or preserved fish; caviar and caviar substitutes prepared from fish eggs -- Caviar substitutes', 1, '2026-02-01 06:58:03', '2026-02-01 06:58:03'),
(10, '02032200', 5.00, 'Frozen swine meat (pork), specifically hams, shoulders, and their cuts with the bone in', 'Meat of swine, fresh, chilled or frozen; Frozen: Hams, shoulders and cuts thereof, with bone in', 1, '2026-02-01 06:59:01', '2026-02-01 06:59:01'),
(11, '03048990', 5.00, 'Frozen fillets of other fish', '\"Other\" frozen fish fillets and fish meat (whether or not minced), specifically classifying miscellaneous, unspecified frozen fish products under Chapter 3. This code applies to, for example, reefcod fillets or other unidentified frozen fish fillets', 1, '2026-02-01 07:00:10', '2026-02-01 07:00:10'),
(12, '02044200', 5.00, 'Frozen, bone-in cuts of sheep or goat meat (other than carcasses)', 'Meat of sheep or goats, fresh, chilled or frozen; other meat of sheep, frozen: other cuts with bone in', 1, '2026-02-01 07:02:40', '2026-02-01 07:02:40'),
(13, '02044300', 5.00, 'Frozen, boneless meat of sheep', 'Meat of sheep or goats, fresh, chilled or frozen; other meat of sheep, frozen; boneless', 1, '2026-02-01 07:03:18', '2026-02-01 07:03:18'),
(14, '996812', 18.00, 'Postal and Courier Services', 'Courier services (door-to-door, express, time-definite, and intercity deliveries)', 1, '2026-02-01 07:03:41', '2026-02-01 07:04:13'),
(15, '20041000', 5.00, 'Other vegetables (like potatoes) prepared/preserved (not pickled), and frozen', 'Potatoes prepared or preserved, but not by vinegar or acetic acid, and frozen, excluding products under heading 2006', 1, '2026-02-01 07:05:46', '2026-02-01 07:05:46'),
(16, '03061719', 0.00, 'Frozen shrimp, pawns and \"Other\" (Scampi', 'Other frozen shrimps and prawns (Scampi - Macrobrachium spp.)', 1, '2026-02-01 07:07:45', '2026-02-01 07:07:45'),
(17, '03044920', 0.00, 'Fresh or chilled fillets of Seer fish', 'Fresh or chilled fillets of Seer fish', 1, '2026-02-01 07:08:24', '2026-02-01 07:08:24'),
(18, '02031200', 0.00, 'Meat and edible meat offal', 'Fresh or chilled hams, shoulders, and their cuts, with bone-in, of swine (pork)', 1, '2026-02-01 07:09:41', '2026-02-01 07:09:41'),
(19, '02101200', 5.00, 'Meat and Edible Meat Offal', 'Meat of swine, specifically bellies (streaky) and cuts thereof, which are salted, in brine, dried, or smoked', 1, '2026-02-01 07:13:43', '2026-02-01 07:13:43'),
(20, '16042000', 12.00, 'Prepared Or Preserved Fish', 'Prepared Or Preserved Fish; Caviar And Caviar Substitutes Prepared From Fish Eggs Other Prepared Or Preserved Fish', 1, '2026-03-04 18:55:42', '2026-03-17 04:26:26');

-- --------------------------------------------------------

--
-- Table structure for table `impersonation_logs`
--

CREATE TABLE `impersonation_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `admin_id` bigint UNSIGNED NOT NULL,
  `impersonated_user_id` bigint UNSIGNED NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'impersonation',
  `started_at` timestamp NOT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `ended_reason` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_lots`
--

CREATE TABLE `inventory_lots` (
  `id` bigint UNSIGNED NOT NULL,
  `lot_code` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `vendor_id` bigint UNSIGNED DEFAULT NULL,
  `vendor_invoice_id` bigint UNSIGNED DEFAULT NULL,
  `vendor_invoice_item_id` bigint UNSIGNED DEFAULT NULL,
  `production_run_id` bigint UNSIGNED DEFAULT NULL,
  `parent_inventory_lot_id` bigint UNSIGNED DEFAULT NULL,
  `root_inventory_lot_id` bigint UNSIGNED DEFAULT NULL,
  `lot_stage` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'raw',
  `inward_mode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_saleable` tinyint(1) NOT NULL DEFAULT '1',
  `can_repack` tinyint(1) NOT NULL DEFAULT '0',
  `lot_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `batch_code` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `packed_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `received_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `available_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `unit_weight_kg` decimal(10,3) DEFAULT NULL,
  `total_weight_kg` decimal(12,3) DEFAULT NULL,
  `available_weight_kg` decimal(12,3) DEFAULT NULL,
  `piece_count` int UNSIGNED DEFAULT NULL,
  `available_piece_count` int UNSIGNED DEFAULT NULL,
  `pack_size_kg` decimal(10,3) DEFAULT NULL,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  `cost_per_kg` decimal(12,2) DEFAULT NULL,
  `total_cost` decimal(12,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by_id` bigint UNSIGNED DEFAULT NULL,
  `updated_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_pieces`
--

CREATE TABLE `inventory_pieces` (
  `id` bigint UNSIGNED NOT NULL,
  `inventory_lot_id` bigint UNSIGNED NOT NULL,
  `piece_no` int UNSIGNED NOT NULL,
  `weight_kg` decimal(10,3) NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `consumed_in_production_run_id` bigint UNSIGNED DEFAULT NULL,
  `sold_order_item_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','due','partial','past_due','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mailed_to_customer_at` timestamp NULL DEFAULT NULL,
  `mailed_to_accountant_at` timestamp NULL DEFAULT NULL,
  `tally_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` bigint UNSIGNED NOT NULL,
  `invoice_id` bigint UNSIGNED NOT NULL,
  `order_item_id` bigint UNSIGNED NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `sell_unit` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_weight` decimal(10,3) DEFAULT NULL,
  `pricing_unit` enum('pack','kg') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pack',
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_payments`
--

CREATE TABLE `invoice_payments` (
  `id` bigint UNSIGNED NOT NULL,
  `payment_id` bigint UNSIGNED NOT NULL,
  `invoice_id` bigint UNSIGNED NOT NULL,
  `amount_applied` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000001_create_cache_table', 1),
(2, '0001_01_01_000002_create_jobs_table', 1),
(3, '2025_01_01_000000_create_users_table', 1),
(4, '2025_01_01_000001_create_settings_table', 1),
(5, '2025_01_01_000002_create_categories_table', 1),
(6, '2025_01_01_000003_create_attributes_table', 1),
(7, '2025_01_01_000004_create_attribute_values_table', 1),
(8, '2025_01_01_000005_create_vendors_table', 1),
(9, '2025_01_01_000006_create_products_table', 1),
(10, '2025_01_01_000007_create_product_images_table', 1),
(11, '2025_01_01_000008_create_category_product_table', 1),
(12, '2025_01_01_000009_create_product_attribute_values_table', 1),
(13, '2025_01_01_000010_create_product_variants_table', 1),
(14, '2025_01_01_000011_create_variant_values_table', 1),
(15, '2025_01_01_000012_create_coupons_table', 1),
(16, '2025_01_01_000013_create_product_offers_table', 1),
(17, '2025_01_01_000014_create_customer_addresses_table', 1),
(18, '2025_01_01_000015_create_newsletter_subscribers_table', 1),
(19, '2025_01_01_000016_create_newsletter_campaigns_table', 1),
(20, '2025_01_01_000017_create_newsletter_campaign_recipients_table', 1),
(21, '2025_01_01_000018_create_carts_table', 1),
(22, '2025_01_01_000019_create_cart_items_table', 1),
(23, '2025_01_01_000020_create_orders_table', 1),
(24, '2025_01_01_000021_create_order_addresses_table', 1),
(25, '2025_01_01_000022_create_order_items_table', 1),
(26, '2025_01_01_000023_create_coupon_redemptions_table', 1),
(27, '2025_01_01_000024_create_payments_table', 1),
(28, '2025_01_01_000025_create_invoices_table', 1),
(29, '2025_01_01_000026_create_invoice_items_table', 1),
(30, '2025_01_01_000027_create_shipments_table', 1),
(31, '2025_01_01_000028_create_order_status_history_table', 1),
(32, '2025_01_01_000029_create_wishlists_table', 1),
(33, '2025_01_01_000030_create_out_of_stock_subscriptions_table', 1),
(34, '2025_01_01_000031_create_out_of_stock_notifications_table', 1),
(35, '2025_01_01_000032_create_vendor_invoices_table', 1),
(36, '2025_01_01_000033_create_vendor_invoice_items_table', 1),
(37, '2025_01_01_000034_create_vendor_payments_table', 1),
(38, '2025_01_01_000035_create_stock_movements_table', 1),
(39, '2025_01_01_000036_create_ticket_categories_table', 1),
(40, '2025_01_01_000037_create_ticket_tags_table', 1),
(41, '2025_01_01_000038_create_tickets_table', 1),
(42, '2025_01_01_000039_create_ticket_tag_ticket_table', 1),
(43, '2025_01_01_000040_create_ticket_messages_table', 1),
(44, '2025_01_01_000041_create_ticket_attachments_table', 1),
(45, '2025_01_01_000042_create_ticket_status_history_table', 1),
(46, '2025_01_01_000043_create_ticket_assignee_history_table', 1),
(47, '2025_12_05_082019_create_permission_tables', 1),
(48, '2025_12_05_082138_create_notifications_table', 1),
(49, '2025_12_05_095524_create_sessions_table', 2),
(50, '2025_12_05_175742_add_deleted_at_to_product_images_table', 3),
(51, '2025_12_05_143221_update_categories_unique_index', 4),
(52, '2025_12_06_115159_adjust_unique_index_on_products_slug', 4),
(53, '2025_12_06_115421_adjust_unique_index_on_attributes_slug', 4),
(54, '2025_12_06_115421_adjust_unique_index_on_categories_slug', 4),
(55, '2025_12_06_115421_adjust_unique_index_on_product_variant_sku', 4),
(56, '2025_12_06_115421_adjust_unique_index_on_vendors_code', 4),
(57, '2025_12_06_115422_adjust_unique_index_on_vendors_code', 5),
(58, '2025_12_06_144128_add_new_and_special_flags_to_products_table', 6),
(59, '2025_12_19_160051_create_impersonation_logs_table', 7),
(60, '2026_01_04_210559_add_barcode_to_products_table', 8),
(61, '2026_01_04_210703_add_barcode_to_product_variants_table', 9),
(62, '2026_01_07_173453_extend_payments_for_multi_invoice_and_manual', 10),
(63, '2026_01_07_173531_create_invoice_payments_table', 11),
(64, '2026_01_07_173612_alter_invoices_add_part_payment_status', 12),
(65, '2026_01_10_111824_create_password_reset_tokens_table', 13),
(66, '2026_01_13_122143_add_gst_fields_to_products_table', 14),
(67, '2026_01_13_151200_2026_01_13_000001_create_hsn_codes_table', 15),
(68, '2026_01_13_151222_2026_01_13_000002_add_hsn_code_id_to_products_table', 16),
(69, '2026_01_15_001812_2026_01_13_000010_add_print_tracking_to_orders_table', 17),
(70, '2026_01_15_000100_add_customer_type_to_users_table', 18),
(71, '2026_01_15_000101_create_customer_product_prices_table', 19),
(72, '2026_01_15_000102_create_b2b_customer_products_table', 20),
(73, '2026_01_18_000001_expand_users_customer_type_add_staff', 21),
(74, '2026_01_22_175037_2026_01_22_000001_create_inventory_lots_table', 22),
(75, '2026_01_22_175154_2026_01_22_000002_create_inventory_pieces_table', 23),
(76, '2026_01_23_223747_2026_01_23_000001_add_consumed_quantity_to_inventory_lots_table', 24),
(77, '2026_01_23_223919_2026_01_23_000002_create_production_runs_table', 25),
(78, '2026_01_23_224037_2026_01_23_000003_create_inventory_packs_table', 26),
(79, '2026_01_23_224213_2026_01_23_000004_add_production_run_id_to_inventory_pieces_table', 27),
(80, '2026_01_25_200225_add_pricing_unit_to_products_and_variants', 28),
(81, '2026_01_25_200249_add_item_weight_and_pricing_unit_to_order_and_invoice_items', 29),
(82, '2026_01_25_200305_add_item_weight_to_cart_items', 30),
(83, '2026_01_26_215006_add_units_to_products_table', 31),
(84, '2026_01_26_222845_add_sell_unit_to_products_and_item_weight_kg_to_order_items', 32),
(85, '2026_01_27_000640_2026_01_26_000001_add_sell_by_and_item_weight_kg', 33),
(86, '2026_01_27_102316_add_sell_unit_to_products_table', 34),
(87, '2026_01_27_102512_add_sell_unit_and_item_weight_to_order_items_table', 35),
(88, '2026_01_27_211951_simplify_products_units_add_product_weight', 36),
(89, '2026_01_27_212028_add_item_weight_to_order_items', 37),
(90, '12026_01_27_212028_add_item_weight_to_order_items', 38),
(91, '2026_01_28_120635_add_item_weight_to_order_and_invoice_items', 39),
(92, '2026_02_02_105320_add_country_of_origin_to_products_table', 40),
(93, '2026_02_02_122708_create_countries_table', 41),
(94, '2026_02_03_001419_create_states_table', 42),
(95, '2026_02_03_001518_create_cities_table', 43),
(96, '2026_03_15_110754_create_product_translations', 44),
(97, '2026_03_16_153151_create_recipes_table', 45),
(98, '2026_03_16_153226_create_product_recipe_table', 46),
(99, '2026_03_16_161217_upgrade_recipes_table_for_multilingual', 47),
(100, '2026_03_16_171256_create_languages_table', 48),
(101, '2026_03_16_202001_create_pages_table', 49),
(102, '2026_03_17_193734_add_weight_fields_to_vendor_invoice_items_table', 50),
(103, '2026_03_17_215030_add_vendor_invoice_item_id_to_inventory_lots_table', 51),
(104, '2026_03_18_095904_add_inventory_behavior_fields_to_products_table', 52),
(105, '2026_03_18_102315_rebuild_inventory_tables_and_add_production_io_tables', 53),
(106, '2026_03_18_102315_rebuild_inventory_tables_and_add_production_io_tables1', 54),
(107, '2026_03_19_205624_announcement', 55),
(108, '2026_03_20_124937_add_background_image_path_to_announcements_table', 56),
(109, '2026_03_21_150935_create_product_collections_table', 57),
(110, '2026_03_21_151051_create_product_collection_product_table', 58),
(111, '2026_03_23_161705_product_variant_attribute_values', 59),
(112, '2026_03_23_165932_create_product_variant_attribute_values_table', 60),
(113, '2026_04_10_082812_create_bandara_credit_wallets_table', 61),
(114, '2026_04_10_082945_create_bandara_credit_transactions_table', 62);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(5, 'App\\Models\\User', 2),
(5, 'App\\Models\\User', 3),
(2, 'App\\Models\\User', 28),
(7, 'App\\Models\\User', 29),
(5, 'App\\Models\\User', 30),
(3, 'App\\Models\\User', 31),
(5, 'App\\Models\\User', 32),
(5, 'App\\Models\\User', 33),
(5, 'App\\Models\\User', 39),
(5, 'App\\Models\\User', 40),
(2, 'App\\Models\\User', 41);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_campaigns`
--

CREATE TABLE `newsletter_campaigns` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_html` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_text` longtext COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','scheduled','sending','sent','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_campaign_recipients`
--

CREATE TABLE `newsletter_campaign_recipients` (
  `id` bigint UNSIGNED NOT NULL,
  `campaign_id` bigint UNSIGNED NOT NULL,
  `subscriber_id` bigint UNSIGNED NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `open_count` int NOT NULL DEFAULT '0',
  `last_opened_at` timestamp NULL DEFAULT NULL,
  `bounce_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unsubscribe_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','active','unsubscribed','bounced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `confirmation_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `name`, `status`, `confirmation_token`, `confirmed_at`, `unsubscribed_at`, `source`, `created_at`, `updated_at`) VALUES
(3, 'parag.parulekar@gmail.com', NULL, 'active', NULL, '2026-03-31 06:19:46', NULL, 'frontend_user', '2026-03-31 06:19:17', '2026-03-31 06:19:46');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint UNSIGNED NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `order_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `status` enum('processing','shipped','delivered','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'processing',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `coupon_id` bigint UNSIGNED DEFAULT NULL,
  `gst_type` enum('intra_state','inter_state') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cgst_amount` decimal(10,2) DEFAULT NULL,
  `sgst_amount` decimal(10,2) DEFAULT NULL,
  `igst_amount` decimal(10,2) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `razorpay_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razorpay_payment_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razorpay_signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_note` text COLLATE utf8mb4_unicode_ci,
  `placed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `cancelled_by_id` bigint UNSIGNED DEFAULT NULL,
  `tally_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tally_export_status` enum('not_exported','pending','exported','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_exported',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `printed_at` timestamp NULL DEFAULT NULL,
  `printed_by_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_addresses`
--

CREATE TABLE `order_addresses` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `type` enum('billing','shipping') COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'India',
  `pincode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gstin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attributes_snapshot` json DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `sell_unit` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_weight` decimal(10,3) DEFAULT NULL,
  `pricing_unit` enum('pack','kg') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pack',
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `cgst_amount` decimal(10,2) DEFAULT NULL,
  `sgst_amount` decimal(10,2) DEFAULT NULL,
  `igst_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `from_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `out_of_stock_notifications`
--

CREATE TABLE `out_of_stock_notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `subscription_id` bigint UNSIGNED NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_by_id` bigint UNSIGNED DEFAULT NULL,
  `channel` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `status` enum('sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `out_of_stock_subscriptions`
--

CREATE TABLE `out_of_stock_subscriptions` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','notified','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INR',
  `method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'razorpay',
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('created','authorized','captured','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created',
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_data` json DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `recorded_by_id` bigint UNSIGNED DEFAULT NULL,
  `cheque_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `cheque_bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_branch_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'manage products', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(2, 'view products', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(3, 'manage orders', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(4, 'view orders', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(5, 'manage invoices', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(6, 'view invoices', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(7, 'manage customers', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(8, 'view customers', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(9, 'manage vendors', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(10, 'view vendors', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(11, 'manage tickets', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(12, 'view tickets', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(13, 'manage settings', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(14, 'view reports', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(15, 'manage coupons', 'web', '2025-12-07 10:13:25', '2025-12-07 10:13:25'),
(16, 'manage users', 'web', '2025-12-19 10:05:29', '2025-12-19 10:05:29'),
(17, 'manage marketing', 'web', '2026-01-11 07:08:20', '2026-01-11 07:08:20'),
(18, 'manage vendor payments', 'web', '2026-01-11 07:08:20', '2026-01-11 07:08:20'),
(19, 'create vendor invoice', 'web', NULL, NULL),
(20, 'manage sales', 'web', NULL, NULL),
(21, 'view payments', 'web', '2026-01-18 07:09:06', '2026-01-18 07:09:06'),
(22, 'manage stores', 'web', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `production_runs`
--

CREATE TABLE `production_runs` (
  `id` bigint UNSIGNED NOT NULL,
  `run_number` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `run_date` date NOT NULL,
  `run_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `process_flow_json` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `input_weight_kg` decimal(12,3) DEFAULT NULL,
  `saleable_output_weight_kg` decimal(12,3) DEFAULT NULL,
  `trim_weight_kg` decimal(12,3) DEFAULT NULL,
  `waste_weight_kg` decimal(12,3) DEFAULT NULL,
  `yield_percent` decimal(8,2) DEFAULT NULL,
  `created_by_id` bigint UNSIGNED DEFAULT NULL,
  `updated_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `production_runs`
--

INSERT INTO `production_runs` (`id`, `run_number`, `run_date`, `run_type`, `status`, `process_flow_json`, `notes`, `input_weight_kg`, `saleable_output_weight_kg`, `trim_weight_kg`, `waste_weight_kg`, `yield_percent`, `created_by_id`, `updated_by_id`, `created_at`, `updated_at`) VALUES
(1, 'PR-20260318-183528-992', '2026-03-18', 'raw_to_slab', 'completed', '[{\"step\": \"slab\", \"inventory_output\": true}]', NULL, 4.500, 4.100, 0.000, 0.400, 91.11, 1, NULL, '2026-03-18 13:05:28', '2026-03-18 13:05:28'),
(2, 'PR-20260318-203559-535', '2026-03-18', 'raw_to_slab', 'completed', '[{\"step\": \"slab\", \"inventory_output\": true}]', NULL, 5.000, 1.350, 0.000, 0.000, 27.00, 1, NULL, '2026-03-18 15:05:59', '2026-03-18 15:05:59'),
(3, 'PR-20260318-203844-993', '2026-03-18', 'slab_to_slice', 'completed', '[{\"step\": \"slice\", \"inventory_output\": true}]', NULL, 0.650, 0.500, 0.000, 0.000, 76.92, 1, NULL, '2026-03-18 15:08:44', '2026-03-18 15:08:44'),
(4, 'PR-20260319-090850-211', '2026-03-19', 'raw_to_slab', 'completed', '[{\"step\": \"slab\", \"inventory_output\": true}]', NULL, 4.650, 4.000, 0.000, 0.650, 86.02, 1, NULL, '2026-03-19 03:38:50', '2026-03-19 03:38:50');

-- --------------------------------------------------------

--
-- Table structure for table `production_run_inputs`
--

CREATE TABLE `production_run_inputs` (
  `id` bigint UNSIGNED NOT NULL,
  `production_run_id` bigint UNSIGNED NOT NULL,
  `inventory_lot_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `consumed_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `consumed_weight_kg` decimal(12,3) NOT NULL DEFAULT '0.000',
  `consumed_piece_count` int UNSIGNED DEFAULT NULL,
  `unit_cost_snapshot` decimal(12,2) DEFAULT NULL,
  `total_cost_snapshot` decimal(12,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `production_run_outputs`
--

CREATE TABLE `production_run_outputs` (
  `id` bigint UNSIGNED NOT NULL,
  `production_run_id` bigint UNSIGNED NOT NULL,
  `inventory_lot_id` bigint UNSIGNED DEFAULT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `output_stage` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `produced_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `produced_weight_kg` decimal(12,3) NOT NULL DEFAULT '0.000',
  `piece_count` int UNSIGNED DEFAULT NULL,
  `unit_weight_kg` decimal(10,3) DEFAULT NULL,
  `pack_size_kg` decimal(10,3) DEFAULT NULL,
  `is_saleable` tinyint(1) NOT NULL DEFAULT '1',
  `can_repack` tinyint(1) NOT NULL DEFAULT '0',
  `inventory_output` tinyint(1) NOT NULL DEFAULT '1',
  `allocated_cost` decimal(12,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('simple','variable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'simple',
  `lot_stage_default` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inventory_is_saleable` tinyint(1) NOT NULL DEFAULT '1',
  `inventory_can_repack` tinyint(1) NOT NULL DEFAULT '0',
  `short_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `primary_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manage_stock` tinyint(1) NOT NULL DEFAULT '0',
  `stock_quantity` decimal(10,2) DEFAULT NULL,
  `low_stock_threshold` decimal(10,2) DEFAULT NULL,
  `min_order_quantity` decimal(10,2) DEFAULT NULL,
  `sell_unit` enum('pack','piece','kg') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'piece',
  `product_weight` decimal(10,3) DEFAULT NULL,
  `country_of_origin` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `mrp_price` decimal(10,2) NOT NULL,
  `price_includes_gst` tinyint(1) NOT NULL DEFAULT '0',
  `gst_rate` decimal(5,2) NOT NULL DEFAULT '5.00',
  `special_price` decimal(10,2) DEFAULT NULL,
  `special_starts_at` datetime DEFAULT NULL,
  `special_ends_at` datetime DEFAULT NULL,
  `dynamic_pricing_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_new` tinyint(1) NOT NULL DEFAULT '0',
  `is_special` tinyint(1) NOT NULL DEFAULT '0',
  `vendor_id` bigint UNSIGNED DEFAULT NULL,
  `hsn_code_id` bigint UNSIGNED DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `created_by_id` bigint UNSIGNED DEFAULT NULL,
  `updated_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_attribute_values`
--

CREATE TABLE `product_attribute_values` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `attribute_id` bigint UNSIGNED NOT NULL,
  `attribute_value_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_collections`
--

CREATE TABLE `product_collections` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `eyebrow` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cta_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cta_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `selection_mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `rules` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `show_on_home` tinyint(1) NOT NULL DEFAULT '0',
  `home_section` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `home_order` int UNSIGNED NOT NULL DEFAULT '0',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_collections`
--

INSERT INTO `product_collections` (`id`, `name`, `slug`, `kind`, `eyebrow`, `description`, `image_path`, `cta_text`, `cta_url`, `selection_mode`, `rules`, `is_active`, `show_on_home`, `home_section`, `home_order`, `starts_at`, `ends_at`, `created_at`, `updated_at`) VALUES
(2, 'Weeknight wins', 'weeknight-wins', 'occasion', 'Quick meals', 'Fast freezer-to-pan favourites for busy evenings and repeat household orders.', 'product-collections/CO2PNnVC8bRazYqa8ltrxd4ErgcMV3DTkmlPdgAG.png', 'Shop weeknight wins', '/collection/weeknight-wins', 'manual', NULL, 1, 1, 'occasions', 1, NULL, NULL, '2026-03-21 16:13:43', '2026-04-10 01:18:54'),
(3, 'Entertaining', 'party-starters', 'occasion', 'Party starters', 'Crowd-pleasing bites and easy-serving favourites for gatherings, celebrations and sharing.', 'product-collections/HQdOpNS4JqUvMqYsGlFIHRY1BybyMas6kz5WNsaS.png', 'Shop party starters', '/collections/party', 'manual', NULL, 1, 1, 'occasions', 2, NULL, NULL, '2026-03-21 16:16:17', '2026-04-10 12:36:58'),
(4, 'Everyday staples', 'family-table-favourites', 'occasion', 'Family table favourites', 'Practical frozen essentials for regular home cooking, dependable stocking, and everyday meals.', 'product-collections/j36l6Ua22KZIjiIFpDlalnt2EMfhE9jCfYvnj9VY.png', 'Shop family favourites', '/collections/family', 'manual', NULL, 1, 1, 'occasions', 3, NULL, NULL, '2026-03-21 16:17:49', '2026-04-10 12:37:38'),
(5, 'Chef spotlight', 'chef-picks', 'chef', 'Serving ideas that turn products into meals people want to make again.', 'Use this space for serving notes, quick prep guidance, and practical suggestions that make the product feel easier to cook and easier to order again.', 'product-collections/YzK1UzbP16QB6FZTRUSKnGqf82LUDRvKoL3zBDY4.png', 'Browse chef picks', '/collections/chef-picks', 'manual', NULL, 1, 1, 'chef_picks', 1, NULL, NULL, '2026-03-21 16:20:23', '2026-04-10 13:17:57');

-- --------------------------------------------------------

--
-- Table structure for table `product_collection_product`
--

CREATE TABLE `product_collection_product` (
  `id` bigint UNSIGNED NOT NULL,
  `product_collection_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `position` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_offers`
--

CREATE TABLE `product_offers` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `offer_type` enum('flat','percent','fixed_price') COLLATE utf8mb4_unicode_ci NOT NULL,
  `offer_value` decimal(10,2) NOT NULL,
  `starts_at` timestamp NOT NULL,
  `ends_at` timestamp NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_recipe`
--

CREATE TABLE `product_recipe` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `recipe_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `barcode` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manage_stock` tinyint(1) NOT NULL DEFAULT '1',
  `stock_quantity` decimal(10,2) DEFAULT NULL,
  `low_stock_threshold` decimal(10,2) DEFAULT NULL,
  `min_order_quantity` decimal(10,2) DEFAULT NULL,
  `product_weight` decimal(10,3) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pricing_unit` enum('pack','kg') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_variant_attribute_values`
--

CREATE TABLE `product_variant_attribute_values` (
  `id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED NOT NULL,
  `product_attribute_value_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` bigint UNSIGNED NOT NULL,
  `title` json DEFAULT NULL,
  `slug` json DEFAULT NULL,
  `short_description` json DEFAULT NULL,
  `description` json DEFAULT NULL,
  `ingredients` json DEFAULT NULL,
  `steps` json DEFAULT NULL,
  `prep_time_minutes` int UNSIGNED DEFAULT NULL,
  `cook_time_minutes` int UNSIGNED DEFAULT NULL,
  `servings` int UNSIGNED DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by_id` bigint UNSIGNED DEFAULT NULL,
  `updated_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`id`, `title`, `slug`, `short_description`, `description`, `ingredients`, `steps`, `prep_time_minutes`, `cook_time_minutes`, `servings`, `image_path`, `video_url`, `sort_order`, `is_active`, `created_by_id`, `updated_by_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '{\"en\": \"Crispy pork slice\"}', '{\"en\": \"crispy-pork-slice\"}', '[]', '{\"en\": \"Crispy pork slice\"}', '{\"en\": [\"500gm pork\", \"salt\", \"vinegar\"]}', '{\"en\": [\"boil\", \"salt\", \"fridge\", \"bake\", \"broil\", \"wait for 20 mins\", \"cut and serve\"]}', 30, 360, 3, 'recipes/PnIZ14AOMRwV1D0koann7yQuRVSLCzpGEeSMOHEf.jpg', NULL, 0, 1, 1, 1, '2026-03-17 06:15:58', '2026-03-21 06:33:25', NULL),
(2, '{\"en\": \"Stir fry pork\"}', '{\"en\": \"stir-fry-pork\"}', '{\"en\": \"Stir friend slices of pork\"}', '{\"en\": \"Stir fry slices of pork\\r\\nIt tastes better when the pork is with skin so it becomes a bit crispy\"}', '{\"en\": [\"500 gm pork belly with skin slice\", \"soya sauce\", \"fish sauce\", \"red chilly\"]}', '{\"en\": [\"oil\", \"saute\", \"garlic\", \"fish sauce\", \"soya sauce\"]}', 5, 30, 2, 'recipes/cztydbkTuw1WG5BreIhsSjmYwXuOwAezQQEYxf7a.jpg', NULL, 0, 1, 1, 1, '2026-03-17 06:38:13', '2026-03-21 06:24:21', NULL),
(3, '{\"en\": \"Grilled pork neck\"}', '{\"en\": \"grilled-pork-neck\"}', '{\"en\": \"Easily prepare delicious grilled pork neck using our special marinade powder.\"}', '{\"en\": \"The result is tender, aromatic pork with deep, infused flavor—no additional seasoning required. It boasts a superb texture and a perfect balance of sweet and savory notes. Serve alongside a zesty Jaew dipping sauce for the ultimate experience.\"}', '{\"en\": [\"Pork Neck (or Pork Collar): 500 g – 1 kg\", \"Grilled Pork Neck Marinade Powder: 1 packet\", \"Water: 1–2 tablespoons\", \"(Optional) Fresh Milk or Thick Coconut Milk: 1–2 tablespoons\", \"*Ingredients for *Jaew Dipping Sauce:**\", \"Fish sauce,\", \"Lime juice,\", \"Palm sugar,\", \"Roasted rice powder,\", \"Chili flakes,\", \"Sliced ​​shallots,\", \"Sawtooth coriander.\"]}', '{\"en\": [\"Marinate pork neck with grilled pork neck marinade power + water + fresh/coconut milk\", \"Marinate for atleast 1 hour\", \"In the meantime make Jaew dipping sauce (mix all ingredients together)\", \"Put marinate on grill low fire\", \"Grill for 8-10 minutes on either side\", \"Eat by dipping in Jaew sauce\"]}', 30, 20, 4, 'recipes/oQBrtnLqloxHC71Morb2XTQgRMjRsj9E6f3Zuzen.jpg', NULL, 0, 1, 1, 1, '2026-03-21 06:45:25', '2026-03-21 06:46:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(2, 'Manager', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(3, 'Support', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(4, 'CA-Accountant', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(5, 'Customer', 'web', '2025-12-05 03:17:50', '2025-12-05 03:17:50'),
(6, 'Accountant', 'web', '2026-01-11 07:25:10', '2026-01-11 07:25:10'),
(7, 'Stores', 'web', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 1),
(1, 2),
(2, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 2),
(8, 2),
(9, 2),
(10, 2),
(11, 2),
(12, 2),
(14, 2),
(15, 2),
(17, 2),
(18, 2),
(19, 2),
(20, 2),
(21, 2),
(22, 2),
(11, 3),
(12, 3),
(6, 4),
(14, 4),
(1, 7),
(2, 7),
(9, 7),
(10, 7),
(22, 7);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('aKWAGHVoRC5HbgSdycKcjnXjKtmGKuJqQKUQ1RJN', 1, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQUZqNDIyTGRnbXFER0tGcWM0Y2ZucUlNOFZEYUFnNGNwU2ZwVFFBNCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==', 1775885461),
('gZSCzwoQTsg4wX1DRt7520pBGu3ezFlrVNbMZdeW', NULL, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:149.0) Gecko/20100101 Firefox/149.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibVloS3JZem85YTliUnJnYU9wRVJLbEVPRVllS0h4Vzd0RDNSY3FGVyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FjY291bnQvcmV3YXJkcyI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1775959664),
('IdE3r24ke2Smv0f7n5LqBeV8rVWzDECOxVM026Nd', 1, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoianFXVUg4UHZqVExRT3lUakJHVlBSSE9Uc2daRlozTlIzQlJUZUNidCI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQ4OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYWRtaW4vYjJiLWN1c3RvbWVycy8zOS9tb3EiO3M6NToicm91dGUiO3M6MTk6ImFkbWluLmIyYi5tb3EuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1775959728),
('q7LAb2tuPGlCpZuiVyZwU9XZmr6d2EzJ9R7T61eP', 1, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRVFqcXZiYjJhcnhpZ3VMQjRLN0J6bVM1dnNSelZ5YVc5UVFldVhoNiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9vcmRlcnMvcHJpbnQvbmV3IjtzOjU6InJvdXRlIjtzOjIyOiJhZG1pbi5vcmRlcnMucHJpbnQubmV3Ijt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1775887933),
('zgBzSNyI232dRxZs9aaXpIvn3pwsQNltkbwcrPHk', 40, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:149.0) Gecko/20100101 Firefox/149.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiM3FOVGRyS1lQdlFhQmxUcFM5RmJEek50SlBGNWlsYlNhMW5JRWdvOSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjM3OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYWNjb3VudC9yZXdhcmRzIjtzOjU6InJvdXRlIjtzOjE1OiJhY2NvdW50LnJld2FyZHMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo0MDt9', 1775887929);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `type`, `group`, `created_at`, `updated_at`) VALUES
(1, 'store.name', 'Bandara by Maytira', 'string', 'store', NULL, NULL),
(2, 'store.logo_path', NULL, 'string', 'store', NULL, NULL),
(3, 'tax.home_state', 'Maharashtra', 'string', 'tax', NULL, NULL),
(4, 'tax.cgst_rate', '2.5', 'float', 'tax', NULL, NULL),
(5, 'tax.sgst_rate', '2.5', 'float', 'tax', NULL, NULL),
(6, 'tax.igst_rate', '5', 'float', 'tax', NULL, NULL),
(7, 'payment.razorpay_key_id', '', 'string', 'payment', NULL, NULL),
(8, 'payment.razorpay_key_secret', '', 'string', 'payment', NULL, NULL),
(9, 'tally.api_key', '', 'string', 'tally', NULL, NULL),
(10, 'features.dynamic_pricing', '1', 'bool', 'features', NULL, NULL),
(11, 'features.out_of_stock_notifications', '1', 'bool', 'features', NULL, NULL),
(12, 'features.dark_mode', '1', 'bool', 'features', NULL, NULL),
(13, 'features.newsletter', '1', 'bool', 'features', NULL, NULL),
(14, 'features.wishlist', '1', 'bool', 'features', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carrier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','shipped','delivered','returned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE `states` (
  `id` bigint UNSIGNED NOT NULL,
  `country_code` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IN',
  `code` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `country_code`, `code`, `name`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'IN', 'AP', 'Andhra Pradesh', 1, 1, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(2, 'IN', 'AR', 'Arunachal Pradesh', 1, 2, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(3, 'IN', 'AS', 'Assam', 1, 3, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(4, 'IN', 'BR', 'Bihar', 1, 4, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(5, 'IN', 'CG', 'Chhattisgarh', 1, 5, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(6, 'IN', 'GA', 'Goa', 1, 6, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(7, 'IN', 'GJ', 'Gujarat', 1, 7, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(8, 'IN', 'HR', 'Haryana', 1, 8, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(9, 'IN', 'HP', 'Himachal Pradesh', 1, 9, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(10, 'IN', 'JH', 'Jharkhand', 1, 10, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(11, 'IN', 'KA', 'Karnataka', 1, 11, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(12, 'IN', 'KL', 'Kerala', 1, 12, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(13, 'IN', 'MP', 'Madhya Pradesh', 1, 13, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(14, 'IN', 'MH', 'Maharashtra', 1, 14, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(15, 'IN', 'MN', 'Manipur', 1, 15, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(16, 'IN', 'ML', 'Meghalaya', 1, 16, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(17, 'IN', 'MZ', 'Mizoram', 1, 17, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(18, 'IN', 'NL', 'Nagaland', 1, 18, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(19, 'IN', 'OD', 'Odisha', 1, 19, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(20, 'IN', 'PB', 'Punjab', 1, 20, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(21, 'IN', 'RJ', 'Rajasthan', 1, 21, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(22, 'IN', 'SK', 'Sikkim', 1, 22, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(23, 'IN', 'TN', 'Tamil Nadu', 1, 23, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(24, 'IN', 'TS', 'Telangana', 1, 24, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(25, 'IN', 'TR', 'Tripura', 1, 25, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(26, 'IN', 'UP', 'Uttar Pradesh', 1, 26, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(27, 'IN', 'UK', 'Uttarakhand', 1, 27, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(28, 'IN', 'WB', 'West Bengal', 1, 28, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(29, 'IN', 'AN', 'Andaman and Nicobar Islands', 1, 29, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(30, 'IN', 'CH', 'Chandigarh', 1, 30, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(31, 'IN', 'DH', 'Dadra and Nagar Haveli and Daman and Diu', 1, 31, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(32, 'IN', 'DL', 'Delhi', 1, 32, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(33, 'IN', 'JK', 'Jammu and Kashmir', 1, 33, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(34, 'IN', 'LA', 'Ladakh', 1, 34, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(35, 'IN', 'LD', 'Lakshadweep', 1, 35, '2026-02-02 19:14:57', '2026-02-02 19:14:57'),
(36, 'IN', 'PY', 'Puducherry', 1, 36, '2026-02-02 19:14:57', '2026-02-02 19:14:57');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `vendor_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `movement_type` enum('sale','purchase','adjustment','return') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` bigint UNSIGNED NOT NULL,
  `ticket_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `status` enum('new','awaiting_support','awaiting_customer','resolved','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `priority` enum('normal','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `assigned_to_id` bigint UNSIGNED DEFAULT NULL,
  `created_by_id` bigint UNSIGNED DEFAULT NULL,
  `last_reply_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_assignee_history`
--

CREATE TABLE `ticket_assignee_history` (
  `id` bigint UNSIGNED NOT NULL,
  `ticket_id` bigint UNSIGNED NOT NULL,
  `from_user_id` bigint UNSIGNED DEFAULT NULL,
  `to_user_id` bigint UNSIGNED DEFAULT NULL,
  `changed_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_attachments`
--

CREATE TABLE `ticket_attachments` (
  `id` bigint UNSIGNED NOT NULL,
  `ticket_message_id` bigint UNSIGNED NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_categories`
--

CREATE TABLE `ticket_categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `position` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_categories`
--

INSERT INTO `ticket_categories` (`id`, `name`, `slug`, `description`, `position`, `is_active`, `created_at`, `updated_at`) VALUES
(5, 'Sales', 'sales', NULL, 0, 1, '2026-01-11 12:56:07', '2026-01-11 12:56:07'),
(6, 'Technical', 'technical', NULL, 0, 1, '2026-01-11 12:56:22', '2026-01-11 12:56:22'),
(7, 'Billing', 'billing', 'Billing department', 0, 1, '2026-01-11 12:56:41', '2026-01-12 09:59:00');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` bigint UNSIGNED NOT NULL,
  `ticket_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `sender_type` enum('customer','agent','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer',
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_status_history`
--

CREATE TABLE `ticket_status_history` (
  `id` bigint UNSIGNED NOT NULL,
  `ticket_id` bigint UNSIGNED NOT NULL,
  `from_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_tags`
--

CREATE TABLE `ticket_tags` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_tags`
--

INSERT INTO `ticket_tags` (`id`, `name`, `slug`, `color`, `created_at`, `updated_at`) VALUES
(3, 'Technical', 'technical', 'blue', '2026-01-11 12:58:03', '2026-01-11 12:58:03'),
(4, 'Billing', 'billing', 'orange', '2026-01-11 12:58:12', '2026-01-11 12:58:12'),
(5, 'Support', 'support', 'brown', '2026-01-11 12:58:32', '2026-01-11 12:58:32');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_tag_ticket`
--

CREATE TABLE `ticket_tag_ticket` (
  `ticket_id` bigint UNSIGNED NOT NULL,
  `ticket_tag_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_type` enum('b2c','b2b','staff') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'b2c',
  `gst_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fssai_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme_preference` enum('system','light','dark') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `phone`, `avatar_path`, `customer_type`, `gst_number`, `fssai_number`, `theme_preference`, `is_active`, `last_login_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Parag Parulekar', 'parag@bandara.in', '2026-03-18 15:28:49', '$2y$12$P.E3lbL1RqX8VGHNuV0DtOxOvvxFG5lcGAreEWsNXwFYquBKSpCP6', NULL, NULL, 'staff', '', '', 'system', 1, NULL, NULL, '2025-12-05 03:27:27', '2025-12-05 03:27:27'),
(33, 'kaustubh', 'kaustubhshinde1225@gmail.com', '2026-03-18 15:28:49', '$2y$12$S1ifFl1CAc1yoJ5YwqTqn.fRlkytLvJTcucEb6tXi2R0iIqEDDZs2', '9082916969', NULL, 'b2c', NULL, NULL, 'system', 1, NULL, NULL, '2026-03-18 15:28:04', '2026-03-18 15:28:49'),
(39, 'Vijay Parulekar', 'vijay.b.parulekar@gmail.com', '2026-03-21 05:29:22', '$2y$12$XGlt0NMSsCj.znkgmT9LqudYANlq.r5n.6dWPP6EpCtkHWG82.COW', '9823290102', NULL, 'b2b', 'GSTIN28373', '234724236', 'system', 1, NULL, NULL, '2026-03-19 14:47:43', '2026-04-12 00:38:16'),
(40, 'Disha Barve', 'parag.parulekar@gmail.com', '2026-03-21 05:29:45', '$2y$12$ilHIvhmFWsur/aizhK/IyuXl5B1UnMyrQGqeFd2I7qa1VMmOrP14y', '9823170102', 'avatars/5hnU3L6DrEE32aqaSejt7V5JvhhUF86zs8nsk0UL.jpg', 'b2c', NULL, NULL, 'system', 1, NULL, NULL, '2026-03-21 05:29:45', '2026-03-31 06:25:59'),
(41, 'Maytira Mala', 'maytira24@gmail.com', '2026-03-22 04:33:55', '$2y$12$hNTbZf.IrgawUb8K1Wr9VOH2OjIscLG9oDWCtgyWLBWkOn/rnghMa', '7770097621', NULL, 'staff', NULL, NULL, 'system', 1, NULL, NULL, '2026-03-22 04:33:55', '2026-03-22 04:33:55');

-- --------------------------------------------------------

--
-- Table structure for table `variant_values`
--

CREATE TABLE `variant_values` (
  `id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED NOT NULL,
  `attribute_id` bigint UNSIGNED NOT NULL,
  `attribute_value_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gst_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fssai_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'India',
  `pincode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `name`, `code`, `email`, `phone`, `gst_number`, `fssai_number`, `address_line1`, `address_line2`, `city`, `state`, `state_code`, `country`, `pincode`, `notes`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(2, 'Mayur Piggery Farm', 'MayurPiggery', 'amit.lot@mayurfarm.com', NULL, '27ACSPJ4509P1ZV', '11518037000030', 'Sr. No. 49, A/p Post Punawale, Tal. Mulshi', 'Mumbai Pune Expressway, Opp Bhrart Petrol Pump', 'Pune', 'Maharashtra', 'MH', 'India', '411033', NULL, 1, '2026-02-01 07:17:53', '2026-02-01 08:01:03', NULL),
(3, 'Empire Industries Limited', 'Empire', NULL, NULL, '27AAACE2757R2Z2', '10012022000345', 'Shop No, Gagan Avenue CHS Ltd', 'Next to Sai Service, Kondhawa Budruk', 'Pune', 'Maharashtra', 'MH', 'India', '411048', NULL, 1, '2026-02-01 07:20:53', '2026-02-01 08:00:55', NULL),
(4, 'Quality NZ Imports Private Limited', 'QualityNZ', 'sales.west@qualitynz.com', '7208904441', '27AAACQ3055E1ZP', '10013011001545', 'Plot No. D-4, Voles House Road No. 20', 'MIDC Marol, Andheri (E)', 'Mumbai', 'Maharashtra', 'MH', 'India', '400093', NULL, 1, '2026-02-01 07:50:37', '2026-02-01 07:50:37', NULL),
(5, 'Fortune Gourmet Specialities Private Limited', 'FortunePune', 'stores.pune@fortunegourmet.com', '8454046463', '27AAACF495B1ZY', '1151803700000', 'Survey No. 423, Plot No. 24/25/26, Jagtap Nagar', 'Behind Thergaon Police Chowki, Thergaon', 'Pune', 'Maharashtra', 'MH', 'India', '411001', NULL, 1, '2026-02-01 07:53:17', '2026-02-01 07:53:17', NULL),
(6, 'Fortune Gourmet Specialities Private Limited', 'FortuneMumbai', 'storesramesh@fortunegourmet.com', '2241200155', '27AAACF4952B1ZY', '11517005000032', '36 ABCD Marol Co-op Industrial Estate Ltd. Near M. V. Road', 'J.B. Nagar Post, Marol, Andheri (E)', 'Mumbai', 'Maharashtra', 'MH', 'India', '400059', NULL, 1, '2026-02-01 07:59:40', '2026-02-01 08:00:48', NULL),
(7, 'Ice Age Enterprises', 'IceAgePrawnPune', 'iceageenterprisespune@gmail.com', NULL, '27AAIFI2925C1ZM', '11523035000156', 'Sr No 25/26 Wing E 102', 'Brahma Sky City Vishrantwadi', 'Pune', 'Maharashtra', 'MH', 'India', '411015', NULL, 1, '2026-02-01 08:49:51', '2026-02-01 08:49:51', NULL),
(8, 'The Frozen House', 'Gadre', NULL, NULL, '27BWFPP9765H1ZT', 'NA', '7, Azure A wing,', 'Tathwade', 'Pune', 'Maharashtra', 'MH', 'India', '411024', NULL, 1, '2026-02-05 14:21:09', '2026-02-05 14:21:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vendor_invoices`
--

CREATE TABLE `vendor_invoices` (
  `id` bigint UNSIGNED NOT NULL,
  `vendor_id` bigint UNSIGNED NOT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_date` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','partially_paid','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `tally_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_invoice_items`
--

CREATE TABLE `vendor_invoice_items` (
  `id` bigint UNSIGNED NOT NULL,
  `vendor_invoice_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_weight_kg` decimal(10,3) DEFAULT NULL,
  `total_weight_kg` decimal(12,3) DEFAULT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_payments`
--

CREATE TABLE `vendor_payments` (
  `id` bigint UNSIGNED NOT NULL,
  `vendor_id` bigint UNSIGNED NOT NULL,
  `vendor_invoice_id` bigint UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `tally_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attributes`
--
ALTER TABLE `attributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attributes_slug_deleted_at_unique` (`slug`,`deleted_at`);

--
-- Indexes for table `attribute_values`
--
ALTER TABLE `attribute_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attribute_values_attribute_id_foreign` (`attribute_id`);

--
-- Indexes for table `b2b_customer_products`
--
ALTER TABLE `b2b_customer_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `b2b_user_product_unique` (`user_id`,`product_id`),
  ADD KEY `b2b_customer_products_product_id_foreign` (`product_id`),
  ADD KEY `b2b_customer_products_created_by_id_foreign` (`created_by_id`),
  ADD KEY `b2b_customer_products_updated_by_id_foreign` (`updated_by_id`),
  ADD KEY `b2b_user_active_idx` (`user_id`,`is_active`);

--
-- Indexes for table `bandara_credit_transactions`
--
ALTER TABLE `bandara_credit_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bandara_credit_transactions_idempotency_key_unique` (`idempotency_key`),
  ADD KEY `bandara_credit_transactions_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `bandara_credit_transactions_order_id_type_index` (`order_id`,`type`),
  ADD KEY `bandara_credit_transactions_type_status_index` (`type`,`status`),
  ADD KEY `bandara_credit_transactions_order_id_index` (`order_id`);

--
-- Indexes for table `bandara_credit_wallets`
--
ALTER TABLE `bandara_credit_wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bandara_credit_wallets_user_id_unique` (`user_id`),
  ADD KEY `bandara_credit_wallets_tier_updated_at_index` (`tier`,`updated_at`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carts_user_id_foreign` (`user_id`),
  ADD KEY `carts_coupon_id_foreign` (`coupon_id`),
  ADD KEY `carts_session_id_index` (`session_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_items_cart_id_foreign` (`cart_id`),
  ADD KEY `cart_items_product_id_foreign` (`product_id`),
  ADD KEY `cart_items_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categories_parent_id_foreign` (`parent_id`),
  ADD KEY `categories_slug_deleted_at_unique` (`slug`,`deleted_at`);

--
-- Indexes for table `category_product`
--
ALTER TABLE `category_product`
  ADD PRIMARY KEY (`category_id`,`product_id`),
  ADD KEY `category_product_product_id_foreign` (`product_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cities_country_code_state_code_name_unique` (`country_code`,`state_code`,`name`),
  ADD KEY `cities_country_code_state_code_name_index` (`country_code`,`state_code`,`name`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coupons_code_unique` (`code`),
  ADD KEY `coupons_created_by_id_foreign` (`created_by_id`);

--
-- Indexes for table `coupon_redemptions`
--
ALTER TABLE `coupon_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_redemptions_coupon_id_foreign` (`coupon_id`),
  ADD KEY `coupon_redemptions_user_id_foreign` (`user_id`),
  ADD KEY `coupon_redemptions_order_id_foreign` (`order_id`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_addresses_user_id_foreign` (`user_id`);

--
-- Indexes for table `customer_product_prices`
--
ALTER TABLE `customer_product_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_product_prices_product_id_foreign` (`product_id`),
  ADD KEY `customer_product_prices_product_variant_id_foreign` (`product_variant_id`),
  ADD KEY `customer_product_prices_created_by_id_foreign` (`created_by_id`),
  ADD KEY `customer_product_prices_updated_by_id_foreign` (`updated_by_id`),
  ADD KEY `cpp_lookup` (`user_id`,`product_id`,`product_variant_id`,`is_active`),
  ADD KEY `cpp_validity` (`valid_from`,`valid_to`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `hsn_codes`
--
ALTER TABLE `hsn_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hsn_codes_code_unique` (`code`);

--
-- Indexes for table `impersonation_logs`
--
ALTER TABLE `impersonation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `impersonation_logs_admin_id_foreign` (`admin_id`),
  ADD KEY `impersonation_logs_impersonated_user_id_foreign` (`impersonated_user_id`);

--
-- Indexes for table `inventory_lots`
--
ALTER TABLE `inventory_lots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inventory_lots_lot_code_unique` (`lot_code`),
  ADD KEY `inventory_lots_product_variant_id_foreign` (`product_variant_id`),
  ADD KEY `inventory_lots_vendor_id_foreign` (`vendor_id`),
  ADD KEY `inventory_lots_vendor_invoice_id_foreign` (`vendor_invoice_id`),
  ADD KEY `inventory_lots_vendor_invoice_item_id_foreign` (`vendor_invoice_item_id`),
  ADD KEY `inventory_lots_production_run_id_foreign` (`production_run_id`),
  ADD KEY `inventory_lots_created_by_id_foreign` (`created_by_id`),
  ADD KEY `inventory_lots_updated_by_id_foreign` (`updated_by_id`),
  ADD KEY `inventory_lots_product_id_lot_stage_index` (`product_id`,`lot_stage`),
  ADD KEY `inventory_lots_is_saleable_can_repack_index` (`is_saleable`,`can_repack`),
  ADD KEY `inventory_lots_parent_inventory_lot_id_index` (`parent_inventory_lot_id`),
  ADD KEY `inventory_lots_root_inventory_lot_id_index` (`root_inventory_lot_id`),
  ADD KEY `inventory_lots_lot_stage_index` (`lot_stage`),
  ADD KEY `inventory_lots_lot_status_index` (`lot_status`),
  ADD KEY `inventory_lots_batch_code_index` (`batch_code`),
  ADD KEY `inventory_lots_expiry_date_index` (`expiry_date`);

--
-- Indexes for table `inventory_pieces`
--
ALTER TABLE `inventory_pieces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `inventory_pieces_inventory_lot_id_piece_no_unique` (`inventory_lot_id`,`piece_no`),
  ADD KEY `inventory_pieces_consumed_in_production_run_id_foreign` (`consumed_in_production_run_id`),
  ADD KEY `inventory_pieces_status_index` (`status`),
  ADD KEY `inventory_pieces_sold_order_item_id_index` (`sold_order_item_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  ADD KEY `invoices_order_id_foreign` (`order_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  ADD KEY `invoice_items_order_item_id_foreign` (`order_item_id`);

--
-- Indexes for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_payments_payment_id_foreign` (`payment_id`),
  ADD KEY `invoice_payments_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `newsletter_campaigns_created_by_id_foreign` (`created_by_id`);

--
-- Indexes for table `newsletter_campaign_recipients`
--
ALTER TABLE `newsletter_campaign_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `newsletter_campaign_recipients_campaign_id_foreign` (`campaign_id`),
  ADD KEY `newsletter_campaign_recipients_subscriber_id_foreign` (`subscriber_id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `newsletter_subscribers_email_unique` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_number_unique` (`order_number`),
  ADD KEY `orders_user_id_foreign` (`user_id`),
  ADD KEY `orders_coupon_id_foreign` (`coupon_id`),
  ADD KEY `orders_cancelled_by_id_foreign` (`cancelled_by_id`),
  ADD KEY `orders_printed_by_id_foreign` (`printed_by_id`),
  ADD KEY `orders_printed_at_index` (`printed_at`);

--
-- Indexes for table `order_addresses`
--
ALTER TABLE `order_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_addresses_order_id_foreign` (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_product_id_foreign` (`product_id`),
  ADD KEY `order_items_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_status_history_order_id_foreign` (`order_id`),
  ADD KEY `order_status_history_changed_by_id_foreign` (`changed_by_id`);

--
-- Indexes for table `out_of_stock_notifications`
--
ALTER TABLE `out_of_stock_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `out_of_stock_notifications_subscription_id_foreign` (`subscription_id`),
  ADD KEY `out_of_stock_notifications_sent_by_id_foreign` (`sent_by_id`);

--
-- Indexes for table `out_of_stock_subscriptions`
--
ALTER TABLE `out_of_stock_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `out_of_stock_subscriptions_product_id_foreign` (`product_id`),
  ADD KEY `out_of_stock_subscriptions_product_variant_id_foreign` (`product_variant_id`),
  ADD KEY `out_of_stock_subscriptions_user_id_foreign` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_order_id_foreign` (`order_id`),
  ADD KEY `payments_user_id_foreign` (`user_id`),
  ADD KEY `payments_recorded_by_id_foreign` (`recorded_by_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `production_runs`
--
ALTER TABLE `production_runs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `production_runs_run_number_unique` (`run_number`),
  ADD KEY `production_runs_created_by_id_foreign` (`created_by_id`),
  ADD KEY `production_runs_updated_by_id_foreign` (`updated_by_id`),
  ADD KEY `production_runs_run_date_index` (`run_date`),
  ADD KEY `production_runs_run_type_index` (`run_type`),
  ADD KEY `production_runs_status_index` (`status`);

--
-- Indexes for table `production_run_inputs`
--
ALTER TABLE `production_run_inputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `production_run_inputs_production_run_id_foreign` (`production_run_id`),
  ADD KEY `production_run_inputs_inventory_lot_id_foreign` (`inventory_lot_id`),
  ADD KEY `production_run_inputs_product_id_foreign` (`product_id`),
  ADD KEY `production_run_inputs_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexes for table `production_run_outputs`
--
ALTER TABLE `production_run_outputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `production_run_outputs_production_run_id_foreign` (`production_run_id`),
  ADD KEY `production_run_outputs_inventory_lot_id_foreign` (`inventory_lot_id`),
  ADD KEY `production_run_outputs_product_id_foreign` (`product_id`),
  ADD KEY `production_run_outputs_product_variant_id_foreign` (`product_variant_id`),
  ADD KEY `production_run_outputs_output_stage_index` (`output_stage`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_slug_deleted_at_unique` (`slug`,`deleted_at`),
  ADD UNIQUE KEY `products_barcode_unique` (`barcode`),
  ADD KEY `products_vendor_id_foreign` (`vendor_id`),
  ADD KEY `products_created_by_id_foreign` (`created_by_id`),
  ADD KEY `products_updated_by_id_foreign` (`updated_by_id`),
  ADD KEY `products_hsn_code_id_foreign` (`hsn_code_id`);

--
-- Indexes for table `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_attribute_values_product_id_foreign` (`product_id`),
  ADD KEY `product_attribute_values_attribute_id_foreign` (`attribute_id`),
  ADD KEY `product_attribute_values_attribute_value_id_foreign` (`attribute_value_id`);

--
-- Indexes for table `product_collections`
--
ALTER TABLE `product_collections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_collections_slug_unique` (`slug`);

--
-- Indexes for table `product_collection_product`
--
ALTER TABLE `product_collection_product`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pcp_collection_product_unique` (`product_collection_id`,`product_id`),
  ADD KEY `product_collection_product_product_id_foreign` (`product_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_images_product_id_foreign` (`product_id`);

--
-- Indexes for table `product_offers`
--
ALTER TABLE `product_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_offers_product_id_foreign` (`product_id`),
  ADD KEY `product_offers_product_variant_id_foreign` (`product_variant_id`),
  ADD KEY `product_offers_created_by_id_foreign` (`created_by_id`);

--
-- Indexes for table `product_recipe`
--
ALTER TABLE `product_recipe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_recipe_product_id_recipe_id_unique` (`product_id`,`recipe_id`),
  ADD KEY `product_recipe_recipe_id_foreign` (`recipe_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_variants_sku_deleted_at_unique` (`sku`,`deleted_at`),
  ADD UNIQUE KEY `product_variants_barcode_unique` (`barcode`),
  ADD KEY `product_variants_product_id_foreign` (`product_id`);

--
-- Indexes for table `product_variant_attribute_values`
--
ALTER TABLE `product_variant_attribute_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pvav_variant_value_unique` (`product_variant_id`,`product_attribute_value_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipes_created_by_id_foreign` (`created_by_id`),
  ADD KEY `recipes_updated_by_id_foreign` (`updated_by_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipments_order_id_foreign` (`order_id`);

--
-- Indexes for table `states`
--
ALTER TABLE `states`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `states_country_code_code_unique` (`country_code`,`code`),
  ADD KEY `states_country_code_name_index` (`country_code`,`name`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_product_id_foreign` (`product_id`),
  ADD KEY `stock_movements_product_variant_id_foreign` (`product_variant_id`),
  ADD KEY `stock_movements_vendor_id_foreign` (`vendor_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tickets_ticket_number_unique` (`ticket_number`),
  ADD KEY `tickets_customer_id_foreign` (`user_id`),
  ADD KEY `tickets_category_id_foreign` (`category_id`),
  ADD KEY `tickets_assigned_to_id_foreign` (`assigned_to_id`),
  ADD KEY `tickets_created_by_id_foreign` (`created_by_id`);

--
-- Indexes for table `ticket_assignee_history`
--
ALTER TABLE `ticket_assignee_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_assignee_history_ticket_id_foreign` (`ticket_id`),
  ADD KEY `ticket_assignee_history_from_user_id_foreign` (`from_user_id`),
  ADD KEY `ticket_assignee_history_to_user_id_foreign` (`to_user_id`),
  ADD KEY `ticket_assignee_history_changed_by_id_foreign` (`changed_by_id`);

--
-- Indexes for table `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_attachments_ticket_message_id_foreign` (`ticket_message_id`);

--
-- Indexes for table `ticket_categories`
--
ALTER TABLE `ticket_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_categories_slug_unique` (`slug`);

--
-- Indexes for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_messages_ticket_id_foreign` (`ticket_id`),
  ADD KEY `ticket_messages_sender_id_foreign` (`user_id`);

--
-- Indexes for table `ticket_status_history`
--
ALTER TABLE `ticket_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_status_history_ticket_id_foreign` (`ticket_id`),
  ADD KEY `ticket_status_history_changed_by_id_foreign` (`changed_by_id`);

--
-- Indexes for table `ticket_tags`
--
ALTER TABLE `ticket_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_tags_slug_unique` (`slug`);

--
-- Indexes for table `ticket_tag_ticket`
--
ALTER TABLE `ticket_tag_ticket`
  ADD PRIMARY KEY (`ticket_id`,`ticket_tag_id`),
  ADD KEY `ticket_tag_ticket_ticket_tag_id_foreign` (`ticket_tag_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `variant_values`
--
ALTER TABLE `variant_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `variant_values_product_variant_id_foreign` (`product_variant_id`),
  ADD KEY `variant_values_attribute_id_foreign` (`attribute_id`),
  ADD KEY `variant_values_attribute_value_id_foreign` (`attribute_value_id`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendors_code_deleted_at_unique` (`code`,`deleted_at`);

--
-- Indexes for table `vendor_invoices`
--
ALTER TABLE `vendor_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_invoices_vendor_id_foreign` (`vendor_id`);

--
-- Indexes for table `vendor_invoice_items`
--
ALTER TABLE `vendor_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_invoice_items_vendor_invoice_id_foreign` (`vendor_invoice_id`),
  ADD KEY `vendor_invoice_items_product_id_foreign` (`product_id`),
  ADD KEY `vendor_invoice_items_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexes for table `vendor_payments`
--
ALTER TABLE `vendor_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_payments_vendor_id_foreign` (`vendor_id`),
  ADD KEY `vendor_payments_vendor_invoice_id_foreign` (`vendor_invoice_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wishlists_unique_user_product_variant` (`user_id`,`product_id`,`product_variant_id`),
  ADD KEY `wishlists_product_id_foreign` (`product_id`),
  ADD KEY `wishlists_product_variant_id_foreign` (`product_variant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attributes`
--
ALTER TABLE `attributes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attribute_values`
--
ALTER TABLE `attribute_values`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `b2b_customer_products`
--
ALTER TABLE `b2b_customer_products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bandara_credit_transactions`
--
ALTER TABLE `bandara_credit_transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `bandara_credit_wallets`
--
ALTER TABLE `bandara_credit_wallets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=324;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=384;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `coupon_redemptions`
--
ALTER TABLE `coupon_redemptions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `customer_product_prices`
--
ALTER TABLE `customer_product_prices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hsn_codes`
--
ALTER TABLE `hsn_codes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `impersonation_logs`
--
ALTER TABLE `impersonation_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_lots`
--
ALTER TABLE `inventory_lots`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory_pieces`
--
ALTER TABLE `inventory_pieces`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newsletter_campaign_recipients`
--
ALTER TABLE `newsletter_campaign_recipients`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=174;

--
-- AUTO_INCREMENT for table `order_addresses`
--
ALTER TABLE `order_addresses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=347;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=224;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `out_of_stock_notifications`
--
ALTER TABLE `out_of_stock_notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `out_of_stock_subscriptions`
--
ALTER TABLE `out_of_stock_subscriptions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `production_runs`
--
ALTER TABLE `production_runs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `production_run_inputs`
--
ALTER TABLE `production_run_inputs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `production_run_outputs`
--
ALTER TABLE `production_run_outputs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_collections`
--
ALTER TABLE `product_collections`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_collection_product`
--
ALTER TABLE `product_collection_product`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `product_offers`
--
ALTER TABLE `product_offers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_recipe`
--
ALTER TABLE `product_recipe`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `product_variant_attribute_values`
--
ALTER TABLE `product_variant_attribute_values`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `states`
--
ALTER TABLE `states`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `ticket_assignee_history`
--
ALTER TABLE `ticket_assignee_history`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `ticket_categories`
--
ALTER TABLE `ticket_categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `ticket_status_history`
--
ALTER TABLE `ticket_status_history`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `ticket_tags`
--
ALTER TABLE `ticket_tags`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `variant_values`
--
ALTER TABLE `variant_values`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vendor_invoices`
--
ALTER TABLE `vendor_invoices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vendor_invoice_items`
--
ALTER TABLE `vendor_invoice_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vendor_payments`
--
ALTER TABLE `vendor_payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attribute_values`
--
ALTER TABLE `attribute_values`
  ADD CONSTRAINT `attribute_values_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `b2b_customer_products`
--
ALTER TABLE `b2b_customer_products`
  ADD CONSTRAINT `b2b_customer_products_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `b2b_customer_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `b2b_customer_products_updated_by_id_foreign` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `b2b_customer_products_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bandara_credit_transactions`
--
ALTER TABLE `bandara_credit_transactions`
  ADD CONSTRAINT `bandara_credit_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bandara_credit_wallets`
--
ALTER TABLE `bandara_credit_wallets`
  ADD CONSTRAINT `bandara_credit_wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `category_product`
--
ALTER TABLE `category_product`
  ADD CONSTRAINT `category_product_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `category_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupons`
--
ALTER TABLE `coupons`
  ADD CONSTRAINT `coupons_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `coupon_redemptions`
--
ALTER TABLE `coupon_redemptions`
  ADD CONSTRAINT `coupon_redemptions_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coupon_redemptions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `coupon_redemptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_product_prices`
--
ALTER TABLE `customer_product_prices`
  ADD CONSTRAINT `customer_product_prices_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customer_product_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_product_prices_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customer_product_prices_updated_by_id_foreign` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customer_product_prices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `impersonation_logs`
--
ALTER TABLE `impersonation_logs`
  ADD CONSTRAINT `impersonation_logs_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `impersonation_logs_impersonated_user_id_foreign` FOREIGN KEY (`impersonated_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_lots`
--
ALTER TABLE `inventory_lots`
  ADD CONSTRAINT `inventory_lots_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_lots_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_lots_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_lots_production_run_id_foreign` FOREIGN KEY (`production_run_id`) REFERENCES `production_runs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_lots_updated_by_id_foreign` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_lots_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_lots_vendor_invoice_id_foreign` FOREIGN KEY (`vendor_invoice_id`) REFERENCES `vendor_invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_lots_vendor_invoice_item_id_foreign` FOREIGN KEY (`vendor_invoice_item_id`) REFERENCES `vendor_invoice_items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_pieces`
--
ALTER TABLE `inventory_pieces`
  ADD CONSTRAINT `inventory_pieces_consumed_in_production_run_id_foreign` FOREIGN KEY (`consumed_in_production_run_id`) REFERENCES `production_runs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_pieces_inventory_lot_id_foreign` FOREIGN KEY (`inventory_lot_id`) REFERENCES `inventory_lots` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_payments`
--
ALTER TABLE `invoice_payments`
  ADD CONSTRAINT `invoice_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_payments_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `newsletter_campaigns`
--
ALTER TABLE `newsletter_campaigns`
  ADD CONSTRAINT `newsletter_campaigns_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `newsletter_campaign_recipients`
--
ALTER TABLE `newsletter_campaign_recipients`
  ADD CONSTRAINT `newsletter_campaign_recipients_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `newsletter_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `newsletter_campaign_recipients_subscriber_id_foreign` FOREIGN KEY (`subscriber_id`) REFERENCES `newsletter_subscribers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_cancelled_by_id_foreign` FOREIGN KEY (`cancelled_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_printed_by_id_foreign` FOREIGN KEY (`printed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_addresses`
--
ALTER TABLE `order_addresses`
  ADD CONSTRAINT `order_addresses_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_changed_by_id_foreign` FOREIGN KEY (`changed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_status_history_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `out_of_stock_notifications`
--
ALTER TABLE `out_of_stock_notifications`
  ADD CONSTRAINT `out_of_stock_notifications_sent_by_id_foreign` FOREIGN KEY (`sent_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `out_of_stock_notifications_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `out_of_stock_subscriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `out_of_stock_subscriptions`
--
ALTER TABLE `out_of_stock_subscriptions`
  ADD CONSTRAINT `out_of_stock_subscriptions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `out_of_stock_subscriptions_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `out_of_stock_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_recorded_by_id_foreign` FOREIGN KEY (`recorded_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `production_runs`
--
ALTER TABLE `production_runs`
  ADD CONSTRAINT `production_runs_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_runs_updated_by_id_foreign` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `production_run_inputs`
--
ALTER TABLE `production_run_inputs`
  ADD CONSTRAINT `production_run_inputs_inventory_lot_id_foreign` FOREIGN KEY (`inventory_lot_id`) REFERENCES `inventory_lots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_run_inputs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_run_inputs_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_run_inputs_production_run_id_foreign` FOREIGN KEY (`production_run_id`) REFERENCES `production_runs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `production_run_outputs`
--
ALTER TABLE `production_run_outputs`
  ADD CONSTRAINT `production_run_outputs_inventory_lot_id_foreign` FOREIGN KEY (`inventory_lot_id`) REFERENCES `inventory_lots` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_run_outputs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_run_outputs_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_run_outputs_production_run_id_foreign` FOREIGN KEY (`production_run_id`) REFERENCES `production_runs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_hsn_code_id_foreign` FOREIGN KEY (`hsn_code_id`) REFERENCES `hsn_codes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_updated_by_id_foreign` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_attribute_values`
--
ALTER TABLE `product_attribute_values`
  ADD CONSTRAINT `product_attribute_values_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_attribute_values_attribute_value_id_foreign` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_attribute_values_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_collection_product`
--
ALTER TABLE `product_collection_product`
  ADD CONSTRAINT `product_collection_product_product_collection_id_foreign` FOREIGN KEY (`product_collection_id`) REFERENCES `product_collections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_collection_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_offers`
--
ALTER TABLE `product_offers`
  ADD CONSTRAINT `product_offers_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_offers_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_offers_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_recipe`
--
ALTER TABLE `product_recipe`
  ADD CONSTRAINT `product_recipe_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_recipe_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variant_attribute_values`
--
ALTER TABLE `product_variant_attribute_values`
  ADD CONSTRAINT `product_variant_attribute_values_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `recipes_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `recipes_updated_by_id_foreign` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_movements_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_assigned_to_id_foreign` FOREIGN KEY (`assigned_to_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `ticket_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tickets_customer_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_assignee_history`
--
ALTER TABLE `ticket_assignee_history`
  ADD CONSTRAINT `ticket_assignee_history_changed_by_id_foreign` FOREIGN KEY (`changed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ticket_assignee_history_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ticket_assignee_history_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_assignee_history_to_user_id_foreign` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD CONSTRAINT `ticket_attachments_ticket_message_id_foreign` FOREIGN KEY (`ticket_message_id`) REFERENCES `ticket_messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `ticket_messages_sender_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ticket_messages_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_status_history`
--
ALTER TABLE `ticket_status_history`
  ADD CONSTRAINT `ticket_status_history_changed_by_id_foreign` FOREIGN KEY (`changed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ticket_status_history_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_tag_ticket`
--
ALTER TABLE `ticket_tag_ticket`
  ADD CONSTRAINT `ticket_tag_ticket_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_tag_ticket_ticket_tag_id_foreign` FOREIGN KEY (`ticket_tag_id`) REFERENCES `ticket_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `variant_values`
--
ALTER TABLE `variant_values`
  ADD CONSTRAINT `variant_values_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `variant_values_attribute_value_id_foreign` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `variant_values_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_invoices`
--
ALTER TABLE `vendor_invoices`
  ADD CONSTRAINT `vendor_invoices_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_invoice_items`
--
ALTER TABLE `vendor_invoice_items`
  ADD CONSTRAINT `vendor_invoice_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vendor_invoice_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vendor_invoice_items_vendor_invoice_id_foreign` FOREIGN KEY (`vendor_invoice_id`) REFERENCES `vendor_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_payments`
--
ALTER TABLE `vendor_payments`
  ADD CONSTRAINT `vendor_payments_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vendor_payments_vendor_invoice_id_foreign` FOREIGN KEY (`vendor_invoice_id`) REFERENCES `vendor_invoices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `wishlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
