-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Май 08 2026 г., 12:05
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `a`
--

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `status` enum('draft','active','sold') DEFAULT 'draft',
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `quantity`, `price`, `created_at`, `updated_at`, `user_id`, `status`, `image`) VALUES
(1, 'asisbek', 'Лав вичакум', 0, 12222.00, '2026-04-27 09:58:20', '2026-04-27 11:00:11', 5, 'active', NULL),
(2, 'xiaomi', NULL, 0, 120.00, '2026-04-27 11:18:07', '2026-04-27 11:19:08', 5, 'active', NULL),
(3, 'Samsung galaxy g23', NULL, 0, 100.00, '2026-04-27 11:50:44', '2026-04-27 13:52:28', 5, 'active', '1777290644_69ef4d94e4789.webp'),
(4, 'iphone 17', 'iphone 17 pro max', 22, 1333.00, '2026-04-28 13:38:27', '2026-04-28 13:38:27', NULL, 'draft', NULL),
(6, 'uaz', NULL, 1, 1488.00, '2026-04-28 13:42:07', '2026-04-28 13:42:07', 5, 'active', '1777383727_69f0b92f9df6b.jpg'),
(7, 'ddr 5 ram 64 gb', 'HYPER RGB DDR 5 RAM', 22, 2222.00, '2026-04-28 14:23:23', '2026-04-28 14:23:23', NULL, 'draft', NULL),
(8, 'paracetamol', NULL, 222, 222.00, '2026-04-28 14:23:46', '2026-04-28 14:23:46', 5, 'active', NULL),
(9, 'nurofen', 'nurofen shat lav dexa', 222, 22.00, '2026-04-28 17:01:58', '2026-04-28 17:01:58', NULL, 'draft', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `report_text` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `product_id`, `report_text`, `status`, `created_at`) VALUES
(2, 5, 8, 'aaa', 'new', '2026-05-07 13:41:39');

-- --------------------------------------------------------

--
-- Структура таблицы `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `sold_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `sales`
--

INSERT INTO `sales` (`id`, `product_id`, `user_id`, `quantity_sold`, `total_price`, `sold_at`, `updated_at`) VALUES
(3, 1, 5, 1, 12222.00, '2026-04-27 10:13:55', NULL),
(6, 1, 5, 2, 24444.00, '2026-04-27 10:47:00', NULL),
(8, 2, 5, 1, 120.00, '2026-04-27 11:19:06', NULL),
(9, 2, 5, 1, 120.00, '2026-04-27 11:19:07', NULL),
(10, 2, 5, 1, 120.00, '2026-04-27 11:19:07', NULL),
(11, 2, 5, 1, 120.00, '2026-04-27 11:19:08', NULL),
(12, 3, 5, 1, 100.00, '2026-04-27 11:50:56', NULL),
(13, 3, 5, 1, 100.00, '2026-04-27 11:57:30', NULL),
(14, 3, 5, 1, 100.00, '2026-04-27 11:57:30', NULL),
(15, 3, 5, 1, 100.00, '2026-04-27 11:57:31', NULL),
(16, 3, 5, 1, 100.00, '2026-04-27 11:57:32', NULL),
(17, 3, 5, 4, 400.00, '2026-04-27 13:43:28', NULL),
(18, 3, 5, 1, 100.00, '2026-04-27 13:52:28', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `image`) VALUES
(3, 'vigen', '$2y$10$7YEazWtjfAyuJEmEMwOre.EiIwUOvyQ3Qr3LL1Ja71iv1KA7Pzw0K', 'admin', '2026-04-24 10:24:06', 'user_3_1778171976.jpg'),
(5, 'Davo23', '$2y$10$bOSYmPiQXFgSgssOTAxfZOVB4JyatmKtIAeGQA2nRPWiIxqezAx/S', 'user', '2026-04-25 17:19:31', 'user_5_1778172005.jpg');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
