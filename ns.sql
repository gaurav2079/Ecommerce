-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2025 at 04:43 PM
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
-- Database: `ns`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `activity_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `activity_type`, `activity_details`, `ip_address`, `activity_time`) VALUES
(1, 36, 'login_history_deletion', 'All login history records deleted', '::1', '2025-09-16 10:53:08'),
(2, 36, 'login_history_deletion', 'All login history records deleted', '::1', '2025-09-16 11:12:34'),
(3, 36, 'profile_update', 'User updated their profile information', '::1', '2025-09-18 03:40:36'),
(4, 36, 'password_change', 'User changed their password', '::1', '2025-09-18 03:40:36'),
(5, 36, 'profile_update', 'User updated their profile information', '::1', '2025-09-18 04:02:58'),
(6, 36, 'password_change', 'User changed their password', '::1', '2025-09-18 04:02:58'),
(7, 36, 'profile_update', 'User updated their profile information', '::1', '2025-09-18 04:32:58'),
(8, 38, 'profile_update', 'User updated their profile information', '::1', '2025-09-18 04:37:28'),
(9, 38, 'password_change', 'User changed their password', '::1', '2025-09-18 04:37:28'),
(10, 38, 'profile_update', 'User updated their profile information', '::1', '2025-09-18 04:38:15'),
(11, 38, 'profile_update', 'User updated their profile information', '::1', '2025-09-18 04:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(100) NOT NULL,
  `name` varchar(20) NOT NULL,
  `password` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `password`) VALUES
(1, 'admin', '6216f8a75fd5bb3d5f22b6f9958cdede3fc086c2');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `pid` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` int(10) NOT NULL,
  `quantity` int(10) NOT NULL,
  `image` varchar(100) NOT NULL,
  `product_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `pid`, `name`, `price`, `quantity`, `image`, `product_id`) VALUES
(57, 36, 15, 'Mouse', 499, 1, 'mouse-1.webp', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_history`
--

