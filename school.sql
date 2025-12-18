-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 18, 2025 at 02:35 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school`
--

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `class_teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `class_name`, `year`, `class_teacher_id`) VALUES
(1, 'Test Class', 2023, NULL),
(2, 'Demo Class A', 2025, 12),
(3, '5 Amanah', 2025, 16),
(4, '5 Bestari', 2025, 19),
(5, '6 Cemerlang', 2025, 15),
(6, '6 Dinamik', 2025, 17),
(7, '4 Harmoni', 2025, 18),
(8, '4 Intelek', 2025, 20),
(9, '3 Jujur', 2025, 21);

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `notice_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','alert','event') DEFAULT 'info',
  `audience` enum('all','admin','class_teacher','subject_teacher') DEFAULT 'all',
  `event_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`notice_id`, `title`, `message`, `type`, `audience`, `event_date`, `created_by`, `created_at`) VALUES
(1, 'School Sports Day', 'The annual sports day will be held on the main ground. All teachers must attend.', 'event', 'all', '2025-12-10', 5, '2025-11-27 11:24:22'),
(2, 'Exam Results Submission', 'Please submit all grading for the Midterm exams by Friday.', 'alert', 'subject_teacher', '2025-11-28', 5, '2025-11-27 11:24:22'),
(3, 'Staff Meeting', 'Monthly staff meeting in the Conference Hall.', 'info', 'all', '2025-12-01', 5, '2025-11-27 11:24:22');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_id`, `setting_key`, `setting_value`) VALUES
(1, 'school_name', 'SIRAJ Al Alusi'),
(2, 'school_address', 'Jalan 1/2, Kuala Lumpur, Malaysia'),
(3, 'school_phone', '+60 3-1234 5678'),
(4, 'current_year', '2025'),
(5, 'current_term', 'Midterm');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `school_register_no` varchar(50) DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `previous_school` varchar(255) DEFAULT NULL,
  `student_name` varchar(100) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `sports_house` varchar(50) DEFAULT NULL,
  `birth_cert_no` varchar(50) DEFAULT NULL,
  `birth_place` varchar(100) DEFAULT NULL,
  `ic_no` varchar(50) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `race` varchar(50) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `father_ic` varchar(20) DEFAULT NULL,
  `father_phone` varchar(20) DEFAULT NULL,
  `father_job` varchar(100) DEFAULT NULL,
  `father_salary` decimal(10,2) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `mother_ic` varchar(20) DEFAULT NULL,
  `mother_phone` varchar(20) DEFAULT NULL,
  `mother_job` varchar(100) DEFAULT NULL,
  `mother_salary` decimal(10,2) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_ic` varchar(20) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_job` varchar(100) DEFAULT NULL,
  `guardian_salary` decimal(10,2) DEFAULT NULL,
  `is_baitulmal_recipient` varchar(10) DEFAULT NULL,
  `is_orphan` varchar(10) DEFAULT NULL,
  `parents_marital_status` varchar(50) DEFAULT NULL,
  `uniform_unit` varchar(100) DEFAULT NULL,
  `uniform_position` varchar(100) DEFAULT NULL,
  `club_association` varchar(100) DEFAULT NULL,
  `club_position` varchar(100) DEFAULT NULL,
  `sports_game` varchar(100) DEFAULT NULL,
  `sports_position` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `school_register_no`, `enrollment_date`, `previous_school`, `student_name`, `gender`, `sports_house`, `birth_cert_no`, `birth_place`, `ic_no`, `birthdate`, `race`, `religion`, `nationality`, `address`, `father_name`, `father_ic`, `father_phone`, `father_job`, `father_salary`, `mother_name`, `mother_ic`, `mother_phone`, `mother_job`, `mother_salary`, `guardian_name`, `guardian_ic`, `guardian_phone`, `guardian_job`, `guardian_salary`, `is_baitulmal_recipient`, `is_orphan`, `parents_marital_status`, `uniform_unit`, `uniform_position`, `club_association`, `club_position`, `sports_game`, `sports_position`, `phone`, `class_id`, `photo`) VALUES
(2, '12345', NULL, NULL, 'Test Stu 2', 'Male', 'Red', '2348585', 'Kuala Lumpur', 'A23MJ', '2025-11-18', 'Muslim', 'Islam', 'Malaysia', 'Jhe 234, Jhe la , KL', 'Juba Fat', '1253t', '0274642', 'Army', 10003.00, 'Noumi', '12645672', '0192863', 'Hpuse', 0.00, 'Father', '25364', NULL, NULL, NULL, 'No', 'Yes', 'Married', 'Red Crescent', '7', NULL, NULL, 'Football', 'Keeper', '12345', 7, '946ba80258f2e78f_1763662210.png'),
(3, '123456', '2025-12-08', NULL, 'Jubayer', 'Female', NULL, NULL, NULL, '121221', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 'stu_6937312ad68f0.png'),
(4, 'SR001', '2022-01-01', 'SK KL', 'Ahmad Firdaus', 'Male', 'Red', 'BRC001', 'Kuala Lumpur', '150101101111', '2015-01-01', 'Malay', 'Islam', 'Malaysian', 'KL Street 1', 'Ali Firdaus', '700101101111', '0121111111', 'Engineer', 4500.00, 'Siti Mariam', '720202202222', '0132222222', 'Teacher', 4200.00, 'Ali Firdaus', '700101101111', '0121111111', 'Engineer', 4500.00, 'No', 'No', 'Married', 'Scouts', 'Member', 'Science Club', 'Member', 'Football', 'Forward', '0111111111', 9, NULL),
(5, 'SR002', '2022-01-01', 'SK Sel', 'Nur Iman', 'Female', 'Blue', 'BRC002', 'Selangor', '150202202222', '2015-02-02', 'Malay', 'Islam', 'Malaysian', 'Selangor Street 2', 'Hassan Iman', '680303303333', '0123333333', 'Clerk', 3000.00, 'Zahra Iman', '700404404444', '0134444444', 'Housewife', 0.00, 'Hassan Iman', '680303303333', '0123333333', 'Clerk', 3000.00, 'Yes', 'No', 'Married', 'Guides', 'Leader', 'Math Club', 'Member', 'Netball', 'Center', '0112222222', 7, NULL),
(6, 'SR003', '2022-01-01', 'SJKC Pen', 'Daniel Lee', 'Male', 'Green', 'BRC003', 'Penang', '150303303333', '2015-03-03', 'Chinese', 'Buddhist', 'Malaysian', 'Penang Road', 'Lee Chong', '690505505555', '0125555555', 'Trader', 5000.00, 'Mei Ling', '710606606666', '0136666666', 'Clerk', 2800.00, 'Lee Chong', '690505505555', '0125555555', 'Trader', 5000.00, 'No', 'No', 'Married', 'Cadet', 'Member', 'Chess Club', 'Member', 'Badminton', 'Single', '0113333333', 7, NULL),
(7, 'SR004', '2022-01-01', 'SK JB', 'Aisyah Noor', 'Female', 'Yellow', 'BRC004', 'Johor', '150404404444', '2015-04-04', 'Malay', 'Islam', 'Malaysian', 'Johor Bahru', 'Noor Azman', '670707707777', '0127777777', 'Driver', 2800.00, 'Rosmah Noor', '690808808888', '0138888888', 'Tailor', 2000.00, 'Noor Azman', '670707707777', '0127777777', 'Driver', 2800.00, 'Yes', 'No', 'Married', 'Scouts', 'Member', 'Art Club', 'Member', 'Athletics', 'Runner', '0114444444', 9, NULL),
(8, 'SR005', '2022-01-01', 'SK Ipoh', 'Adam Rizqi', 'Male', 'Red', 'BRC005', 'Perak', '150505505555', '2015-05-05', 'Malay', 'Islam', 'Malaysian', 'Ipoh City', 'Rizal Adam', '660909909999', '0129999999', 'Technician', 3800.00, 'Sarah Adam', '681010101010', '0131010101', 'Nurse', 4200.00, 'Rizal Adam', '660909909999', '0129999999', 'Technician', 3800.00, 'No', 'No', 'Married', 'Prefect', 'Leader', 'Debate Club', 'Leader', 'Basketball', 'Guard', '0115555555', 9, NULL),
(9, 'SR006', '2022-01-01', 'SK Mel', 'Sofia Hana', 'Female', 'Blue', 'BRC006', 'Melaka', '150606606666', '2015-06-06', 'Malay', 'Islam', 'Malaysian', 'Melaka Town', 'Hanafi Sof', '650111111111', '0121111222', 'Farmer', 2500.00, 'Mariam Sof', '670222222222', '0132222333', 'Housewife', 0.00, 'Hanafi Sof', '650111111111', '0121111222', 'Farmer', 2500.00, 'Yes', 'No', 'Married', 'Guides', 'Member', 'Music Club', 'Member', 'Volleyball', 'Spiker', '0116666666', 7, NULL),
(10, 'SR007', '2022-01-01', 'SJKC KL', 'Kevin Tan', 'Male', 'Green', 'BRC007', 'KL', '150707707777', '2015-07-07', 'Chinese', 'Christian', 'Malaysian', 'KL City', 'Tan Boon', '640333333333', '0123333444', 'Business', 6500.00, 'Janet Tan', '660444444444', '0134444555', 'Accountant', 5200.00, 'Tan Boon', '640333333333', '0123333444', 'Business', 6500.00, 'No', 'No', 'Married', 'Cadet', 'Member', 'Robotic Club', 'Member', 'Table Tennis', 'Single', '0117777777', 7, NULL),
(11, 'SR008', '2022-01-01', 'SK Sel', 'Aiman Hakimi', 'Male', 'Red', 'BRC008', 'Selangor', '150808808888', '2015-08-08', 'Malay', 'Islam', 'Malaysian', 'Selangor', 'Hakimi Zainal', '650101010101', '0121010101', 'Manager', 5500.00, 'Sarina Hakimi', '670202020202', '0132020202', 'Clerk', 3200.00, 'Hakimi Zainal', '650101010101', '0121010101', 'Manager', 5500.00, 'No', 'No', 'Married', 'Scouts', 'Member', 'Math Club', 'Member', 'Football', 'Midfielder', '0118888888', 9, NULL),
(12, 'SR009', '2022-01-01', 'SJKC Pen', 'Melissa Lim', 'Female', 'Green', 'BRC009', 'Penang', '150909909999', '2015-09-09', 'Chinese', 'Christian', 'Malaysian', 'Penang', 'Lim Teck', '660303030303', '0123030303', 'Sales', 4200.00, 'Anna Lim', '680404040404', '0134040404', 'Admin', 3000.00, 'Lim Teck', '660303030303', '0123030303', 'Sales', 4200.00, 'No', 'No', 'Married', 'Guides', 'Member', 'Music Club', 'Member', 'Badminton', 'Double', '0119999999', 7, NULL),
(13, 'SR010', '2022-01-01', 'SK Perlis', 'Irfan Zaki', 'Male', 'Blue', 'BRC010', 'Perlis', '151010101010', '2015-10-10', 'Malay', 'Islam', 'Malaysian', 'Perlis', 'Zaki Irfan', '640505050505', '0125050505', 'Technician', 3600.00, 'Rina Zaki', '660606060606', '0136060606', 'Nurse', 4000.00, 'Zaki Irfan', '640505050505', '0125050505', 'Technician', 3600.00, 'Yes', 'No', 'Married', 'Prefect', 'Member', 'Science Club', 'Member', 'Athletics', 'Runner', '0111010101', 7, NULL),
(14, 'SR011', '2022-01-01', 'SK Ter', 'Nur Aqilah', 'Female', 'Yellow', 'BRC011', 'Terengganu', '151111111111', '2015-11-11', 'Malay', 'Islam', 'Malaysian', 'Terengganu', 'Azman Aqil', '630707070707', '0127070707', 'Fisherman', 2500.00, 'Aina Aqil', '650808080808', '0138080808', 'Housewife', 0.00, 'Azman Aqil', '630707070707', '0127070707', 'Fisherman', 2500.00, 'Yes', 'No', 'Married', 'Guides', 'Member', 'Art Club', 'Member', 'Netball', 'Goalkeeper', '0111111222', 7, NULL),
(15, 'SR012', '2022-01-01', 'SK Sabah', 'Ryan Joseph', 'Male', 'Red', 'BRC012', 'Sabah', '151212121212', '2015-12-12', 'Kadazan', 'Christian', 'Malaysian', 'Sabah', 'Joseph Ryan', '620909090909', '0129090909', 'Mechanic', 3400.00, 'Maria Ryan', '640101010101', '0131010111', 'Clerk', 2800.00, 'Joseph Ryan', '620909090909', '0129090909', 'Mechanic', 3400.00, 'No', 'No', 'Married', 'Cadet', 'Member', 'Robotic Club', 'Member', 'Basketball', 'Center', '0111212121', 7, NULL),
(16, 'SR013', '2023-01-01', 'SK KL', 'Alya Nabila', 'Female', 'Green', 'BRC013', 'KL', '160101010101', '2016-01-01', 'Malay', 'Islam', 'Malaysian', 'KL', 'Nabil Alya', '660202020202', '0122020202', 'Consultant', 6000.00, 'Huda Alya', '680303030303', '0133030303', 'HR Officer', 4500.00, 'Nabil Alya', '660202020202', '0122020202', 'Consultant', 6000.00, 'No', 'No', 'Married', 'Guides', 'Leader', 'Debate Club', 'Leader', 'Public Speaking', 'Speaker', '0111313131', 9, NULL),
(17, 'SR014', '2023-01-01', 'SJKT NS', 'Arjun Kumar', 'Male', 'Blue', 'BRC014', 'Negeri Sembilan', '160202020202', '2016-02-02', 'Indian', 'Hindu', 'Malaysian', 'NS', 'Kumar Arjun', '650404040404', '0124040404', 'Supervisor', 3800.00, 'Latha Kumar', '670505050505', '0135050505', 'Clerk', 2900.00, 'Kumar Arjun', '650404040404', '0124040404', 'Supervisor', 3800.00, 'No', 'No', 'Married', 'Scouts', 'Member', 'Science Club', 'Member', 'Cricket', 'Batsman', '0111414141', 7, NULL),
(18, 'SR015', '2023-01-01', 'SK PJ', 'Sabrina Noor', 'Female', 'Red', 'BRC015', 'Putrajaya', '160303030303', '2016-03-03', 'Malay', 'Islam', 'Malaysian', 'Putrajaya', 'Noor Sabri', '640606060606', '0126060606', 'Officer', 4200.00, 'Lina Sabri', '660707070707', '0137070707', 'Teacher', 4500.00, 'Noor Sabri', '640606060606', '0126060606', 'Officer', 4200.00, 'No', 'No', 'Married', 'Prefect', 'Leader', 'Language Club', 'Member', 'Debate', 'Speaker', '0111515151', 7, NULL),
(19, 'SR016', '2023-01-01', 'SJKC Johor', 'Benjamin Low', 'Male', 'Green', 'BRC016', 'Johor', '160404040404', '2016-04-04', 'Chinese', 'Christian', 'Malaysian', 'Johor', 'Low Seng', '630808080808', '0128080808', 'Engineer', 7200.00, 'May Low', '650909090909', '0139090909', 'Designer', 4800.00, 'Low Seng', '630808080808', '0128080808', 'Engineer', 7200.00, 'No', 'No', 'Married', 'Cadet', 'Member', 'ICT Club', 'Member', 'Swimming', 'Freestyle', '0111616161', 7, NULL),
(20, 'SR017', '2023-01-01', 'SK Pahang', 'Haziq Aiman', 'Male', 'Blue', 'BRC017', 'Pahang', '160505050505', '2016-05-05', 'Malay', 'Islam', 'Malaysian', 'Pahang', 'Aiman Haziq', '620101010101', '0121010123', 'Security', 3000.00, 'Rohani Aiman', '640202020202', '0132020234', 'Cleaner', 1800.00, 'Aiman Haziq', '620101010101', '0121010123', 'Security', 3000.00, 'Yes', 'No', 'Married', 'Scouts', 'Member', 'Outdoor Club', 'Member', 'Hiking', 'Participant', '0111717171', 7, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_marks`
--

