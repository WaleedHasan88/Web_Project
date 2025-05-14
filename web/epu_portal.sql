-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2025 at 11:24 PM
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
-- Database: `epu_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `first_name`, `last_name`, `full_name`, `remember_token`, `token_expiry`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$y6ext/OAQi/LHwd9YGnD4OxSyKYu9qEY8YlcrJ50HLtAMdiONuvgm', 'admin1@gmail.com', 'Admin', 'User', 'System Administrator', NULL, NULL, '2025-04-21 19:45:46', '2025-05-14 09:00:04');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `date_of_birth` date NOT NULL,
  `program` enum('undergraduate','graduate') NOT NULL,
  `major` varchar(100) NOT NULL,
  `gpa` decimal(3,2) NOT NULL,
  `transcript_path` varchar(255) NOT NULL,
  `test_scores_path` varchar(255) NOT NULL,
  `recommendation_path` varchar(255) NOT NULL,
  `personal_statement` text NOT NULL,
  `extracurricular_activities` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `program`, `major`, `gpa`, `transcript_path`, `test_scores_path`, `recommendation_path`, `personal_statement`, `extracurricular_activities`, `status`, `admin_comment`, `created_at`, `updated_at`) VALUES
(1, 'Waleed', 'Hasan', 'student@epu.edu', '+9647509876543', '2025-05-04', 'undergraduate', 'ST', 3.58, 'uploads/applications/681d226ee32a5_bootstrap1.pdf', 'uploads/applications/681d226ee339f_bootstrap2.pdf', 'uploads/applications/681d226ee343f_test1 - Copy.pdf', 'asdfasdf', '', 'pending', NULL, '2025-05-08 21:30:22', '2025-05-08 21:30:22'),
(2, 'Waleed', 'Hasan', 'student@epu.edu', '+9647509876543', '2025-05-07', 'undergraduate', 'ST', 3.00, 'uploads/applications/681d228d0bbea_bootstrap1.pdf', 'uploads/applications/681d228d0bd27_bootstrap1.pdf', 'uploads/applications/681d228d0bdeb_bootstrap2.pdf', 'adsfad', '', 'pending', NULL, '2025-05-08 21:30:53', '2025-05-08 21:30:53'),
(4, 'ahmad', 'mohamad', 'student@epu.edu', '+9647509876543', '1998-05-13', 'undergraduate', 'i am a want to studnet', 3.60, 'uploads/applications/682455ed87a74_bootstrap1.pdf', 'uploads/applications/682455ed87d7b_bootstrap2.pdf', 'uploads/applications/682455ed87e9c_test1 - Copy.pdf', 'aaaaaa', 'aaa', 'approved', '', '2025-05-14 08:35:57', '2025-05-14 08:45:28'),
(5, 'Joy', 'Frazier', 'cidyma@mailinator.com', '+1 (524) 268-1065', '1987-10-05', 'undergraduate', 'Veniam voluptatem r', 4.00, 'uploads/applications/transcript_682501ab6e6d2_Doc1.pdf', 'uploads/applications/scores_682501ab6e87a_Doc1.pdf', 'uploads/applications/recommendation_682501ab6e9f2_Doc1.pdf', 'Neque optio aliquaas', 'Maxime suscipit volu', 'pending', NULL, '2025-05-14 20:48:43', '2025-05-14 20:48:43');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `instructor` varchar(100) NOT NULL,
  `schedule` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `credits` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_code`, `course_name`, `instructor`, `schedule`, `department`, `credits`, `description`, `created_at`, `updated_at`) VALUES
(31, '101WEB', 'Web Development', 'Niyaz Mohaammad', '', 'Information Systems Engineering', 6, NULL, '2025-05-09 18:31:58', '2025-05-09 18:51:09'),
(32, '102AOOP', 'Advanced Object-Oriented Programming', 'Kosrat Ahmad', '', 'Information Systems Engineering', 6, NULL, '2025-05-09 18:33:46', '2025-05-09 18:51:12');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `grade` varchar(2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `numeric_grade` decimal(5,2) DEFAULT NULL,
  `updated_by` varchar(10) DEFAULT NULL COMMENT 'Format: T123 for teachers, A123 for admins'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `semester`, `year`, `grade`, `created_at`, `updated_at`, `status`, `numeric_grade`, `updated_by`) VALUES