INSERT INTO `login_history` (`id`, `user_id`, `login_time`, `ip_address`, `user_agent`, `success`) VALUES
(4, 36, '2025-09-18 09:24:42', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 1),
(5, 38, '2025-09-18 10:21:56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 1),
(6, 40, '2025-10-16 07:42:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `number` varchar(12) NOT NULL,
  `message` varchar(500) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `name`, `email`, `number`, `message`, `created_at`, `is_read`) VALUES
(1, 1, 'sita', 'sita@gmail.com', '9830292282', 'hi', '2025-05-21 12:52:30', 0),
(2, 14, 'Gaurav Kandel', 'kandelgaurav04@gmail.com', '9840245415', 'i am facing a purchase error\r\n', '2025-08-01 14:11:02', 0),
(3, 36, 'Gaurav Kandel', 'Admin@gmail.com', '9840245415', 'bxbb,jb', '2025-09-13 04:14:28', 0),
(4, 0, 'Gaurav Kandel', 'kandelgk64@gmail.com', '9840245415', 'hi', '2025-09-29 15:15:16', 0),
(5, 0, 'admin', 'Admin@gmail.com', '9840245415', 'dsd', '2025-09-29 15:16:23', 0);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unsubscribed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('order','promotion','system') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `order_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(286, 36, NULL, 'Welcome to Nepal Store! Enjoy your shopping experience.', '', 1, '2025-09-16 03:59:11'),
(290, 36, '71', 'Your order #71 status has been updated to pending.', '', 0, '2025-09-16 11:25:16'),
(291, 36, '72', 'Your order #72 status has been updated to pending.', '', 0, '2025-09-16 11:51:14'),
(292, 36, '71', 'Your order #71 has been approved and is now being processed you get it soon!', '', 0, '2025-09-16 11:51:35'),
(293, 38, NULL, 'Welcome to Nepal Store! Enjoy your shopping experience.', '', 0, '2025-09-18 04:36:49'),
(294, 39, NULL, 'Welcome to Nepal Store! Enjoy your shopping experience.', '', 0, '2025-09-22 07:13:08'),
(295, 40, NULL, 'Welcome to Nepal Store! Enjoy your shopping experience.', '', 0, '2025-10-16 01:56:58');

-- --------------------------------------------------------

--
-- Table structure for table `offers`
--

CREATE TABLE `offers` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `discount` int(11) NOT NULL,
  `link` varchar(255) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `name` varchar(20) NOT NULL,
  `number` varchar(10) NOT NULL,
  `email` varchar(50) NOT NULL,
  `method` varchar(50) NOT NULL,
  `address` varchar(500) NOT NULL,
  `total_products` varchar(1000) NOT NULL,
  `total_price` int(100) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `product_details` text DEFAULT NULL,
  `product_ids` varchar(255) DEFAULT NULL,
  `placed_on` date NOT NULL DEFAULT current_timestamp(),
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `name`, `number`, `email`, `method`, `address`, `total_products`, `total_price`, `status`, `product_details`, `product_ids`, `placed_on`, `payment_status`, `transaction_id`) VALUES
(69, 36, 'Gaurav Kandel', '9840245415', 'Admin@gmail.com', 'esewa', 'bagmati - 44207', 'laptop i5 (15000 x 1) - ', 15000, 'pending', NULL, '', '2025-09-07', 'cancelled', NULL),
(71, 36, 'Gaurav Kandel', '9840245415', 'Admin@gmail.com', 'esewa', 'bagmati - 44207', 'laptop i5 (15000 x 1) - ', 15000, 'pending', NULL, '', '2025-09-13', 'completed', NULL),
(72, 36, 'Gaurav Kandel', '9840245415', 'Admin@gmail.com', 'cash on delivery', 'bagmati - 44207', 'Washing machine (20000 x 5) - ', 100000, 'pending', NULL, '', '2025-09-13', 'pending', NULL),
(73, 40, 'jabisha', '9840245415', 'Admin@gmail.com', 'esewa', 'bagmati - 44207', 'Washing machine (20000 x 1) - Mouse (499 x 4) - ', 21996, 'pending', NULL, ',', '2025-10-16', 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_verification`
--

CREATE TABLE `otp_verification` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expiry_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `details` varchar(500) NOT NULL,
  `price` int(10) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `image_01` varchar(100) NOT NULL,
  `image_02` varchar(100) NOT NULL,
  `image_03` varchar(100) NOT NULL,
  `status` varchar(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `details`, `price`, `stock`, `quantity`, `image_01`, `image_02`, `image_03`, `status`) VALUES
(11, 'Washing machine', 'best for washing chothes', 20000, 10, 20, 'washing machine-1.webp', 'washing machine-2.webp', 'washing machine-3.webp', 'active'),
(15, 'Mouse', 'A high-precision, ergonomic mouse designed for smooth navigation and comfort. Features include optical/laser tracking for accurate movement, responsive click buttons, and a scroll wheel for easy browsing. Available in wired or wireless models, compatible with most operating systems. Ideal for everyday use, gaming, or professional work.', 499, 10, 0, 'mouse-1.webp', 'mouse-2.webp', 'mouse-3.webp', 'active'),
(17, 'Camera', 'new', 599, 10, 0, 'camera-1.webp', 'camera-2.webp', 'camera-3.webp', 'active'),
(19, 'watch', 'new product ', 599, 100, 50, '68e5311234b15_1759850770.webp', '68e5311234b1f_1759850770.webp', '68e5311234b21_1759850770.webp', 'active'),
(20, 'admin', 'b,kbSKk,asb', 444, 1222, 10, '68e5324e96394_1759851086.webp', '68e5324e9639b_1759851086.webp', '68e5324e9639c_1759851086.webp', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(100) NOT NULL,
  `name` varchar(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `birth_date` date NOT NULL DEFAULT '2000-01-01',
  `gender` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `otp`, `otp_expiry`, `phone`, `address`, `profile_pic`, `created_at`, `updated_at`, `last_login`, `last_login_ip`, `birth_date`, `gender`) VALUES
(28, 'Shiyam', 'shiyam@gmail.com', '234962207', NULL, NULL, NULL, NULL, NULL, '2025-08-10 02:39:10', '2025-09-16 10:44:52', NULL, NULL, '2000-01-01', NULL),
(36, 'Gaurav Kandel', 'kandelgaurav04@gmail.com', '228481177', '836728', 1757735730, '9840245415', 'Rapti,8\r\nKhurkhure\r\nBadahara', '68cb8b7af1b951.86134106.png', '2025-09-04 12:30:24', '2025-09-18 00:47:58', '2025-09-18 05:39:42', '::1', '2002-12-07', 'male'),
(38, 'kandel gaurav', 'kandelgk64@gmail.com', '925400306', NULL, NULL, '', '', NULL, '2025-09-18 04:36:22', '2025-09-18 00:53:17', '2025-09-18 06:36:56', '::1', '2000-01-01', ''),
(39, 'Sushil', 'sushil@gmail.com', '395865210', NULL, NULL, NULL, NULL, NULL, '2025-09-22 07:12:46', '2025-09-22 07:12:46', NULL, NULL, '2000-01-01', NULL),
(40, 'jabisha', 'jabisha@gmail.com', '764165800', NULL, NULL, NULL, NULL, NULL, '2025-10-16 01:55:13', '2025-10-15 22:12:54', '2025-10-16 03:57:54', '::1', '2000-01-01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(100) NOT NULL,
  `user_id` int(100) NOT NULL,
  `pid` int(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` int(100) NOT NULL,
  `image` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `pid`, `name`, `price`, `image`) VALUES
(13, 36, 17, 'Camera', 599, 'camera-1.webp');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_index` (`user_id`),
  ADD KEY `activity_time_index` (`activity_time`),
  ADD KEY `activity_type_index` (`activity_type`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cart_product` (`product_id`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `fk_product` (`product_id`);

--
-- Indexes for table `otp_verification`
--
ALTER TABLE `otp_verification`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `expiry_time` (`expiry_time`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=296;

--
-- AUTO_INCREMENT for table `offers`
--
ALTER TABLE `offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_verification`
--
ALTER TABLE `otp_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `login_history`
--
ALTER TABLE `login_history`
  ADD CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
