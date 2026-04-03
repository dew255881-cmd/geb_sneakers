-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 05:51 PM
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
-- Database: `geb_sneakers`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_addresses`
--

CREATE TABLE `tb_addresses` (
  `addr_id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `addr_label` varchar(50) DEFAULT 'บ้าน',
  `addr_fullname` varchar(100) NOT NULL,
  `addr_phone` varchar(20) NOT NULL,
  `addr_detail` text NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_brands`
--

CREATE TABLE `tb_brands` (
  `b_id` int(11) NOT NULL,
  `b_name` varchar(100) NOT NULL,
  `b_logo` varchar(255) DEFAULT 'default_logo.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_brands`
--

INSERT INTO `tb_brands` (`b_id`, `b_name`, `b_logo`, `created_at`, `updated_at`) VALUES
(1, 'Nike', 'nike.png', '2026-03-31 09:24:10', '2026-03-31 09:24:10'),
(2, 'Adidas', 'adidas.png', '2026-03-31 09:24:10', '2026-03-31 09:24:10'),
(3, 'Jordan', 'jordan.png', '2026-03-31 09:24:10', '2026-03-31 09:24:10'),
(4, 'New Balance', 'default_logo.png', '2026-03-31 09:51:45', '2026-03-31 10:21:35'),
(5, 'Converse', 'default_logo.png', '2026-03-31 09:51:45', '2026-03-31 10:21:41'),
(6, 'Vans', 'default_logo.png', '2026-03-31 10:07:44', '2026-03-31 10:21:47');

-- --------------------------------------------------------

--
-- Table structure for table `tb_orders`
--