(48, 18, 31, 'Spring', 2025, 'A-', '2025-05-09 18:46:34', '2025-05-14 20:19:10', 'approved', 90.00, 'T29'),
(49, 19, 31, 'Spring', 2025, 'C-', '2025-05-09 18:46:45', '2025-05-12 20:13:09', 'approved', 70.00, 'T29'),
(50, 20, 32, 'Spring', 2025, NULL, '2025-05-09 18:46:51', '2025-05-09 18:46:51', 'approved', NULL, NULL),
(52, 21, 32, 'Spring', 2025, NULL, '2025-05-09 18:48:28', '2025-05-09 18:48:28', 'approved', NULL, NULL),
(54, 18, 32, 'Spring', 2025, NULL, '2025-05-14 08:54:00', '2025-05-14 08:54:00', 'pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lecture_files`
--

CREATE TABLE `lecture_files` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lecture_files`
--

INSERT INTO `lecture_files` (`id`, `course_id`, `title`, `description`, `file_name`, `file_path`, `uploaded_by`, `uploaded_at`) VALUES
(4, 31, 'Leacture 1', 'Please Study ', 'l1.pdf', '681e59461fde0_l1.pdf', 29, '2025-05-09 19:36:38');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `day` varchar(10) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `course_id`, `day`, `start_time`, `end_time`, `room`, `created_at`, `updated_at`) VALUES
(41, 32, 'Wednesday', '10:00:00', '11:30:00', 'Lab 3', '2025-05-12 21:29:01', '2025-05-12 21:29:01'),
(42, 31, 'Thursday', '09:00:00', '13:00:00', 'Lab 1', '2025-05-14 08:48:19', '2025-05-14 08:48:19');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `year` int(11) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `degree` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `first_name`, `last_name`, `password`, `email`, `full_name`, `department`, `year`, `remember_token`, `token_expiry`, `created_at`, `updated_at`, `degree`) VALUES
(18, 'S1', 'Waleed', 'Hasan', '$2y$10$SNwO4FjAY/MRzcE/ieodBuilCAzpv//d9tUEI0P4j1d7rqpG84B6m', 'Waleed@epu.edu.iq', '', '', 0, '4aebf054ece5867a5b1d7d9771b4f410c84b5ddc8449aa02c7270f9c6d7cd7ea', '2025-06-13 10:53:37', '2025-05-09 18:39:25', '2025-05-14 08:53:37', NULL),
(19, 'S2', 'Zaynab', 'Tariq', '$2y$10$x4MKVDcsB8FKductYRYPM.xeP05wPyuWFXNUp5iOvqeLWkeW6vZWO', 'Zaynab@epu.edu.iq', '', '', 0, NULL, NULL, '2025-05-09 18:40:40', '2025-05-09 18:40:40', NULL),
(20, 'S3', 'Ahmad', 'Muhammad', '$2y$10$IF5Z5eqmrI8Vp/0qMAbhOeJFoXkFjnR20AvDdYf6.wWN5LMGAlhMm', 'Ahmad@epu.edu.iq', '', '', 0, NULL, NULL, '2025-05-09 18:41:16', '2025-05-09 18:41:16', NULL),
(21, 'S4', 'Mohammed', 'Ali', '$2y$10$qhvu2gbSeB6F.PfD4Zga4eRIwoAfmDnN71DNS7PWNc1HRwp2KQeAq', 'Mohammed@epu.edu.iq', '', '', 0, NULL, NULL, '2025-05-09 18:41:52', '2025-05-09 18:41:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `teacher_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `teacher_id`, `first_name`, `last_name`, `password`, `email`, `full_name`, `department`, `specialization`, `remember_token`, `token_expiry`, `created_at`, `updated_at`) VALUES
(29, 'T1', 'Niyaz', 'Mohaammad', '$2y$10$QD.6cPLCXRlLxE.MRscH1uTMkkTpjJpOFiGQTQ8ItOZF1oPNo09iK', 'Niyaz@epu.edu', 'Niyaz Mohaammad', 'Information Systems Engineering', 'Web Development', '0603c836d5317e6faac8554ff64f7d4809798cce4a99d02c4ea809c4d452b411', '2025-06-13 10:45:43', '2025-05-09 18:37:38', '2025-05-14 08:45:43'),
(30, 'T2', 'Kosrat', 'Ahmad', '$2y$10$66uaTAo0UJcOkqibFXp9p.AShbtj.Ews6ogMA8Eu6TDb7SWXO8AK2', 'Kosrat@epu.edu.iq', 'Kosrat Ahmad', 'Information Systems Engineering', 'Programmin', '25377d6fc29e5bb95e43cabf71599bce7ab6d0329a43129556f48f3f167affa1', '2025-06-11 23:27:59', '2025-05-09 18:38:30', '2025-05-12 21:27:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`,`semester`,`year`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `lecture_files`
--
ALTER TABLE `lecture_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_id` (`teacher_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `lecture_files`
--
ALTER TABLE `lecture_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lecture_files`
--
ALTER TABLE `lecture_files`
  ADD CONSTRAINT `lecture_files_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lecture_files_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