CREATE TABLE `student_marks` (
  `mark_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `exam_type` varchar(50) NOT NULL,
  `mark_obtained` decimal(5,2) DEFAULT NULL,
  `max_mark` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_subject_enrollment`
--

CREATE TABLE `student_subject_enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `enrollment_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_subject_enrollment`
--

INSERT INTO `student_subject_enrollment` (`enrollment_id`, `student_id`, `subject_id`, `class_id`, `enrollment_date`) VALUES
(10, 8, 11, 9, '2025-12-15 04:43:22'),
(11, 8, 12, 9, '2025-12-15 04:43:22'),
(12, 8, 13, 9, '2025-12-15 04:43:22'),
(13, 8, 14, 9, '2025-12-15 04:43:22'),
(14, 4, 11, 9, '2025-12-15 04:43:22'),
(15, 4, 12, 9, '2025-12-15 04:43:22'),
(16, 4, 13, 9, '2025-12-15 04:43:22'),
(17, 4, 14, 9, '2025-12-15 04:43:22'),
(18, 11, 11, 9, '2025-12-15 04:43:22'),
(19, 11, 12, 9, '2025-12-15 04:43:22'),
(20, 11, 13, 9, '2025-12-15 04:43:22'),
(21, 11, 14, 9, '2025-12-15 04:43:22'),
(22, 7, 11, 9, '2025-12-15 04:43:22'),
(23, 7, 12, 9, '2025-12-15 04:43:22'),
(24, 7, 13, 9, '2025-12-15 04:43:22'),
(25, 7, 14, 9, '2025-12-15 04:43:22'),
(26, 16, 11, 9, '2025-12-15 04:43:22'),
(27, 16, 12, 9, '2025-12-15 04:43:22'),
(28, 16, 13, 9, '2025-12-15 04:43:22'),
(29, 16, 14, 9, '2025-12-15 04:43:22'),
(30, 17, 9, 7, '2025-12-17 03:24:52'),
(31, 17, 11, 7, '2025-12-17 03:24:52'),
(32, 19, 9, 7, '2025-12-17 03:24:52'),
(33, 19, 11, 7, '2025-12-17 03:24:52'),
(34, 6, 9, 7, '2025-12-17 03:24:52'),
(35, 6, 11, 7, '2025-12-17 03:24:52'),
(36, 20, 9, 7, '2025-12-17 03:24:52'),
(37, 20, 11, 7, '2025-12-17 03:24:52'),
(38, 13, 9, 7, '2025-12-17 03:24:52'),
(39, 13, 11, 7, '2025-12-17 03:24:52'),
(40, 3, 9, 7, '2025-12-17 03:24:52'),
(41, 3, 11, 7, '2025-12-17 03:24:52'),
(42, 10, 9, 7, '2025-12-17 03:24:52'),
(43, 10, 11, 7, '2025-12-17 03:24:52'),
(44, 12, 9, 7, '2025-12-17 03:24:52'),
(45, 12, 11, 7, '2025-12-17 03:24:52'),
(46, 14, 9, 7, '2025-12-17 03:24:52'),
(47, 14, 11, 7, '2025-12-17 03:24:52'),
(48, 5, 9, 7, '2025-12-17 03:24:52'),
(49, 5, 11, 7, '2025-12-17 03:24:52'),
(50, 15, 9, 7, '2025-12-17 03:24:52'),
(51, 15, 11, 7, '2025-12-17 03:24:52'),
(52, 18, 9, 7, '2025-12-17 03:24:52'),
(53, 18, 11, 7, '2025-12-17 03:24:52'),
(54, 9, 9, 7, '2025-12-17 03:24:52'),
(55, 9, 11, 7, '2025-12-17 03:24:52'),
(56, 2, 9, 7, '2025-12-17 03:24:52'),
(57, 2, 11, 7, '2025-12-17 03:24:52');

-- --------------------------------------------------------

--
-- Table structure for table `student_transfers`
--

CREATE TABLE `student_transfers` (
  `transfer_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `from_class_id` int(11) NOT NULL,
  `to_class_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `subject_code`, `teacher_id`, `class_id`) VALUES
(2, 'Demo Subject', 'DEMO101', 11, 2),
(3, 'Physics', 'SUB-1', 13, 1),
(4, 'Mathematics', 'MTH-5A', 15, 3),
(5, 'Science', 'SCI-5A', 17, 3),
(6, 'English', 'ENG-5B', 18, 4),
(7, 'History', 'HIS-6C', 16, 5),
(8, 'Geography', 'GEO-6D', 21, 6),
(9, 'Bahasa Melayu', 'BM-4H', 19, 7),
(10, 'Physics', 'PHY-6C', 15, 5),
(11, 'Bahasa Melayu', 'BM-4H-3Jujur', 19, 7),
(12, 'Demo Subject', 'DEMO101-3Jujur', 11, 9),
(13, 'English', 'ENG-5B-3Jujur', 21, 9),
(14, 'Mathematics', 'MTH-5A-3Jujur', 15, 9);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','class_teacher','subject_teacher') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL,
  `ic_no` varchar(50) DEFAULT NULL,
  `teacher_id_no` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `teaching_subjects` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `role`, `created_at`, `avatar`, `ic_no`, `teacher_id_no`, `phone`, `teaching_subjects`) VALUES
(10, 'admin', '$2y$10$.E1S150riokivbIpmRInmOR3FFuQXUKhWiVzI/HS6INdV1YjbObLi', 'Super Admin', 'admin', '2025-12-08 14:15:31', NULL, NULL, NULL, NULL, NULL),
(11, 'subjectteacher', '$2y$10$4FIGLJV50zj539e1G18aZ.mZm5r.L4eYVXCrSIbvZVrv3y3mAfKwS', 'Demo Subject Teacher', 'subject_teacher', '2025-12-08 14:15:31', NULL, '1234567', 'ST001', '01162166244', NULL),
(12, 'classteacher', '$2y$10$VMxpgbalE8n8LAKeuRLxteC/2A1VFQnlZ2/ZkIYbKaGLh1FTlPn.e', 'Demo Class Teacher', 'class_teacher', '2025-12-08 14:15:31', NULL, NULL, NULL, NULL, NULL),
(13, 'afif', '$2y$10$LhoiOdxWWjNGiR28PmmUXuONPsZDLfTNaStOC9L9L1mGqkoZaH.4W', 'Afif', 'subject_teacher', '2025-12-09 01:54:29', NULL, '34526354', 'STD002', '0264762', NULL),
(14, 'admin1', '$2y$10$yuOwRlHjGlPF0nfjOr0p6.PNE383LNyFODTUfedsM8.2.G6gYEWry', 'Admin One', 'admin', '2025-12-14 20:24:49', NULL, '900101015555', 'A-001', '0123456789', NULL),
(15, 'ali.hassan', '$2y$10$CFjNvBEqYMEV8hQQ5T9SdecHm3k.j0DQcrMPO1tQt81m2XPz4YZRq', 'Ali Hassan', 'subject_teacher', '2025-12-14 20:24:49', NULL, '880202026666', 'T-001', '0134567890', NULL),
(16, 'siti.aminah', '$2y$10$rWCBIz9XgsewDQA8KWEUsungFMhtOnxeFd.jPgr4OuD1hoarCMwSS', 'Siti Aminah', 'class_teacher', '2025-12-14 20:24:49', NULL, '870303037777', 'T-002', '0145678901', NULL),
(17, 'john.doe', '$2y$10$LrZduQ9h/OILkiWofLr/iOhdWs8jWdIHaxv3KhSYY2qdUPW236r3K', 'John Doe', 'subject_teacher', '2025-12-14 20:24:49', NULL, '890404048888', 'T-003', '0156789012', NULL),
(18, 'nur.aisyah', '$2y$10$HWEkx/GJm7M.RBgEeaqRYOxR0AhyEqKrvpLw7xqelkpoXBMjQg.vy', 'Nur Aisyah', 'subject_teacher', '2025-12-14 20:24:49', NULL, '910505059999', 'T-004', '0167890123', NULL),
(19, 'rahman.karim', '$2y$10$E59j2fj526FTFj2yp6JG.OqM.w3uVTP1yWAqa5kXywckGlzYX4mgW', 'Rahman Karim', 'class_teacher', '2025-12-14 20:24:49', NULL, '860606061111', 'T-005', '0178901234', NULL),
(20, 'fatimah.zahra', '$2y$10$0ZbzxCw5TPcn1tCgHaP72Ou58szxS3IaCDsI684ndM4J6S/X49jPS', 'Fatimah Zahra', 'subject_teacher', '2025-12-14 20:24:49', NULL, '920707072222', 'T-006', '0189012345', NULL),
(21, 'jubayer', '$2y$10$SFVoD.Cp98lqWw1qUuxm5OoiLcWTZaUxm4WS7ADApuKgEnBZsODTO', 'Md Jubayer', 'class_teacher', '2025-12-15 04:55:00', NULL, 'A00957369', 'T-2025-001', '01162166248', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `class_teacher_id` (`class_teacher_id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`notice_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD PRIMARY KEY (`mark_id`),
  ADD KEY `enrollment_id` (`enrollment_id`);

--
-- Indexes for table `student_subject_enrollment`
--
ALTER TABLE `student_subject_enrollment`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `student_transfers`
--
ALTER TABLE `student_transfers`
  ADD PRIMARY KEY (`transfer_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `from_class_id` (`from_class_id`),
  ADD KEY `to_class_id` (`to_class_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `notice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `student_marks`
--
ALTER TABLE `student_marks`
  MODIFY `mark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_subject_enrollment`
--
ALTER TABLE `student_subject_enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `student_transfers`
--
ALTER TABLE `student_transfers`
  MODIFY `transfer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`class_teacher_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `student_marks`
--
ALTER TABLE `student_marks`
  ADD CONSTRAINT `student_marks_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `student_subject_enrollment` (`enrollment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_subject_enrollment`
--
ALTER TABLE `student_subject_enrollment`
  ADD CONSTRAINT `student_subject_enrollment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_subject_enrollment_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_subject_enrollment_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `student_transfers`
--
ALTER TABLE `student_transfers`
  ADD CONSTRAINT `student_transfers_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_transfers_ibfk_2` FOREIGN KEY (`from_class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `student_transfers_ibfk_3` FOREIGN KEY (`to_class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `subjects_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