CREATE TABLE `tb_orders` (
  `o_id` int(11) NOT NULL,
  `u_id` int(11) DEFAULT NULL,
  `addr_id` int(11) DEFAULT NULL,
  `o_fullname` varchar(100) DEFAULT NULL,
  `o_phone` varchar(20) DEFAULT NULL,
  `o_address` text DEFAULT NULL,
  `o_total` decimal(10,2) NOT NULL,
  `o_status` enum('pending','confirmed','shipped','done','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_orders`
--

INSERT INTO `tb_orders` (`o_id`, `u_id`, `addr_id`, `o_fullname`, `o_phone`, `o_address`, `o_total`, `o_status`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'Teeranai Thong-u-thai', '0639315561', 'บ้านม้งหนองหอย ต.แม่แรม อ.แม่ริม จ.เชียงใหม่ 50180', 3890.00, 'cancelled', '2026-03-31 15:14:48', '2026-03-31 15:20:06');

-- --------------------------------------------------------

--
-- Table structure for table `tb_order_details`
--

CREATE TABLE `tb_order_details` (
  `d_id` int(11) NOT NULL,
  `o_id` int(11) DEFAULT NULL,
  `p_id` int(11) DEFAULT NULL,
  `color_id` int(11) DEFAULT NULL,
  `size_number` varchar(10) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `price_at_order` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_order_details`
--

INSERT INTO `tb_order_details` (`d_id`, `o_id`, `p_id`, `color_id`, `size_number`, `qty`, `price_at_order`) VALUES
(1, 1, 1, NULL, '42', 1, 3890.00);

-- --------------------------------------------------------

--
-- Table structure for table `tb_payments`
--

CREATE TABLE `tb_payments` (
  `pay_id` int(11) NOT NULL,
  `o_id` int(11) DEFAULT NULL,
  `pay_slip` varchar(255) NOT NULL,
  `pay_amount` decimal(10,2) NOT NULL,
  `pay_date` datetime DEFAULT NULL,
  `pay_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_payments`
--

INSERT INTO `tb_payments` (`pay_id`, `o_id`, `pay_slip`, `pay_amount`, `pay_date`, `pay_status`, `admin_note`, `created_at`, `updated_at`) VALUES
(1, 1, '69cbe531a22b5.jpg', 3890.00, '2026-03-31 17:14:00', 'approved', '', '2026-03-31 15:16:01', '2026-03-31 15:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `tb_products`
--

CREATE TABLE `tb_products` (
  `p_id` int(11) NOT NULL,
  `p_name` varchar(255) NOT NULL,
  `b_id` int(11) DEFAULT NULL,
  `p_price` decimal(10,2) NOT NULL,
  `p_detail` text DEFAULT NULL,
  `p_img` varchar(255) DEFAULT 'no_image.png',
  `p_status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_products`
--

INSERT INTO `tb_products` (`p_id`, `p_name`, `b_id`, `p_price`, `p_detail`, `p_img`, `p_status`, `created_at`, `updated_at`) VALUES
(1, 'VANS PREMIUM OLD SKOOL - BLACK/WHITE', 6, 3890.00, 'Style 36 รองเท้าสเก็ตคู่ที่สองที่ Vans สร้างขึ้น เปิดตัวครั้งแรกในปี 1977 และเป็นรองเท้ารุ่นแรกของ Vans ที่มี Sidestripe™ อันเป็นเอกลักษณ์ (หรือที่รู้จักในชื่อ “Jazz Stripe”) ซึ่งกลายเป็นสัญลักษณ์สำคัญในประวัติศาสตร์รองเท้า ต่อมา Style 36 ถูกเปลี่ยนชื่อเป็น Old Skool™ ในช่วงต้นยุค 90 ก่อนจะฉลองครบรอบ 30 ปี และกลายเป็นส่วนหนึ่งของ Vans Classic อย่างเป็นทางการ รายละเอียดการออกแบบของรองเท้า Premium Old Skool 36 ประกอบด้วยขอบยางที่มีความเงาสูงขึ้นและการเย็บที่ละเอียดอ่อน\r\nผ้าแคนวาสน้ำหนัก 8 ออนซ์ พร้อมการพิมพ์ลายและหนังกลับ รองเท้าทรงโลว์ท็อปพร้อม Sidestripe™ อันเป็นเอกลักษณ์ ขอบรองเท้าบุด้วยหนังฟูลเกรน เชือกรองเท้าผ้าฝ้าย 100% ป้ายโลโก้แบบถัก ขอบยางสูงขึ้นพร้อมผิวเงา เทปขอบรองเท้าเสริมความแข็งแรงแบบ Osnaburg สไตล์ยุค 90 ปลอกคอบุนุ่ม พื้นรองเท้า Sola Foam All-Day-Comfort (ADC) ที่ป้องกันความเมื่อยล้าและผลิตจากวัสดุชีวภาพ 30%', '69cbe3f7a2d52.jpg', 'active', '2026-03-31 15:10:47', '2026-03-31 15:10:47');

-- --------------------------------------------------------

--
-- Table structure for table `tb_product_colors`
--

CREATE TABLE `tb_product_colors` (
  `color_id` int(11) NOT NULL,
  `p_id` int(11) NOT NULL,
  `color_name` varchar(50) NOT NULL,
  `color_img` varchar(255) DEFAULT 'no_image.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_stock`
--

CREATE TABLE `tb_stock` (
  `s_id` int(11) NOT NULL,
  `p_id` int(11) DEFAULT NULL,
  `color_id` int(11) DEFAULT NULL,
  `size_number` varchar(10) NOT NULL,
  `qty` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_stock`
--

INSERT INTO `tb_stock` (`s_id`, `p_id`, `color_id`, `size_number`, `qty`, `updated_at`) VALUES
(1, 1, NULL, '42', 10, '2026-03-31 15:20:06');

-- --------------------------------------------------------

--
-- Table structure for table `tb_users`
--

CREATE TABLE `tb_users` (
  `u_id` int(11) NOT NULL,
  `u_username` varchar(50) NOT NULL,
  `u_password` varchar(255) NOT NULL,
  `u_fullname` varchar(100) DEFAULT NULL,
  `u_tel` varchar(20) DEFAULT NULL,
  `u_address` text DEFAULT NULL,
  `u_level` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_users`
--

INSERT INTO `tb_users` (`u_id`, `u_username`, `u_password`, `u_fullname`, `u_tel`, `u_address`, `u_level`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$hy25pfaNzH1ZnwMgTC4znu0/HpSthkxbIMo24PyjIhbP43c2Iy7Ne', 'admin', '0639315561', NULL, 'admin', '2026-03-31 10:17:15', '2026-03-31 10:17:41'),
(2, 'test', '$2y$10$xjedx9e7QDcdqRwtNSyEzesmF3CG9KvTi3BeqMxuvcStqXznz0FA6', 'Teeranai Thong-u-thai', '0639315561', NULL, 'user', '2026-03-31 10:14:41', '2026-03-31 10:14:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_addresses`
--
ALTER TABLE `tb_addresses`
  ADD PRIMARY KEY (`addr_id`),
  ADD KEY `u_id` (`u_id`);

--
-- Indexes for table `tb_brands`
--
ALTER TABLE `tb_brands`
  ADD PRIMARY KEY (`b_id`);

--
-- Indexes for table `tb_orders`
--
ALTER TABLE `tb_orders`
  ADD PRIMARY KEY (`o_id`),
  ADD KEY `u_id` (`u_id`),
  ADD KEY `fk_orders_address` (`addr_id`);

--
-- Indexes for table `tb_order_details`
--
ALTER TABLE `tb_order_details`
  ADD PRIMARY KEY (`d_id`),
  ADD KEY `o_id` (`o_id`),
  ADD KEY `p_id` (`p_id`),
  ADD KEY `fk_orderdetails_color_ref` (`color_id`);

--
-- Indexes for table `tb_payments`
--
ALTER TABLE `tb_payments`
  ADD PRIMARY KEY (`pay_id`),
  ADD KEY `o_id` (`o_id`);

--
-- Indexes for table `tb_products`
--
ALTER TABLE `tb_products`
  ADD PRIMARY KEY (`p_id`),
  ADD KEY `b_id` (`b_id`);

--
-- Indexes for table `tb_product_colors`
--
ALTER TABLE `tb_product_colors`
  ADD PRIMARY KEY (`color_id`),
  ADD KEY `p_id` (`p_id`);

--
-- Indexes for table `tb_stock`
--
ALTER TABLE `tb_stock`
  ADD PRIMARY KEY (`s_id`),
  ADD UNIQUE KEY `unique_product_color_size` (`p_id`,`color_id`,`size_number`),
  ADD KEY `p_id` (`p_id`),
  ADD KEY `fk_stock_color_ref` (`color_id`);

--
-- Indexes for table `tb_users`
--
ALTER TABLE `tb_users`
  ADD PRIMARY KEY (`u_id`),
  ADD UNIQUE KEY `u_username` (`u_username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_addresses`
--
ALTER TABLE `tb_addresses`
  MODIFY `addr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_brands`
--
ALTER TABLE `tb_brands`
  MODIFY `b_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tb_orders`
--
ALTER TABLE `tb_orders`
  MODIFY `o_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_order_details`
--
ALTER TABLE `tb_order_details`
  MODIFY `d_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_payments`
--
ALTER TABLE `tb_payments`
  MODIFY `pay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_products`
--
ALTER TABLE `tb_products`
  MODIFY `p_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_product_colors`
--
ALTER TABLE `tb_product_colors`
  MODIFY `color_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_stock`
--
ALTER TABLE `tb_stock`
  MODIFY `s_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_users`
--
ALTER TABLE `tb_users`
  MODIFY `u_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_addresses`
--
ALTER TABLE `tb_addresses`
  ADD CONSTRAINT `tb_addresses_ibfk_1` FOREIGN KEY (`u_id`) REFERENCES `tb_users` (`u_id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_orders`
--
ALTER TABLE `tb_orders`
  ADD CONSTRAINT `fk_orders_address` FOREIGN KEY (`addr_id`) REFERENCES `tb_addresses` (`addr_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`u_id`) REFERENCES `tb_users` (`u_id`) ON DELETE SET NULL;

--
-- Constraints for table `tb_order_details`
--
ALTER TABLE `tb_order_details`
  ADD CONSTRAINT `fk_orderdetails_color_ref` FOREIGN KEY (`color_id`) REFERENCES `tb_product_colors` (`color_id`),
  ADD CONSTRAINT `fk_orderdetails_order` FOREIGN KEY (`o_id`) REFERENCES `tb_orders` (`o_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orderdetails_product` FOREIGN KEY (`p_id`) REFERENCES `tb_products` (`p_id`);

--
-- Constraints for table `tb_payments`
--
ALTER TABLE `tb_payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`o_id`) REFERENCES `tb_orders` (`o_id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_products`
--
ALTER TABLE `tb_products`
  ADD CONSTRAINT `fk_products_brand` FOREIGN KEY (`b_id`) REFERENCES `tb_brands` (`b_id`) ON DELETE SET NULL;

--
-- Constraints for table `tb_product_colors`
--
ALTER TABLE `tb_product_colors`
  ADD CONSTRAINT `tb_product_colors_ibfk_1` FOREIGN KEY (`p_id`) REFERENCES `tb_products` (`p_id`) ON DELETE CASCADE;

--
-- Constraints for table `tb_stock`
--
ALTER TABLE `tb_stock`
  ADD CONSTRAINT `fk_stock_color_ref` FOREIGN KEY (`color_id`) REFERENCES `tb_product_colors` (`color_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stock_product` FOREIGN KEY (`p_id`) REFERENCES `tb_products` (`p_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
