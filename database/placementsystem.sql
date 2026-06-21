-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2026 at 07:22 PM
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
-- Database: `placementsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `alumni_mentorship`
--

CREATE TABLE `alumni_mentorship` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','active','closed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alumni_profiles`
--

CREATE TABLE `alumni_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company` varchar(150) DEFAULT NULL,
  `designation` varchar(150) DEFAULT NULL,
  `batch_year` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `linkedin` varchar(300) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `is_mentor` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alumni_referrals`
--

CREATE TABLE `alumni_referrals` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `job_title` varchar(200) DEFAULT NULL,
  `company` varchar(150) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('applied','shortlisted','rejected','selected') DEFAULT 'applied',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `feedback` text DEFAULT NULL,
  `feedback_at` timestamp NULL DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') DEFAULT NULL,
  `approval_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `job_id`, `student_id`, `status`, `applied_at`, `feedback`, `feedback_at`, `approval_status`, `approval_note`) VALUES
(6, 1, 8, 'applied', '2026-06-14 17:20:43', NULL, NULL, NULL, NULL),
(7, 2, 10, 'shortlisted', '2026-06-14 17:55:55', NULL, NULL, 'approved', ''),
(8, 1, 10, 'shortlisted', '2026-06-14 18:09:47', NULL, NULL, 'approved', ''),
(9, 1, 412, 'applied', '2026-06-14 19:06:18', NULL, NULL, NULL, NULL),
(10, 1, 20, 'applied', '2026-06-15 18:33:21', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(10) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#3f51b5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`id`, `name`, `icon`, `description`, `color`) VALUES
(1, 'First Application', '🚀', 'Applied for your first job', '#1565c0'),
(2, 'Active Applicant', '📋', 'Applied for 5+ jobs', '#1976d2'),
(3, 'Job Hunter', '🎯', 'Applied for 10+ jobs', '#0288d1'),
(4, 'Test Taker', '📝', 'Completed your first test', '#7b1fa2'),
(5, 'Test Pro', '🏆', 'Scored 80%+ in a test', '#6a1b9a'),
(6, 'Coder', '💻', 'Solved your first coding problem', '#2e7d32'),
(7, 'Code Master', '⚡', 'Solved 10+ coding problems', '#1b5e20'),
(8, 'Forum Contributor', '💬', 'Posted in the discussion forum', '#e65100'),
(9, 'Shortlisted', '⭐', 'Got shortlisted by a company', '#f57f17'),
(10, 'Placed', '🎉', 'Got selected by a company', '#2e7d32'),
(11, 'Profile Complete', '✅', 'Completed your profile', '#00695c'),
(12, 'Resume Uploaded', '📄', 'Uploaded your resume', '#4a148c');

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('interview','test','placement_drive','deadline','other') DEFAULT 'other',
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `meeting_link` varchar(500) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `target_role` enum('all','student','recruiter') DEFAULT 'all',
  `color` varchar(20) DEFAULT '#3f51b5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_logs`
--

CREATE TABLE `chatbot_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coding_problems`
--

CREATE TABLE `coding_problems` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT 'easy',
  `category` varchar(100) DEFAULT NULL,
  `sample_input` text DEFAULT NULL,
  `sample_output` text DEFAULT NULL,
  `points` int(11) DEFAULT 10,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `company_tag` varchar(100) DEFAULT NULL,
  `year_asked` year(4) DEFAULT NULL,
  `hints` text DEFAULT NULL,
  `tags` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coding_problems`
--

INSERT INTO `coding_problems` (`id`, `title`, `description`, `difficulty`, `category`, `sample_input`, `sample_output`, `points`, `status`, `created_at`, `company_tag`, `year_asked`, `hints`, `tags`) VALUES
(1, 'TCS NQT - Coding Section 2023', 'Given an array of n integers, find the count of pairs (i,j) where i<j and arr[i]+arr[j] is even.\\n\\nInput: n, then n integers\\nOutput: Count of such pairs.', 'easy', 'Arrays', '5\n1 2 3 4 5', '4', 10, 'active', '2026-06-14 19:54:12', 'TCS', '2023', 'Two numbers sum to even if both are odd or both are even.', 'arrays,pairs,math'),
(2, 'TCS - Reverse Words in Sentence', 'Reverse the order of words in a sentence.\\n\\nInput: A sentence\\nOutput: Words in reverse order.', 'easy', 'Strings', 'Hello World from TCS', 'TCS from World Hello', 10, 'active', '2026-06-14 19:54:12', 'TCS', '2023', 'Split by space, reverse list, join.', 'strings,reverse'),
(3, 'TCS - Pattern Printing', 'Print a right-angled triangle of * with n rows.\\n\\nInput: n\\nOutput: Triangle pattern.', 'easy', 'Patterns', '4', '*\n**\n***\n****', 10, 'active', '2026-06-14 19:54:12', 'TCS', '2022', 'Use nested loops.', 'patterns,loops'),
(4, 'Infosys - Second Largest', 'Find the second largest element in an array.\\n\\nInput: n, then n integers\\nOutput: Second largest value.', 'easy', 'Arrays', '5\n12 35 1 10 34', '34', 10, 'active', '2026-06-14 19:54:12', 'Infosys', '2023', 'Sort and pick second last, or track two maximums.', 'arrays,sorting'),
(5, 'Infosys - String Compression', 'Compress a string by counting consecutive characters. e.g. aaabbc -> a3b2c1\\n\\nInput: A string\\nOutput: Compressed string.', 'medium', 'Strings', 'aaabbc', 'a3b2c1', 20, 'active', '2026-06-14 19:54:12', 'Infosys', '2023', 'Use a pointer and counter.', 'strings,compression'),
(6, 'Infosys - Missing Number', 'Find the missing number in an array containing 1 to n with one missing.\\n\\nInput: n, then n-1 integers\\nOutput: Missing number.', 'easy', 'Math', '5\n1 2 4 5', '3', 10, 'active', '2026-06-14 19:54:12', 'Infosys', '2022', 'Sum formula: n*(n+1)/2 minus array sum.', 'math,arrays'),
(7, 'Wipro - Rotate Array', 'Rotate an array to the right by k positions.\\n\\nInput: n, k, then n integers\\nOutput: Rotated array.', 'medium', 'Arrays', '5 2\n1 2 3 4 5', '4 5 1 2 3', 20, 'active', '2026-06-14 19:54:12', 'Wipro', '2023', 'Use slicing: arr[-k:] + arr[:-k].', 'arrays,rotation'),
(8, 'Wipro - Leap Year Check', 'Check if a given year is a leap year.\\n\\nInput: Year\\nOutput: \"Leap Year\" or \"Not a Leap Year\".', 'easy', 'Logic', '2024', 'Leap Year', 10, 'active', '2026-06-14 19:54:12', 'Wipro', '2022', 'Divisible by 4, but not 100, unless also 400.', 'logic,math'),
(9, 'Wipro - Count Duplicates', 'Count the number of duplicate elements in an array.\\n\\nInput: n, then n integers\\nOutput: Count of duplicates.', 'easy', 'Arrays', '6\n1 2 3 2 4 1', '2', 10, 'active', '2026-06-14 19:54:12', 'Wipro', '2023', 'Use a frequency map.', 'arrays,hashing'),
(10, 'Accenture - Largest Palindrome Substring', 'Find the length of the largest palindrome substring.\\n\\nInput: A string\\nOutput: Length of largest palindrome substring.', 'hard', 'Strings', 'babad', '3', 30, 'active', '2026-06-14 19:54:12', 'Accenture', '2023', 'Expand around center technique.', 'strings,dp,palindrome'),
(11, 'Accenture - Number to Words', 'Convert a number (0-999) to words.\\n\\nInput: Integer n\\nOutput: Number in words.', 'medium', 'Strings', '256', 'two hundred fifty six', 20, 'active', '2026-06-14 19:54:12', 'Accenture', '2022', 'Handle hundreds, tens, ones separately.', 'strings,math'),
(12, 'Cognizant - Frequency Sort', 'Sort array elements by frequency (descending). Same frequency: ascending order.\\n\\nInput: n, then n integers\\nOutput: Sorted array.', 'medium', 'Sorting', '6\n4 5 6 5 4 3', '4 4 5 5 3 6', 20, 'active', '2026-06-14 19:54:12', 'Cognizant', '2023', 'Count frequencies then sort by (-freq, val).', 'sorting,hashing'),
(13, 'Cognizant - Balanced Brackets', 'Check if brackets in a string are balanced.\\n\\nInput: String with ()[]{}\\nOutput: \"Balanced\" or \"Not Balanced\".', 'medium', 'Stacks', '{[()]}', 'Balanced', 20, 'active', '2026-06-14 19:54:12', 'Cognizant', '2022', 'Use a stack for opening brackets.', 'stack,strings'),
(14, 'Amazon - Subarray with Given Sum', 'Find if there exists a subarray with sum equal to target.\\n\\nInput: n, target, then n integers\\nOutput: \"Yes\" or \"No\".', 'medium', 'Arrays', '5 12\n1 4 20 3 10', 'Yes', 20, 'active', '2026-06-14 19:54:12', 'Amazon', '2023', 'Use sliding window or prefix sum.', 'arrays,sliding-window'),
(15, 'Amazon - LRU Cache', 'Implement an LRU cache with get and put operations.\\n\\nCapacity c, then q queries: GET key or PUT key value.\\nOutput result of each GET (-1 if not found).', 'hard', 'Data Structures', '2\n4\nPUT 1 1\nPUT 2 2\nGET 1\nPUT 3 3\nGET 2', '1\n-1', 30, 'active', '2026-06-14 19:54:12', 'Amazon', '2023', 'Use OrderedDict or a doubly linked list + hashmap.', 'data-structures,cache'),
(16, 'Flipkart - Minimum Jumps', 'Given array where each element is max jump length, find minimum jumps to reach end.\\n\\nInput: n, then n integers\\nOutput: Minimum jumps or -1.', 'hard', 'Dynamic Programming', '6\n2 3 1 1 2 4', '3', 30, 'active', '2026-06-14 19:54:12', 'Flipkart', '2023', 'Greedy approach tracking current and next reach.', 'dp,greedy,arrays'),
(17, 'Flipkart - Group Anagrams', 'Group anagrams together from a list of words.\\n\\nInput: n, then n words\\nOutput: Groups separated by | sorted alphabetically within group.', 'medium', 'Strings', '6\neat tea tan ate nat bat', 'ate eat tea | ant nat tan | bat', 20, 'active', '2026-06-14 19:54:12', 'Flipkart', '2022', 'Sort each word as key in a hashmap.', 'strings,hashing'),
(18, 'Microsoft - Maximum Subarray', 'Find the contiguous subarray with the largest sum (Kadane\'s Algorithm).\\n\\nInput: n, then n integers\\nOutput: Maximum subarray sum.', 'medium', 'Arrays', '8\n-2 1 -3 4 -1 2 1 -5 4', '6', 20, 'active', '2026-06-14 19:54:12', 'Microsoft', '2023', 'Track current sum and max sum.', 'arrays,dp,kadane'),
(19, 'Microsoft - Number of Islands', 'Given a 2D grid of 1s (land) and 0s (water), count the number of islands.\\n\\nInput: rows cols, then grid\\nOutput: Number of islands.', 'hard', 'Graphs', '4 5\n1 1 0 0 0\n1 1 0 0 0\n0 0 1 0 0\n0 0 0 1 1', '3', 30, 'active', '2026-06-14 19:54:12', 'Microsoft', '2023', 'DFS/BFS to mark visited land cells.', 'graphs,dfs,matrix'),
(20, 'Hello World', 'Write a program that prints \"Hello, World!\" to the console.', 'easy', 'Basics', '', 'Hello, World!', 5, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Just use a print statement.', 'basics,output'),
(21, 'Sum of Two Numbers', 'Given two integers a and b, print their sum.\\n\\nInput: Two integers on separate lines.\\nOutput: Their sum.', 'easy', 'Math', '3\n5', '8', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Read two inputs and add them.', 'math,input'),
(22, 'Factorial', 'Calculate the factorial of a given non-negative integer n.\\n\\nInput: A single integer n (0 ≤ n ≤ 12)\\nOutput: n!', 'easy', 'Math', '5', '120', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Use a loop or recursion.', 'math,recursion'),
(23, 'Fibonacci Series', 'Print the first n numbers of the Fibonacci series.\\n\\nInput: Integer n\\nOutput: Space-separated Fibonacci numbers.', 'easy', 'Math', '7', '0 1 1 2 3 5 8', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Start with 0 and 1, each next = sum of previous two.', 'math,series'),
(24, 'Palindrome Check', 'Check if a given string is a palindrome.\\n\\nInput: A string\\nOutput: \"Yes\" if palindrome, \"No\" otherwise.', 'easy', 'Strings', 'racecar', 'Yes', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Compare string with its reverse.', 'strings,palindrome'),
(25, 'Reverse a String', 'Reverse the given string.\\n\\nInput: A string\\nOutput: Reversed string.', 'easy', 'Strings', 'hello', 'olleh', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Use slicing or a loop.', 'strings'),
(26, 'Count Vowels', 'Count the number of vowels (a,e,i,o,u) in a string.\\n\\nInput: A string\\nOutput: Count of vowels.', 'easy', 'Strings', 'Hello World', '3', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Iterate and check each character.', 'strings,counting'),
(27, 'Find Maximum', 'Find the maximum element in a list of n integers.\\n\\nInput: First line is n, second line has n space-separated integers.\\nOutput: Maximum value.', 'easy', 'Arrays', '5\n3 1 4 1 5', '5', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Use max() or iterate.', 'arrays,searching'),
(28, 'Linear Search', 'Search for a target value in an array and print its index (0-based). Print -1 if not found.\\n\\nInput: n, array elements, target\\nOutput: Index or -1.', 'easy', 'Searching', '5\n2 4 6 8 10\n6', '2', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Iterate through array.', 'arrays,searching'),
(29, 'Bubble Sort', 'Sort an array of n integers in ascending order using bubble sort.\\n\\nInput: n, then n integers\\nOutput: Sorted array space-separated.', 'medium', 'Sorting', '5\n64 34 25 12 22', '12 22 25 34 64', 20, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Compare adjacent elements and swap.', 'sorting,arrays'),
(30, 'Binary Search', 'Given a sorted array and a target, find the target using binary search. Print index or -1.\\n\\nInput: n, sorted array, target.', 'medium', 'Searching', '6\n1 3 5 7 9 11\n7', '3', 20, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Use low, mid, high pointers.', 'searching,binary'),
(31, 'FizzBuzz', 'Print numbers 1 to n. For multiples of 3 print \"Fizz\", multiples of 5 print \"Buzz\", both print \"FizzBuzz\".\\n\\nInput: n\\nOutput: One per line.', 'easy', 'Logic', '15', 'FizzBuzz output', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Use modulo operator.', 'logic,loops'),
(32, 'Prime Check', 'Check if a number is prime.\\n\\nInput: Integer n\\nOutput: \"Prime\" or \"Not Prime\".', 'easy', 'Math', '17', 'Prime', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Check divisibility up to sqrt(n).', 'math,prime'),
(33, 'Count Words', 'Count the number of words in a sentence.\\n\\nInput: A sentence\\nOutput: Word count.', 'easy', 'Strings', 'Hello World from Python', '4', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Split by spaces.', 'strings'),
(34, 'Sum of Digits', 'Find the sum of digits of a given number.\\n\\nInput: Integer n\\nOutput: Sum of its digits.', 'easy', 'Math', '1234', '10', 10, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Extract each digit using modulo.', 'math'),
(35, 'Matrix Addition', 'Add two 2x2 matrices.\\n\\nInput: 4 numbers for matrix A, then 4 for matrix B (row by row)\\nOutput: Result matrix row by row.', 'medium', 'Arrays', '1 2\n3 4\n5 6\n7 8', '6 8\n10 12', 20, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Add corresponding elements.', 'arrays,matrix'),
(36, 'Anagram Check', 'Check if two strings are anagrams.\\n\\nInput: Two strings on separate lines\\nOutput: \"Yes\" or \"No\".', 'medium', 'Strings', 'listen\nsilent', 'Yes', 20, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Sort both strings and compare.', 'strings,sorting'),
(37, 'Stack Implementation', 'Implement a stack with push, pop, and peek operations.\\n\\nProcess q queries: PUSH x, POP, PEEK\\nOutput result of POP and PEEK operations.', 'medium', 'Data Structures', '5\nPUSH 1\nPUSH 2\nPEEK\nPOP\nPEEK', '2\n2\n1', 20, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Use a list as stack.', 'data-structures,stack'),
(38, 'Linked List Length', 'Given n elements, create a linked list and find its length.\\n\\nInput: n, then n elements\\nOutput: Length of linked list.', 'medium', 'Data Structures', '5\n1 2 3 4 5', '5', 20, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Create nodes and traverse.', 'data-structures,linked-list'),
(39, 'Two Sum', 'Given an array and a target, find two indices such that they add up to target.\\n\\nInput: n, array, target\\nOutput: Two indices (0-based) space-separated.', 'medium', 'Arrays', '4\n2 7 11 15\n9', '0 1', 20, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Use a hash map for O(n) solution.', 'arrays,hashing'),
(40, 'Longest Common Subsequence', 'Find the length of the longest common subsequence of two strings.\\n\\nInput: Two strings\\nOutput: LCS length.', 'hard', 'Dynamic Programming', 'ABCBDAB\nBDCAB', '4', 30, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Use DP table.', 'dp,strings'),
(41, 'Knapsack Problem', 'Given weights and values of n items and capacity W, find max value.\\n\\nInput: n, W, then n pairs of weight value\\nOutput: Maximum value.', 'hard', 'Dynamic Programming', '4 8\n2 3\n3 4\n4 5\n5 6', '10', 30, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Use 2D DP table.', 'dp,optimization'),
(42, 'Graph BFS', 'Given an undirected graph, perform BFS from node 0.\\n\\nInput: n nodes, m edges, then m edges\\nOutput: BFS traversal order.', 'hard', 'Graphs', '5 4\n0 1\n0 2\n1 3\n2 4', '0 1 2 3 4', 30, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Use a queue.', 'graphs,bfs'),
(43, 'Merge Sort', 'Sort an array using merge sort.\\n\\nInput: n, then n integers\\nOutput: Sorted array.', 'hard', 'Sorting', '6\n38 27 43 3 9 82', '3 9 27 38 43 82', 30, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Divide and conquer.', 'sorting,recursion'),
(44, 'Valid Parentheses', 'Check if a string of brackets is valid.\\n\\nInput: String of (){}[]\\nOutput: \"Valid\" or \"Invalid\".', 'medium', 'Stacks', '({[]})', 'Valid', 20, 'active', '2026-06-14 19:54:12', NULL, NULL, 'Use a stack.', 'stack,strings');

-- --------------------------------------------------------

--
-- Table structure for table `coding_submissions`
--

CREATE TABLE `coding_submissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `problem_id` int(11) NOT NULL,
  `language` varchar(20) DEFAULT NULL,
  `code` text DEFAULT NULL,
  `status` enum('accepted','wrong','error','timeout') DEFAULT 'error',
  `points_earned` int(11) DEFAULT 0,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coding_test_cases`
--

CREATE TABLE `coding_test_cases` (
  `id` int(11) NOT NULL,
  `problem_id` int(11) NOT NULL,
  `input` text NOT NULL,
  `expected_output` text NOT NULL,
  `is_sample` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coding_test_cases`
--

INSERT INTO `coding_test_cases` (`id`, `problem_id`, `input`, `expected_output`, `is_sample`) VALUES
(1, 1, '5\r\n1 2 3 4 5', '4', 1),
(2, 1, '4\r\n2 4 6 8', '6', 1),
(3, 1, '1\r\n10', '0', 0),
(4, 1, '6\r\n1 2 3 4 5 6', '6', 0);

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `user_id`, `company_name`, `industry`, `website`, `description`, `contact_person`, `phone`) VALUES
(1, 2, 'TCS', 'Information Technology', 'tcs.com', 'Tata Consultancy Services', NULL, NULL),
(2, 3, 'Infosys', 'Information Technology', 'infosys.com', 'Infosys Limited', NULL, NULL),
(3, 4, 'Wipro', 'Information Technology', 'wipro.com', 'Wipro Technologies', NULL, NULL),
(4, 5, 'Amazon', 'E-commerce & Cloud', 'amazon.jobs', 'Amazon India', NULL, NULL),
(5, 6, 'Google', 'Technology', 'careers.google.com', 'Google India', NULL, NULL),
(6, 7, 'Accenture', 'Consulting', 'accenture.com', 'Accenture India', NULL, NULL),
(7, 410, 'harika', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `doc_type` enum('certificate','marksheet','id_proof','offer_letter','other') NOT NULL,
  `doc_name` varchar(200) NOT NULL,
  `file_path` varchar(300) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `user_id`, `doc_type`, `doc_name`, `file_path`, `file_size`, `status`, `admin_remarks`, `uploaded_at`, `reviewed_at`) VALUES
(5, 412, 'id_proof', 'Aadhar card', 'doc_412_1781465862_6a2f03065d4ca.pdf', 1051432, 'approved', '', '2026-06-14 19:37:42', '2026-06-15 16:07:35');

-- --------------------------------------------------------

--
-- Table structure for table `eligibility_criteria`
--

CREATE TABLE `eligibility_criteria` (
  `id` int(11) NOT NULL,
  `min_cgpa` decimal(4,2) DEFAULT 6.00,
  `min_attendance` decimal(5,2) DEFAULT 75.00,
  `max_backlogs` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eligibility_criteria`
--

INSERT INTO `eligibility_criteria` (`id`, `min_cgpa`, `min_attendance`, `max_backlogs`, `updated_at`) VALUES
(1, 6.00, 75.00, 0, '2026-06-13 06:05:01');

-- --------------------------------------------------------

--
-- Table structure for table `forum_categories`
--

CREATE TABLE `forum_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(10) DEFAULT '?',
  `description` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_categories`
--

INSERT INTO `forum_categories` (`id`, `name`, `icon`, `description`, `sort_order`) VALUES
(1, 'Interview Experiences', '🎤', 'Share your interview experiences and tips', 1),
(4, 'Technical Help', '💻', 'Get help with coding and technical questions', 4),
(5, 'General Discussion', '💬', 'General placement-related discussions', 5);

-- --------------------------------------------------------

--
-- Table structure for table `forum_likes`
--

CREATE TABLE `forum_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `reply_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

CREATE TABLE `forum_posts` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `views` int(11) DEFAULT 0,
  `is_pinned` tinyint(4) DEFAULT 0,
  `is_locked` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_replies`
--

CREATE TABLE `forum_replies` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `is_solution` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `internships`
--

CREATE TABLE `internships` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `stipend` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `min_cgpa` decimal(4,2) DEFAULT 0.00,
  `deadline` date DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `allowed_streams` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `internships`
--

INSERT INTO `internships` (`id`, `company_id`, `title`, `description`, `requirements`, `stipend`, `location`, `duration`, `min_cgpa`, `deadline`, `status`, `created_at`, `allowed_streams`) VALUES
(1, 3, 'Software Intern', 'Work on live projects and gain hands-on experience in software development.', 'B.E/B.Tech 3rd or 4th year, Basic coding skills, Eagerness to learn', '15,000-20,000/month', 'Bangalore', '', 6.50, '2026-06-28', 'open', '2026-06-18 10:16:48', NULL),
(2, 4, 'Software Development Engineer Intern', 'Work on real Amazon projects, contribute to production code, and collaborate with senior engineers.', 'Pre-final year B.E/B.Tech CS/IT, Strong DSA, Competitive programming experience preferred, CGPA 7.0+', '80,000-1,00,000/month', 'Bangalore, Hyderabad', '', 7.00, '2026-07-03', 'open', '2026-06-18 10:16:48', NULL),
(3, 6, 'Tech Intern', 'Support project teams and gain experience in consulting and technology delivery.', 'B.E/B.Tech 3rd or final year, Good communication, Basic programming', '20,000-25,000/month', 'Bangalore, Mumbai', '', 6.00, '2026-07-01', 'open', '2026-06-18 10:16:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `internship_applications`
--

CREATE TABLE `internship_applications` (
  `id` int(11) NOT NULL,
  `internship_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('applied','shortlisted','rejected','selected','completed') DEFAULT 'applied',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completion_date` date DEFAULT NULL,
  `certificate_issued` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `duration` int(11) DEFAULT 60,
  `meeting_link` varchar(500) DEFAULT NULL,
  `platform` enum('google_meet','zoom','teams','jitsi','other') DEFAULT 'google_meet',
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','rescheduled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `minutes` text DEFAULT NULL,
  `recording_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interview_experiences`
--

CREATE TABLE `interview_experiences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `job_role` varchar(150) DEFAULT NULL,
  `interview_date` date DEFAULT NULL,
  `difficulty` enum('Easy','Medium','Hard') DEFAULT 'Medium',
  `outcome` enum('Selected','Rejected','Pending') DEFAULT 'Pending',
  `rounds` text DEFAULT NULL,
  `experience` text NOT NULL,
  `tips` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `job_type` enum('Full-time','Part-time','Internship') DEFAULT 'Full-time',
  `min_cgpa` decimal(4,2) DEFAULT 0.00,
  `deadline` date DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `allowed_streams` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `company_id`, `title`, `description`, `requirements`, `salary_range`, `location`, `job_type`, `min_cgpa`, `deadline`, `status`, `created_at`, `allowed_streams`) VALUES
(1, 1, 'Software Engineer', 'Design and develop scalable software applications. Work in agile teams to deliver high-quality solutions.', 'B.E/B.Tech in CS/IT, Strong in Java or Python, Good DSA skills, CGPA 6.5+', '3.5-7 LPA', 'Chennai, Hyderabad, Pune', 'Full-time', 6.50, '2026-07-13', 'open', '2026-06-13 06:27:38', NULL),
(2, 1, 'System Engineer', 'Maintain and support enterprise IT systems. Troubleshoot and resolve technical issues.', 'B.E/B.Tech any branch, Basic programming knowledge, CGPA 6.0+', '3-5 LPA', 'Pan India', 'Full-time', 6.00, '2026-07-08', 'open', '2026-06-13 06:27:38', NULL),
(3, 2, 'Associate Developer', 'Build and maintain web applications using Java and related frameworks. Collaborate with senior developers.', 'B.E/B.Tech CS/IT/ECE, Java knowledge, SQL basics, CGPA 6.5+', '3.6-6.5 LPA', 'Bangalore, Pune, Hyderabad', 'Full-time', 6.50, '2026-07-11', 'open', '2026-06-13 06:27:38', NULL),
(4, 2, 'Operations Analyst', 'Analyze business processes and provide data-driven insights. Work with cross-functional teams.', 'B.E/B.Tech or MBA, Analytical skills, Excel/SQL knowledge, CGPA 6.0+', '4-7 LPA', 'Bangalore, Mysore', 'Full-time', 6.00, '2026-07-03', 'open', '2026-06-13 06:27:38', NULL),
(5, 3, 'Project Engineer', 'Contribute to software development projects across various technology stacks. Learn and grow in a dynamic environment.', 'B.E/B.Tech any branch, Programming skills in any language, CGPA 6.0+', '3.5-5.5 LPA', 'Bangalore, Hyderabad, Chennai', 'Full-time', 6.00, '2026-07-18', 'open', '2026-06-13 06:27:38', NULL),
(7, 4, 'SDE-1 (Software Development Engineer)', 'Write high-quality code, participate in design reviews and contribute to Amazon\'s products used by millions worldwide.', 'B.E/B.Tech CS/IT, Excellent DSA, Proficiency in C++/Java/Python, System design basics, CGPA 7.5+', '18-32 LPA', 'Bangalore, Hyderabad', 'Full-time', 7.50, '2026-07-28', 'open', '2026-06-13 06:27:38', NULL),
(9, 5, 'Software Engineer (STEP)', 'Build Google products from the ground up. Solve challenging engineering problems at massive scale.', 'B.E/B.Tech CS/IT, Exceptional problem-solving, Proficiency in C++/Java/Python/Go, CGPA 8.0+', '25-45 LPA', 'Bangalore', 'Full-time', 8.00, '2026-08-12', 'open', '2026-06-13 06:27:38', NULL),
(10, 6, 'Application Developer Associate', 'Develop and maintain client applications. Work on diverse projects across industries.', 'B.E/B.Tech CS/IT/ECE, Knowledge of web technologies, Good communication skills, CGPA 6.5+', '4.5-8 LPA', 'Bangalore, Mumbai, Hyderabad, Chennai', 'Full-time', 6.50, '2026-07-13', 'open', '2026-06-13 06:27:38', NULL),
(11, 6, 'Technology Analyst', 'Analyze and implement technology solutions for clients. Bridge business requirements with technical implementation.', 'B.E/B.Tech any branch, Analytical thinking, Basic SQL/Excel, CGPA 6.0+', '4-7.5 LPA', 'Pan India', 'Full-time', 6.00, '2026-07-05', 'open', '2026-06-13 06:27:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_activity`
--

CREATE TABLE `login_activity` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(300) DEFAULT NULL,
  `status` enum('success','failed') DEFAULT 'success',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_activity`
--

INSERT INTO `login_activity` (`id`, `user_id`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES
(1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-13 07:41:49'),
(2, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-13 08:45:55'),
(3, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-13 17:06:54'),
(5, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-13 19:03:46'),
(7, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-13 19:11:18'),
(9, 410, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 06:39:52'),
(11, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 07:30:24'),
(12, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 09:38:47'),
(13, 410, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 10:15:05'),
(16, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 12:22:15'),
(17, 410, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 12:24:16'),
(18, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 12:24:36'),
(19, 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'failed', '2026-06-14 12:38:02'),
(20, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 12:38:38'),
(21, 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 12:40:23'),
(22, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'failed', '2026-06-14 12:51:58'),
(23, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 12:52:24'),
(24, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 12:57:23'),
(25, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 14:28:41'),
(28, 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'failed', '2026-06-14 16:32:30'),
(29, 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 16:32:54'),
(30, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 16:41:45'),
(31, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 17:20:21'),
(32, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 17:22:43'),
(33, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 17:30:42'),
(34, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 17:31:47'),
(35, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 17:34:18'),
(36, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 17:35:56'),
(37, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 17:54:52'),
(38, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 17:56:19'),
(39, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 18:02:04'),
(40, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 18:09:39'),
(41, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 18:10:03'),
(42, 412, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 18:36:09'),
(43, 412, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'failed', '2026-06-14 19:35:25'),
(44, 410, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 19:35:45'),
(45, 412, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-14 19:36:08'),
(46, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 16:06:59'),
(47, 412, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 16:07:56'),
(48, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 16:12:43'),
(49, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 16:55:24'),
(50, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 17:33:58'),
(51, 412, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 17:47:05'),
(52, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 17:47:22'),
(53, 412, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 18:28:16'),
(54, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 18:29:49'),
(55, 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 18:30:14'),
(56, 20, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 18:31:44'),
(57, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-15 18:36:54'),
(58, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-17 16:16:19'),
(59, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-17 16:19:08'),
(60, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-17 17:10:56'),
(61, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-17 17:13:23'),
(62, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-17 17:18:22'),
(63, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-17 17:22:11'),
(64, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-17 18:42:21'),
(65, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-17 18:48:39'),
(66, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-17 18:49:13'),
(67, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-17 18:52:39'),
(68, 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-18 09:22:15'),
(69, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-18 09:23:10'),
(70, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'success', '2026-06-18 10:02:27');

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `posted_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `title`, `content`, `posted_by`, `created_at`) VALUES
(1, 'TCS NQT selection process', 'Prepare well for the placements high chances for getting students placed in TCS', 1, '2026-06-18 10:09:54');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('job','application','interview','test','notice','system') DEFAULT 'system',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(9, 10, 'application', '⭐ Application Shortlisted: System Engineer', 'Congratulations! You have been shortlisted for System Engineer at TCS.', '/placement system/student/applications.php', 0, '2026-06-14 17:59:10'),
(10, 10, 'application', 'Application Update', 'Your shortlisting for System Engineer at TCS has been approved by admin.', NULL, 0, '2026-06-14 17:59:31'),
(11, 10, 'application', '⭐ Application Shortlisted: Software Engineer', 'Congratulations! You have been shortlisted for Software Engineer at TCS.', '/placement system/student/applications.php', 0, '2026-06-14 18:10:32'),
(12, 10, 'application', 'Application Update', 'Your shortlisting for Software Engineer at TCS has been approved by admin.', NULL, 0, '2026-06-14 18:11:57'),
(13, 1, 'system', '📄 New Document Uploaded', 'harika uploaded a Id proof for verification.', '/placement%20system/admin/documents/index.php', 1, '2026-06-14 19:17:55'),
(14, 1, 'system', '📄 New Document Uploaded', ' uploaded a Id proof for verification.', '/placement%20system/admin/documents/index.php', 1, '2026-06-14 19:21:06'),
(15, 1, 'system', '📄 New Document Uploaded', ' uploaded a Id proof for verification.', '/placement%20system/admin/documents/index.php', 1, '2026-06-14 19:24:31'),
(16, 1, 'system', '📄 New Document Uploaded', ' uploaded a Id proof for verification.', '/placement%20system/admin/documents/index.php', 1, '2026-06-14 19:32:56'),
(17, 1, 'system', '📄 New Document Uploaded', 'harika uploaded a Id proof for verification.', '/placement%20system/admin/documents/index.php', 1, '2026-06-14 19:37:42'),
(18, 412, 'system', 'Document Review Update', 'Your document \'Aadhar card\' has been ✅ approved.', '/placement%20system/student/profile.php', 0, '2026-06-15 16:07:35'),
(19, 10, 'application', 'New Placement Round Added', 'Round 1 (Aptitude test) has been added for Software Engineer at TCS. Check your dashboard for details and links.', '/placement%20system/student/applications.php', 1, '2026-06-17 17:03:18'),
(20, 10, 'application', 'New Placement Round Added', 'Round 2 (Aptitude test) has been added for Software Engineer at TCS. Check your dashboard for details and links.', '/placement%20system/student/applications.php', 1, '2026-06-17 17:18:01'),
(21, 10, 'application', 'New Placement Round Added', 'Round 2 (coding) has been added for Software Engineer at TCS. Check your dashboard for details and links.', '/placement%20system/student/applications.php', 1, '2026-06-17 18:37:40'),
(22, 10, 'application', 'New Placement Round Added', 'Round 3 (coding) has been added for Software Engineer at TCS. Check your dashboard for details and links.', '/placement%20system/student/applications.php', 1, '2026-06-17 18:40:45'),
(23, 8, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(24, 9, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(25, 10, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(26, 11, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(27, 12, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(28, 13, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(29, 14, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(30, 15, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(31, 16, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(32, 17, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(33, 18, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(34, 19, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(35, 20, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(36, 21, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(37, 22, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(38, 23, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(39, 24, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(40, 25, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(41, 26, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(42, 27, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(43, 28, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(44, 29, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(45, 30, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(46, 31, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(47, 32, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(48, 33, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(49, 34, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(50, 35, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(51, 36, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(52, 37, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(53, 38, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(54, 39, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(55, 40, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(56, 41, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(57, 42, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(58, 43, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(59, 44, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(60, 45, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(61, 46, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(62, 47, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(63, 48, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(64, 49, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(65, 50, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(66, 51, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(67, 52, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(68, 53, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(69, 54, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(70, 55, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(71, 56, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(72, 57, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(73, 58, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(74, 59, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(75, 60, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(76, 61, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(77, 62, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(78, 63, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(79, 64, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(80, 65, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(81, 66, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(82, 67, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(83, 68, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(84, 69, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(85, 70, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(86, 71, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(87, 72, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(88, 73, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(89, 74, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(90, 75, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(91, 76, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(92, 77, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(93, 78, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(94, 79, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(95, 80, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(96, 81, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(97, 82, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(98, 83, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(99, 84, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(100, 85, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(101, 86, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(102, 87, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(103, 88, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(104, 89, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(105, 90, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(106, 91, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(107, 92, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(108, 93, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(109, 94, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(110, 95, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(111, 96, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(112, 97, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(113, 98, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(114, 99, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(115, 100, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(116, 101, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(117, 102, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(118, 103, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(119, 104, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(120, 105, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(121, 106, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(122, 107, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(123, 108, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(124, 109, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(125, 110, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(126, 111, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(127, 112, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(128, 113, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(129, 114, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(130, 115, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(131, 116, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(132, 117, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(133, 118, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(134, 119, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(135, 120, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(136, 121, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(137, 122, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(138, 123, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(139, 124, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(140, 125, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(141, 126, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(142, 127, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(143, 128, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(144, 129, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(145, 130, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(146, 131, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(147, 132, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(148, 133, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(149, 134, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(150, 135, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(151, 136, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(152, 137, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(153, 138, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(154, 139, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(155, 140, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(156, 141, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(157, 142, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(158, 143, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(159, 144, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(160, 145, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(161, 146, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(162, 147, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(163, 148, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(164, 149, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(165, 150, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(166, 151, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(167, 152, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(168, 153, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(169, 154, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(170, 155, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(171, 156, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(172, 157, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(173, 158, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(174, 159, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(175, 160, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(176, 161, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(177, 162, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(178, 163, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(179, 164, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(180, 165, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(181, 166, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(182, 167, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(183, 168, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(184, 169, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(185, 170, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(186, 171, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(187, 172, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(188, 173, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(189, 174, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(190, 175, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(191, 176, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(192, 177, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(193, 178, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(194, 179, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(195, 180, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(196, 181, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(197, 182, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(198, 183, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(199, 184, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(200, 185, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(201, 186, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(202, 187, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(203, 188, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(204, 189, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(205, 190, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(206, 191, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(207, 192, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(208, 193, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(209, 194, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(210, 195, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(211, 196, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(212, 197, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(213, 198, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(214, 199, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(215, 200, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(216, 201, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(217, 202, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(218, 203, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(219, 204, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(220, 205, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(221, 206, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(222, 207, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(223, 208, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(224, 209, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(225, 210, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(226, 211, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(227, 212, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(228, 213, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(229, 214, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(230, 215, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(231, 216, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(232, 217, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(233, 218, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(234, 219, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(235, 220, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(236, 221, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(237, 222, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(238, 223, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(239, 224, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(240, 225, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(241, 226, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(242, 227, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(243, 228, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(244, 229, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(245, 230, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(246, 231, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(247, 232, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(248, 233, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(249, 234, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(250, 235, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(251, 236, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(252, 237, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(253, 238, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(254, 239, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(255, 240, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(256, 241, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(257, 242, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(258, 243, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(259, 244, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(260, 245, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54');
INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(261, 246, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(262, 247, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(263, 248, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(264, 249, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(265, 250, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(266, 251, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(267, 252, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(268, 253, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(269, 254, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(270, 255, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(271, 256, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(272, 257, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(273, 258, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(274, 259, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(275, 260, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(276, 261, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(277, 262, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(278, 263, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(279, 264, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(280, 265, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(281, 266, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(282, 267, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(283, 268, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(284, 269, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(285, 270, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(286, 271, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(287, 272, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(288, 273, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(289, 274, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(290, 275, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(291, 276, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(292, 277, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(293, 278, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(294, 279, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(295, 280, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(296, 281, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(297, 282, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(298, 283, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(299, 284, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(300, 285, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(301, 286, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(302, 287, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(303, 288, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(304, 289, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(305, 290, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(306, 291, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(307, 292, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(308, 293, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(309, 294, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(310, 295, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(311, 296, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(312, 297, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(313, 298, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(314, 299, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(315, 300, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(316, 301, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(317, 302, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(318, 303, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(319, 304, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(320, 305, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(321, 306, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(322, 307, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(323, 308, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(324, 309, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(325, 310, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(326, 311, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(327, 312, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(328, 313, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(329, 314, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(330, 315, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(331, 316, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(332, 317, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(333, 318, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(334, 319, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(335, 320, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(336, 321, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(337, 322, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(338, 323, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(339, 324, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(340, 325, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(341, 326, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(342, 327, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(343, 328, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(344, 329, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(345, 330, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(346, 331, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(347, 332, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(348, 333, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(349, 334, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(350, 335, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(351, 336, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(352, 337, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(353, 338, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(354, 339, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(355, 340, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(356, 341, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(357, 342, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(358, 343, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(359, 344, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(360, 345, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(361, 346, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(362, 347, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(363, 348, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(364, 349, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(365, 350, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(366, 351, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(367, 352, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(368, 353, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(369, 354, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(370, 355, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(371, 356, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(372, 357, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(373, 358, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(374, 359, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(375, 360, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(376, 361, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(377, 362, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(378, 363, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(379, 364, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(380, 365, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(381, 366, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(382, 367, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(383, 368, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(384, 369, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(385, 370, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(386, 371, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(387, 372, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(388, 373, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(389, 374, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(390, 375, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(391, 376, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(392, 377, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(393, 378, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(394, 379, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(395, 380, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(396, 381, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(397, 382, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(398, 383, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(399, 384, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(400, 385, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(401, 386, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(402, 387, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(403, 388, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(404, 389, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(405, 390, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(406, 391, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(407, 392, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(408, 393, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(409, 394, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(410, 395, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(411, 396, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(412, 397, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(413, 398, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(414, 399, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(415, 400, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(416, 401, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(417, 402, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(418, 403, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(419, 404, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(420, 405, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(421, 406, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(422, 407, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(423, 408, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(424, 412, 'notice', '📢 New Notice: TCS NQT selection process', 'A new placement notice has been posted: TCS NQT selection process', '/placement system/student/notices.php', 0, '2026-06-18 10:09:54'),
(425, 412, 'system', 'Placement Eligibility Update', 'Your placement eligibility has been approved for placement by the admin.', NULL, 0, '2026-06-18 10:20:09'),
(426, 12, 'system', 'Placement Eligibility Approved', 'Congratulations! You have been approved for placement drives.', NULL, 0, '2026-06-18 10:20:23'),
(427, 10, 'system', 'Placement Eligibility Approved', 'Congratulations! You have been approved for placement drives.', NULL, 0, '2026-06-18 10:20:23'),
(428, 8, 'system', 'Placement Eligibility Approved', 'Congratulations! You have been approved for placement drives.', NULL, 0, '2026-06-18 10:20:23'),
(429, 12, 'system', 'Placement Eligibility Approved', 'Congratulations! You have been approved for placement drives.', NULL, 0, '2026-06-18 10:20:32'),
(430, 10, 'system', 'Placement Eligibility Approved', 'Congratulations! You have been approved for placement drives.', NULL, 0, '2026-06-18 10:20:32'),
(431, 8, 'system', 'Placement Eligibility Approved', 'Congratulations! You have been approved for placement drives.', NULL, 0, '2026-06-18 10:20:32'),
(432, 12, 'system', 'Placement Eligibility Approved', 'Congratulations! You have been approved for placement drives.', NULL, 0, '2026-06-18 10:25:45'),
(433, 10, 'system', 'Placement Eligibility Approved', 'Congratulations! You have been approved for placement drives.', NULL, 0, '2026-06-18 10:25:45'),
(434, 8, 'system', 'Placement Eligibility Approved', 'Congratulations! You have been approved for placement drives.', NULL, 0, '2026-06-18 10:25:45'),
(435, 412, 'system', 'Placement Eligibility Update', 'Your placement eligibility has been approved for placement by the admin.', NULL, 0, '2026-06-18 10:25:58');

-- --------------------------------------------------------

--
-- Table structure for table `placement_rounds`
--

CREATE TABLE `placement_rounds` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `round_number` int(11) NOT NULL,
  `round_name` varchar(100) NOT NULL,
  `round_type` enum('aptitude','technical','hr','coding','group_discussion','other') DEFAULT 'other',
  `description` text DEFAULT NULL,
  `test_link` varchar(500) DEFAULT NULL,
  `meeting_link` varchar(500) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT 60,
  `status` enum('upcoming','active','completed') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `test_id` int(11) DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `coding_problem_id` int(11) DEFAULT NULL,
  `min_pass_score` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `placement_rounds`
--

INSERT INTO `placement_rounds` (`id`, `job_id`, `round_number`, `round_name`, `round_type`, `description`, `test_link`, `meeting_link`, `scheduled_at`, `duration`, `status`, `created_at`, `test_id`, `end_time`, `coding_problem_id`, `min_pass_score`) VALUES
(1, 1, 1, 'Aptitude test', 'aptitude', '', 'http://localhost/placement%20system/student/aptitude_test/take_test.php?test_id=1', '', '2026-06-17 22:50:00', 20, 'completed', '2026-06-17 17:03:18', NULL, NULL, NULL, 0),
(3, 1, 2, 'coding', 'coding', '', '', '', '2026-06-18 00:15:00', 30, 'completed', '2026-06-17 18:37:40', NULL, '2026-06-18 00:45:00', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `resume_analysis`
--

CREATE TABLE `resume_analysis` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` int(11) DEFAULT 0,
  `found_skills` text DEFAULT NULL,
  `missing_skills` text DEFAULT NULL,
  `suggestions` text DEFAULT NULL,
  `matched_jobs` text DEFAULT NULL,
  `sections_found` text DEFAULT NULL,
  `analyzed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resume_analysis`
--

INSERT INTO `resume_analysis` (`id`, `user_id`, `score`, `found_skills`, `missing_skills`, `suggestions`, `matched_jobs`, `sections_found`, `analyzed_at`) VALUES
(3, 8, 90, '{\"programming\":[\"python\",\"java\",\"go\",\"r\",\"html\",\"css\",\"sql\"],\"frameworks\":[\"spring\"],\"databases\":[\"mysql\",\"oracle\"],\"tools\":[],\"soft_skills\":[\"analytical\"],\"concepts\":[\"machine learning\",\"deep learning\",\"data structures\",\"algorithms\",\"nlp\"]}', '[\"javascript\",\"php\",\"c++\",\"git\",\"github\",\"linux\",\"mongodb\",\"object oriented\"]', '[{\"type\":\"error\",\"msg\":\"Add your contact information (email, phone, LinkedIn, GitHub).\"},{\"type\":\"info\",\"msg\":\"Mention achievements, awards, or hackathon participations.\"},{\"type\":\"warning\",\"msg\":\"Add your GitHub profile link to showcase your projects.\"},{\"type\":\"warning\",\"msg\":\"Add your LinkedIn profile URL for professional networking.\"},{\"type\":\"warning\",\"msg\":\"Learn and add Git\\/GitHub \\u2014 it is essential for all tech roles.\"},{\"type\":\"success\",\"msg\":\"Excellent resume! Keep updating it with new projects and certifications.\"}]', '{\"Software Developer\":55,\"Data Scientist\":45,\"Machine Learning Engineer\":44,\"Database Administrator\":33,\"Full Stack Developer\":30}', '{\"education\":true,\"experience\":true,\"skills\":true,\"projects\":true,\"certifications\":true,\"achievements\":false,\"contact\":false,\"objective\":true}', '2026-06-14 17:34:52'),
(4, 8, 90, '{\"programming\":[\"python\",\"java\",\"go\",\"r\",\"html\",\"css\",\"sql\"],\"frameworks\":[\"spring\"],\"databases\":[\"mysql\",\"oracle\"],\"tools\":[],\"soft_skills\":[\"analytical\"],\"concepts\":[\"machine learning\",\"deep learning\",\"data structures\",\"algorithms\",\"nlp\"]}', '[\"javascript\",\"php\",\"c++\",\"git\",\"github\",\"linux\",\"mongodb\",\"object oriented\"]', '[{\"type\":\"error\",\"msg\":\"Add your contact information (email, phone, LinkedIn, GitHub).\"},{\"type\":\"info\",\"msg\":\"Mention achievements, awards, or hackathon participations.\"},{\"type\":\"warning\",\"msg\":\"Add your GitHub profile link to showcase your projects.\"},{\"type\":\"warning\",\"msg\":\"Add your LinkedIn profile URL for professional networking.\"},{\"type\":\"warning\",\"msg\":\"Learn and add Git\\/GitHub \\u2014 it is essential for all tech roles.\"},{\"type\":\"success\",\"msg\":\"Excellent resume! Keep updating it with new projects and certifications.\"}]', '{\"Software Developer\":55,\"Data Scientist\":45,\"Machine Learning Engineer\":44,\"Database Administrator\":33,\"Full Stack Developer\":30}', '{\"education\":true,\"experience\":true,\"skills\":true,\"projects\":true,\"certifications\":true,\"achievements\":false,\"contact\":false,\"objective\":true}', '2026-06-14 17:34:54'),
(5, 8, 90, '{\"programming\":[\"python\",\"java\",\"go\",\"r\",\"html\",\"css\",\"sql\"],\"frameworks\":[\"spring\"],\"databases\":[\"mysql\",\"oracle\"],\"tools\":[],\"soft_skills\":[\"analytical\"],\"concepts\":[\"machine learning\",\"deep learning\",\"data structures\",\"algorithms\",\"nlp\"]}', '[\"javascript\",\"php\",\"c++\",\"git\",\"github\",\"linux\",\"mongodb\",\"object oriented\"]', '[{\"type\":\"error\",\"msg\":\"Add your contact information (email, phone, LinkedIn, GitHub).\"},{\"type\":\"info\",\"msg\":\"Mention achievements, awards, or hackathon participations.\"},{\"type\":\"warning\",\"msg\":\"Add your GitHub profile link to showcase your projects.\"},{\"type\":\"warning\",\"msg\":\"Add your LinkedIn profile URL for professional networking.\"},{\"type\":\"warning\",\"msg\":\"Learn and add Git\\/GitHub \\u2014 it is essential for all tech roles.\"},{\"type\":\"success\",\"msg\":\"Excellent resume! Keep updating it with new projects and certifications.\"}]', '{\"Software Developer\":55,\"Data Scientist\":45,\"Machine Learning Engineer\":44,\"Database Administrator\":33,\"Full Stack Developer\":30}', '{\"education\":true,\"experience\":true,\"skills\":true,\"projects\":true,\"certifications\":true,\"achievements\":false,\"contact\":false,\"objective\":true}', '2026-06-14 17:34:55'),
(6, 8, 90, '{\"programming\":[\"python\",\"java\",\"go\",\"r\",\"html\",\"css\",\"sql\"],\"frameworks\":[\"spring\"],\"databases\":[\"mysql\",\"oracle\"],\"tools\":[],\"soft_skills\":[\"analytical\"],\"concepts\":[\"machine learning\",\"deep learning\",\"data structures\",\"algorithms\",\"nlp\"]}', '[\"javascript\",\"php\",\"c++\",\"git\",\"github\",\"linux\",\"mongodb\",\"object oriented\"]', '[{\"type\":\"error\",\"msg\":\"Add your contact information (email, phone, LinkedIn, GitHub).\"},{\"type\":\"info\",\"msg\":\"Mention achievements, awards, or hackathon participations.\"},{\"type\":\"warning\",\"msg\":\"Add your GitHub profile link to showcase your projects.\"},{\"type\":\"warning\",\"msg\":\"Add your LinkedIn profile URL for professional networking.\"},{\"type\":\"warning\",\"msg\":\"Learn and add Git\\/GitHub \\u2014 it is essential for all tech roles.\"},{\"type\":\"success\",\"msg\":\"Excellent resume! Keep updating it with new projects and certifications.\"}]', '{\"Software Developer\":55,\"Data Scientist\":45,\"Machine Learning Engineer\":44,\"Database Administrator\":33,\"Full Stack Developer\":30}', '{\"education\":true,\"experience\":true,\"skills\":true,\"projects\":true,\"certifications\":true,\"achievements\":false,\"contact\":false,\"objective\":true}', '2026-06-14 17:34:56'),
(7, 10, 90, '{\"programming\":[\"python\",\"java\",\"go\",\"r\",\"html\",\"css\",\"sql\"],\"frameworks\":[\"spring\"],\"databases\":[\"mysql\",\"oracle\"],\"tools\":[],\"soft_skills\":[\"analytical\"],\"concepts\":[\"machine learning\",\"deep learning\",\"data structures\",\"algorithms\",\"nlp\"]}', '[\"javascript\",\"php\",\"c++\",\"git\",\"github\",\"linux\",\"mongodb\",\"object oriented\"]', '[{\"type\":\"error\",\"msg\":\"Add your contact information (email, phone, LinkedIn, GitHub).\"},{\"type\":\"info\",\"msg\":\"Mention achievements, awards, or hackathon participations.\"},{\"type\":\"warning\",\"msg\":\"Add your GitHub profile link to showcase your projects.\"},{\"type\":\"warning\",\"msg\":\"Add your LinkedIn profile URL for professional networking.\"},{\"type\":\"warning\",\"msg\":\"Learn and add Git\\/GitHub \\u2014 it is essential for all tech roles.\"},{\"type\":\"success\",\"msg\":\"Excellent resume! Keep updating it with new projects and certifications.\"}]', '{\"Software Developer\":55,\"Data Scientist\":45,\"Machine Learning Engineer\":44,\"Database Administrator\":33,\"Full Stack Developer\":30}', '{\"education\":true,\"experience\":true,\"skills\":true,\"projects\":true,\"certifications\":true,\"achievements\":false,\"contact\":false,\"objective\":true}', '2026-06-14 17:55:18'),
(8, 412, 98, '{\"programming\":[\"python\",\"java\",\"go\",\"r\",\"html\",\"css\",\"sql\"],\"frameworks\":[\"spring\"],\"databases\":[\"mysql\",\"oracle\"],\"tools\":[],\"soft_skills\":[\"analytical\"],\"concepts\":[\"machine learning\",\"deep learning\",\"data structures\",\"algorithms\",\"nlp\"]}', '[\"javascript\",\"php\",\"c++\",\"git\",\"github\",\"linux\",\"mongodb\",\"object oriented\"]', '[{\"type\":\"info\",\"msg\":\"Mention achievements, awards, or hackathon participations.\"},{\"type\":\"warning\",\"msg\":\"Add your GitHub profile link to showcase your projects.\"},{\"type\":\"warning\",\"msg\":\"Add your LinkedIn profile URL for professional networking.\"},{\"type\":\"warning\",\"msg\":\"Learn and add Git\\/GitHub \\u2014 it is essential for all tech roles.\"},{\"type\":\"success\",\"msg\":\"Excellent resume! Keep updating it with new projects and certifications.\"}]', '{\"Software Developer\":55,\"Data Scientist\":45,\"Machine Learning Engineer\":44,\"Database Administrator\":33,\"Full Stack Developer\":30}', '{\"education\":true,\"experience\":true,\"skills\":true,\"projects\":true,\"certifications\":true,\"achievements\":false,\"contact\":true,\"objective\":true}', '2026-06-14 18:40:50'),
(9, 412, 98, '{\"programming\":[\"python\",\"java\",\"go\",\"r\",\"html\",\"css\",\"sql\"],\"frameworks\":[\"spring\"],\"databases\":[\"mysql\",\"oracle\"],\"tools\":[],\"soft_skills\":[\"analytical\"],\"concepts\":[\"machine learning\",\"deep learning\",\"data structures\",\"algorithms\",\"nlp\"]}', '[\"javascript\",\"php\",\"c++\",\"git\",\"github\",\"linux\",\"mongodb\",\"object oriented\"]', '[{\"type\":\"info\",\"msg\":\"Mention achievements, awards, or hackathon participations.\"},{\"type\":\"warning\",\"msg\":\"Add your GitHub profile link to showcase your projects.\"},{\"type\":\"warning\",\"msg\":\"Add your LinkedIn profile URL for professional networking.\"},{\"type\":\"warning\",\"msg\":\"Learn and add Git\\/GitHub \\u2014 it is essential for all tech roles.\"},{\"type\":\"success\",\"msg\":\"Excellent resume! Keep updating it with new projects and certifications.\"}]', '{\"Software Developer\":55,\"Data Scientist\":45,\"Machine Learning Engineer\":44,\"Database Administrator\":33,\"Full Stack Developer\":30}', '{\"education\":true,\"experience\":true,\"skills\":true,\"projects\":true,\"certifications\":true,\"achievements\":false,\"contact\":true,\"objective\":true}', '2026-06-14 18:40:54'),
(10, 412, 98, '{\"programming\":[\"python\",\"java\",\"go\",\"r\",\"html\",\"css\",\"sql\"],\"frameworks\":[\"spring\"],\"databases\":[\"mysql\",\"oracle\"],\"tools\":[],\"soft_skills\":[\"analytical\"],\"concepts\":[\"machine learning\",\"deep learning\",\"data structures\",\"algorithms\",\"nlp\"]}', '[\"javascript\",\"php\",\"c++\",\"git\",\"github\",\"linux\",\"mongodb\",\"object oriented\"]', '[{\"type\":\"info\",\"msg\":\"Mention achievements, awards, or hackathon participations.\"},{\"type\":\"warning\",\"msg\":\"Add your GitHub profile link to showcase your projects.\"},{\"type\":\"warning\",\"msg\":\"Add your LinkedIn profile URL for professional networking.\"},{\"type\":\"warning\",\"msg\":\"Learn and add Git\\/GitHub \\u2014 it is essential for all tech roles.\"},{\"type\":\"success\",\"msg\":\"Excellent resume! Keep updating it with new projects and certifications.\"}]', '{\"Software Developer\":55,\"Data Scientist\":45,\"Machine Learning Engineer\":44,\"Database Administrator\":33,\"Full Stack Developer\":30}', '{\"education\":true,\"experience\":true,\"skills\":true,\"projects\":true,\"certifications\":true,\"achievements\":false,\"contact\":true,\"objective\":true}', '2026-06-14 18:43:26'),
(11, 412, 88, '{\"programming\":[\"python\",\"java\",\"html\",\"css\"],\"frameworks\":[],\"databases\":[\"mysql\",\"oracle\"],\"tools\":[],\"soft_skills\":[\"analytical\"],\"concepts\":[\"machine learning\",\"deep learning\",\"data structures\",\"algorithms\",\"nlp\"]}', '[\"javascript\",\"php\",\"c++\",\"git\",\"github\",\"linux\",\"mongodb\",\"object oriented\"]', '[{\"type\":\"info\",\"msg\":\"Mention achievements, awards, or hackathon participations.\"},{\"type\":\"warning\",\"msg\":\"Add your GitHub profile link to showcase your projects.\"},{\"type\":\"warning\",\"msg\":\"Add your LinkedIn profile URL for professional networking.\"},{\"type\":\"warning\",\"msg\":\"Learn and add Git\\/GitHub \\u2014 it is essential for all tech roles.\"},{\"type\":\"success\",\"msg\":\"Excellent resume! Keep updating it with new projects and certifications.\"}]', '{\"Software Developer\":55,\"Machine Learning Engineer\":44,\"Full Stack Developer\":30,\"Web Developer\":27,\"Data Scientist\":27}', '{\"education\":true,\"experience\":true,\"skills\":true,\"projects\":true,\"certifications\":true,\"achievements\":false,\"contact\":true,\"objective\":true}', '2026-06-14 18:55:35'),
(12, 412, 88, '{\"programming\":[\"python\",\"java\",\"html\",\"css\"],\"frameworks\":[],\"databases\":[\"mysql\",\"oracle\"],\"tools\":[],\"soft_skills\":[\"analytical\"],\"concepts\":[\"machine learning\",\"deep learning\",\"data structures\",\"algorithms\",\"nlp\"]}', '[\"javascript\",\"php\",\"c++\",\"git\",\"github\",\"linux\",\"mongodb\",\"object oriented\"]', '[{\"type\":\"info\",\"msg\":\"Mention achievements, awards, or hackathon participations.\"},{\"type\":\"warning\",\"msg\":\"Add your GitHub profile link to showcase your projects.\"},{\"type\":\"warning\",\"msg\":\"Add your LinkedIn profile URL for professional networking.\"},{\"type\":\"warning\",\"msg\":\"Learn and add Git\\/GitHub \\u2014 it is essential for all tech roles.\"},{\"type\":\"success\",\"msg\":\"Excellent resume! Keep updating it with new projects and certifications.\"}]', '{\"Software Developer\":55,\"Machine Learning Engineer\":44,\"Full Stack Developer\":30,\"Web Developer\":27,\"Data Scientist\":27}', '{\"education\":true,\"experience\":true,\"skills\":true,\"projects\":true,\"certifications\":true,\"achievements\":false,\"contact\":true,\"objective\":true}', '2026-06-14 18:55:38'),
(13, 20, 96, '{\"programming\":[\"python\",\"c#\",\"r\",\"sql\"],\"frameworks\":[\"asp.net\"],\"databases\":[\"mysql\"],\"tools\":[\"azure\",\"excel\"],\"soft_skills\":[\"communication\"],\"concepts\":[\"cloud computing\"]}', '[\"java\",\"javascript\",\"php\",\"c++\",\"git\",\"github\",\"linux\",\"mongodb\",\"data structures\",\"algorithms\",\"object oriented\"]', '[{\"type\":\"warning\",\"msg\":\"Add your GitHub profile link to showcase your projects.\"},{\"type\":\"warning\",\"msg\":\"Learn and add Git\\/GitHub \\u2014 it is essential for all tech roles.\"},{\"type\":\"success\",\"msg\":\"Excellent resume! Keep updating it with new projects and certifications.\"}]', '{\"Cloud Engineer\":33,\"Business Analyst\":33,\"Data Scientist\":27,\"Database Administrator\":22}', '{\"education\":true,\"experience\":true,\"skills\":true,\"projects\":true,\"certifications\":true,\"achievements\":true,\"contact\":true,\"objective\":true}', '2026-06-15 18:32:13');

-- --------------------------------------------------------

--
-- Table structure for table `round_eligible`
--

CREATE TABLE `round_eligible` (
  `id` int(11) NOT NULL,
  `round_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `eligible` tinyint(1) DEFAULT 1,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_tests`
--

CREATE TABLE `scheduled_tests` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `note` text DEFAULT NULL,
  `status` enum('pending','completed','missed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `attendance_pct` decimal(5,2) DEFAULT 0.00,
  `backlogs` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `placement_approval` enum('pending','approved','rejected') DEFAULT 'pending',
  `approval_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_attendance`
--

INSERT INTO `student_attendance` (`id`, `user_id`, `attendance_pct`, `backlogs`, `updated_at`, `placement_approval`, `approval_note`) VALUES
(2, 8, 82.00, 0, '2026-06-18 10:20:23', 'approved', NULL),
(3, 10, 85.00, 0, '2026-06-18 10:20:23', 'approved', NULL),
(4, 12, 80.00, 0, '2026-06-18 10:20:23', 'approved', NULL),
(5, 11, 60.00, 1, '2026-06-14 17:53:12', 'pending', NULL),
(6, 412, 0.00, 0, '2026-06-18 10:20:09', 'approved', '');

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `roll_number` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `year_of_passing` int(11) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `tenth_board` varchar(100) DEFAULT NULL,
  `tenth_percent` decimal(5,2) DEFAULT NULL,
  `twelfth_board` varchar(100) DEFAULT NULL,
  `twelfth_percent` decimal(5,2) DEFAULT NULL,
  `has_internship` tinyint(4) DEFAULT 0,
  `has_training` tinyint(4) DEFAULT 0,
  `backlogs` int(11) DEFAULT 0,
  `innovative_project` tinyint(4) DEFAULT 0,
  `communication_level` int(11) DEFAULT 0,
  `technical_course` tinyint(4) DEFAULT 0,
  `placement_status` enum('Placed','Not Placed') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_profiles`
--

INSERT INTO `student_profiles` (`id`, `user_id`, `roll_number`, `department`, `year_of_passing`, `cgpa`, `skills`, `resume_path`, `phone`, `address`, `gender`, `tenth_board`, `tenth_percent`, `twelfth_board`, `twelfth_percent`, `has_internship`, `has_training`, `backlogs`, `innovative_project`, `communication_level`, `technical_course`, `placement_status`) VALUES
(1, 8, '', 'Mechanical', 2027, 7.37, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', 'resume_8.pdf', '', '', 'Female', 'State Board', 96.70, 'CBSE', 70.20, 0, 1, 0, 0, 3, 1, 'Not Placed'),
(2, 9, NULL, 'Electronics and Communication Engineering', NULL, 9.35, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'WBBSE', 96.20, 'WBCHSE', 90.60, 0, 0, 0, 1, 4, 0, 'Not Placed'),
(3, 10, NULL, 'Information Technology', NULL, 7.84, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'State Board', 97.50, 'CBSE', 69.60, 0, 1, 0, 1, 3, 1, 'Placed'),
(4, 11, NULL, 'Computer Science in AIML', NULL, 7.87, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 96.90, 'Other state Board', 77.60, 1, 0, 1, 1, 2, 1, 'Not Placed'),
(5, 12, NULL, 'Computer Science and Engineering', NULL, 9.26, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'ICSE', 99.10, 'CBSE', 62.80, 1, 1, 0, 1, 1, 1, 'Not Placed'),
(6, 13, NULL, 'Computer Science and Engineering', NULL, 9.20, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'CBSE', 85.50, 'CBSE', 86.00, 1, 0, 0, 1, 2, 1, 'Not Placed'),
(7, 14, NULL, 'Computer Science and Engineering', NULL, 8.83, 'Lean Manufacturing, Quality Control, AutoCAD, Production Planning, Six Sigma', NULL, NULL, NULL, 'Male', 'State Board', 98.20, 'CBSE', 83.40, 1, 1, 1, 1, 2, 1, 'Not Placed'),
(8, 15, NULL, 'Production Engineering', NULL, 7.52, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'State Board', 79.40, 'Diploma', 82.10, 1, 0, 0, 1, 3, 1, 'Placed'),
(9, 16, NULL, 'Computer Science in AIML', NULL, 7.06, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Male', 'WBBSE', 88.00, 'Other state Board', 83.20, 0, 0, 0, 1, 3, 1, 'Placed'),
(10, 17, NULL, 'Civil Engineering', NULL, 7.34, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 84.30, 'CBSE', 70.90, 0, 0, 0, 0, 3, 1, 'Not Placed'),
(11, 18, NULL, 'Computer Science and Engineering', NULL, 7.12, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 84.10, 'Other state Board', 67.90, 0, 0, 1, 0, 1, 0, 'Not Placed'),
(12, 19, NULL, 'Computer Science and Engineering', NULL, 8.87, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'State Board', 97.80, 'ISE', 95.00, 1, 1, 0, 1, 4, 1, 'Not Placed'),
(13, 20, NULL, 'Computer Science and Engineering', NULL, 7.13, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming, python, c#, r, sql, asp.net, mysql, azure, excel, cloud computing', NULL, NULL, NULL, 'Male', 'ICSE', 83.00, 'CBSE', 79.80, 1, 0, 0, 0, 4, 1, 'Not Placed'),
(14, 21, NULL, 'Mechanical Engineering', NULL, 7.18, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'CBSE', 78.50, 'Other state Board', 62.00, 1, 1, 0, 1, 1, 1, 'Placed'),
(15, 22, NULL, 'Information Technology', NULL, 9.14, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'WBBSE', 95.90, 'CBSE', 73.20, 1, 1, 1, 1, 1, 1, 'Not Placed'),
(16, 23, NULL, 'Electronics and Communication Engineering', NULL, 7.58, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'WBBSE', 75.00, 'Other state Board', 68.90, 0, 0, 0, 1, 4, 1, 'Placed'),
(17, 24, NULL, 'Computer Science and Engineering', NULL, 7.66, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 89.80, 'CBSE', 88.60, 1, 1, 0, 1, 5, 1, 'Placed'),
(18, 25, NULL, 'Computer Science and Engineering', NULL, 8.92, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 93.60, 'WBCHSE', 87.60, 1, 0, 0, 0, 3, 0, 'Not Placed'),
(19, 26, NULL, 'Computer Science and Engineering', NULL, 7.17, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'CBSE', 87.40, 'ISE', 80.20, 1, 1, 0, 0, 4, 1, 'Not Placed'),
(20, 27, NULL, 'Electrical Engineering', NULL, 7.51, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'WBBSE', 96.30, 'CBSE', 78.00, 0, 1, 0, 1, 1, 1, 'Placed'),
(21, 28, NULL, 'Mechanical Engineering', NULL, 8.97, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'State Board', 82.40, 'Other state Board', 77.30, 0, 1, 0, 1, 2, 1, 'Placed'),
(22, 29, NULL, 'Electrical Engineering', NULL, 8.79, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'ICSE', 87.60, 'ISE', 88.90, 0, 0, 0, 0, 4, 1, 'Not Placed'),
(23, 30, NULL, 'Electrical Engineering', NULL, 8.10, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'ICSE', 76.90, 'CBSE', 68.20, 1, 1, 0, 1, 5, 1, 'Placed'),
(24, 31, NULL, 'Electrical Engineering', NULL, 7.08, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Male', 'ICSE', 72.80, 'Other state Board', 87.20, 0, 0, 1, 0, 4, 1, 'Not Placed'),
(25, 32, NULL, 'Civil Engineering', NULL, 7.79, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'State Board', 98.20, 'CBSE', 68.10, 0, 1, 0, 1, 1, 1, 'Placed'),
(26, 33, NULL, 'Electronics and Communication Engineering', NULL, 7.78, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'WBBSE', 89.30, 'CBSE', 71.20, 1, 1, 0, 1, 1, 1, 'Placed'),
(27, 34, NULL, 'Civil Engineering', NULL, 7.22, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'CBSE', 90.20, 'CBSE', 77.20, 0, 0, 0, 1, 1, 1, 'Placed'),
(28, 35, NULL, 'Computer Science and Engineering', NULL, 8.84, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 77.90, 'CBSE', 80.10, 1, 1, 1, 0, 4, 1, 'Not Placed'),
(29, 36, NULL, 'Computer Science and Engineering', NULL, 9.37, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'WBBSE', 98.50, 'Other state Board', 84.20, 1, 0, 0, 1, 4, 1, 'Not Placed'),
(30, 37, NULL, 'Electronics and Communication Engineering', NULL, 8.98, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 72.20, 'WBCHSE', 93.70, 1, 1, 0, 0, 5, 0, 'Not Placed'),
(31, 38, NULL, 'Computer Science and Engineering', NULL, 8.85, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 93.40, 'ISE', 92.20, 1, 0, 0, 1, 4, 1, 'Placed'),
(32, 39, NULL, 'Computer Science and Engineering', NULL, 7.80, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'ICSE', 85.60, 'ISE', 86.40, 1, 1, 0, 1, 4, 0, 'Not Placed'),
(33, 40, NULL, 'Computer Science in Data Science', NULL, 9.26, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'ICSE', 94.60, 'ISE', 79.60, 1, 0, 0, 1, 3, 1, 'Placed'),
(34, 41, NULL, 'Mechanical Engineering', NULL, 7.01, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'WBBSE', 79.60, 'CBSE', 74.10, 0, 1, 0, 1, 5, 1, 'Placed'),
(35, 42, NULL, 'Civil Engineering', NULL, 8.87, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'CBSE', 73.80, 'CBSE', 73.40, 0, 1, 0, 1, 5, 0, 'Not Placed'),
(36, 43, NULL, 'Computer Science and Engineering', NULL, 9.00, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'CBSE', 77.80, 'WBCHSE', 94.30, 1, 1, 0, 0, 4, 0, 'Not Placed'),
(37, 44, NULL, 'Information Technology', NULL, 7.74, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'State Board', 77.60, 'WBCHSE', 80.90, 0, 0, 0, 1, 3, 1, 'Placed'),
(38, 45, NULL, 'Information Technology', NULL, 8.48, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 71.00, 'ISE', 67.10, 1, 1, 0, 1, 3, 1, 'Placed'),
(39, 46, NULL, 'Electronics and Communication Engineering', NULL, 7.05, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'CBSE', 94.10, 'CBSE', 66.40, 0, 1, 1, 1, 2, 1, 'Not Placed'),
(40, 47, NULL, 'Computer Science in AIML', NULL, 7.16, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'ICSE', 72.10, 'CBSE', 70.20, 0, 0, 0, 1, 2, 1, 'Placed'),
(41, 48, NULL, 'Computer Science and Engineering', NULL, 7.95, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'ICSE', 82.60, 'WBCHSE', 67.30, 1, 1, 0, 1, 3, 1, 'Placed'),
(42, 49, NULL, 'Electrical and Electronics Engineering', NULL, 7.88, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'WBBSE', 71.90, 'WBCHSE', 67.40, 0, 0, 0, 1, 1, 1, 'Placed'),
(43, 50, NULL, 'Electronics and Communication Engineering', NULL, 8.86, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 80.50, 'ISE', 74.20, 1, 1, 1, 1, 2, 1, 'Not Placed'),
(44, 51, NULL, 'Electronics and Communication Engineering', NULL, 7.65, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'State Board', 87.20, 'WBCHSE', 80.40, 1, 1, 1, 0, 4, 1, 'Not Placed'),
(45, 52, NULL, 'Computer Science and Engineering', NULL, 9.34, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'CBSE', 70.30, 'Other state Board', 79.00, 1, 1, 0, 1, 2, 1, 'Not Placed'),
(46, 53, NULL, 'Mechanical Engineering', NULL, 8.52, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'State Board', 75.20, 'WBCHSE', 82.50, 0, 0, 0, 0, 5, 0, 'Not Placed'),
(47, 54, NULL, 'Electrical Engineering', NULL, 8.65, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'State Board', 78.90, 'ISE', 91.20, 0, 1, 0, 1, 1, 1, 'Placed'),
(48, 55, NULL, 'Computer Science in AIML', NULL, 7.10, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'WBBSE', 86.30, 'Other state Board', 62.60, 0, 1, 0, 1, 1, 1, 'Placed'),
(49, 56, NULL, 'Civil Engineering', NULL, 7.09, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Female', 'State Board', 96.90, 'CBSE', 89.80, 1, 1, 1, 1, 3, 1, 'Not Placed'),
(50, 57, NULL, 'Computer Science in AIML', NULL, 7.64, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 75.90, 'CBSE', 85.40, 0, 1, 1, 1, 4, 1, 'Not Placed'),
(51, 58, NULL, 'Computer Science and Engineering', NULL, 8.57, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'CBSE', 99.60, 'ISE', 81.70, 1, 0, 0, 0, 3, 0, 'Not Placed'),
(52, 59, NULL, 'Information Technology', NULL, 7.69, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'CBSE', 95.00, 'CBSE', 92.50, 1, 1, 0, 0, 5, 1, 'Not Placed'),
(53, 60, NULL, 'Civil Engineering', NULL, 7.57, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 72.00, 'CBSE', 65.10, 0, 1, 1, 1, 1, 0, 'Not Placed'),
(54, 61, NULL, 'Computer Science and Engineering', NULL, 9.23, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Female', 'ICSE', 88.60, 'WBCHSE', 73.20, 1, 1, 0, 1, 3, 1, 'Placed'),
(55, 62, NULL, 'Computer Science in AIML', NULL, 9.51, 'Python, R, Statistics, Data Analysis, Algorithms, Mathematical Modelling', NULL, NULL, NULL, 'Male', 'CBSE', 80.70, 'Other state Board', 76.10, 1, 0, 1, 1, 1, 1, 'Not Placed'),
(56, 63, NULL, 'IMsc Maths and Computing', NULL, 8.15, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'CBSE', 95.00, 'CBSE', 90.00, 1, 0, 0, 1, 4, 1, 'Not Placed'),
(57, 64, NULL, 'Mechanical Engineering', NULL, 9.35, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'State Board', 92.00, 'Other state Board', 67.40, 0, 1, 0, 1, 2, 1, 'Placed'),
(58, 65, NULL, 'Computer Science and Design', NULL, 8.58, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Male', 'ICSE', 84.40, 'Other state Board', 87.60, 1, 0, 0, 1, 3, 1, 'Placed'),
(59, 66, NULL, 'Computer Science and Design', NULL, 7.13, NULL, NULL, NULL, NULL, 'Male', 'WBBSE', 86.60, 'WBCHSE', 84.40, 0, 0, 0, 1, 3, 1, 'Placed'),
(60, 67, NULL, 'Electronics and Communication and Engineeing', NULL, 8.91, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'CBSE', 96.40, 'CBSE', 97.00, 0, 1, 1, 0, 3, 1, 'Not Placed'),
(61, 68, NULL, 'Electronics and Communication Engineering', NULL, 8.94, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'ICSE', 76.10, 'Other state Board', 60.40, 1, 0, 0, 1, 1, 1, 'Placed'),
(62, 69, NULL, 'Electrical Engineering', NULL, 8.99, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'CBSE', 75.60, 'Other state Board', 93.80, 1, 0, 0, 1, 4, 1, 'Placed'),
(63, 70, NULL, 'Mechanical Engineering', NULL, 7.60, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'WBBSE', 99.90, 'CBSE', 86.90, 0, 1, 0, 1, 4, 1, 'Placed'),
(64, 71, NULL, 'Information Technology', NULL, 5.75, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Male', 'CBSE', 75.00, 'CBSE', 48.00, 0, 0, 1, 0, 3, 1, 'Not Placed'),
(65, 72, NULL, 'Computer Science in Data Science', NULL, 7.92, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'State Board', 79.60, 'WBCHSE', 61.70, 0, 1, 1, 1, 4, 1, 'Not Placed'),
(66, 73, NULL, 'Chemical Engineering', NULL, 8.37, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'State Board', 91.10, 'ISE', 74.20, 0, 0, 0, 1, 2, 1, 'Placed'),
(67, 74, NULL, 'Electronics and Communication Engineering', NULL, 9.56, NULL, NULL, NULL, NULL, 'Female', 'CBSE', 86.40, 'ISE', 85.10, 0, 0, 0, 1, 3, 1, 'Not Placed'),
(68, 75, NULL, 'Civil Engineering', NULL, 9.35, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'State Board', 70.40, 'ISE', 87.20, 0, 0, 0, 1, 1, 1, 'Placed'),
(69, 76, NULL, 'Computer Science and Engineering', NULL, 7.45, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'ICSE', 86.70, 'Other state Board', 93.00, 1, 1, 0, 1, 1, 1, 'Placed'),
(70, 77, NULL, 'Computer Science in AIML', NULL, 8.02, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 72.20, 'CBSE', 74.80, 1, 1, 0, 1, 2, 1, 'Placed'),
(71, 78, NULL, 'Computer Science and Engineering', NULL, 8.20, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'CBSE', 75.00, 'CBSE', 68.00, 1, 0, 1, 1, 4, 1, 'Placed'),
(72, 79, NULL, 'Information Technology', NULL, 9.46, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'CBSE', 95.00, 'CBSE', 86.40, 1, 1, 0, 1, 3, 1, 'Not Placed'),
(73, 80, NULL, 'Electrical and Electronics Engineering', NULL, 8.61, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'State Board', 88.10, 'ISE', 60.20, 0, 0, 0, 1, 2, 1, 'Placed'),
(74, 81, NULL, 'Electrical Engineering', NULL, 8.53, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'ICSE', 94.30, 'ISE', 72.30, 0, 1, 0, 1, 2, 1, 'Placed'),
(75, 82, NULL, 'Information Technology', NULL, 9.30, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Male', 'WBBSE', 85.00, 'WBCHSE', 87.00, 0, 1, 0, 1, 3, 1, 'Placed'),
(76, 83, NULL, 'Electrical and Electronics Engineering', NULL, 8.49, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Male', 'ICSE', 82.80, 'ISE', 87.20, 0, 1, 0, 0, 4, 1, 'Not Placed'),
(77, 84, NULL, 'Computer Science and Design', NULL, 7.50, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'WBBSE', 64.00, 'WBCHSE', 68.00, 0, 1, 0, 1, 4, 1, 'Placed'),
(78, 85, NULL, 'Electronics and Communication Engineering', NULL, 9.31, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'ICSE', 94.00, 'ISE', 87.20, 1, 0, 0, 1, 5, 1, 'Placed'),
(79, 86, NULL, 'Mechanical Engineering', NULL, 9.01, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'WBBSE', 71.80, 'WBCHSE', 93.30, 1, 0, 0, 0, 2, 0, 'Not Placed'),
(80, 87, NULL, 'Chemical Engineering', NULL, 7.84, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'State Board', 76.00, 'CBSE', 60.30, 0, 0, 1, 1, 1, 1, 'Not Placed'),
(81, 88, NULL, 'Information Technology', NULL, 8.27, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'State Board', 72.20, 'CBSE', 77.00, 1, 1, 1, 1, 3, 1, 'Not Placed'),
(82, 89, NULL, 'Electrical Engineering', NULL, 9.29, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'ICSE', 96.50, 'ISE', 86.80, 0, 1, 0, 1, 3, 1, 'Placed'),
(83, 90, NULL, 'Chemical Engineering', NULL, 9.02, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 86.90, 'Other state Board', 87.40, 0, 1, 0, 1, 4, 0, 'Not Placed'),
(84, 91, NULL, 'Computer Science and Engineering', NULL, 9.26, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'ICSE', 82.40, 'Other state Board', 88.60, 1, 0, 0, 0, 1, 0, 'Not Placed'),
(85, 92, NULL, 'Information Technology', NULL, 7.32, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 74.90, 'Other state Board', 81.50, 1, 0, 0, 1, 5, 1, 'Placed'),
(86, 93, NULL, 'Computer Science and Engineering', NULL, 8.84, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'WBBSE', 75.00, 'Other state Board', 81.10, 1, 0, 1, 0, 2, 1, 'Not Placed'),
(87, 94, NULL, 'Information Technology', NULL, 7.03, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'CBSE', 87.40, 'CBSE', 81.60, 0, 1, 0, 1, 2, 1, 'Not Placed'),
(88, 95, NULL, 'Computer Science in Data Science', NULL, 7.92, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 73.90, 'CBSE', 63.90, 1, 0, 0, 1, 4, 1, 'Placed'),
(89, 96, NULL, 'Electronics and Communication Engineering', NULL, 8.11, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'WBBSE', 82.10, 'ISE', 72.10, 1, 0, 0, 1, 4, 1, 'Placed'),
(90, 97, NULL, 'Electronics and Communication Engineering', NULL, 8.46, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Male', 'ICSE', 70.10, 'Other state Board', 74.70, 1, 1, 0, 0, 1, 0, 'Not Placed'),
(91, 98, NULL, 'Computer Science and Design', NULL, 9.38, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'State Board', 82.70, 'ISE', 92.20, 1, 0, 0, 1, 2, 1, 'Placed'),
(92, 99, NULL, 'Computer Science and Engineering', NULL, 8.34, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'ICSE', 81.90, 'WBCHSE', 84.50, 1, 1, 1, 0, 1, 0, 'Not Placed'),
(93, 100, NULL, 'Electrical Engineering', NULL, 7.15, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'CBSE', 88.70, 'ISE', 65.90, 0, 0, 0, 1, 5, 0, 'Not Placed'),
(94, 101, NULL, 'Computer Science and Design', NULL, 8.80, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 93.50, 'Other state Board', 69.80, 1, 0, 1, 1, 3, 1, 'Not Placed'),
(95, 102, NULL, 'Electronics and Communication Engineering', NULL, 9.30, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'CBSE', 98.90, 'CBSE', 94.10, 1, 0, 0, 0, 5, 1, 'Not Placed'),
(96, 103, NULL, 'Computer Science and Design', NULL, 8.95, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'ICSE', 77.40, 'ISE', 67.10, 0, 1, 0, 0, 5, 1, 'Not Placed'),
(97, 104, NULL, 'Electronics and Communication Engineering', NULL, 7.44, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Male', 'State Board', 94.60, 'CBSE', 88.30, 1, 0, 0, 1, 1, 1, 'Placed'),
(98, 105, NULL, 'Electrical and Electronics Engineering', NULL, 7.78, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'CBSE', 82.30, 'ISE', 65.90, 0, 1, 0, 1, 4, 1, 'Placed'),
(99, 106, NULL, 'Computer Science and Engineering', NULL, 9.20, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 85.60, 'WBCHSE', 68.20, 1, 1, 0, 0, 4, 1, 'Not Placed'),
(100, 107, NULL, 'Electronics and Communication Engineering', NULL, 9.10, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'WBBSE', 88.20, 'ISE', 71.50, 1, 1, 0, 1, 5, 1, 'Placed'),
(101, 108, NULL, 'Information Technology', NULL, 7.82, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'WBBSE', 96.80, 'Other state Board', 94.90, 1, 0, 1, 0, 2, 1, 'Not Placed'),
(102, 109, NULL, 'Electronics and Communication Engineering', NULL, 7.29, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'CBSE', 86.70, 'Other state Board', 63.90, 1, 1, 0, 1, 2, 0, 'Not Placed'),
(103, 110, NULL, 'Computer Science and Design', NULL, 8.84, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'ICSE', 72.60, 'WBCHSE', 91.60, 1, 1, 0, 0, 2, 1, 'Not Placed'),
(104, 111, NULL, 'Computer Science and Engineering', NULL, 7.67, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'ICSE', 94.70, 'Other state Board', 87.30, 1, 0, 0, 0, 2, 0, 'Not Placed'),
(105, 112, NULL, 'Electrical and Electronics Engineering', NULL, 7.18, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'ICSE', 99.60, 'Other state Board', 94.00, 0, 1, 1, 1, 5, 0, 'Not Placed'),
(106, 113, NULL, 'Electronics and Communication Engineering', NULL, 7.57, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'WBBSE', 81.40, 'ISE', 68.10, 1, 1, 0, 1, 2, 0, 'Not Placed'),
(107, 114, NULL, 'Electrical Engineering', NULL, 7.15, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 77.70, 'CBSE', 79.20, 0, 0, 1, 0, 5, 1, 'Not Placed'),
(108, 115, NULL, 'Computer Science and Engineering', NULL, 8.97, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Male', 'WBBSE', 80.20, 'Other state Board', 91.80, 1, 0, 0, 1, 5, 1, 'Placed'),
(109, 116, NULL, 'Civil Engineering', NULL, 8.57, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 89.60, 'WBCHSE', 78.00, 1, 1, 0, 1, 1, 1, 'Placed'),
(110, 117, NULL, 'Computer Science and Engineering', NULL, 7.42, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 71.40, 'ISE', 92.30, 0, 0, 1, 1, 4, 1, 'Not Placed'),
(111, 118, NULL, 'Computer Science and Engineering', NULL, 9.47, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'ICSE', 87.00, 'CBSE', 75.00, 1, 1, 0, 1, 3, 0, 'Not Placed'),
(112, 119, NULL, 'Information Technology', NULL, 7.25, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'State Board', 84.70, 'WBCHSE', 84.40, 0, 1, 0, 0, 1, 0, 'Not Placed'),
(113, 120, NULL, 'Information Technology', NULL, 8.33, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'WBBSE', 72.00, 'CBSE', 64.40, 1, 1, 0, 1, 5, 1, 'Placed'),
(114, 121, NULL, 'Computer Science in AIML', NULL, 9.17, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Male', 'ICSE', 74.80, 'ISE', 83.90, 1, 1, 0, 1, 2, 1, 'Placed'),
(115, 122, NULL, 'Computer Science and Design', NULL, 8.90, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Male', 'WBBSE', 70.00, 'WBCHSE', 78.00, 1, 0, 0, 1, 2, 1, 'Placed'),
(116, 123, NULL, 'Computer Science and Design', NULL, 8.43, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'WBBSE', 75.90, 'WBCHSE', 85.20, 0, 1, 0, 1, 1, 1, 'Placed'),
(117, 124, NULL, 'Civil Engineering', NULL, 8.12, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'State Board', 72.40, 'CBSE', 67.60, 0, 0, 0, 1, 4, 1, 'Placed'),
(118, 125, NULL, 'Civil Engineering', NULL, 7.49, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 96.20, 'CBSE', 60.10, 0, 1, 0, 1, 1, 1, 'Placed'),
(119, 126, NULL, 'Computer Science and Engineering', NULL, 9.00, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'State Board', 93.00, 'Other state Board', 92.00, 1, 0, 0, 0, 3, 1, 'Not Placed'),
(120, 127, NULL, 'Computer Science in AIML', NULL, 9.48, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Male', 'State Board', 70.00, 'ISE', 94.80, 1, 0, 0, 1, 5, 1, 'Placed'),
(121, 128, NULL, 'Electrical and Electronics Engineering', NULL, 8.95, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'ICSE', 75.60, 'WBCHSE', 68.90, 0, 0, 1, 1, 3, 0, 'Not Placed'),
(122, 129, NULL, 'Computer Science and Design', NULL, 9.00, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'WBBSE', 60.00, 'WBCHSE', 69.00, 0, 0, 0, 0, 3, 0, 'Placed'),
(123, 130, NULL, 'Mechanical Engineering', NULL, 8.85, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Male', 'CBSE', 85.30, 'Other state Board', 72.80, 1, 1, 0, 1, 2, 1, 'Placed'),
(124, 131, NULL, 'Computer Science and Design', NULL, 9.37, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'ICSE', 89.20, 'CBSE', 82.70, 1, 0, 0, 1, 1, 1, 'Placed'),
(125, 132, NULL, 'Electrical Engineering', NULL, 8.59, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'WBBSE', 94.90, 'WBCHSE', 84.70, 0, 0, 0, 1, 3, 0, 'Not Placed'),
(126, 133, NULL, 'Electrical Engineering', NULL, 8.15, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'CBSE', 87.00, 'CBSE', 87.80, 1, 0, 1, 1, 4, 1, 'Not Placed'),
(127, 134, NULL, 'Electronics and Communication Engineering', NULL, 7.19, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'CBSE', 98.70, 'CBSE', 60.90, 1, 0, 0, 1, 4, 0, 'Not Placed'),
(128, 135, NULL, 'Electrical Engineering', NULL, 9.44, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'CBSE', 84.60, 'ISE', 79.10, 0, 0, 0, 1, 1, 1, 'Placed'),
(129, 136, NULL, 'Mechanical Engineering', NULL, 7.97, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'State Board', 80.40, 'CBSE', 65.60, 0, 0, 0, 1, 4, 1, 'Placed'),
(130, 137, NULL, 'Mechanical Engineering', NULL, 9.12, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Male', 'State Board', 80.30, 'WBCHSE', 74.90, 1, 0, 1, 1, 4, 1, 'Not Placed'),
(131, 138, NULL, 'Civil Engineering', NULL, 7.42, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'ICSE', 95.40, 'Other state Board', 65.80, 0, 1, 1, 1, 4, 1, 'Not Placed'),
(132, 139, NULL, 'Civil Engineering', NULL, 8.46, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'State Board', 87.50, 'Other state Board', 78.90, 0, 0, 0, 0, 4, 1, 'Not Placed'),
(133, 140, NULL, 'Information Technology', NULL, 8.47, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Male', 'ICSE', 91.80, 'WBCHSE', 89.50, 1, 0, 0, 1, 2, 1, 'Placed'),
(134, 141, NULL, 'Civil Engineering', NULL, 8.58, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'ICSE', 95.40, 'WBCHSE', 93.00, 0, 1, 0, 1, 2, 1, 'Placed'),
(135, 142, NULL, 'Mechanical Engineering', NULL, 7.17, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Male', 'State Board', 96.40, 'ISE', 83.00, 1, 0, 0, 1, 1, 1, 'Placed'),
(136, 143, NULL, 'Electrical and Electronics Engineering', NULL, 8.54, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'State Board', 79.60, 'CBSE', 87.40, 0, 1, 0, 1, 1, 1, 'Not Placed'),
(137, 144, NULL, 'Electrical and Electronics Engineering', NULL, 8.34, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'State Board', 100.00, 'WBCHSE', 93.10, 0, 0, 0, 1, 4, 1, 'Placed'),
(138, 145, NULL, 'Electrical and Electronics Engineering', NULL, 9.32, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 70.50, 'CBSE', 82.90, 1, 1, 0, 1, 2, 1, 'Placed'),
(139, 146, NULL, 'Computer Science and Engineering', NULL, 9.10, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'WBBSE', 70.00, 'WBBSE', 80.00, 0, 1, 1, 1, 3, 1, 'Placed'),
(140, 147, NULL, 'Electronics and Communication Engineering', NULL, 9.00, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'State Board', 86.80, 'CBSE', 64.60, 1, 1, 1, 1, 1, 0, 'Not Placed'),
(141, 148, NULL, 'Computer Science and Engineering', NULL, 7.96, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'ICSE', 91.40, 'ISE', 92.20, 1, 0, 0, 1, 4, 1, 'Placed'),
(142, 149, NULL, 'Electrical Engineering', NULL, 9.48, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'CBSE', 70.40, 'ISE', 89.30, 0, 0, 0, 1, 2, 1, 'Placed'),
(143, 150, NULL, 'Electrical Engineering', NULL, 7.99, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Male', 'State Board', 78.60, 'CBSE', 83.90, 0, 1, 0, 1, 2, 1, 'Placed'),
(144, 151, NULL, 'Computer Science in Data Science', NULL, 9.39, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Male', 'WBBSE', 90.50, 'WBCHSE', 61.00, 1, 1, 0, 1, 4, 1, 'Placed'),
(145, 152, NULL, 'Computer Science in Data Science', NULL, 7.62, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Female', 'CBSE', 91.80, 'Other state Board', 78.70, 1, 1, 0, 1, 1, 1, 'Placed'),
(146, 153, NULL, 'Computer Science in AIML', NULL, 7.13, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'WBBSE', 91.90, 'WBCHSE', 79.90, 0, 1, 0, 1, 5, 1, 'Placed'),
(147, 154, NULL, 'Computer Science and Engineering', NULL, 7.16, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'WBBSE', 74.10, 'Other state Board', 67.80, 0, 0, 0, 1, 4, 1, 'Placed'),
(148, 155, NULL, 'Computer Science and Design', NULL, 8.88, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'ICSE', 96.70, 'WBCHSE', 75.70, 1, 1, 0, 1, 3, 1, 'Placed'),
(149, 156, NULL, 'Computer Science in Data Science', NULL, 7.09, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'CBSE', 81.20, 'Other state Board', 74.70, 0, 1, 1, 0, 3, 1, 'Not Placed'),
(150, 157, NULL, 'Electrical and Electronics Engineering', NULL, 8.80, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'State Board', 80.20, 'WBCHSE', 94.30, 0, 0, 1, 1, 5, 1, 'Not Placed'),
(151, 158, NULL, 'Electrical Engineering', NULL, 7.37, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 99.50, 'CBSE', 90.10, 0, 0, 0, 1, 1, 1, 'Placed'),
(152, 159, NULL, 'Electronics and Communication Engineering', NULL, 7.37, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Male', 'CBSE', 83.10, 'CBSE', 61.90, 1, 0, 0, 1, 3, 1, 'Placed'),
(153, 160, NULL, 'Computer Science in Data Science', NULL, 8.55, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'CBSE', 75.50, 'WBCHSE', 73.80, 0, 1, 0, 1, 5, 1, 'Placed'),
(154, 161, NULL, 'Information Technology', NULL, 9.07, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'ICSE', 76.30, 'CBSE', 81.80, 1, 1, 0, 1, 5, 1, 'Placed'),
(155, 162, NULL, 'Electrical Engineering', NULL, 8.35, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'ICSE', 73.00, 'WBCHSE', 72.80, 0, 0, 0, 0, 4, 1, 'Not Placed'),
(156, 163, NULL, 'Electronics and Communication Engineering', NULL, 8.88, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Male', 'State Board', 84.20, 'ISE', 78.80, 1, 0, 0, 1, 3, 1, 'Placed'),
(157, 164, NULL, 'Civil Engineering', NULL, 8.33, 'Embedded Systems, VLSI, PCB Design, MATLAB, Microcontrollers', NULL, NULL, NULL, 'Male', 'CBSE', 90.60, 'CBSE', 69.90, 0, 0, 0, 1, 2, 1, 'Placed'),
(158, 165, NULL, 'Electronics Engineering', NULL, 8.30, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'State Board', 94.60, 'Diploma board - MSBTE', 91.50, 1, 1, 1, 0, 2, 1, 'Not Placed'),
(159, 166, NULL, 'Electrical and Electronics Engineering', NULL, 7.36, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 81.20, 'WBCHSE', 70.00, 0, 1, 0, 1, 2, 1, 'Not Placed'),
(160, 167, NULL, 'Computer Science and Engineering', NULL, 7.90, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'CBSE', 83.00, 'CBSE', 88.00, 0, 0, 0, 0, 2, 0, 'Placed'),
(161, 168, NULL, 'Computer Science in AIML', NULL, 8.78, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'CBSE', 92.30, 'CBSE', 91.30, 1, 1, 1, 0, 2, 1, 'Not Placed'),
(162, 169, NULL, 'Electrical Engineering', NULL, 8.54, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'WBBSE', 96.60, 'WBCHSE', 85.00, 1, 0, 0, 1, 4, 1, 'Placed'),
(163, 170, NULL, 'Mechanical Engineering', NULL, 7.52, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Male', 'ICSE', 71.30, 'WBCHSE', 91.80, 0, 1, 0, 1, 1, 1, 'Placed'),
(164, 171, NULL, 'Computer Science and Design', NULL, 9.21, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'CBSE', 79.00, 'CBSE', 83.00, 0, 0, 0, 0, 4, 1, 'Not Placed'),
(165, 172, NULL, 'Information Technology', NULL, 7.34, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'CBSE', 74.00, 'WBCHSE', 92.00, 1, 1, 0, 1, 4, 1, 'Placed'),
(166, 173, NULL, 'Mechanical Engineering', NULL, 8.89, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'ICSE', 95.20, 'ISE', 60.90, 1, 1, 1, 0, 2, 0, 'Not Placed'),
(167, 174, NULL, 'Information Technology', NULL, 9.20, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'WBBSE', 32.00, 'WBCHSE', 59.83, 0, 0, 0, 1, 2, 1, 'Not Placed'),
(168, 175, NULL, 'Mechanical Engineering', NULL, 8.34, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'State Board', 96.10, 'CBSE', 87.90, 0, 1, 0, 1, 2, 1, 'Placed'),
(169, 176, NULL, 'Computer Science in Data Science', NULL, 8.94, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'ICSE', 97.50, 'ISE', 62.30, 1, 1, 0, 1, 4, 1, 'Not Placed'),
(170, 177, NULL, 'Electronics and Communication Engineering', NULL, 7.24, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'State Board', 89.90, 'Other state Board', 73.10, 0, 0, 0, 1, 3, 0, 'Not Placed'),
(171, 178, NULL, 'Information Technology', NULL, 9.11, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'CBSE', 90.20, 'ISE', 76.00, 1, 1, 0, 1, 2, 1, 'Placed'),
(172, 179, NULL, 'Electronics and Communication Engineering', NULL, 7.87, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'CBSE', 85.40, 'Other state Board', 91.70, 1, 1, 0, 1, 5, 1, 'Not Placed'),
(173, 180, NULL, 'Information Technology', NULL, 7.49, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'ICSE', 92.10, 'ISE', 83.00, 0, 0, 0, 1, 2, 1, 'Placed'),
(174, 181, NULL, 'Computer Science and Engineering', NULL, 8.11, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'CBSE', 74.70, 'CBSE', 93.10, 1, 1, 0, 1, 3, 0, 'Not Placed'),
(175, 182, NULL, 'Electrical Engineering', NULL, 7.90, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Male', 'WBBSE', 47.00, 'WBCHSE', 88.00, 1, 1, 1, 1, 3, 1, 'Placed'),
(176, 183, NULL, 'Civil Engineering', NULL, 8.49, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'CBSE', 93.40, 'Other state Board', 88.00, 0, 0, 0, 1, 2, 1, 'Placed'),
(177, 184, NULL, 'Computer Science and Design', NULL, 9.29, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'State Board', 97.50, 'WBCHSE', 60.30, 1, 0, 0, 1, 1, 1, 'Placed'),
(178, 185, NULL, 'Computer Science and Design', NULL, 8.20, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'WBBSE', 91.00, 'WBCHSE', 87.00, 1, 1, 1, 1, 3, 0, 'Placed'),
(179, 186, NULL, 'Mechanical Engineering', NULL, 8.36, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'ICSE', 74.30, 'Other state Board', 93.10, 0, 0, 0, 1, 5, 1, 'Placed'),
(180, 187, NULL, 'Computer Science in Data Science', NULL, 8.26, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'ICSE', 99.80, 'ISE', 80.80, 1, 0, 0, 1, 3, 1, 'Placed'),
(181, 188, NULL, 'Mechanical Engineering', NULL, 8.20, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'CBSE', 70.70, 'Other state Board', 62.20, 0, 0, 0, 1, 5, 1, 'Placed'),
(182, 189, NULL, 'Chemical Engineering', NULL, 7.96, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Male', 'State Board', 95.40, 'CBSE', 79.00, 1, 1, 0, 1, 5, 1, 'Placed'),
(183, 190, NULL, 'Computer Science and Design', NULL, 7.40, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 88.40, 'Other state Board', 83.40, 0, 1, 0, 1, 1, 1, 'Not Placed'),
(184, 191, NULL, 'Computer Science and Engineering', NULL, 8.41, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Male', 'WBBSE', 89.50, 'ISE', 79.90, 1, 0, 1, 0, 5, 0, 'Not Placed'),
(185, 192, NULL, 'Chemical Engineering', NULL, 9.28, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'State Board', 72.20, 'Other state Board', 80.50, 0, 0, 0, 0, 2, 1, 'Not Placed'),
(186, 193, NULL, 'Computer Science and Engineering', NULL, 8.18, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Male', 'WBBSE', 87.50, 'WBCHSE', 72.90, 1, 0, 0, 0, 3, 0, 'Not Placed'),
(187, 194, NULL, 'Computer Science in Data Science', NULL, 8.18, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'State Board', 75.20, 'ISE', 85.50, 1, 1, 0, 0, 1, 0, 'Not Placed'),
(188, 195, NULL, 'Electronics and Communication Engineering', NULL, 8.41, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'ICSE', 87.40, 'WBCHSE', 91.40, 1, 0, 1, 1, 1, 1, 'Not Placed'),
(189, 196, NULL, 'Information Technology', NULL, 8.52, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'ICSE', 98.30, 'Other state Board', 94.70, 1, 0, 0, 1, 1, 1, 'Placed'),
(190, 197, NULL, 'Information Technology', NULL, 7.69, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Male', 'ICSE', 85.20, 'Other state Board', 80.70, 0, 0, 0, 1, 1, 1, 'Placed'),
(191, 198, NULL, 'Electrical and Electronics Engineering', NULL, 7.15, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'State Board', 86.60, 'Other state Board', 81.30, 0, 1, 1, 0, 5, 0, 'Not Placed'),
(192, 199, NULL, 'Mechanical Engineering', NULL, 7.55, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'ICSE', 94.10, 'ISE', 62.80, 0, 1, 0, 1, 5, 1, 'Placed'),
(193, 200, NULL, 'Information Technology', NULL, 9.34, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Male', 'WBBSE', 79.40, 'WBCHSE', 79.40, 0, 1, 0, 1, 2, 0, 'Not Placed'),
(194, 201, NULL, 'Computer Science in Data Science', NULL, 8.08, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'WBBSE', 95.10, 'Other state Board', 81.70, 1, 0, 0, 1, 1, 1, 'Not Placed'),
(195, 202, NULL, 'Electronics and Communication Engineering', NULL, 9.21, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'ICSE', 72.00, 'Other state Board', 90.50, 0, 1, 0, 1, 1, 1, 'Placed'),
(196, 203, NULL, 'Chemical Engineering', NULL, 7.67, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 84.50, 'WBCHSE', 87.40, 0, 0, 0, 1, 5, 1, 'Placed'),
(197, 204, NULL, 'Electronics and Communication Engineering', NULL, 7.35, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Male', 'WBBSE', 75.70, 'Other state Board', 80.60, 1, 0, 0, 1, 4, 1, 'Placed'),
(198, 205, NULL, 'Chemical Engineering', NULL, 7.14, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'CBSE', 91.70, 'WBCHSE', 91.90, 1, 0, 0, 0, 2, 1, 'Not Placed'),
(199, 206, NULL, 'Electrical Engineering', NULL, 8.45, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'ICSE', 82.80, 'Other state Board', 89.80, 1, 1, 0, 1, 3, 1, 'Not Placed'),
(200, 207, NULL, 'Computer Science and Engineering', NULL, 8.82, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'State Board', 80.20, 'CBSE', 74.70, 1, 1, 1, 0, 2, 1, 'Not Placed'),
(201, 208, NULL, 'Electrical and Electronics Engineering', NULL, 8.88, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'ICSE', 92.70, 'CBSE', 82.10, 1, 1, 1, 0, 1, 1, 'Not Placed'),
(202, 209, NULL, 'Mechanical Engineering', NULL, 9.23, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'CBSE', 82.90, 'Other state Board', 86.10, 0, 1, 0, 0, 4, 1, 'Not Placed'),
(203, 210, NULL, 'Electrical Engineering', NULL, 9.02, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'State Board', 85.70, 'Other state Board', 63.60, 1, 0, 0, 1, 4, 1, 'Placed'),
(204, 211, NULL, 'Electronics and Communication Engineering', NULL, 7.23, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'CBSE', 87.50, 'Other state Board', 87.30, 1, 1, 0, 0, 3, 0, 'Not Placed'),
(205, 212, NULL, 'Electrical and Electronics Engineering', NULL, 8.21, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'ICSE', 95.60, 'CBSE', 94.00, 0, 1, 0, 0, 5, 0, 'Not Placed'),
(206, 213, NULL, 'Mechanical Engineering', NULL, 9.12, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Male', 'ICSE', 77.60, 'ISE', 84.60, 0, 1, 1, 0, 2, 0, 'Not Placed'),
(207, 214, NULL, 'Computer Science and Design', NULL, 9.21, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'CBSE', 79.70, 'Other state Board', 91.50, 1, 1, 0, 0, 2, 1, 'Not Placed'),
(208, 215, NULL, 'Electrical and Electronics Engineering', NULL, 7.60, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'WBBSE', 82.00, 'Other state Board', 75.90, 0, 1, 0, 1, 1, 1, 'Placed'),
(209, 216, NULL, 'Civil Engineering', NULL, 8.22, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Female', 'WBBSE', 70.70, 'ISE', 77.40, 0, 0, 0, 0, 2, 1, 'Not Placed'),
(210, 217, NULL, 'Computer Science in AIML', NULL, 8.92, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'CBSE', 77.30, 'CBSE', 91.40, 1, 0, 0, 1, 3, 1, 'Placed'),
(211, 218, NULL, 'Electrical Engineering', NULL, 8.10, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'ICSE', 81.00, 'CBSE', 79.80, 0, 1, 0, 1, 2, 0, 'Not Placed'),
(212, 219, NULL, 'Mechanical Engineering', NULL, 7.46, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'CBSE', 82.90, 'ISE', 71.80, 0, 0, 1, 1, 4, 0, 'Not Placed'),
(213, 220, NULL, 'Electronics and Communication Engineering', NULL, 9.04, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 76.20, 'Other state Board', 74.80, 1, 0, 0, 0, 4, 0, 'Not Placed'),
(214, 221, NULL, 'Computer Science and Engineering', NULL, 8.95, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'ICSE', 83.60, 'Other state Board', 70.90, 0, 0, 0, 1, 2, 1, 'Placed'),
(215, 222, NULL, 'Mechanical Engineering', NULL, 9.16, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'ICSE', 78.70, 'Other state Board', 62.50, 1, 1, 1, 0, 3, 0, 'Not Placed'),
(216, 223, NULL, 'Electrical Engineering', NULL, 8.23, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Male', 'State Board', 78.60, 'ISE', 86.90, 0, 1, 1, 1, 1, 1, 'Not Placed'),
(217, 224, NULL, 'Chemical Engineering', NULL, 7.10, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 88.10, 'ISE', 82.60, 1, 1, 0, 1, 4, 1, 'Placed'),
(218, 225, NULL, 'Computer Science and Engineering', NULL, 7.49, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'State Board', 85.50, 'Other state Board', 82.40, 0, 0, 0, 1, 1, 1, 'Placed'),
(219, 226, NULL, 'Computer Science and Engineering', NULL, 9.48, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 91.80, 'CISCE', 88.50, 0, 0, 1, 1, 3, 1, 'Placed'),
(220, 227, NULL, 'Computer Science and Engineering', NULL, 7.79, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'WBBSE', 73.20, 'WBCHSE', 65.00, 0, 0, 0, 1, 4, 1, 'Placed'),
(221, 228, NULL, 'Electrical Engineering', NULL, 9.16, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'CBSE', 99.90, 'ISE', 86.40, 1, 0, 0, 1, 3, 1, 'Placed'),
(222, 229, NULL, 'Civil Engineering', NULL, 8.30, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 77.50, 'WBCHSE', 69.00, 0, 0, 0, 1, 4, 0, 'Not Placed'),
(223, 230, NULL, 'Electronics and Communication Engineering', NULL, 7.11, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'WBBSE', 83.70, 'WBCHSE', 91.00, 0, 1, 0, 0, 4, 1, 'Not Placed'),
(224, 231, NULL, 'Computer Science and Design', NULL, 8.34, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'ICSE', 91.70, 'CBSE', 68.90, 0, 1, 0, 0, 2, 0, 'Not Placed'),
(225, 232, NULL, 'Electronics and Communication Engineering', NULL, 9.16, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Male', 'WBBSE', 72.40, 'ISE', 80.60, 1, 0, 0, 1, 2, 1, 'Placed'),
(226, 233, NULL, 'Electrical and Electronics Engineering', NULL, 8.64, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'WBBSE', 70.80, 'Other state Board', 65.10, 0, 1, 0, 1, 3, 0, 'Not Placed'),
(227, 234, NULL, 'Electrical and Electronics Engineering', NULL, 8.49, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'ICSE', 82.90, 'ISE', 87.30, 0, 1, 0, 1, 2, 1, 'Placed');
INSERT INTO `student_profiles` (`id`, `user_id`, `roll_number`, `department`, `year_of_passing`, `cgpa`, `skills`, `resume_path`, `phone`, `address`, `gender`, `tenth_board`, `tenth_percent`, `twelfth_board`, `twelfth_percent`, `has_internship`, `has_training`, `backlogs`, `innovative_project`, `communication_level`, `technical_course`, `placement_status`) VALUES
(228, 235, NULL, 'Electrical Engineering', NULL, 7.01, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'CBSE', 84.80, 'Other state Board', 92.80, 0, 0, 0, 1, 2, 1, 'Placed'),
(229, 236, NULL, 'Civil Engineering', NULL, 9.35, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'ICSE', 99.80, 'CBSE', 63.70, 0, 1, 0, 0, 3, 0, 'Not Placed'),
(230, 237, NULL, 'Electronics and Communication Engineering', NULL, 8.43, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'State Board', 70.60, 'Other state Board', 70.70, 1, 1, 0, 1, 1, 1, 'Placed'),
(231, 238, NULL, 'Electrical and Electronics Engineering', NULL, 8.49, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'State Board', 91.20, 'Other state Board', 73.30, 1, 0, 0, 1, 5, 1, 'Placed'),
(232, 239, NULL, 'Electronics and Communication Engineering', NULL, 7.28, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'State Board', 80.50, 'CBSE', 68.60, 0, 0, 1, 1, 2, 0, 'Not Placed'),
(233, 240, NULL, 'Electronics and Communication Engineering', NULL, 8.80, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'CBSE', 70.90, 'ISE', 94.80, 1, 0, 0, 1, 1, 1, 'Not Placed'),
(234, 241, NULL, 'Chemical Engineering', NULL, 8.24, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'WBBSE', 97.00, 'WBCHSE', 88.60, 0, 1, 0, 1, 4, 1, 'Placed'),
(235, 242, NULL, 'Electronics and Communication Engineering', NULL, 7.61, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 88.30, 'Other state Board', 78.40, 1, 1, 0, 0, 5, 0, 'Not Placed'),
(236, 243, NULL, 'Computer Science and Engineering', NULL, 7.27, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'WBBSE', 88.70, 'WBCHSE', 83.60, 0, 1, 0, 1, 5, 1, 'Placed'),
(237, 244, NULL, 'Electronics and Communication Engineering', NULL, 9.25, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'CBSE', 85.30, 'CBSE', 76.40, 0, 0, 0, 1, 4, 0, 'Not Placed'),
(238, 245, NULL, 'Electrical Engineering', NULL, 7.93, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Male', 'State Board', 59.00, 'Other state Board', 55.00, 0, 1, 1, 1, 3, 0, 'Placed'),
(239, 246, NULL, 'Computer Science and Design', NULL, 8.68, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'State Board', 87.10, 'CBSE', 87.00, 1, 0, 0, 1, 5, 1, 'Placed'),
(240, 247, NULL, 'Information Technology', NULL, 8.27, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'State Board', 96.50, 'Other state Board', 80.90, 1, 0, 0, 0, 1, 1, 'Not Placed'),
(241, 248, NULL, 'Electrical Engineering', NULL, 8.82, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'State Board', 91.00, 'Other state Board', 86.10, 0, 1, 0, 1, 2, 1, 'Placed'),
(242, 249, NULL, 'Electronics and Communication Engineering', NULL, 7.04, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 81.80, 'ISE', 63.50, 1, 1, 1, 0, 4, 1, 'Not Placed'),
(243, 250, NULL, 'Computer Science and Engineering', NULL, 7.12, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'State Board', 73.00, 'Other state Board', 84.00, 0, 1, 0, 0, 3, 1, 'Not Placed'),
(244, 251, NULL, 'Computer Science and Engineering', NULL, 8.66, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'ICSE', 76.40, 'Other state Board', 66.20, 1, 1, 0, 0, 2, 1, 'Not Placed'),
(245, 252, NULL, 'Chemical Engineering', NULL, 8.13, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'ICSE', 83.90, 'WBCHSE', 93.50, 0, 1, 1, 1, 5, 0, 'Not Placed'),
(246, 253, NULL, 'Computer Science in Data Science', NULL, 8.27, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'State Board', 90.50, 'Other state Board', 83.10, 1, 0, 1, 0, 3, 1, 'Not Placed'),
(247, 254, NULL, 'Civil Engineering', NULL, 8.71, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'ICSE', 94.00, 'CBSE', 74.70, 0, 0, 1, 0, 4, 0, 'Not Placed'),
(248, 255, NULL, 'Electronics and Communication Engineering', NULL, 8.85, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'CBSE', 85.90, 'ISE', 76.90, 1, 1, 0, 1, 5, 1, 'Placed'),
(249, 256, NULL, 'Computer Science and Engineering', NULL, 8.47, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 98.50, 'WBCHSE', 69.80, 0, 0, 0, 1, 2, 1, 'Not Placed'),
(250, 257, NULL, 'Computer Science and Engineering', NULL, 7.09, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 90.60, 'Other state Board', 64.20, 0, 1, 1, 0, 1, 0, 'Not Placed'),
(251, 258, NULL, 'Computer Science and Engineering', NULL, 8.26, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'CBSE', 97.20, 'CBSE', 96.40, 1, 1, 1, 1, 3, 1, 'Not Placed'),
(252, 259, NULL, 'Computer Science and Design', NULL, 8.33, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'WBBSE', 89.20, 'WBCHSE', 89.70, 1, 0, 1, 0, 2, 0, 'Not Placed'),
(253, 260, NULL, 'Electrical and Electronics Engineering', NULL, 9.17, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'State Board', 97.60, 'Other state Board', 87.50, 1, 1, 0, 0, 1, 0, 'Not Placed'),
(254, 261, NULL, 'Computer Science and Design', NULL, 8.11, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'CBSE', 84.20, 'Other state Board', 88.00, 1, 1, 0, 1, 4, 1, 'Placed'),
(255, 262, NULL, 'Computer Science in AIML', NULL, 8.57, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 82.80, 'CBSE', 61.90, 1, 0, 0, 1, 2, 1, 'Placed'),
(256, 263, NULL, 'Electronics and Communication Engineering', NULL, 8.04, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'CBSE', 72.60, 'Other state Board', 62.60, 0, 0, 0, 1, 5, 1, 'Not Placed'),
(257, 264, NULL, 'Chemical Engineering', NULL, 7.39, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'State Board', 91.00, 'ISE', 91.80, 1, 1, 0, 0, 1, 1, 'Not Placed'),
(258, 265, NULL, 'Computer Science in Data Science', NULL, 7.51, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'CBSE', 82.80, 'ISE', 85.00, 0, 0, 1, 1, 4, 0, 'Not Placed'),
(259, 266, NULL, 'Computer Science in Data Science', NULL, 7.77, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'State Board', 75.40, 'ISE', 83.10, 0, 1, 0, 1, 4, 1, 'Placed'),
(260, 267, NULL, 'Electronics and Communication Engineering', NULL, 7.32, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'ICSE', 90.80, 'ISE', 74.70, 1, 1, 0, 0, 4, 0, 'Not Placed'),
(261, 268, NULL, 'Mechanical Engineering', NULL, 7.40, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'State Board', 93.00, 'Other state Board', 66.40, 0, 1, 0, 1, 4, 0, 'Not Placed'),
(262, 269, NULL, 'Computer Science and Engineering', NULL, 8.01, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'ICSE', 78.30, 'ISE', 92.50, 1, 1, 0, 0, 2, 0, 'Not Placed'),
(263, 270, NULL, 'Electronics and Communication Engineering', NULL, 7.12, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'ICSE', 74.10, 'CBSE', 80.10, 1, 0, 0, 1, 4, 1, 'Placed'),
(264, 271, NULL, 'Computer Science and Engineering', NULL, 8.77, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'State Board', 78.40, 'ISE', 81.60, 1, 0, 0, 1, 1, 1, 'Placed'),
(265, 272, NULL, 'Electronics and Communication Engineering', NULL, 7.54, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'State Board', 70.50, 'WBCHSE', 63.90, 1, 1, 0, 1, 5, 1, 'Placed'),
(266, 273, NULL, 'Electronics and Communication Engineering', NULL, 8.82, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'State Board', 86.70, 'WBCHSE', 71.40, 1, 1, 0, 1, 1, 1, 'Placed'),
(267, 274, NULL, 'Computer Science in AIML', NULL, 7.21, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Male', 'CBSE', 95.00, 'CBSE', 93.20, 0, 0, 0, 1, 4, 1, 'Placed'),
(268, 275, NULL, 'Electrical and Electronics Engineering', NULL, 7.20, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'WBBSE', 84.50, 'Other state Board', 67.00, 1, 1, 1, 0, 5, 0, 'Not Placed'),
(269, 276, NULL, 'Computer Science and Engineering', NULL, 7.79, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 76.80, 'ISE', 82.00, 1, 0, 0, 1, 4, 1, 'Placed'),
(270, 277, NULL, 'Computer Science and Engineering', NULL, 9.94, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 90.80, 'CBSE', 92.00, 1, 0, 1, 1, 4, 0, 'Placed'),
(271, 278, NULL, 'Computer Science and Engineering', NULL, 8.83, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'WBBSE', 73.10, 'CBSE', 66.30, 1, 0, 0, 1, 2, 1, 'Placed'),
(272, 279, NULL, 'Civil Engineering', NULL, 8.13, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 93.20, 'CBSE', 89.00, 1, 1, 0, 1, 5, 1, 'Placed'),
(273, 280, NULL, 'Computer Science and Engineering', NULL, 9.39, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Female', 'State Board', 81.80, 'ISE', 74.50, 0, 0, 0, 1, 1, 1, 'Placed'),
(274, 281, NULL, 'Computer Science and Design', NULL, 8.80, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'WBBSE', 86.50, 'WBCHSE', 93.40, 0, 0, 1, 1, 5, 1, 'Not Placed'),
(275, 282, NULL, 'Electrical and Electronics Engineering', NULL, 7.99, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'CBSE', 85.50, 'Other state Board', 62.30, 0, 0, 1, 1, 5, 1, 'Not Placed'),
(276, 283, NULL, 'Information Technology', NULL, 7.65, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'WBBSE', 86.60, 'Other state Board', 71.30, 1, 0, 0, 1, 3, 1, 'Placed'),
(277, 284, NULL, 'Information Technology', NULL, 8.30, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'ICSE', 99.20, 'CBSE', 73.40, 1, 1, 0, 1, 5, 0, 'Not Placed'),
(278, 285, NULL, 'Electronics and Communication Engineering', NULL, 7.90, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 85.10, 'WBCHSE', 87.30, 1, 0, 1, 1, 5, 0, 'Not Placed'),
(279, 286, NULL, 'Computer Science and Engineering', NULL, 9.55, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'WBBSE', 98.90, 'WBCHSE', 78.60, 0, 0, 0, 1, 3, 1, 'Not Placed'),
(280, 287, NULL, 'Mechanical Engineering', NULL, 8.20, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'ICSE', 95.00, 'ISC', 93.00, 1, 0, 1, 1, 3, 1, 'Placed'),
(281, 288, NULL, 'Computer Science in Data Science', NULL, 7.00, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'ICSE', 84.30, 'CBSE', 90.00, 0, 1, 0, 1, 4, 1, 'Placed'),
(282, 289, NULL, 'Chemical Engineering', NULL, 9.36, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'ICSE', 90.30, 'ISE', 83.90, 1, 1, 0, 1, 1, 1, 'Placed'),
(283, 290, NULL, 'Mechanical Engineering', NULL, 7.17, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'State Board', 87.30, 'Other state Board', 60.30, 0, 0, 0, 1, 2, 1, 'Placed'),
(284, 291, NULL, 'Computer Science in Data Science', NULL, 9.08, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'ICSE', 74.60, 'Other state Board', 88.00, 1, 0, 0, 1, 1, 1, 'Placed'),
(285, 292, NULL, 'Mechanical Engineering', NULL, 9.32, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 82.40, 'Other state Board', 92.50, 1, 1, 0, 1, 4, 1, 'Placed'),
(286, 293, NULL, 'Computer Science and Engineering', NULL, 8.20, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'ICSE', 85.00, 'CBSE', 87.70, 1, 1, 0, 1, 5, 1, 'Placed'),
(287, 294, NULL, 'Mechanical Engineering', NULL, 8.94, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'CBSE', 80.20, 'WBCHSE', 92.40, 1, 0, 0, 1, 5, 1, 'Placed'),
(288, 295, NULL, 'Information Technology', NULL, 8.97, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'CBSE', 84.00, 'ISE', 83.90, 1, 1, 0, 1, 5, 1, 'Placed'),
(289, 296, NULL, 'Mechanical Engineering', NULL, 9.59, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'CBSE', 75.90, 'Other state Board', 85.60, 0, 1, 0, 1, 2, 1, 'Placed'),
(290, 297, NULL, 'Information Technology', NULL, 9.25, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'ICSE', 96.00, 'ISE', 94.00, 1, 0, 0, 1, 1, 1, 'Placed'),
(291, 298, NULL, 'Information Technology', NULL, 9.20, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Male', 'CBSE', 89.30, 'CBSE', 81.40, 0, 1, 1, 1, 2, 1, 'Placed'),
(292, 299, NULL, 'Chemical Engineering', NULL, 7.77, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Male', 'CBSE', 99.00, 'Other state Board', 84.00, 0, 1, 0, 1, 5, 1, 'Placed'),
(293, 300, NULL, 'Electrical and Electronics Engineering', NULL, 7.00, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'CBSE', 85.20, 'CBSE', 90.10, 0, 1, 0, 1, 4, 1, 'Placed'),
(294, 301, NULL, 'Computer Science and Engineering', NULL, 9.60, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'WBBSE', 90.00, 'WBCHSE', 89.00, 1, 1, 1, 1, 2, 1, 'Placed'),
(295, 302, NULL, 'Electrical Engineering', NULL, 7.14, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'WBBSE', 74.00, 'WBCHSE', 81.40, 0, 1, 1, 0, 5, 1, 'Not Placed'),
(296, 303, NULL, 'Information Technology', NULL, 7.17, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 81.20, 'CBSE', 71.90, 0, 0, 0, 1, 5, 1, 'Placed'),
(297, 304, NULL, 'Electronics and Communication Engineering', NULL, 9.09, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Male', 'CBSE', 80.30, 'Other state Board', 87.70, 1, 0, 0, 1, 5, 1, 'Placed'),
(298, 305, NULL, 'Civil Engineering', NULL, 7.11, 'Embedded Systems, PCB Design, Arduino, IoT, MATLAB, Microcontrollers', NULL, NULL, NULL, 'Male', 'ICSE', 97.60, 'CBSE', 85.10, 1, 0, 1, 0, 2, 1, 'Not Placed'),
(299, 306, NULL, 'Electronic Engineering', NULL, 7.80, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'State Board', 87.20, 'MSBTE', 82.40, 0, 1, 0, 0, 2, 1, 'Not Placed'),
(300, 307, NULL, 'Information Technology', NULL, 9.49, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 89.30, 'WBCHSE', 90.70, 1, 0, 1, 1, 5, 0, 'Not Placed'),
(301, 308, NULL, 'Computer Science and Engineering', NULL, 7.27, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'State Board', 86.70, 'CBSE', 72.00, 1, 1, 0, 1, 4, 1, 'Placed'),
(302, 309, NULL, 'Information Technology', NULL, 8.33, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 89.00, 'ISE', 93.80, 1, 1, 1, 1, 3, 1, 'Not Placed'),
(303, 310, NULL, 'Computer Science and Engineering', NULL, 8.02, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'State Board', 85.20, 'CBSE', 88.20, 1, 0, 0, 1, 5, 1, 'Placed'),
(304, 311, NULL, 'Electronics and Communication Engineering', NULL, 9.37, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'ICSE', 88.00, 'WBCHSE', 64.60, 1, 0, 1, 0, 2, 1, 'Not Placed'),
(305, 312, NULL, 'Electronics and Communication Engineering', NULL, 7.10, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'WBBSE', 73.00, 'WBCHSE', 75.90, 0, 1, 0, 1, 3, 1, 'Placed'),
(306, 313, NULL, 'Electronics and Communication Engineering', NULL, 7.19, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'State Board', 96.00, 'ISE', 85.60, 1, 0, 1, 1, 3, 0, 'Not Placed'),
(307, 314, NULL, 'Computer Science and Engineering', NULL, 8.03, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'WBBSE', 78.40, 'WBCHSE', 66.80, 0, 0, 0, 0, 2, 0, 'Not Placed'),
(308, 315, NULL, 'Mechanical Engineering', NULL, 8.43, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'CBSE', 71.70, 'CBSE', 74.40, 0, 0, 0, 1, 1, 0, 'Not Placed'),
(309, 316, NULL, 'Electrical Engineering', NULL, 9.01, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'CBSE', 93.00, 'ISE', 62.50, 1, 0, 0, 1, 2, 1, 'Placed'),
(310, 317, NULL, 'Electrical Engineering', NULL, 8.09, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'CBSE', 72.80, 'Other state Board', 94.70, 0, 1, 0, 1, 3, 1, 'Placed'),
(311, 318, NULL, 'Electrical Engineering', NULL, 7.58, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'State Board', 96.80, 'Other state Board', 65.80, 0, 1, 0, 0, 2, 0, 'Not Placed'),
(312, 319, NULL, 'Computer Science in AIML', NULL, 8.40, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'ICSE', 93.20, 'CBSE', 82.00, 1, 1, 1, 0, 1, 0, 'Not Placed'),
(313, 320, NULL, 'Electronics and Communication Engineering', NULL, 8.62, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'ICSE', 84.70, 'CBSE', 67.10, 1, 0, 0, 0, 2, 0, 'Not Placed'),
(314, 321, NULL, 'Computer Science in Data Science', NULL, 8.11, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'WBBSE', 70.10, 'Other state Board', 82.20, 0, 1, 1, 0, 3, 1, 'Not Placed'),
(315, 322, NULL, 'Electronics and Communication Engineering', NULL, 8.24, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Male', 'CBSE', 92.80, 'Other state Board', 68.20, 1, 1, 0, 1, 3, 1, 'Placed'),
(316, 323, NULL, 'Electrical and Electronics Engineering', NULL, 8.69, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'ICSE', 97.20, 'CBSE', 78.60, 1, 0, 1, 0, 1, 0, 'Not Placed'),
(317, 324, NULL, 'Electronics and Communication Engineering', NULL, 7.07, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'ICSE', 99.50, 'CBSE', 77.50, 1, 1, 1, 1, 2, 0, 'Not Placed'),
(318, 325, NULL, 'Computer Science in AIML', NULL, 7.56, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'ICSE', 99.30, 'ISE', 73.80, 0, 1, 0, 1, 3, 1, 'Placed'),
(319, 326, NULL, 'Computer Science and Engineering', NULL, 7.75, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Male', 'State Board', 81.70, 'ISE', 84.40, 1, 1, 1, 1, 4, 0, 'Not Placed'),
(320, 327, NULL, 'Civil Engineering', NULL, 7.68, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'State Board', 99.50, 'ISE', 83.20, 0, 0, 1, 1, 5, 0, 'Not Placed'),
(321, 328, NULL, 'Information Technology', NULL, 7.16, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'ICSE', 77.10, 'WBCHSE', 66.90, 0, 1, 0, 0, 5, 1, 'Not Placed'),
(322, 329, NULL, 'Mechanical Engineering', NULL, 8.94, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Male', 'State Board', 80.80, 'CBSE', 85.30, 0, 0, 1, 1, 1, 1, 'Not Placed'),
(323, 330, NULL, 'Civil Engineering', NULL, 9.21, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 88.50, 'WBCHSE', 81.80, 1, 1, 1, 0, 5, 1, 'Not Placed'),
(324, 331, NULL, 'Computer Science and Engineering', NULL, 7.67, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'State Board', 90.80, 'CBSE', 62.20, 1, 1, 0, 1, 4, 0, 'Not Placed'),
(325, 332, NULL, 'Computer Science and Engineering', NULL, 9.39, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'State Board', 83.10, 'ISE', 74.50, 1, 1, 0, 1, 2, 1, 'Placed'),
(326, 333, NULL, 'Computer Science and Engineering', NULL, 7.94, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 90.00, 'ISE', 86.40, 1, 0, 0, 1, 3, 1, 'Placed'),
(327, 334, NULL, 'Computer Science and Engineering', NULL, 8.31, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'CBSE', 71.50, 'CBSE', 70.30, 0, 0, 0, 1, 2, 1, 'Placed'),
(328, 335, NULL, 'Information Technology', NULL, 9.26, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'WBBSE', 95.60, 'ISE', 68.70, 1, 0, 0, 1, 2, 1, 'Placed'),
(329, 336, NULL, 'Information Technology', NULL, 9.47, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 94.70, 'CBSE', 67.20, 1, 0, 0, 1, 2, 1, 'Not Placed'),
(330, 337, NULL, 'Computer Science and Engineering', NULL, 7.41, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'CBSE', 80.50, 'WBCHSE', 83.10, 1, 0, 0, 1, 4, 0, 'Not Placed'),
(331, 338, NULL, 'Computer Science in AIML', NULL, 8.16, NULL, NULL, NULL, NULL, 'Female', 'ICSE', 80.80, 'ISE', 73.50, 1, 1, 1, 1, 3, 1, 'Not Placed'),
(332, 339, NULL, 'Electronics and Communication and Engineeing', NULL, 8.25, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'WBBSE', 92.50, 'WBCHSE', 93.00, 1, 0, 0, 1, 3, 1, 'Not Placed'),
(333, 340, NULL, 'Electronics and Communication Engineering', NULL, 7.99, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'State Board', 90.10, 'CBSE', 92.30, 1, 0, 0, 1, 2, 1, 'Placed'),
(334, 341, NULL, 'Information Technology', NULL, 8.17, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'WBBSE', 86.60, 'WBCHSE', 76.00, 1, 0, 0, 1, 5, 1, 'Placed'),
(335, 342, NULL, 'Chemical Engineering', NULL, 7.12, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'CBSE', 83.20, 'CBSE', 69.50, 0, 1, 0, 0, 5, 0, 'Not Placed'),
(336, 343, NULL, 'Computer Science and Engineering', NULL, 8.26, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Male', 'State Board', 57.70, 'Other state Board', 56.70, 0, 1, 0, 1, 3, 1, 'Not Placed'),
(337, 344, NULL, 'Chemical Engineering', NULL, 9.05, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Male', 'State Board', 77.10, 'ISE', 92.70, 1, 1, 0, 1, 2, 0, 'Not Placed'),
(338, 345, NULL, 'Electrical and Electronics Engineering', NULL, 8.08, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'State Board', 91.10, 'CBSE', 91.10, 1, 1, 1, 0, 4, 0, 'Not Placed'),
(339, 346, NULL, 'Electronics and Communication Engineering', NULL, 9.37, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'State Board', 95.80, 'Other state Board', 72.10, 0, 0, 0, 1, 1, 1, 'Placed'),
(340, 347, NULL, 'Information Technology', NULL, 9.36, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'CBSE', 71.60, 'Other state Board', 63.30, 1, 1, 0, 1, 1, 1, 'Placed'),
(341, 348, NULL, 'Chemical Engineering', NULL, 8.30, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'State Board', 99.70, 'ISE', 93.30, 1, 1, 1, 0, 2, 0, 'Not Placed'),
(342, 349, NULL, 'Computer Science and Engineering', NULL, 9.14, NULL, NULL, NULL, NULL, 'Female', 'State Board', 79.90, 'ISE', 79.00, 1, 0, 1, 1, 1, 0, 'Not Placed'),
(343, 350, NULL, 'Electronics and Communication and Engineeing', NULL, 8.00, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'ICSE', 75.00, 'CBSE', 70.00, 0, 1, 1, 1, 2, 1, 'Placed'),
(344, 351, NULL, 'Electrical Engineering', NULL, 9.13, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'ICSE', 86.30, 'Other state Board', 85.10, 1, 1, 0, 1, 1, 1, 'Placed'),
(345, 352, NULL, 'Chemical Engineering', NULL, 8.15, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 82.00, 'Other state Board', 71.70, 0, 1, 1, 0, 5, 0, 'Not Placed'),
(346, 353, NULL, 'Computer Science and Engineering', NULL, 7.59, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 82.40, 'Other state Board', 72.30, 1, 0, 0, 1, 3, 1, 'Not Placed'),
(347, 354, NULL, 'Electronics and Communication Engineering', NULL, 9.07, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'CBSE', 79.80, 'CBSE', 88.80, 1, 1, 0, 1, 5, 1, 'Placed'),
(348, 355, NULL, 'Computer Science and Engineering', NULL, 7.10, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'State Board', 61.00, 'Other state Board', 53.00, 1, 1, 1, 1, 3, 1, 'Not Placed'),
(349, 356, NULL, 'Electronics and Communication Engineering', NULL, 8.68, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'CBSE', 71.20, 'Other state Board', 81.10, 1, 1, 0, 1, 3, 1, 'Placed'),
(350, 357, NULL, 'Electrical Engineering', NULL, 8.66, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Male', 'State Board', 80.40, 'WBCHSE', 92.10, 0, 1, 0, 1, 1, 1, 'Placed'),
(351, 358, NULL, 'Computer Science in Data Science', NULL, 7.50, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'CBSE', 72.00, 'ISE', 84.80, 1, 0, 1, 0, 3, 1, 'Not Placed'),
(352, 359, NULL, 'Electronics and Communication Engineering', NULL, 7.85, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Male', 'CBSE', 72.20, 'Other state Board', 88.60, 1, 1, 0, 1, 5, 1, 'Placed'),
(353, 360, NULL, 'Computer Science in Data Science', NULL, 7.78, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'CBSE', 88.20, 'CBSE', 78.10, 1, 1, 1, 1, 4, 1, 'Not Placed'),
(354, 361, NULL, 'Electrical and Electronics Engineering', NULL, 8.22, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'State Board', 73.80, 'ISE', 77.60, 0, 1, 1, 0, 2, 1, 'Not Placed'),
(355, 362, NULL, 'Information Technology', NULL, 8.15, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'ICSE', 86.80, 'CBSE', 71.60, 1, 1, 0, 0, 2, 0, 'Not Placed'),
(356, 363, NULL, 'Electronics and Communication Engineering', NULL, 8.06, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'CBSE', 78.80, 'CBSE', 60.90, 1, 1, 0, 0, 1, 0, 'Not Placed'),
(357, 364, NULL, 'Computer Science and Engineering', NULL, 7.71, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'State Board', 96.50, 'ISE', 78.40, 1, 1, 0, 1, 1, 0, 'Not Placed'),
(358, 365, NULL, 'Computer Science and Engineering', NULL, 8.02, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'State Board', 88.80, 'CBSE', 69.30, 0, 1, 0, 1, 4, 1, 'Placed'),
(359, 366, NULL, 'Electrical Engineering', NULL, 9.16, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'WBBSE', 85.40, 'WBCHSE', 75.60, 1, 0, 0, 1, 1, 1, 'Placed'),
(360, 367, NULL, 'Computer Science and Engineering', NULL, 8.65, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'ICSE', 78.20, 'ISE', 74.70, 1, 1, 0, 1, 5, 1, 'Placed'),
(361, 368, NULL, 'Computer Science in Data Science', NULL, 8.68, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'State Board', 80.00, 'Other state Board', 62.30, 0, 0, 0, 1, 2, 1, 'Placed'),
(362, 369, NULL, 'Civil Engineering', NULL, 8.41, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Female', 'WBBSE', 80.80, 'WBCHSE', 85.40, 1, 1, 0, 0, 3, 1, 'Not Placed'),
(363, 370, NULL, 'Chemical Engineering', NULL, 9.30, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Female', 'WBBSE', 97.90, 'WBCHSE', 63.10, 1, 1, 1, 1, 1, 0, 'Not Placed'),
(364, 371, NULL, 'Mechanical Engineering', NULL, 8.01, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'CBSE', 87.50, 'WBCHSE', 77.80, 0, 0, 1, 1, 5, 1, 'Not Placed'),
(365, 372, NULL, 'Computer Science and Engineering', NULL, 8.45, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Male', 'ICSE', 79.90, 'ISE', 82.90, 1, 1, 0, 1, 1, 1, 'Placed'),
(366, 373, NULL, 'Electrical and Electronics Engineering', NULL, 9.12, 'Embedded Systems, MATLAB, Power Electronics, PLC, Microcontrollers', NULL, NULL, NULL, 'Female', 'ICSE', 86.20, 'CBSE', 76.80, 0, 1, 0, 0, 2, 1, 'Not Placed'),
(367, 374, NULL, 'Electrical and Electronics Engineering', NULL, 7.92, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'ICSE', 79.60, 'ISE', 68.00, 0, 0, 1, 0, 4, 1, 'Not Placed'),
(368, 375, NULL, 'Computer Science in AIML', NULL, 9.24, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Female', 'WBBSE', 98.40, 'Other state Board', 72.60, 1, 0, 0, 1, 1, 1, 'Placed'),
(369, 376, NULL, 'Computer Science in AIML', NULL, 8.99, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'ICSE', 94.10, 'WBCHSE', 68.60, 1, 1, 1, 1, 2, 0, 'Not Placed'),
(370, 377, NULL, 'Electrical Engineering', NULL, 7.24, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'WBBSE', 70.00, 'ISE', 66.30, 0, 0, 0, 1, 4, 1, 'Placed'),
(371, 378, NULL, 'Civil Engineering', NULL, 7.14, 'AutoCAD, SolidWorks, ANSYS, CATIA, Manufacturing Processes, CNC Programming', NULL, NULL, NULL, 'Male', 'State Board', 85.70, 'CBSE', 63.10, 0, 1, 0, 0, 5, 0, 'Not Placed'),
(372, 379, NULL, 'Mechanical Engineering', NULL, 8.35, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Female', 'WBBSE', 84.30, 'ISE', 86.10, 0, 1, 0, 1, 3, 1, 'Placed'),
(373, 380, NULL, 'Information Technology', NULL, 8.93, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'State Board', 97.50, 'ISE', 69.40, 1, 1, 0, 1, 2, 1, 'Not Placed'),
(374, 381, NULL, 'Computer Science and Engineering', NULL, 8.80, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Male', 'ICSE', 91.10, 'CBSE', 65.60, 1, 1, 0, 1, 1, 1, 'Placed'),
(375, 382, NULL, 'Computer Science in Data Science', NULL, 9.29, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 83.70, 'CBSE', 79.90, 1, 1, 1, 0, 2, 0, 'Not Placed'),
(376, 383, NULL, 'Computer Science and Engineering', NULL, 8.40, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Female', 'WBBSE', 73.60, 'CBSE', 84.20, 1, 0, 0, 1, 2, 1, 'Placed'),
(377, 384, NULL, 'Computer Science in AIML', NULL, 9.11, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'CBSE', 99.90, 'CBSE', 65.10, 1, 1, 0, 1, 2, 1, 'Placed'),
(378, 385, NULL, 'Computer Science in AIML', NULL, 7.65, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Male', 'CBSE', 71.10, 'CBSE', 81.10, 1, 1, 0, 1, 2, 1, 'Placed'),
(379, 386, NULL, 'Computer Science in Data Science', NULL, 7.86, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'State Board', 94.30, 'Other state Board', 90.90, 1, 1, 0, 1, 4, 1, 'Placed'),
(380, 387, NULL, 'Civil Engineering', NULL, 7.22, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'WBBSE', 74.40, 'WBCHSE', 82.30, 0, 1, 0, 1, 3, 1, 'Placed'),
(381, 388, NULL, 'Computer Science and Engineering', NULL, 6.50, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'State Board', 82.00, 'Other state Board', 83.00, 1, 1, 0, 1, 4, 1, 'Not Placed'),
(382, 389, NULL, 'Computer Science and Engineering', NULL, 8.62, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'State Board', 83.50, 'ISE', 72.50, 1, 0, 0, 1, 5, 1, 'Placed'),
(383, 390, NULL, 'Information Technology', NULL, 9.32, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Male', 'WBBSE', 88.60, 'WBCHSE', 82.60, 1, 0, 0, 1, 3, 1, 'Placed'),
(384, 391, NULL, 'Electrical Engineering', NULL, 8.89, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'State Board', 73.80, 'BSEB', 70.30, 1, 1, 0, 1, 4, 1, 'Not Placed'),
(385, 392, NULL, 'Electronics and Communication Engineering', NULL, 7.16, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'ICSE', 98.80, 'ISE', 85.30, 1, 0, 0, 1, 2, 1, 'Placed'),
(386, 393, NULL, 'Electronics and Communication Engineering', NULL, 8.15, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Female', 'State Board', 83.90, 'CBSE', 74.90, 0, 1, 0, 1, 2, 1, 'Placed'),
(387, 394, NULL, 'Electronics and Communication Engineering', NULL, 7.50, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 78.70, 'Other state Board', 60.90, 1, 1, 0, 1, 2, 1, 'Placed'),
(388, 395, NULL, 'Computer Science and Engineering', NULL, 5.50, 'UI/UX Design, Figma, HTML, CSS, JavaScript, Design Thinking', NULL, NULL, NULL, 'Male', 'CBSE', 45.00, 'WBCHSE', 45.00, 0, 1, 0, 1, 3, 1, 'Placed'),
(389, 396, NULL, 'Computer Science and Design', NULL, 8.30, 'Process Simulation, Aspen HYSYS, MATLAB, Process Design, Thermodynamics', NULL, NULL, NULL, 'Male', 'WBBSE', 70.00, 'WBCHSE', 82.00, 1, 0, 0, 1, 3, 0, 'Placed'),
(390, 397, NULL, 'Chemical Engineering', NULL, 8.26, 'Java, Python, SQL, Web Development, Cloud Computing, Networking', NULL, NULL, NULL, 'Male', 'WBBSE', 82.30, 'ISE', 68.10, 0, 0, 1, 1, 2, 1, 'Not Placed'),
(391, 398, NULL, 'Information Technology', NULL, 8.06, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'ICSE', 82.40, 'ISE', 82.90, 1, 0, 0, 0, 5, 1, 'Not Placed'),
(392, 399, NULL, 'Computer Science and Engineering', NULL, 7.42, 'Python, SQL, Data Analytics, Machine Learning, Power BI, Statistics', NULL, NULL, NULL, 'Female', 'CBSE', 74.80, 'ISE', 84.90, 1, 0, 0, 1, 1, 1, 'Placed'),
(393, 400, NULL, 'Computer Science in Data Science', NULL, 8.68, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'CBSE', 94.60, 'ISE', 84.90, 1, 1, 1, 0, 2, 1, 'Not Placed'),
(394, 401, NULL, 'Electronics and Communication Engineering', NULL, 9.35, 'Python, Machine Learning, Deep Learning, TensorFlow, Data Analysis, AI', NULL, NULL, NULL, 'Male', 'CBSE', 86.40, 'ISE', 72.30, 1, 1, 1, 0, 5, 0, 'Not Placed'),
(395, 402, NULL, 'Computer Science in AIML', NULL, 7.37, 'AutoCAD, STAAD Pro, Revit, Surveying, Structural Analysis', NULL, NULL, NULL, 'Female', 'WBBSE', 88.60, 'WBCHSE', 65.80, 0, 1, 1, 0, 2, 1, 'Not Placed'),
(396, 403, NULL, 'Civil Engineering', NULL, 9.19, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Male', 'State Board', 97.80, 'ISE', 94.20, 1, 0, 0, 1, 5, 1, 'Placed'),
(397, 404, NULL, 'Computer Science and Engineering', NULL, 8.34, 'Java, Python, C++, Data Structures, DBMS, Web Development', NULL, NULL, NULL, 'Female', 'ICSE', 88.80, 'CBSE', 89.10, 1, 1, 0, 0, 1, 1, 'Not Placed'),
(398, 405, NULL, 'Computer Science and Engineering', NULL, 7.77, 'Power Systems, MATLAB, PLC, Electrical Machines, Control Systems', NULL, NULL, NULL, 'Female', 'State Board', 86.70, 'CBSE', 86.70, 1, 0, 0, 1, 2, 1, 'Placed'),
(399, 406, NULL, 'Electrical Engineering', NULL, 8.85, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'CBSE', 83.50, 'CBSE', 61.10, 0, 0, 0, 1, 1, 1, 'Placed'),
(400, 407, NULL, 'Electronics and Communication Engineering', NULL, 7.89, 'Embedded Systems, VLSI, Verilog, MATLAB, PCB Design, IoT', NULL, NULL, NULL, 'Male', 'ICSE', 99.80, 'ISE', 75.40, 1, 1, 1, 1, 5, 1, 'Not Placed'),
(401, 408, NULL, 'Electronics and Communication Engineering', NULL, 7.89, NULL, NULL, NULL, NULL, 'Male', 'WBBSE', 94.20, 'CBSE', 90.00, 1, 0, 0, 0, 3, 1, 'Not Placed'),
(440, 412, '', '', 2027, 7.00, 'Java,Python,SQL,Javascript', 'resume_412.pdf', '9912694616', '', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('aptitude','technical','coding') DEFAULT 'aptitude',
  `duration` int(11) DEFAULT 30,
  `total_marks` int(11) DEFAULT 0,
  `pass_marks` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tests`
--

INSERT INTO `tests` (`id`, `title`, `description`, `category`, `duration`, `total_marks`, `pass_marks`, `status`, `created_by`, `created_at`) VALUES
(1, 'Aptitude test', 'The pass marks for the test is 25', 'aptitude', 30, 30, 25, 'active', 1, '2026-06-17 16:44:38');

-- --------------------------------------------------------

--
-- Table structure for table `test_answers`
--

CREATE TABLE `test_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_answer` enum('a','b','c','d') DEFAULT NULL,
  `is_correct` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_answers`
--

INSERT INTO `test_answers` (`id`, `attempt_id`, `question_id`, `selected_answer`, `is_correct`) VALUES
(1, 1, 1, 'b', 1),
(2, 1, 2, 'b', 1),
(3, 1, 3, 'b', 1),
(4, 1, 4, 'b', 1),
(5, 1, 5, 'c', 1),
(6, 1, 6, 'c', 1),
(7, 1, 7, 'b', 1),
(8, 1, 8, 'a', 1),
(9, 1, 9, 'a', 1),
(10, 1, 10, 'c', 1),
(11, 2, 1, 'b', 1),
(12, 2, 2, 'b', 1),
(13, 2, 3, 'b', 1),
(14, 2, 4, 'b', 1),
(15, 2, 5, 'c', 1),
(16, 2, 6, 'c', 1),
(17, 2, 7, 'b', 1),
(18, 2, 8, 'a', 1),
(19, 2, 9, 'a', 1),
(20, 2, 10, 'c', 1),
(21, 3, 1, 'b', 1),
(22, 3, 2, 'b', 1),
(23, 3, 3, 'b', 1),
(24, 3, 4, 'b', 1),
(25, 3, 5, 'c', 1),
(26, 3, 6, 'c', 1),
(27, 3, 7, 'b', 1),
(28, 3, 8, 'a', 1),
(29, 3, 9, 'a', 1),
(30, 3, 10, 'c', 1),
(31, 4, 1, 'b', 1),
(32, 4, 2, 'b', 1),
(33, 4, 3, 'b', 1),
(34, 4, 4, 'b', 1),
(35, 4, 5, 'c', 1),
(36, 4, 6, 'c', 1),
(37, 4, 7, 'b', 1),
(38, 4, 8, 'a', 1),
(39, 4, 9, 'a', 1),
(40, 4, 10, 'c', 1);

-- --------------------------------------------------------

--
-- Table structure for table `test_attempts`
--

CREATE TABLE `test_attempts` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` int(11) DEFAULT 0,
  `total_marks` int(11) DEFAULT 0,
  `correct_answers` int(11) DEFAULT 0,
  `wrong_answers` int(11) DEFAULT 0,
  `status` enum('started','completed') DEFAULT 'started',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_attempts`
--

INSERT INTO `test_attempts` (`id`, `test_id`, `student_id`, `score`, `total_marks`, `correct_answers`, `wrong_answers`, `status`, `started_at`, `completed_at`) VALUES
(1, 1, 10, 30, 30, 10, 0, 'completed', '2026-06-17 17:11:15', '2026-06-17 17:12:29'),
(2, 1, 10, 30, 30, 10, 0, 'completed', '2026-06-17 17:19:43', '2026-06-17 17:20:44'),
(3, 1, 10, 30, 30, 10, 0, 'completed', '2026-06-17 17:21:00', '2026-06-17 17:21:47'),
(4, 1, 10, 30, 30, 10, 0, 'completed', '2026-06-17 18:50:57', '2026-06-17 18:51:42');

-- --------------------------------------------------------

--
-- Table structure for table `test_questions`
--

CREATE TABLE `test_questions` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_answer` enum('a','b','c','d') NOT NULL,
  `marks` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_questions`
--

INSERT INTO `test_questions` (`id`, `test_id`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `marks`) VALUES
(1, 1, 'A student\'s marks increased from 400 to 460. What is the percentage increase?', '10%', '15%', '20%', '25%', 'b', 3),
(2, 1, 'A can complete a task in 12 days, and B can complete the same task in 18 days. How many days will they take together?', '6.2 days', '7.2 days', '8.5 days', '9 days', 'b', 3),
(3, 1, 'A shopkeeper buys an item for ₹800 and sells it for ₹960. What is the profit percentage?', '15', '20', '25', '30', 'b', 3),
(4, 1, 'The ratio of boys to girls in a class is 3:2. If there are 30 boys, how many girls are there?', '15', '20', '25', '30', 'b', 3),
(5, 1, 'Find the next number:\r\n\r\n2, 6, 12, 20, 30, ?', '36', '40', '42', '44', 'c', 3),
(6, 1, 'If all programmers are engineers and some engineers are managers, which statement is definitely true?', 'All managers are programmers', 'Some programmers are managers', 'All programmers are engineers', 'All engineers are programmers', 'c', 3),
(7, 1, 'A car travels 240 km in 4 hours. What is its average speed?', '50 km/h', '60 km/h', '70 km/h', '80 km/h', 'b', 3),
(8, 1, 'A bag contains 5 red balls and 3 blue balls. What is the probability of drawing a blue ball?', '3/8', '5/8', '1/2', '2/3', 'a', 3),
(9, 1, 'If CAT is coded as DBU, then DOG is coded as:', 'EPH', 'DPH', 'FQI', 'EPG', 'a', 3),
(10, 1, 'The sales of a company in four quarters are:\r\n\r\nQuarter	Sales\r\nQ1	120\r\nQ2	150\r\nQ3	180\r\nQ4	210\r\nWhat is the average quarterly sales?', '150', '160', '165', '170', 'c', 3);

-- --------------------------------------------------------

--
-- Table structure for table `two_factor_settings`
--

CREATE TABLE `two_factor_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_enabled` tinyint(4) DEFAULT 0,
  `secret_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student','recruiter') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@campus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-06-13 06:05:01'),
(2, 'TCS HR', 'hr@tcs.com', '$2y$10$0Q2e2vf4nuMF8PAZDivZB.X4gK3eT3eXOq6/e8VeHuPxniiTeVXE.', 'recruiter', '2026-06-13 06:27:36'),
(3, 'Infosys HR', 'hr@infosys.com', '$2y$10$PvgyAE59.YM11tro.sfqXeo3uIT4Y3vBS/ZcIC3vrqaDR8JOqJU1C', 'recruiter', '2026-06-13 06:27:36'),
(4, 'Wipro HR', 'hr@wipro.com', '$2y$10$s60wMVwzrqMTbn7otohrbOiV/DyIGc9Crdm6SZaXwCSYivefSIgFG', 'recruiter', '2026-06-13 06:27:37'),
(5, 'Amazon HR', 'hr@amazon.com', '$2y$10$UOVAkrZ4P651BcH1XE8O4uNi4R3Rs/TQgLoVh85DJYLj9Is1WQISi', 'recruiter', '2026-06-13 06:27:37'),
(6, 'Google HR', 'hr@google.com', '$2y$10$Lpx5X/eNRxa58jBPeqh5w.6vwwoERXOZxRkV4oEzsreTixE7bF.G2', 'recruiter', '2026-06-13 06:27:37'),
(7, 'Accenture HR', 'hr@accenture.com', '$2y$10$sYQi5ljoPSjTJGnWxiAlW.NaFXmQNc4PCeeOpqOg0ip8KuHz9FkVC', 'recruiter', '2026-06-13 06:27:38'),
(8, 'Payal Roy', 'payal_roy79@gmail.com', '$2y$10$raPE/MD21YhnSAa11Ab5rOTdXGidTtNKeCIjHpxOB3pIgmYt9in5O', 'student', '2026-06-13 07:42:27'),
(9, 'Shreyoshi Dey', 'shreyoshi_dey13@gmail.com', '$2y$10$9poX7OFpdpSlm6yFoGiiVOEeSwSIctWTANvKwHHbC3jwYok9eAaji', 'student', '2026-06-13 07:42:27'),
(10, 'Rohan Nandi', 'rohan_nandi12@gmail.com', '$2y$10$iDlSXo8Lxs6eCmY8uFGEBeRUppmqVBexg9TLJaK0vKDpfTEx1lpKq', 'student', '2026-06-13 07:42:27'),
(11, 'Smita Agarwal', 'smita_agarwal90@gmail.com', '$2y$10$Cbzs7HrUoCmb4hWp5WSfxeN29lSisnQqBXuvr.4VMj6PUDL9PAPrO', 'student', '2026-06-13 07:42:27'),
(12, 'Samaira Singhania', 'samaira_singhania95@gmail.com', '$2y$10$LdJjM8LiYTsjWbXgwpFPeufAvUZWqikM5b1Cgr.nn/d5aemFA7MUG', 'student', '2026-06-13 07:42:27'),
(13, 'Rakesh Dey', 'rakeshdeyrlo@gmail.com', '$2y$10$71oxfFBmAkgCBh/pXEA0ReCUnjN/vN3sOEXJIzxE4z2q4p/eTCaki', 'student', '2026-06-13 07:42:27'),
(14, 'Dheeraj Thakur', 'dheeraj_thakur88@gmail.com', '$2y$10$xMk4VQ7U1UgGqbK1CwsVfuvUl1xxKjJVut4DTs0M1wi9DzQnoG23G', 'student', '2026-06-13 07:42:28'),
(15, 'Dharmik Chsuhan Chauhan', 'dharmikc22@gmail.com', '$2y$10$jB67BcHZMuYJBm1nCQz1Je/1cBXrO9TdUrR7DffqTpuUEtfy2xc5q', 'student', '2026-06-13 07:42:28'),
(16, 'Suman Sen', 'suman_sen68@gmail.com', '$2y$10$Ppo0H/M/6b2I13/a.3tOmOFHoBp6EHWAtzsX/jKcMA.auVdJc091S', 'student', '2026-06-13 07:42:28'),
(17, 'Sneha Pal', 'sneha_pal5@gmail.com', '$2y$10$NWoNKOZAdPJePj63.h.SLepWinKFx.ybJrGKP3FQsEZxVTMR4roR2', 'student', '2026-06-13 07:42:28'),
(18, 'Mukesh Mandal', 'mukesh_mandal71@gmail.com', '$2y$10$3oF1vTqshCyS.utMfzr6xeA4D65DQkzwEPBw9BR99iodQdk/ztGcW', 'student', '2026-06-13 07:42:28'),
(19, 'Shreya Shukla', 'shreya_shukla17@gmail.com', '$2y$10$/XN2cm.20H9C1EgIr8squeFjNVAK7mjMP5NKhPwkLLOq0dkz/Zebq', 'student', '2026-06-13 07:42:28'),
(20, 'Pranab Bhattacharya', 'pranab_bhattacharya30@gmail.com', '$2y$10$kTUdm7lF0JlKUeC5uklTl.j5RKcCjnxoEFVZQx/vgGp7FdCO.csuK', 'student', '2026-06-13 07:42:28'),
(21, 'Mita Pal', 'mita_pal33@gmail.com', '$2y$10$xC8BJ9.4YewMrUsmQ.uo2.ALSo3b5uwNg33uq2Le7SIcZS8gT5c32', 'student', '2026-06-13 07:42:28'),
(22, 'Kunal Bhowmick', 'kunal_bhowmick27@gmail.com', '$2y$10$g879.teFOF0PW2XpRLYD6.LwXgaPR4n/z4kJnFR25.dMaYqhwtASi', 'student', '2026-06-13 07:42:28'),
(23, 'Amit Dey', 'amit_dey53@gmail.com', '$2y$10$ZR68UAJqO/4IP78qE42y/.o485zsF7sl1MFxuuQZi9by2PeRkIHUW', 'student', '2026-06-13 07:42:28'),
(24, 'Mita Bardhan', 'mita_bardhan92@gmail.com', '$2y$10$3Uo/Z0dtrCFcL4kooOP9O.ubTbG9up9N3S5GrUwzlb7NX/gE4eLue', 'student', '2026-06-13 07:42:29'),
(25, 'Naitik Chatterjee', 'chatterjeenaitik@gmail.com', '$2y$10$JqwhpV5qkXpA5UnbQssafeso4KORaHPyfDV5pXDRSugGMA2KQT55C', 'student', '2026-06-13 07:42:29'),
(26, 'Debanshu Sen', 'debanshu_sen11@gmail.com', '$2y$10$UDRi.gAf64mY55WGIgKqv.ZgGO7.V1dg12b3NWy0T6Aio8RWIv9c2', 'student', '2026-06-13 07:42:29'),
(27, 'Meyenka Rao', 'meyenka_rao10@gmail.com', '$2y$10$1DDUMF0cUXhEoQvxO3HR3.LElLr7YCkXYQf/MM8pvQpyxLd.XrTv6', 'student', '2026-06-13 07:42:29'),
(28, 'Prakash Singh', 'prakash_singh58@gmail.com', '$2y$10$K/VAiit2PBQNQ9YsF3SEVena7urBKeY8BkcIQrHK0n9xx1B0T1Z0S', 'student', '2026-06-13 07:42:29'),
(29, 'Nayanika Choudhury', 'nayanika_choudhury99@gmail.com', '$2y$10$5s3yX7FLbkF03UA5y9bCT.3t8MjPqKJOaj8D3T12vSmzYmW72q6We', 'student', '2026-06-13 07:42:29'),
(30, 'Ayush Kumar Singh', 'ayush_kumar_singh91@gmail.com', '$2y$10$j0.WypTFNnX1rNgGQm9y8OIHLP0ZGE/e66cMusXjie7jvYrQSveZa', 'student', '2026-06-13 07:42:29'),
(31, 'Rahul Yadav', 'rahul_yadav53@gmail.com', '$2y$10$I0qz3JS5YsVSFLsyTZ1c9ux2Jp9y.d9bdQU.Skd.NDlvJopXBP/yy', 'student', '2026-06-13 07:42:29'),
(32, 'Anisha Mitra', 'anisha_mitra38@gmail.com', '$2y$10$ZCGHYGMIzN.CHFIq8rFALeHSj9Q34mWIXa59pQN01Kcej/ZDMh88e', 'student', '2026-06-13 07:42:29'),
(33, 'Shreya Choudhury', 'shreya_choudhury19@gmail.com', '$2y$10$aHF5T.xwwZLxFw2.5MLdSu4WN0R/Fkuj1WKqIWwfCOIa4JNE7NZFu', 'student', '2026-06-13 07:42:29'),
(34, 'Kaushik Bhowmick', 'kaushik_bhowmick78@gmail.com', '$2y$10$kDmtCoIaqxvYSywUEY549ufYX4u9.90211ddnPHZ7kwSXmYqcsTsK', 'student', '2026-06-13 07:42:30'),
(35, 'Naira Patel', 'naira_patel62@gmail.com', '$2y$10$pbXv0PgLDTvDDZwWE0WZxu05fVjNn4qsT8yesCzvdJ1WGbRUKwLde', 'student', '2026-06-13 07:42:30'),
(36, 'Mita Yadav', 'mita_yadav64@gmail.com', '$2y$10$138fvi6JwzQE07kdArvtp.D3thjGJp7ekkR1sc7AvHzjnbmIWzJei', 'student', '2026-06-13 07:42:30'),
(37, 'Aparna Mishra', 'aparna_mishra16@gmail.com', '$2y$10$OPHOlwauKxtX1TOLZMAD7eUuIWIKAEgheS8ArqzUZ5bgGMDZWkIdy', 'student', '2026-06-13 07:42:30'),
(38, 'Prakash Sah', 'prakash_sah94@gmail.com', '$2y$10$lGpXVuz2yRqHAPrSTLTTieesUwMHjAjt0U5CnonPznU.a3w5mixMm', 'student', '2026-06-13 07:42:30'),
(39, 'Ayushi Shukla', 'ayushi_shukla9@gmail.com', '$2y$10$P8YZ0enf2fJYK8cOqeNnbeneyizbtzl2oQ5SFi3XHa3pXdjD6NTAy', 'student', '2026-06-13 07:42:30'),
(40, 'Nishant Dev', 'nishant_dev95@gmail.com', '$2y$10$Tqsbk73ge6k6Ps7r8xBRlugY2el/iuN0lhenQriS6l/nPQgzDe3T2', 'student', '2026-06-13 07:42:30'),
(41, 'Piyali Mishra', 'piyali_mishra80@gmail.com', '$2y$10$g9AYWgr6V1Le.9XNWR6t6uJgCq8yjaN6Mgbht9SdO3duyKN/ZkSVi', 'student', '2026-06-13 07:42:30'),
(42, 'Suvankar Kar', 'suvankar_kar61@gmail.com', '$2y$10$qrygIdmzEzKDeX4TrGwlGud0tUY9872YGxT4xbglTdUD16apRjjU.', 'student', '2026-06-13 07:42:30'),
(43, 'Arpita Chakraborty', 'arpita_chakraborty84@gmail.com', '$2y$10$etxh6TpRdVuE/st//jFvv.v0/y3Wm4361uwz77uABJA9XfTNYWE56', 'student', '2026-06-13 07:42:30'),
(44, 'Vikram Roy', 'vikram_roy16@gmail.com', '$2y$10$R2cfueUFRzunrjqsybWpTuDchfcamXgijhG8h6gko4qSXwouE4bSm', 'student', '2026-06-13 07:42:31'),
(45, 'Anisha Modi', 'anisha_modi58@gmail.com', '$2y$10$MPK1i.pLJgcnbItnl9CdgOI7ZBlkGHizmRuMQrbO49Uxg6.fcUV4a', 'student', '2026-06-13 07:42:31'),
(46, 'Ayush Singh', 'ayush_singh43@gmail.com', '$2y$10$UCz2TSNnqPrY1lhCV7kFheQ/xNrf.u8Mw6MwthX3ihLo2FWe1g6nq', 'student', '2026-06-13 07:42:31'),
(47, 'Shreya Banerjee', 'shreya_banerjee81@gmail.com', '$2y$10$RtqSu0DMmrPuzHtkFV4bUuDBTNVvYasdVNZ5/UagC0hyRaJkLe24e', 'student', '2026-06-13 07:42:31'),
(48, 'Vidya Mishra', 'vidya_mishra44@gmail.com', '$2y$10$T.9pAGN9.TQgM/ptvu36CelAJmSni1LGlFsXTGJYLZCiKqpTCvr0i', 'student', '2026-06-13 07:42:31'),
(49, 'Abhijit Ganguly', 'abhijit_ganguly66@gmail.com', '$2y$10$/x7aWmi1yu/B8TOzBiB/r.3qIc/IiSJuHeWVyMKd2hlLK/lY7U.BG', 'student', '2026-06-13 07:42:31'),
(50, 'Vidya Iyer', 'vidya_iyer45@gmail.com', '$2y$10$jk1NkJym1dGsZrYAvzUfEuUTCsimhQVbrWJINMPa7v4BK.5qSh79K', 'student', '2026-06-13 07:42:31'),
(51, 'Ayesha Banerjee', 'ayesha_banerjee57@gmail.com', '$2y$10$9uQujyaFkcytHR8jd921w.lE1iJS7pbV1oyCsHnHEzkOoF6zw9IMW', 'student', '2026-06-13 07:42:31'),
(52, 'Ayushi Mazumdar', 'ayushi_mazumdar6@gmail.com', '$2y$10$eJAXo1sAxO5BcH7NigBHm.kcJKO2pSf66Vz5w1Zkf.9DmpLYorMPu', 'student', '2026-06-13 07:42:31'),
(53, 'Anupam Pal', 'anupam_pal96@gmail.com', '$2y$10$82kTFNAgOYUSTrSBxmE0TeO2XEy8PCqcb1n22NU3l4zCpMOW9ct2W', 'student', '2026-06-13 07:42:31'),
(54, 'Suvankar Bhowmick', 'suvankar_bhowmick11@gmail.com', '$2y$10$LgQLspEmyCp6dAvo4iVACeiGQY7M5kgDJN210GIxZ7jP/p/mQKpp6', 'student', '2026-06-13 07:42:31'),
(55, 'Madhuri Agarwal', 'madhuri_agarwal98@gmail.com', '$2y$10$J5.KIpn2KMSxSvYzRpWXeOJg1DYELztMJT5UtcBbDBnkr98vzywI6', 'student', '2026-06-13 07:42:32'),
(56, 'Suhana Mukherjee', 'suhana_mukherjee68@gmail.com', '$2y$10$ETDO1nIr9BU2TG7BCXPdb.haRtko8sH/HUmuoe4W0QOXj.MQXcGIi', 'student', '2026-06-13 07:42:32'),
(57, 'Bharat Mahto', 'bharat_mahto54@gmail.com', '$2y$10$pMsRbwnLKMwCgnRL/nbh4uRYjRrHO6By6Zo8/neitF8NbVZZTmepG', 'student', '2026-06-13 07:42:32'),
(58, 'Jaya Singhania', 'jaya_singhania91@gmail.com', '$2y$10$LOLvrO97BCw609Q5PpyfeugHiyc44baASCMGf82sH/8wgu6amDibe', 'student', '2026-06-13 07:42:32'),
(59, 'Anupama Sinha', 'anupama_sinha15@gmail.com', '$2y$10$RIpZOVydhdQkVn1qcxO0vetIQSDy7x0N3sEGOrXRz7mob1KiOmUvi', 'student', '2026-06-13 07:42:32'),
(60, 'Piyali Dutta', 'piyali_dutta68@gmail.com', '$2y$10$6bpw8w6lEZVL0kjE.7Row.zwOu4psJOcBj0/J/Q4.np05HZftc8A.', 'student', '2026-06-13 07:42:32'),
(61, 'Piyali Chakraborty', 'piyali_chakraborty49@gmail.com', '$2y$10$zrJ6K1/0I8RIdWLAQsCmvecXLQ6wN6XxJkLix/QcOrbsKrcq567pS', 'student', '2026-06-13 07:42:32'),
(62, 'Gopal Mahto', 'gopal_mahto11@gmail.com', '$2y$10$WIVMNsulxUpCt1fY4KgvAe/3acaxfsXbDGYRjcTz3q8iE3.mlCYya', 'student', '2026-06-13 07:42:32'),
(63, 'Siddharth', 'amateurz.media@gmail.com', '$2y$10$fCZnIs8ex/4NKfGoh93cu.CGfaHreeai1mid3.gVH9RkbpV1tDxjW', 'student', '2026-06-13 07:42:32'),
(64, 'Vidya Shukla', 'vidya_shukla46@gmail.com', '$2y$10$UYQiWZRf4mLaMgyzZRcH7u94uaSXZNnCSRVa8F/OmsCt2Kzi9oQFy', 'student', '2026-06-13 07:42:32'),
(65, 'Vikas Dev', 'vikas_dev23@gmail.com', '$2y$10$7VvZjft7iKuyy5X2xhKStuwGYDKmdCa6UEw8YtfNq0DE4/.4uDoI2', 'student', '2026-06-13 07:42:33'),
(66, 'Arnab Sengupta', 'arnab_sengupta45@gmail.com', '$2y$10$doLK5GmOEEEdoD68apNd8e.Shh4tdXaz1jA9uFg9NALfoTz2.mFHq', 'student', '2026-06-13 07:42:33'),
(67, 'Rahul Dey', 'rahul.2002.dey@gmail.com', '$2y$10$q1i6L0v16VvzhFD.8EcUiOmWzmTBfWBdteIHonCax7vVVDaOmQSP6', 'student', '2026-06-13 07:42:33'),
(68, 'Ishika Patel', 'ishika_patel12@gmail.com', '$2y$10$Y61KYpDOr/PSU/TczY.l4e2ZuDuQx.kqiDq4nzY9TC4D/PJnpyTVO', 'student', '2026-06-13 07:42:33'),
(69, 'Nikhil Das', 'nikhil_das17@gmail.com', '$2y$10$KhNeNZpgiI7U0FSc3qEGs.n.k2ASUF1ETgrT3v702NLh6bW22oNIO', 'student', '2026-06-13 07:42:33'),
(70, 'Pranab Mazumdar', 'pranab_mazumdar37@gmail.com', '$2y$10$ljRFFXgRexWfQNQzHP2ES.WhYZmL0mgMBHF9LT/4DUNb5AFBQyRBC', 'student', '2026-06-13 07:42:33'),
(71, 'Shubham Kumar Srivastava', 'shubhamsri28101999@gmail.com', '$2y$10$lsfEgWIpKzd7R8djFoU/UueODBnmbC2NYGG/1Or5EN7KrFrW6lXi.', 'student', '2026-06-13 07:42:33'),
(72, 'Nayana Ghosh', 'nayana_ghosh91@gmail.com', '$2y$10$SIi.13LadDI9.hszltly5Or7D0fmoUQ5sn7deilqwBEH4NbrdXkeq', 'student', '2026-06-13 07:42:33'),
(73, 'Sunil Sinha', 'sunil_sinha25@gmail.com', '$2y$10$Gc0xpjfOs5VakOMLnhApIuaS2e.CCn5aXfjMzyHOUs/KTxbi7lVQ.', 'student', '2026-06-13 07:42:33'),
(74, 'Piyali Bhat', 'piyali_bhat42@gmail.com', '$2y$10$BUPa5LyhZ09KzOO5idMsFuR9Jv72.K0kAgaoJJlIrShrExTY8QbFO', 'student', '2026-06-13 07:42:33'),
(75, 'Sanjana Mazumdar', 'sanjana_mazumdar9@gmail.com', '$2y$10$ipFmwFnkx/C4qf4CtIfWF.M76TPMxBWz83kMNUXTQkMLLQ34hzSoS', 'student', '2026-06-13 07:42:33'),
(76, 'Arnav Bhattacharya', 'arnav_bhattacharya14@gmail.com', '$2y$10$yUzB8Cto8d9Ueq04WW0vOevJk5zPc1RUgPsEKsB8CSpqdYOxvbb3S', 'student', '2026-06-13 07:42:34'),
(77, 'Diya Kapoor', 'diya_kapoor58@gmail.com', '$2y$10$e0WMPywtKo58bzQk.RDypedME9Q3nm80ahg2MC.kdnM.jCNmliWE.', 'student', '2026-06-13 07:42:34'),
(78, 'Vinayak kumar', 'vinayak12031521026@gmail.com', '$2y$10$vTm6sGdX212aBzXv59Biq.yq8tst2Y2x5ufd0d2H/je09PtZc2ndq', 'student', '2026-06-13 07:42:34'),
(79, 'Mahenoor Ahsan', 'mahii828113@gmail.com', '$2y$10$S4vKgdNQp2JI8vV1lhQa9eM.bTgiYkUP86yBW1iIS82W4v07ceHdu', 'student', '2026-06-13 07:42:34'),
(80, 'Aishwarya Kar', 'aishwarya_kar82@gmail.com', '$2y$10$1koQ76YSu6s3wFad7xvmzummp/yU4uT18KSzw1tBkT40Yte8tJXHm', 'student', '2026-06-13 07:42:34'),
(81, 'Anisha Jha', 'anisha_jha88@gmail.com', '$2y$10$BQUb62BMpCxrC1xe8fE/du01uxsZYuMR8CjZNf46mQf155aBNao3S', 'student', '2026-06-13 07:42:34'),
(82, 'Ravi kishan mishra', 'mishraravikishan950@gmail.com', '$2y$10$XzHHL0wiUdugmM625RXbbeFc/fxnNEQcP1v8.StJdp89ikYq4xpoK', 'student', '2026-06-13 07:42:34'),
(83, 'Indrajit Mazumdar', 'indrajit_mazumdar31@gmail.com', '$2y$10$Sls5nUJmz1Iq9vSRGZWli.5AW70IwCzX7iGrbifZ5ECy4WsNEobEu', 'student', '2026-06-13 07:42:34'),
(84, 'PANTHA PATIM BHATTACHARYA', 'panthapatim@gmail.com', '$2y$10$FxLCq1oPcu82fqtO42kjweJyj0b6vVB7gYZVasNH8ysXCxey9iCyq', 'student', '2026-06-13 07:42:34'),
(85, 'Ishani Singh', 'ishani_singh44@gmail.com', '$2y$10$5AbHyTBOAX9H6hHDz8SqiOYwy06wOUE.S9OqwO5/BJbd5/CVpARsa', 'student', '2026-06-13 07:42:34'),
(86, 'Aishwarya Mukherjee', 'aishwarya_mukherjee12@gmail.com', '$2y$10$p/RxeCkKbju3GqZwwkiZwuscfh8uewpBkBGxYLIbSfA95pngDGaGq', 'student', '2026-06-13 07:42:34'),
(87, 'Nikhil Kumar', 'nikhil_kumar94@gmail.com', '$2y$10$AWX8wol9/4CBnhxXmKhft.fz415pDBT2S00xAu4M.6rbGKo.WEEvy', 'student', '2026-06-13 07:42:35'),
(88, 'Bikram Sarkar', 'bikram_sarkar25@gmail.com', '$2y$10$VI4MYFfm0mglXAnxbkIm9eC0109GByRFFQuKvDwnJwSUYgR187gca', 'student', '2026-06-13 07:42:35'),
(89, 'Trisha Singhania', 'trisha_singhania81@gmail.com', '$2y$10$lzoNDGgRobDSnoUrvIdQtOglJeshYHsb4rmMFFiwH5S4nrUbPF6kq', 'student', '2026-06-13 07:42:35'),
(90, 'Indira Iyer', 'indira_iyer26@gmail.com', '$2y$10$g8QfSTA8fMlJNXqpOksvb.mWTtSQxHOYUKOdOTm.C7DUo8zi/c//a', 'student', '2026-06-13 07:42:35'),
(91, 'Ishika Sarkar', 'ishika_sarkar75@gmail.com', '$2y$10$Gq32EWbUQyJRHX9SdF4SDe1YkKHPhGfYFLRE5j.IRQuwIdXudR0Za', 'student', '2026-06-13 07:42:35'),
(92, 'Arjun Pathak', 'arjun_pathak34@gmail.com', '$2y$10$q.w0of9LoAL8BQoaKSqHY.lx3yzhBx2XO8jcBVOhD4km942307kZe', 'student', '2026-06-13 07:42:35'),
(93, 'Soma Singhania', 'soma_singhania43@gmail.com', '$2y$10$UJ4Bj.gkYNxo2Zn9KtoceuqczfSpOxCp0EFQJgzZVP9qfHLbHCgYG', 'student', '2026-06-13 07:42:35'),
(94, 'Ishika Rajput', 'ishika_rajput89@gmail.com', '$2y$10$MYf0EiYO46aNc/dqyLjTOOueJ3Ddb6wjmOJtIM4Z7L6lbfqOxIbpW', 'student', '2026-06-13 07:42:35'),
(95, 'Tithi Bera', 'tithi_bera57@gmail.com', '$2y$10$rVvXYoaUBBcgcnMBXqBDDufD.WPH0PErcH16sYZaP36yb9/X7pyZS', 'student', '2026-06-13 07:42:35'),
(96, 'Suvankar Choudhury', 'suvankar_choudhury10@gmail.com', '$2y$10$fsO7HvvRXIDMNhbg50vhTOj4ziGysZcxeAp5EXeRnI0m6fB3QKkLa', 'student', '2026-06-13 07:42:35'),
(97, 'Mukesh Singh', 'mukesh_singh99@gmail.com', '$2y$10$Sh6YLrf3EiQqAMwKTzjLW.wBHQ8lbFmdPJYvdGgd/cJkdgU.jEfZC', 'student', '2026-06-13 07:42:35'),
(98, 'Nayanika Kumari', 'nayanika_kumari75@gmail.com', '$2y$10$zXBn5NmCMadFDDycG/H6C.VkZmq9MSjbAvPzy922JtetvMq6ogDo2', 'student', '2026-06-13 07:42:36'),
(99, 'Nisha Rao', 'nisha_rao77@gmail.com', '$2y$10$CH3sD8UvhX2ipbLtAhD/Qul.VasUWEAoevEwOhmKT9EO9rSLftV/K', 'student', '2026-06-13 07:42:36'),
(100, 'Kavya Chauhan', 'kavya_chauhan93@gmail.com', '$2y$10$Sj8pdeUT7JNSZzsuwNqOzedi9GFtemaHAuGHtCxP64f3kkewTOqva', 'student', '2026-06-13 07:42:36'),
(101, 'Ragini Gupta', 'raginigupta523@gmail.com', '$2y$10$XVXymwiyigBNQH8EoIIU/ukomCUxZILctB3yKzA5oHNq7bR6tt4sa', 'student', '2026-06-13 07:42:36'),
(102, 'Soma Mandal', 'soma_mandal55@gmail.com', '$2y$10$TSXZGTrZKUU2dgu5.z7sRut6W6VcsnqTnXFASWPXpHiTjVxFZrdlC', 'student', '2026-06-13 07:42:36'),
(103, 'Suvankar Banerjee', 'suvankar_banerjee38@gmail.com', '$2y$10$HwYp0lH7qFHTgkncnrf19.E7qa2msZSIaXb7D6Fad5Ls4wP4oWdIm', 'student', '2026-06-13 07:42:36'),
(104, 'Dev Dey', 'dev_dey63@gmail.com', '$2y$10$Pk5Wwl1t8wAVpPRJS5mky.64Yl7KIgCX2x7ebkKd9S4eHBlWVKNUi', 'student', '2026-06-13 07:42:36'),
(105, 'Dev Bhowmick', 'dev_bhowmick64@gmail.com', '$2y$10$ZMXDtofzW4HSBtUWRHfjSO8WKwg9iq9p7Yb1jleUxeXbQ.kL/TpMe', 'student', '2026-06-13 07:42:36'),
(106, 'Ananya Modi', 'ananya_modi37@gmail.com', '$2y$10$YV9RicrsXfBjDunsCUICL.WPMlaKQXHebtJPT8vrOnxmG7uqHp/c6', 'student', '2026-06-13 07:42:36'),
(107, 'Debika Bhattacharya', 'debika_bhattacharya89@gmail.com', '$2y$10$inQzMZAt0jFaTbzoXJwvb.k/n/ViX4LWbHkxQyMseFCABugoL0i2q', 'student', '2026-06-13 07:42:36'),
(108, 'Piyali Chatterjee', 'piyali_chatterjee88@gmail.com', '$2y$10$vY5zuOfEHtHh40A9Zd5sKONwLmal/mQKD0GUooEu5OdzZRn4LqTGK', 'student', '2026-06-13 07:42:36'),
(109, 'Tanushree Sharma', 'tanushree_sharma2@gmail.com', '$2y$10$adgjQ7BfOL1Ncsm90xQ7FOhzwhUfvd7OA3Eiqr5PgoJykcf4AAQk.', 'student', '2026-06-13 07:42:37'),
(110, 'Susmita Dey', 'susmita_dey53@gmail.com', '$2y$10$joNcaptdWZ4pXJMhpr4n1uMPA47Tu7/B8dy8LE97.YH.rIriQ8qD6', 'student', '2026-06-13 07:42:37'),
(111, 'Ishika Dutta', 'ishika_dutta71@gmail.com', '$2y$10$I9EfpKoFaChLwv/nLofuAeVj0SisQ33VD/VHvgdko.teHR9gAUeu6', 'student', '2026-06-13 07:42:37'),
(112, 'Amit Mahto', 'amit_mahto68@gmail.com', '$2y$10$8ZcssF7Fq/Vpf5cnq691ZuHK9QV48.CHBheaXHI5kDXhCjf/GcFWq', 'student', '2026-06-13 07:42:37'),
(113, 'Sunidhi Jha', 'sunidhi_jha16@gmail.com', '$2y$10$cRw6kY8OPN1nD4CmcmEABuJUDHY/3bfv6y4vDODkHq7XAFELfCKza', 'student', '2026-06-13 07:42:37'),
(114, 'Rajesh Rana', 'rajesh_rana71@gmail.com', '$2y$10$fYR.y6VqTbNwN.RAPPgAce35c7ASvCWzPuI3loeopXSs1q6zfA3vC', 'student', '2026-06-13 07:42:37'),
(115, 'Debjit Pal', 'debjit_pal41@gmail.com', '$2y$10$/9/2fnhcI77FCSpob8Vb.ObC059BEW9NG3VmXTZKRUIM.ijnHBlZu', 'student', '2026-06-13 07:42:37'),
(116, 'Khusi Kar', 'khusi_kar6@gmail.com', '$2y$10$J0dXwpZyoWYIl7fYe0BWYu1sYhN7rdjOI3dv5JfhwHdaC3wSPyQ2q', 'student', '2026-06-13 07:42:37'),
(117, 'Anupama Singh', 'anupama_singh46@gmail.com', '$2y$10$lHgFny7baQuFjWueIo77V.a1vHKYCCfdX5jorPFMEttJ6lwc0J/Qm', 'student', '2026-06-13 07:42:37'),
(118, 'Krishanu Roy', 'krishanuroy733@gmail.com', '$2y$10$AgP7mAVnSWuIw3.1zG.BWuq4hS/tm9qBgnAHf9RRCm/wTzUtxSxym', 'student', '2026-06-13 07:42:37'),
(119, 'Trisha Mishra', 'trisha_mishra98@gmail.com', '$2y$10$P1eqzRAM5UjsF2BkPj/OFuZIEygNdy6j6zEdenikIqWzanzl5VEey', 'student', '2026-06-13 07:42:37'),
(120, 'Debjit Biswas', 'debjit_biswas73@gmail.com', '$2y$10$RBt5Rk78DTNgTDVvl8ba/.B5m9vWKub71l30K5ekMorjSk33Vy57O', 'student', '2026-06-13 07:42:38'),
(121, 'Subhash Mukherjee', 'subhash_mukherjee57@gmail.com', '$2y$10$TqePgbVsVJ6monom8a4MeOA0s.rDgZKoqips6QUycmU7t2KQPHXn.', 'student', '2026-06-13 07:42:38'),
(122, 'Anupam Nandi', 'anupamnandi777@gmail.com', '$2y$10$Ho5Nzrd8Cm3yE7vB8.9fh.H.NST3AdoYnI/lrOBOxwjLl1KqqRHya', 'student', '2026-06-13 07:42:38'),
(123, 'Soma Kapoor', 'soma_kapoor10@gmail.com', '$2y$10$78d/sADkNjLWxect3ANGwum29hx9KZrqUMGX5fvKVOXayIKmrTDTe', 'student', '2026-06-13 07:42:38'),
(124, 'Anupama Mazumdar', 'anupama_mazumdar80@gmail.com', '$2y$10$JKTXaI/azT35NuBWFd.gvOsgdGN4ZpXxTsAGt6rIcLJ65DLP1BO92', 'student', '2026-06-13 07:42:38'),
(125, 'Indira Pal', 'indira_pal59@gmail.com', '$2y$10$5TOcAfn0u5a/srBRgwP7s.vQSQ8SrACjvt2hEFWoR2RWp0dgU8Qsi', 'student', '2026-06-13 07:42:38'),
(126, 'Yeddula Janardhan Reddy', 'yjanardhanreddy111@gmail.com', '$2y$10$5pLxPItMn9oEa14KUSBy7OCD/r6z8l.JFZ3TtvzKTJ9zjJeYUofrO', 'student', '2026-06-13 07:42:38'),
(127, 'Ankit Choudhary', 'ankit_choudhary86@gmail.com', '$2y$10$nurVQ8PkerkoJcknXyw/2OATDJ5fEw3Uk3rIc1rWlf.8JWKCwR4me', 'student', '2026-06-13 07:42:38'),
(128, 'Jaya Pandey', 'jaya_pandey78@gmail.com', '$2y$10$TQU8aUqRRglc2P/MSnjD3.hOqVv4QlpFiuIKN3K4i4Zhjv7PHPr.W', 'student', '2026-06-13 07:42:38'),
(129, 'Tapas Garai', 'tapasgarai739@gmail.com', '$2y$10$TyQTGI594CLhP1pKKn8iounN5e4yP8YjYdln2fScrewrFGOEtaLou', 'student', '2026-06-13 07:42:38'),
(130, 'Kaushik Mukherjee', 'kaushik_mukherjee31@gmail.com', '$2y$10$KPjWg7FbIZrxGQFbw6J6.O25tMJPNkjxseqH1gwVYbmDkcuOAk9cq', 'student', '2026-06-13 07:42:38'),
(131, 'Piyali Bhattacharya', 'piyali_bhattacharya78@gmail.com', '$2y$10$da1kjlz0qMmksjRHKf4YLenESyKgqx1BftTb4fRWFp0OGYSPAn0oi', 'student', '2026-06-13 07:42:39'),
(132, 'Smita Shukla', 'smita_shukla34@gmail.com', '$2y$10$Sp7jJ5UR0n7MQYIFD.5FpeR5uvlQqNtzVQi6NGdmI/8iab7NBItNO', 'student', '2026-06-13 07:42:39'),
(133, 'Joy Ghosh', 'joy_ghosh41@gmail.com', '$2y$10$mEffGC.ILKec2zKexdfHi.WoEV6eNlBhhShr/2dPEjqvgNfa0lJI2', 'student', '2026-06-13 07:42:39'),
(134, 'Kavya Biswas', 'kavya_biswas89@gmail.com', '$2y$10$IbyatZsscPNulCaLSpq/JevS4jjmqB3i4HAlQqcR3JXYcZuHLyWOi', 'student', '2026-06-13 07:42:39'),
(135, 'Saurabh Choudhary', 'saurabh_choudhary98@gmail.com', '$2y$10$HtKR7BRc8nX.0t.rxAecd.6PK65o7o2uymwFcoUJjaT99mvtsgmXe', 'student', '2026-06-13 07:42:39'),
(136, 'Shubham Choudhary', 'shubham_choudhary62@gmail.com', '$2y$10$DkSM/rM4ACl6UEF2VduLOOgeqQ2JDO5hU4p0bHZgbbL7nuV3uHoT6', 'student', '2026-06-13 07:42:39'),
(137, 'Arnav Pal', 'arnav_pal91@gmail.com', '$2y$10$zXXGaGAoQAzxfcpdZtSEMeIIJn0agxO7hfbDjIo4llbVUiO2DbSTu', 'student', '2026-06-13 07:42:39'),
(138, 'Riya Mitra', 'riya_mitra24@gmail.com', '$2y$10$PBVBd7NKayEsqTUlUydSIumSHeWNxPHcETE1R8jNDMaG.5g4kd.Gi', 'student', '2026-06-13 07:42:39'),
(139, 'Aparna Srivastava', 'aparna_srivastava87@gmail.com', '$2y$10$HnHe54Ts/KqlqoTwwFoSzOzfYF7VkcDnQeGrNkgjcuYK7dDWuPwjG', 'student', '2026-06-13 07:42:39'),
(140, 'Ujjal Mitra', 'ujjal_mitra69@gmail.com', '$2y$10$yn4Pi.URhTj5Q.9.YoIlbe33Cx7sYnmLAQApH8zhZZim9dEi0nsyy', 'student', '2026-06-13 07:42:39'),
(141, 'Riya Banerjee', 'riya_banerjee31@gmail.com', '$2y$10$09jaV1EMyr5UYAOOFGQxwOfINah4Hqkxqu6KLiFHlmsreOtuZVYEi', 'student', '2026-06-13 07:42:40'),
(142, 'Rakesh Thakur', 'rakesh_thakur16@gmail.com', '$2y$10$kFZmtEuAnmL1ElW1l9/MV.OZ0zOVVes313GRGcD0MLh3YCqzb6f7C', 'student', '2026-06-13 07:42:40'),
(143, 'Meyenka Verma', 'meyenka_verma92@gmail.com', '$2y$10$r9Wf8WycCaOUTd82IY3eNeV1kw8kf9LZ/TPGzlfvORpv1Fd72z5GC', 'student', '2026-06-13 07:42:40'),
(144, 'Shreya Mahato', 'shreya_mahato48@gmail.com', '$2y$10$sLrjx6xQwkN2haRQif4YQ.NJwfUgMtq2.PJ56YgN1XtHbZ34QTSpi', 'student', '2026-06-13 07:42:40'),
(145, 'Nayana Gupta', 'nayana_gupta76@gmail.com', '$2y$10$pyL85E75bn3Pm.hWeZXmNeHl5qQZGAvNy/1fkA6n0fEF8b52qYJcm', 'student', '2026-06-13 07:42:40'),
(146, 'Subham Ghosh', 'ghoshsubham778@gmail.com', '$2y$10$pnI/elcAeDdjfwxIp0Oh3.T.rbfcOUeghshq7.zHXZnFpNcIb5tpW', 'student', '2026-06-13 07:42:40'),
(147, 'Rohan Chatterjee', 'rohan_chatterjee73@gmail.com', '$2y$10$G1kthtIWD/1XAidrlG2f2uqnFVcoarACN6PEHnSe.dhyZP9GA39qm', 'student', '2026-06-13 07:42:40'),
(148, 'Gopal Das', 'gopal_das52@gmail.com', '$2y$10$o0FKMZ.lp0Pdr901RpWqDOuIoDHWRP7TjVwTTVTyVKArpucyHpGWS', 'student', '2026-06-13 07:42:40'),
(149, 'Rahul Mandal', 'rahul_mandal28@gmail.com', '$2y$10$5Y0I9et1wU.sJhkiB5Ll7OH8emGjsYidl3Xs0p/vhQ0fbH7JbB8da', 'student', '2026-06-13 07:42:40'),
(150, 'Anand Mahto', 'anand_mahto91@gmail.com', '$2y$10$sANC3a6pPT.hRpTu5ljgF.QwbnjnzEf21oxpH.zzlLSnGq8GqFlTG', 'student', '2026-06-13 07:42:40'),
(151, 'Debanshu Ganguly', 'debanshu_ganguly14@gmail.com', '$2y$10$.Re3RmLqzJ3BHO/0yzW7nemQQp4C4SvK2ZXRIHmS8HPzw7AHAVdRC', 'student', '2026-06-13 07:42:40'),
(152, 'Debika Choudhary', 'debika_choudhary46@gmail.com', '$2y$10$J3t57iUgHBCZTYhhFnZ2f.wwdiu6iJ84KxWgbL5S2x4AP9y/E3YDe', 'student', '2026-06-13 07:42:40'),
(153, 'Samik Mahato', 'samik_mahato28@gmail.com', '$2y$10$CAffUCaKHVw4PMlgMZxNOOFPVwDiAQQ2D5JQgroOeNTm6du70vUPi', 'student', '2026-06-13 07:42:40'),
(154, 'Ankita Bhuiyan', 'ankita_bhuiyan57@gmail.com', '$2y$10$2bnoJnmw86UZWslqq7mkO.4EhI4H93WCTgDpPnGx.bAodkTzSl9Bu', 'student', '2026-06-13 07:42:41'),
(155, 'Jaya Mazumdar', 'jaya_mazumdar74@gmail.com', '$2y$10$DGTkP9qgNrMQdA9iTNvldOmOr7/fztuu24Oya2qgbfoNC8rAoPSwy', 'student', '2026-06-13 07:42:41'),
(156, 'Tanushree Chatterjee', 'tanushree_chatterjee8@gmail.com', '$2y$10$U3m0JWjhpahOvxkMCf35AePnQOHLMYfr1dDCDfBdJacYXTyh9Dap.', 'student', '2026-06-13 07:42:41'),
(157, 'Tanushree Ganguly', 'tanushree_ganguly17@gmail.com', '$2y$10$8t/lxzhw7fKqQNCOOckHCuKgb.c8BGGuFo8d6rYOfh6KXthEx.Mx2', 'student', '2026-06-13 07:42:41'),
(158, 'Ritika Pandey', 'ritika_pandey5@gmail.com', '$2y$10$q2Zi1rbpzV6nRJNdvcaGg.IWioBZ5T12Einf4A.x.orIo/58TAwXa', 'student', '2026-06-13 07:42:41'),
(159, 'Rohan Rana', 'rohan_rana72@gmail.com', '$2y$10$QtYjRh2FMJPDLEDz7SzGq.mCS7Jxn.L/cdw9O7w80U7vh93OplCWi', 'student', '2026-06-13 07:42:41'),
(160, 'Tanushree Mandal', 'tanushree_mandal24@gmail.com', '$2y$10$Qbaay42hdGNETSjV3Cp.4.nmiLuQ4cxSrpVbpdprU1EU4XLXhPZtu', 'student', '2026-06-13 07:42:41'),
(161, 'Bharat Sharma', 'bharat_sharma46@gmail.com', '$2y$10$ifmJHeo8n/pp6LgBt./yu..3vxF6qjnk1x8OjJY766Yo5Vyp.m/oq', 'student', '2026-06-13 07:42:41'),
(162, 'Arpita Biswas', 'arpita_biswas29@gmail.com', '$2y$10$Kqdi.sNH7pBqUlFbZox5ue00xmNdEmeMcsjWJJY50zyHmwmv.6YKW', 'student', '2026-06-13 07:42:41'),
(163, 'Rajesh Mandal', 'rajesh_mandal97@gmail.com', '$2y$10$f6MaQ8WoJ78uZu9QTxQZwOeRIE6EFxWFJQZjTJjoD0pO2vYpWdfFW', 'student', '2026-06-13 07:42:41'),
(164, 'Gopal Sah', 'gopal_sah27@gmail.com', '$2y$10$auhr72Ix/qGg.dc9HK6D7eZ/dg0mZtUaXppz6/LM12UulqBXGkEde', 'student', '2026-06-13 07:42:42'),
(165, 'Sayyed Shifa Imdadulla Hussaini', 'shifasayyad032@gmail.com', '$2y$10$rnbF5DwCOn.7/PCQ2yTSpuMyAZsYoYu3HVdWD2iGj9lLflnFoDUje', 'student', '2026-06-13 07:42:42'),
(166, 'Susmita Ghosh', 'susmita_ghosh64@gmail.com', '$2y$10$Lzf00K/voxR.uDXA6..EYOHmwZRtM.fGzCVU4fiKbX2z0LOTLotwK', 'student', '2026-06-13 07:42:42'),
(167, 'Animesh Mahata', 'imanimesh1210@gmail.com', '$2y$10$EYLM6naHT62MGTtFWP4WQuLhJwvJT9WcGK2oHU1E4VTIKz9hjK1kW', 'student', '2026-06-13 07:42:42'),
(168, 'Rohan Kumar Rai', 'rohan_kumar_rai22@gmail.com', '$2y$10$AVVyJW3A4GQwfGAovEsZf.k3c.xlPeRmfDy4hsTIW3.Csi4EmqIxG', 'student', '2026-06-13 07:42:42'),
(169, 'Kavya Iyer', 'kavya_iyer77@gmail.com', '$2y$10$yXQppQlOcA96RvqGPkao1uMEe25MBalPwLcKxvBCBHI2ORXYR0ZJi', 'student', '2026-06-13 07:42:42'),
(170, 'Ujjal Bardhan', 'ujjal_bardhan9@gmail.com', '$2y$10$PBmG8kjyfICbQ.9Hsh5OtO1tP5fZSyekEi95jI1OitGBft6BFap/u', 'student', '2026-06-13 07:42:42'),
(171, 'SAMIK MANDAL', 'samikmandal9@gmail.com', '$2y$10$h.MCAvUIW4CPGCGXLWNDdODk29rzmWKcxfPACUAgSy5jd3RKdva.W', 'student', '2026-06-13 07:42:42'),
(172, 'Nayana Gupta', 'nayana_gupta89@gmail.com', '$2y$10$2eOAjkT.A.iYtruMBTzqW.KskDI/A3uDk0nzMYzVp.gfXD4/MAksm', 'student', '2026-06-13 07:42:42'),
(173, 'Rajesh Sharma', 'rajesh_sharma10@gmail.com', '$2y$10$apuaUOC6aRnsHqWMRFPg4OvdJluZJkiwDJhgM3QkJ2sZJuJeiWhX2', 'student', '2026-06-13 07:42:42'),
(174, 'Dhrubojyoti Saha', 'dhrubosh@gmail.com', '$2y$10$BQtDPUC6tH2oTIpXRBbvU.1go7D5d4iuA5.8aj3QTRw3pH0V4Cekq', 'student', '2026-06-13 07:42:42'),
(175, 'Urmi Bera', 'urmi_bera90@gmail.com', '$2y$10$QVTrkhzgafBPv.hrYPVwgeMlObMLzodPrErD5bs6smf.L9FfwxyeC', 'student', '2026-06-13 07:42:43'),
(176, 'Dheeraj Kumar', 'dheeraj_kumar80@gmail.com', '$2y$10$oQB2Rd.6QlK5aNMbKYynxetE6QrxXvTBiQm/tEWdBuSk10e9JpxQ6', 'student', '2026-06-13 07:42:43'),
(177, 'Riya Sinha', 'riya_sinha96@gmail.com', '$2y$10$hGVP8H/3UmVmv6bbeo.RcuHIwj9Kgcch.gYTb0SObAjZSGb6NYdA.', 'student', '2026-06-13 07:42:43'),
(178, 'Samik Dutta', 'samik_dutta27@gmail.com', '$2y$10$MPF7kPYLdE9/m.w4AP4ozuNmAnzLvCrh./8.Yk8Hk89fFBsF6GT7e', 'student', '2026-06-13 07:42:43'),
(179, 'Radha Kumari', 'radha_kumari58@gmail.com', '$2y$10$9mUVNjTNEvIbVvLDVUmy7.KsfqYMMHtsNcMZHz7ngKsOsnNZQF4l.', 'student', '2026-06-13 07:42:43'),
(180, 'Ritika Choudhury', 'ritika_choudhury18@gmail.com', '$2y$10$ZVYVyT9Ldl4r3pg.mfzhqup4jo2E30Am2Zn/LDcFL2ZxEWXbjV7si', 'student', '2026-06-13 07:42:43'),
(181, 'Ipshita Bhattacharya', 'ipshita_bhattacharya60@gmail.com', '$2y$10$modI2mJHwV2gyZWCBg2aWeXpgs6W6CD..ROFsTPUkHBCvZ9jo6TQy', 'student', '2026-06-13 07:42:43'),
(182, 'Sunil Garai', 'sunilgarai210@gmail.com', '$2y$10$HzxbqVmdTx0C9HqsOOYYMuFvqp.qtY0Lq.dXY5p.VJF5jAUMalcqy', 'student', '2026-06-13 07:42:43'),
(183, 'Pooja Chauhan', 'pooja_chauhan10@gmail.com', '$2y$10$IXbn.EFtzs4K0cmN4Cbhreaan7JIStNkp7sf8ZA.fguGXr0QJ3DIO', 'student', '2026-06-13 07:42:43'),
(184, 'Anisha Ghosh', 'anisha_ghosh17@gmail.com', '$2y$10$cZdCzQLIH3lvLixYZW356.PV6ZXjZVMYCAfn/qHt3.0MOYOZJsN2K', 'student', '2026-06-13 07:42:43'),
(185, 'Milan Kamilya', 'milankamilya5501@gmail.com', '$2y$10$kXn9Oa62pzhQiuWtO.15Ae3q9WhBz1e5vwKCYoDHdyz/VcSq4BHmy', 'student', '2026-06-13 07:42:43'),
(186, 'Mita Dutta', 'mita_dutta42@gmail.com', '$2y$10$SjsbEaIoFuRDFtXQ.NxirOMX6kOj9DFL4dG0abh1/3sw9.kp.zJ7y', 'student', '2026-06-13 07:42:43'),
(187, 'Nishant Mandal', 'nishant_mandal90@gmail.com', '$2y$10$gwIjy9cFaNsbTkC3XyEj3.s7FVq1n8gJJ92SR3MiYRaFxjhQnXvIa', 'student', '2026-06-13 07:42:44'),
(188, 'Debika Mazumdar', 'debika_mazumdar69@gmail.com', '$2y$10$0bqDeu1vGKLZqLmYD6RyCeOJ15P3gMeuIWCbJGp/olXv4FEny9z2e', 'student', '2026-06-13 07:42:44'),
(189, 'Neeraj Verma', 'neeraj_verma56@gmail.com', '$2y$10$3kpjN1coICXPKfH3wtWoYuuYR.fLyicKOrDGm3Ih/Bnw2lPHBgAUe', 'student', '2026-06-13 07:42:44'),
(190, 'Payal Sengupta', 'payal_sengupta31@gmail.com', '$2y$10$VnMEH6O6kv5mazlu0WnmGe4bSFUDbbEvySu/FaczeICxg69V.mohy', 'student', '2026-06-13 07:42:44'),
(191, 'Arnav Sen', 'arnav_sen56@gmail.com', '$2y$10$UeJcumdH.2apkfDF7KdjCuW/G40T1phxIxUxdVsInNCiLFhTDQSsu', 'student', '2026-06-13 07:42:44'),
(192, 'Nikhil Rana', 'nikhil_rana90@gmail.com', '$2y$10$mSWHCOgcTT9YoO7lICrfQ.s9qCE2TwMSwlFMjjIJEuCOzZuJA0R9W', 'student', '2026-06-13 07:42:44'),
(193, 'Anupam Mitra', 'anupam_mitra99@gmail.com', '$2y$10$8rgYMEWP164a4qIwAovf6eeExvjVkCxbXNO7qtDTakdLZAKzFbj8q', 'student', '2026-06-13 07:42:44'),
(194, 'Prakash Pandey', 'prakash_pandey63@gmail.com', '$2y$10$tlFhlqXoBiKsfV1ABP1XjOtEUP7aGX3e7YP3lIT/vaPuN/D8LzNPK', 'student', '2026-06-13 07:42:44'),
(195, 'Abhirup Mazumdar', 'abhirup_mazumdar30@gmail.com', '$2y$10$JtfUWEWUOXDDX0xefuLHLuFSdTOxeeNGIukRZA/zY2Vs/I89KV1Yu', 'student', '2026-06-13 07:42:44'),
(196, 'Bikram Pal', 'bikram_pal7@gmail.com', '$2y$10$CZBddOAlU8YMIf/uORmRZ.uzKPt3YUfh5eJKNWzJnRYzM1m1TlQw2', 'student', '2026-06-13 07:42:44'),
(197, 'Rajiv Mandal', 'rajiv_mandal78@gmail.com', '$2y$10$svGri4eD34MdJUYCRMLZEu7seq49AsdO1jLfWm3k5neRH28XJgeHC', 'student', '2026-06-13 07:42:44'),
(198, 'Amit Sinha', 'amit_sinha11@gmail.com', '$2y$10$T./WSaOWjYQLxZlergcMN.YwWxYoA5iTVxhKkbUVN7f4nSwdHPWuS', 'student', '2026-06-13 07:42:44'),
(199, 'Sunil Singh', 'sunil_singh27@gmail.com', '$2y$10$UwOJND.GFMn7sjOVdtadWuEAKtSfnbUBZ6VFHOueiJZI1y4ys6By2', 'student', '2026-06-13 07:42:45'),
(200, 'Ranjan Giri', 'ranjangiri8500@gmail.com', '$2y$10$2cHZMY.9rxTRIwZwl3IxgOp5BapPgMUJF41UIcOHUxs6uDHTeu7pC', 'student', '2026-06-13 07:42:45'),
(201, 'Ujjal Sengupta', 'ujjal_sengupta98@gmail.com', '$2y$10$IfQK56iHQxealsGogcbQ1Oudo39DU/IpMCtr2y2xtU5NLk5u0VrRG', 'student', '2026-06-13 07:42:45'),
(202, 'Sohini Mishra', 'sohini_mishra17@gmail.com', '$2y$10$mHVh12KFxrz9.2Mjgw/iYeNpRVgqzaBsU1yIFgwpULa9k9CSXOCru', 'student', '2026-06-13 07:42:45'),
(203, 'Jaya Mukherjee', 'jaya_mukherjee94@gmail.com', '$2y$10$wMKpZDk.LXXpoDM/k5ItauccBy8x28sPkJeiJ79OYwzq..NnnAc4O', 'student', '2026-06-13 07:42:45'),
(204, 'Suvankar Dey', 'suvankar_dey96@gmail.com', '$2y$10$X33gcf/apa7B.qYlAwcpo.Zd/ARUX..kdgasMuWwMY2tAr8vrE2Se', 'student', '2026-06-13 07:42:45'),
(205, 'Indrajit Chakraborty', 'indrajit_chakraborty6@gmail.com', '$2y$10$C0UguW0f5DTxLMjfJ5MekeNg8GsWUV0MhJ8md30r4rhD44IwxtizS', 'student', '2026-06-13 07:42:45'),
(206, 'Sneha Kumari', 'sneha_kumari79@gmail.com', '$2y$10$/fuSOdQjqsmx1iIYX1crM.6YIvPTl1U8tEAb74.Qa1DX2.0p4h2HO', 'student', '2026-06-13 07:42:45'),
(207, 'Ananya Choudhary', 'ananya_choudhary92@gmail.com', '$2y$10$P9KTeG6t.JyJAHfftMw0Xu62JPZds6rG/HGO36OAKnvdWFuHuDOjK', 'student', '2026-06-13 07:42:45'),
(208, 'Debika Chauhan', 'debika_chauhan67@gmail.com', '$2y$10$jdF6QX0xdgYtqgkhgNDJneQoVwF.fNQhj0guYL/BgMj5RGXjIHMZ.', 'student', '2026-06-13 07:42:45'),
(209, 'Mita Bera', 'mita_bera69@gmail.com', '$2y$10$j2dM6AWbL2QzMax2FKgJc.VitUT6djwhbFhA1wIOqfsSa7lNetG6G', 'student', '2026-06-13 07:42:45'),
(210, 'Sunil Sharma', 'sunil_sharma33@gmail.com', '$2y$10$0GYNVAsOGSY9sHfK0VvYO.YnpEz.AfiVr7ZoNbvuf4VT.TPOaBf6O', 'student', '2026-06-13 07:42:46'),
(211, 'Koushiki Das', 'koushiki_das41@gmail.com', '$2y$10$3yZxE5HUZs.QkOLrLfEaFOfftNx.3EWbsrBQ0LC.fX.m9BFd97OnW', 'student', '2026-06-13 07:42:46'),
(212, 'Nikhil Chauhan', 'nikhil_chauhan98@gmail.com', '$2y$10$s4vcv9/iR.PK7Rs5k.WoyOWy7yUtXRYXoDmNRy3y1F6qreN3wWHo6', 'student', '2026-06-13 07:42:46'),
(213, 'Amit Das', 'amit_das69@gmail.com', '$2y$10$Bmqlp5W0jAfE060qLafrMOt.eddcCVN8MwHpcFC5GDpL1TI73MIP2', 'student', '2026-06-13 07:42:46'),
(214, 'Kavya Chakraborty', 'kavya_chakraborty27@gmail.com', '$2y$10$vEGygQQBwtNimqIBMhLpceisBuZDq3az7.05/uoF.HHkJyiqyDkDe', 'student', '2026-06-13 07:42:46'),
(215, 'Sneha Pandey', 'sneha_pandey6@gmail.com', '$2y$10$N4u26ktV.nlq729XIzxeX.BgYOx47fI2N3JPHjnNJivWFR2U0Kosy', 'student', '2026-06-13 07:42:46'),
(216, 'Anisha Goswami', 'anisha_goswami29@gmail.com', '$2y$10$/fw5g9W9RdSm7ag2rXdqSuYSQZBcwvy3XBGzhQvHWyMgRFJLpZhEy', 'student', '2026-06-13 07:42:46'),
(217, 'Smita Bhat', 'smita_bhat82@gmail.com', '$2y$10$gPUBSSvIz.EretzEECXrtuhklVABgXq3C3ngPhFAOHsU.2JpyFEqm', 'student', '2026-06-13 07:42:46'),
(218, 'Ayesha Kumari', 'ayesha_kumari81@gmail.com', '$2y$10$WhJGdL/4JnMmuZqwYMQvuOSUzFaHgqmn6ls5BvZOrOXFHyH3VqH1O', 'student', '2026-06-13 07:42:46'),
(219, 'Subhash Paul', 'subhash_paul83@gmail.com', '$2y$10$cZnZdFGORi/uDrkZhDMk/.WHT9oyfNsD2jjR2GMxNmeGIVC09xg6q', 'student', '2026-06-13 07:42:46'),
(220, 'Arpita Choudhary', 'arpita_choudhary4@gmail.com', '$2y$10$n/wQ3rJTk/yaIm9YDppda.Px8BW3AZjdBaCgvhMfYn1wQZhNcdQRi', 'student', '2026-06-13 07:42:47'),
(221, 'Manish Sah', 'manish_sah34@gmail.com', '$2y$10$EYG8S/A9oueCr7no/7kGh.qvygVzSnTLmDzybAiawXKRuVEzBlwua', 'student', '2026-06-13 07:42:47'),
(222, 'Payal Bardhan', 'payal_bardhan57@gmail.com', '$2y$10$3u9jpk7M2d.0kt.ObvCiMu6yR1Z1BHsksO05uydlkeUQBoYbAWp/u', 'student', '2026-06-13 07:42:47'),
(223, 'Debanshu Pal', 'debanshu_pal54@gmail.com', '$2y$10$owGwrlpBtkI11CSng3jVtO6eGI.34bRvXXMSOdjSZ4y3HvpTUBJfi', 'student', '2026-06-13 07:42:47'),
(224, 'Madhuri Jha', 'madhuri_jha17@gmail.com', '$2y$10$wTSuuWDWrF0bCx/DDjrnrOpExqxmdrH/rZjNjfUDVu3gEIvhH2s52', 'student', '2026-06-13 07:42:47'),
(225, 'Jyoti Modi', 'jyoti_modi10@gmail.com', '$2y$10$AH2iVEtkYGUzcUSjNaCzEeE8MFcXoN1r5/Q4BK/rFG7C3ushljL1u', 'student', '2026-06-13 07:42:47'),
(226, 'Raunak Chatterjee', 'chatterjeeraunak2k@gmail.com', '$2y$10$UkusAHhdQEH28Mx6sER7huXxbBF2N44u8FDROVgHpqKUAOShyR1ZS', 'student', '2026-06-13 07:42:47'),
(227, 'Ishika Paul', 'ishika_paul88@gmail.com', '$2y$10$PbAFXKp/gSfyIbQwg.VANuWEewtK51THcgUz2LG.4i5ZeZaoeEb1S', 'student', '2026-06-13 07:42:47'),
(228, 'Ayesha Mukherjee', 'ayesha_mukherjee97@gmail.com', '$2y$10$M6jpa12fL8RAY2.I4METBOpd/YDDTWn4Xx.wpk75ZBcNgQlwhbLA6', 'student', '2026-06-13 07:42:47'),
(229, 'Payal Paul', 'payal_paul96@gmail.com', '$2y$10$ZmCaDuJyVl0WnDWMlqPsYOGhNHXDTtWeJjkrvtsNbHfflaqpb3/ve', 'student', '2026-06-13 07:42:47'),
(230, 'Ipshita Mukherjee', 'ipshita_mukherjee52@gmail.com', '$2y$10$4xgL3fZahqh0HuBy66DrC.Xwa6j9eqdohOUTA3fjNbQFkjKotWLjS', 'student', '2026-06-13 07:42:47'),
(231, 'Ishani Srivastava', 'ishani_srivastava88@gmail.com', '$2y$10$dr5ZQkrNXgbUWeGgOzVfJ.71/K4C87u45CEn1.qj4V1k5glA./1Li', 'student', '2026-06-13 07:42:47'),
(232, 'Debjit Sen', 'debjit_sen92@gmail.com', '$2y$10$b8Nd0QdgjdbIsm0gKgbr5Ok3YPq6N2aGKcy55AoHvlIqVYFDUVrne', 'student', '2026-06-13 07:42:48'),
(233, 'Anjali Bhattacharya', 'anjali_bhattacharya70@gmail.com', '$2y$10$I/rDBYqGrZ5mgE3Gk7cxPOvU/sqGAjgy2fkBi9lgxnHu4jSaPGJlq', 'student', '2026-06-13 07:42:48'),
(234, 'Sunil Yadav', 'sunil_yadav15@gmail.com', '$2y$10$2LZ44ZtOGxhtio0S6FjIuuVKZ6LmBZZZOXyGLN/B4m62K1UWBHMSm', 'student', '2026-06-13 07:42:48'),
(235, 'Jaya Pandey', 'jaya_pandey38@gmail.com', '$2y$10$P3R.JGz5QIRNH4LJpnPxzeJ0VoHt.BjqlWCYHujZWv7KVRxkdLaJu', 'student', '2026-06-13 07:42:48'),
(236, 'Bikram Chakraborty', 'bikram_chakraborty20@gmail.com', '$2y$10$DZRmTiyCcy226L6A9EW/t.Wp29tBbQAJT0EnJHo86K/tXjDT4CxEC', 'student', '2026-06-13 07:42:48'),
(237, 'Ayesha Dey', 'ayesha_dey24@gmail.com', '$2y$10$cuPEHf93a4rB3NyGxYtgeexAOuuWpyRvo2hBYioHeZxNa1G4dNIXa', 'student', '2026-06-13 07:42:48'),
(238, 'Anand Kumar', 'anand_kumar5@gmail.com', '$2y$10$2WQeTicPttc9UaMdSfWmtO9ebmiWiLzq/UXHUEWlbdwxmhWdotBnC', 'student', '2026-06-13 07:42:48'),
(239, 'Kaushik Kar', 'kaushik_kar45@gmail.com', '$2y$10$6ufA4f9p2S1gOIkZtOeOFe/owvkQkdn29Xm3WUtcEMFDpefTy5Ogi', 'student', '2026-06-13 07:42:48'),
(240, 'Piyali Goswami', 'piyali_goswami71@gmail.com', '$2y$10$bQteSITPdpFgWDnMiL31Q.0715lEz54zquzc7f3U4beKFVzpdy0BW', 'student', '2026-06-13 07:42:48'),
(241, 'Mita Mahato', 'mita_mahato27@gmail.com', '$2y$10$t9XA2dx7A2CVKOlKEm7Ky.rl2JYpJqxODp3H6MnN.35M0QTZC/l4K', 'student', '2026-06-13 07:42:48'),
(242, 'Sneha Rao', 'sneha_rao2@gmail.com', '$2y$10$XMQbTUk.4iJRRRi0F57nheb.dlxfHwl7.xCs7XKN.zGgSLk3OWf/m', 'student', '2026-06-13 07:42:49'),
(243, 'Rohan Mitra', 'rohan_mitra72@gmail.com', '$2y$10$FFBg/Zg.CAtd4jXkvducveRUbGtBcmrBWR3exlQhk/AiAfGu6IFUq', 'student', '2026-06-13 07:42:49'),
(244, 'Anisha Kar', 'anisha_kar28@gmail.com', '$2y$10$Z6bRftWjYTx/dpI/uI.BEuNHPyZikwaKCgQRv8jQlXS36xAOIgUM2', 'student', '2026-06-13 07:42:49'),
(245, 'SUMANT KUMAR DEY', 'deysumant150@gmail.com', '$2y$10$Mow7j/5SADxnjZUKt2J6P.lvB5dnRoVDpVAGh72HP3L/bQVyqlVQO', 'student', '2026-06-13 07:42:49'),
(246, 'Madhurima Mukherjee', 'madhurima_mukherjee53@gmail.com', '$2y$10$9Smm5Ma1S1cJEIdwwB9zJeLMpSIiuYl9/8TOiPYK8JiIvUsEgxBKS', 'student', '2026-06-13 07:42:49'),
(247, 'Nikhil Pathak', 'nikhil_pathak24@gmail.com', '$2y$10$2SOzDdfPeIcTQrfdcveW9uH81nSmNqEXPes..NgCHo.vS8MuspoEq', 'student', '2026-06-13 07:42:49'),
(248, 'Debanshu Ghosh', 'debanshu_ghosh46@gmail.com', '$2y$10$ES0mc.BbvKZpoyoDvHhQkuUl2zxMJK0KIrAcYbzbkT7MdwX8MekH.', 'student', '2026-06-13 07:42:49'),
(249, 'Indira Roy', 'indira_roy69@gmail.com', '$2y$10$JTW9lOrYsCQwCK1AWwfYb.i1DMIQmvII09jhnRrdHo1A4z03Zcgwi', 'student', '2026-06-13 07:42:49'),
(250, 'Utkarsh Nandkumar koshti', 'unk5659@gmail.com', '$2y$10$7Wmps15Md2qBvvvajhz/9.5lz0wV3uywwZReTyf4RVImv5UdYzGGO', 'student', '2026-06-13 07:42:49'),
(251, 'Shreya Rajput', 'shreya_rajput89@gmail.com', '$2y$10$gt12a0xAEYkhjAN03JWF2eOEi0sPETGnwavY8O4y812Loft8OOaQ.', 'student', '2026-06-13 07:42:49'),
(252, 'Trisha Dutta', 'trisha_dutta92@gmail.com', '$2y$10$UwZIOQ2K86yokLHsH7NwCuPuacR9kddYvBsponW.wtuz6RC0ymLum', 'student', '2026-06-13 07:42:49'),
(253, 'Nayana Rao', 'nayana_rao35@gmail.com', '$2y$10$YQjqbrm6vELAgxP7i57Jo.LNblBqf4NKk1krC9ILdxqcK9f7j.UGS', 'student', '2026-06-13 07:42:50'),
(254, 'Debika Rao', 'debika_rao29@gmail.com', '$2y$10$nR4SLueajyYdiG.gU0VDXO40VCwb7i5yzBzIvc.cHtiJu2MPeznKC', 'student', '2026-06-13 07:42:50'),
(255, 'Nikhil Jha', 'nikhil_jha10@gmail.com', '$2y$10$Rrn3CWQyOtMo7ji1VYnh7eOmjwOTh4E/XBW7ym9ZFRzGM1OkRKovy', 'student', '2026-06-13 07:42:50'),
(256, 'Naira Singhania', 'naira_singhania72@gmail.com', '$2y$10$8wpjAS7flljz.rY6gNr0D.LdkPn8sgTniRZOjrYAlrVf5YLoL1DfK', 'student', '2026-06-13 07:42:50'),
(257, 'Pawan Dev', 'pawan_dev38@gmail.com', '$2y$10$Ay.wHNz/khFHvGpYkCaCZegMo.LEToKKfhDErkLl.3.c2Bhv0vZUG', 'student', '2026-06-13 07:42:50'),
(258, 'Simran', 'simran.sagar08@gmail.com', '$2y$10$9ySwby84A.xUWXtVhtmJMO6d7oTDw7rAIka8wuBg5qAknHyi1r0ZC', 'student', '2026-06-13 07:42:50'),
(259, 'Aparna Banerjee', 'aparna_banerjee47@gmail.com', '$2y$10$cx67uJ201B/uJJ/FuLIas.MqK/K6kFevbwJVJOdcQj3U1UT.Jp31O', 'student', '2026-06-13 07:42:50'),
(260, 'Naira Rao', 'naira_rao84@gmail.com', '$2y$10$siN43Szc8XcTv4.utPHRS.PyiBeBrm9qf4ir2L9pz78PUXv.RixQa', 'student', '2026-06-13 07:42:50'),
(261, 'Nikhil Kumar', 'nikhil_kumar35@gmail.com', '$2y$10$7dXFfKeo7ADiPC8.awbmn.tid9zy4vjdf9dJfIwTIU4IyNAINYNhK', 'student', '2026-06-13 07:42:50'),
(262, 'Rina Bera', 'rina_bera51@gmail.com', '$2y$10$pMBZlNef2FNvqcrMM5QmZ.8yDj9HgMsnzHObqq6UEMfuFGRXnfL22', 'student', '2026-06-13 07:42:50'),
(263, 'Radha Yadav', 'radha_yadav68@gmail.com', '$2y$10$vUD59oRChTN95rocDV3wO.DjRNWLofVy65WFQDXF4qz09x0Oy6bmq', 'student', '2026-06-13 07:42:51'),
(264, 'Ananya Ghosh', 'ananya_ghosh95@gmail.com', '$2y$10$BgNKN3JSOgWNzozPCBUR/u54qsEZ.hSpRLhHjY0tzAEiYZNU/9RrC', 'student', '2026-06-13 07:42:51'),
(265, 'Mandira Kar', 'mandira_kar78@gmail.com', '$2y$10$gjhMpg/ho4Wxff4/THhzleoBjDzXBo2gd/1oM8rVwpzDrPYAvZ4XS', 'student', '2026-06-13 07:42:51'),
(266, 'Dipankar Ghosh', 'dipankar_ghosh62@gmail.com', '$2y$10$EAZwLIZ055dIXPmea3pM8utcm53y086K4DmlHk/cVrTwHzV8D0pgO', 'student', '2026-06-13 07:42:51'),
(267, 'Vikas Kumar', 'vikas_kumar81@gmail.com', '$2y$10$/LFXQaHNVcRzajhczRkHWOztXZgPes2kHfViaMwO0.AjD6kZyiLmS', 'student', '2026-06-13 07:42:51'),
(268, 'Mukesh Sharma', 'mukesh_sharma85@gmail.com', '$2y$10$aW74ciHMNjhPPImCWM.KlOyJRho3sNDUlrPzvJ4WMoEN4CH5se.Hi', 'student', '2026-06-13 07:42:51'),
(269, 'Khusi Srivastava', 'khusi_srivastava13@gmail.com', '$2y$10$WLfTLwGfAY/QJ00VXiHbveFJF.ZjL7jNDxPM8AWYE5hW9N/IE58Cy', 'student', '2026-06-13 07:42:51'),
(270, 'Piyali Mitra', 'piyali_mitra89@gmail.com', '$2y$10$tD37ZYhlLHEsAMJM3ofYU.XIsx4wYaNEcgyh/z5uXMcT/0xzf6pKS', 'student', '2026-06-13 07:42:51'),
(271, 'Anand Pandey', 'anand_pandey50@gmail.com', '$2y$10$R4.MhH3JqkRbpxTRJZvRgO0PG5SZvezyWibMoJ5kZz6ae/g9qZsuG', 'student', '2026-06-13 07:42:51'),
(272, 'Urmi Srivastava', 'urmi_srivastava15@gmail.com', '$2y$10$80eRnZX1dQ0n1CTHn6APAuWE8/UizyMZmgkAORKVgGN1J2SYfda12', 'student', '2026-06-13 07:42:51'),
(273, 'Abhijit Chakrabarti', 'abhijit_chakrabarti23@gmail.com', '$2y$10$bZrJZ73MEsIg/wxhRKskj.FLxQDdk3WTLopxVWAdCAcQFT1utVwvS', 'student', '2026-06-13 07:42:52'),
(274, 'Rajiv Chakrabarti', 'rajiv_chakrabarti53@gmail.com', '$2y$10$e7yT2rNYPnsxgN8ts5ynKewLLeDh08FL79pQ6C73RUqWpQOgNg1Ne', 'student', '2026-06-13 07:42:52'),
(275, 'Vikram Banerjee', 'vikram_banerjee32@gmail.com', '$2y$10$iWdoA5KabRyDcG5hEJ1gcuD72Kd9Hh.H8zjewGE.3aK0GT5.eM7PG', 'student', '2026-06-13 07:42:52'),
(276, 'Ayush Mahto', 'ayush_mahto90@gmail.com', '$2y$10$RPSxnKbAnZZnw.pmqzhr8OFfJyuNdEpRXZWEOU3jQQvaYAj.awDNy', 'student', '2026-06-13 07:42:52'),
(277, 'Asish Bauri', 'osasish143@gmail.com', '$2y$10$NqSCKDY1jRI09QoG7BA6J.pj4gcybE31peitQwAOIWBCj86b1IlNe', 'student', '2026-06-13 07:42:52'),
(278, 'Smita Kapoor', 'smita_kapoor11@gmail.com', '$2y$10$kjQcQNSngnwxcYh/Wmv02.JdcmHAj13YVr8.sBXVnfPUMcfwYekwa', 'student', '2026-06-13 07:42:52'),
(279, 'Smita Gupta', 'smita_gupta88@gmail.com', '$2y$10$2B2Vz3SxLP5hiZ124u4D8Ox4sHl/yu9mGy/yPLUZxxGgy8kw7n6fK', 'student', '2026-06-13 07:42:52'),
(280, 'Payal Iyer', 'payal_iyer92@gmail.com', '$2y$10$J5qiJbwsqsJTXoO9HssAE.CXe.VEqQtpkAm6t56JW8ypIZ6nQ3IPK', 'student', '2026-06-13 07:42:52'),
(281, 'Ipshita Kar', 'ipshita_kar72@gmail.com', '$2y$10$TgqltIcenZawwnPuxaU3VO1gpbiiZlnpAq4K7fBSB9ukrI769RkP2', 'student', '2026-06-13 07:42:52'),
(282, 'Anupam Biswas', 'anupam_biswas61@gmail.com', '$2y$10$JtFH5URX1pMgg2JGUrhq6.a2pOtPXL0TCr02OxEwTpGmUzfsEqqDC', 'student', '2026-06-13 07:42:52'),
(283, 'Khusi Chakraborty', 'khusi_chakraborty23@gmail.com', '$2y$10$Y7qZ8.1XgR9br4qpbtxk9OUiZLq1vjBSwPHt56wtw/ehcToSM4oXq', 'student', '2026-06-13 07:42:52'),
(284, 'Bharat Pathak', 'bharat_pathak45@gmail.com', '$2y$10$zFWtNL32sirthYVID7I/NO.lmcC1q7Lisgb2ZEKrjKBq1MkmWP4CO', 'student', '2026-06-13 07:42:53'),
(285, 'Kunal Chakraborty', 'kunal_chakraborty15@gmail.com', '$2y$10$yCViNR0UBc34uSvuj1JxQeFRSw2iJ8g5hPHw7L6X/SHOSNg.7P88q', 'student', '2026-06-13 07:42:53'),
(286, 'Dev Bera', 'dev_bera78@gmail.com', '$2y$10$KBTEd48CBZrZd3C3nsr8ye/kyecrLDV/ge6BFm8.Fq/HlvKSH.45K', 'student', '2026-06-13 07:42:53'),
(287, 'Purnima Rai', '8348365251.puru@gmail.com', '$2y$10$.BcyLNoC9NFm8gabFT25IuB.cFMjyuChfmu8kvZewaZf5v.L0BJ.m', 'student', '2026-06-13 07:42:53'),
(288, 'Ananya Chatterjee', 'ananya_chatterjee99@gmail.com', '$2y$10$qgnqQuOKkjk.CamwGFM3y.Vtm/uKOj1rlZ4a7AJJGGm49X9fg2/su', 'student', '2026-06-13 07:42:53'),
(289, 'Anjali Srivastava', 'anjali_srivastava96@gmail.com', '$2y$10$BRvGoG1cf2pmH7c9fayfnOC99XfNN0dbYlsLE9BiqrtXe5guo0C/a', 'student', '2026-06-13 07:42:53'),
(290, 'Tanushree Chakraborty', 'tanushree_chakraborty89@gmail.com', '$2y$10$cdHWqXVRRngKc6eTyyrkd.BRBBZWVsPwUT89hUi4NfGyAf05pmQqG', 'student', '2026-06-13 07:42:53'),
(291, 'Saurabh Das', 'saurabh_das26@gmail.com', '$2y$10$p6sh5b4TgUor3bBIWjV5r.3jId74jHcE2A7WBHjRh4DZQqa1Y04QW', 'student', '2026-06-13 07:42:53'),
(292, 'Nayanika Shukla', 'nayanika_shukla32@gmail.com', '$2y$10$bxweJIZj1fwCP3im4R2lPu65lqiwFO6SN1cYkuLM2jkACHLGKIEs6', 'student', '2026-06-13 07:42:53'),
(293, 'Jyoti Chauhan', 'jyoti_chauhan47@gmail.com', '$2y$10$iBJM8QJEuF/ObLFz6fqcKeHZfyKWVR7I38jH/X8hDqfNmSeWrHSBK', 'student', '2026-06-13 07:42:53'),
(294, 'Nisha Pal', 'nisha_pal33@gmail.com', '$2y$10$putrVImGJCRQIRyyqb6Hzu5lJ59G.zB0DcqVMrv47Nuj5XnHdcR5y', 'student', '2026-06-13 07:42:54'),
(295, 'Dev Bhowmick', 'dev_bhowmick65@gmail.com', '$2y$10$OvhaUhyZsqIvNZaELuBYWu5FGeCIZh8kUqgzesrarZCVzh44amTy2', 'student', '2026-06-13 07:42:54'),
(296, 'Bharat Verma', 'bharat_verma79@gmail.com', '$2y$10$tYKBUQKg2nrvPafN5Bh1MO1iiJk4tch7nyZwmS28SWqYyQw4N8Tpi', 'student', '2026-06-13 07:42:54'),
(297, 'Anjali Das', 'anjali_das43@gmail.com', '$2y$10$9mD61SZb.PawsEZZMtADc.86JDrADgn1bSvOh3FR/P252OqYmm9Du', 'student', '2026-06-13 07:42:54'),
(298, 'Soumik Goswami', 'soumikgooo7@gmail.com', '$2y$10$ONpGxQc3CS/KltCpBzstFODyEMhvh2/Qj4kXNmH7QaptLuJbj09la', 'student', '2026-06-13 07:42:54'),
(299, 'Amit Mahto', 'amit_mahto77@gmail.com', '$2y$10$b1nn1h/oO7QKjY6pmwrE8.PGnsTuhgzcfACJMGRqCSF5B9haLlcDq', 'student', '2026-06-13 07:42:54'),
(300, 'Rakesh Jha', 'rakesh_jha37@gmail.com', '$2y$10$EvgtBw0B5KPJ3RZ.61p7huV4V7hDOWxOb19s44bASUrIZ0beSY8g6', 'student', '2026-06-13 07:42:54'),
(301, 'Amit Banerjee', 'its.amitb77@gmail.com', '$2y$10$pf0WwWgW7iBTcUEdTrrio.v3wNWJlgiT6pUL3JpywTYV7EgXWk5wG', 'student', '2026-06-13 07:42:54'),
(302, 'Khusi Bera', 'khusi_bera5@gmail.com', '$2y$10$y6q.lq.UK5txAJtBPpFtju2K5MfZWVYkuYdYPaBNh0xZwoAbgkyMO', 'student', '2026-06-13 07:42:54'),
(303, 'Mandira Srivastava', 'mandira_srivastava55@gmail.com', '$2y$10$2MvOKb8RIdF0qnLjiouluuHI/SUrmOKn8sYlVIWUSnJPI2J47pE2y', 'student', '2026-06-13 07:42:54'),
(304, 'Rajesh Thakur', 'rajesh_thakur98@gmail.com', '$2y$10$.F.19Gclm00M0hBr2eSkLum23lDC0Cx7EcPJrPTbkAIQxI6l7xGQq', 'student', '2026-06-13 07:42:54'),
(305, 'Kaushik Chatterjee', 'kaushik_chatterjee32@gmail.com', '$2y$10$6CINrA57.tEP.3R2vb/BteX160/bUBU8lzKc8LkjCDWEYkrieKCJC', 'student', '2026-06-13 07:42:55'),
(306, 'Nadgire Shivani', 'shivaninadgire@gmail.com', '$2y$10$6dszpTk.bwT2Tq3jO48iMeIodNXhSBTX3Tym8e8QhaB3WQ/0KLpx6', 'student', '2026-06-13 07:42:55'),
(307, 'Abhijit Sarkar', 'abhijit_sarkar27@gmail.com', '$2y$10$r5yt0HDi9rikDrl6e60uBOSF07houy.o/wLfgiMHYoDmkgopiuJ/W', 'student', '2026-06-13 07:42:55'),
(308, 'Suvankar Ghosh', 'suvankar_ghosh46@gmail.com', '$2y$10$KkR1d2ixHNkPysBIlnQUkOyvLK.9WZNKiepxd3vUL18/Pe6Cpo6Q2', 'student', '2026-06-13 07:42:55'),
(309, 'Kavya Sengupta', 'kavya_sengupta20@gmail.com', '$2y$10$N7JpyZL.1z6rXNvbJ9TQseCk7gvaVZJI6sWAJQ8XA.TPiyMBcMtAW', 'student', '2026-06-13 07:42:55'),
(310, 'Rohan Pandey', 'rohan_pandey58@gmail.com', '$2y$10$tI5sHa1fhGFF02Zu1NCaxurM.aWcI8MGS1vD6a4z4sY0Hl5DijwWK', 'student', '2026-06-13 07:42:55'),
(311, 'Sanjana Sen', 'sanjana_sen32@gmail.com', '$2y$10$5fgFcUVftdwUwetGjoE6muY6S6O0aUqpdpWO7OeeYTuIjssjBYXiy', 'student', '2026-06-13 07:42:55'),
(312, 'Urmi Biswas', 'urmi_biswas99@gmail.com', '$2y$10$Ycu.kP970P/Y2aPadImFtuhuQQ/0RCiYReBN3tVa3Gf1OcfdYjJEi', 'student', '2026-06-13 07:42:55'),
(313, 'Trisha Sharma', 'trisha_sharma9@gmail.com', '$2y$10$MhO0Iffgax.M7sMVLONXH.z63GK2iVpkt6UHEvIwq4NJNnfnrFIIy', 'student', '2026-06-13 07:42:55'),
(314, 'Poulami Chakraborty', 'poulami_chakraborty52@gmail.com', '$2y$10$ohOfp0tZrc66P1617A7djebCKLtcRgoFZg/HVSwBZQZSzgwGd7ire', 'student', '2026-06-13 07:42:55'),
(315, 'Ayesha Kar', 'ayesha_kar40@gmail.com', '$2y$10$FYnSxcfnR2zG0G6g391g..GcSL6wcwIfYrplN1xnbnZskIgBsNspS', 'student', '2026-06-13 07:42:55'),
(316, 'Nisha Bhowmick', 'nisha_bhowmick6@gmail.com', '$2y$10$vOYVvbE7peT9nRzwVhRZWujVrdajbGbGgIRAT16Dc7XF/d8eS7FDy', 'student', '2026-06-13 07:42:56'),
(317, 'Ayush Kumar Rai', 'ayush_kumar_rai64@gmail.com', '$2y$10$WPFjVFBn99WCcktB3ciqYOfw0EQNuZsjcxVknHtlHGt8Ii2pDjBL2', 'student', '2026-06-13 07:42:56'),
(318, 'Manoj Mitra', 'manoj_mitra16@gmail.com', '$2y$10$mrNtqhLrtYuQZR1Z0EZVoeBwWaAwK7ZEBX6FZBooo5Gci8FGirZbK', 'student', '2026-06-13 07:42:56'),
(319, 'Khusi Srivastava', 'khusi_srivastava38@gmail.com', '$2y$10$DYRqsh.Ioyxvpk8VjAENwuW1SXivDc/9BKvFtF7pUjZZbJrWOwzhO', 'student', '2026-06-13 07:42:56'),
(320, 'Urmi Sarkar', 'urmi_sarkar87@gmail.com', '$2y$10$/hVt3YPjdrarygiJaUJHSuAknfZpPnUev6qa1yHRGRBJDdmUadwTO', 'student', '2026-06-13 07:42:56'),
(321, 'Indira Kumari', 'indira_kumari47@gmail.com', '$2y$10$TvzpzmzLBqEcN2czlXw2Yew89bU2rmCwMidVJV1de0Tz5d9li1cwu', 'student', '2026-06-13 07:42:56'),
(322, 'Dipankar Mahato', 'dipankar_mahato14@gmail.com', '$2y$10$OXHyQxb0wYyPDqBWesvo4.riSy3mNhDlMb/wkcXRVwVDyt0iXI.2G', 'student', '2026-06-13 07:42:56'),
(323, 'Niloy Dutta', 'niloy_dutta68@gmail.com', '$2y$10$mpRXwmvX0hCO/YZQS/0aUezVgE57N3BRmBEW3sSLxtRRgD.SbeDXO', 'student', '2026-06-13 07:42:56'),
(324, 'Joy Banerjee', 'joy_banerjee17@gmail.com', '$2y$10$Oi0JTZj8CHfYsvHb6p4pkenCYOwPT8cqqg6PDj3LrbBq6zD6DBWte', 'student', '2026-06-13 07:42:56'),
(325, 'Ishika Kapoor', 'ishika_kapoor91@gmail.com', '$2y$10$NYVvzCpbGjTXBX6jyCTnrOZ70A0fgKVby5do0VnoZATPJ0V4cWY/y', 'student', '2026-06-13 07:42:56'),
(326, 'Anand Kumar', 'anand_kumar55@gmail.com', '$2y$10$gA9OTriJcGUVJSEpWie6l.B7JyMJwQC0fZYxRkwRScax7Gu6ov2Mm', 'student', '2026-06-13 07:42:57'),
(327, 'Sanjana Kumari', 'sanjana_kumari17@gmail.com', '$2y$10$1jY6/jqx6HnwdYwj9AGXnekH81lRWtQsuzcWzr69VydoFGYFif.OK', 'student', '2026-06-13 07:42:57'),
(328, 'Madhuri Srivastava', 'madhuri_srivastava5@gmail.com', '$2y$10$NJHoehgTAem/9NlBP7wBGe/IDsfSULzSE95ZJ3rXCeQ3viI9p8qOi', 'student', '2026-06-13 07:42:57'),
(329, 'Dheeraj Mandal', 'dheeraj_mandal25@gmail.com', '$2y$10$H5W/2Kr1RlinZbYBX2874eX4QJnpShqoztjHYZYGwQ45mEMibWZ32', 'student', '2026-06-13 07:42:57'),
(330, 'Payal Shukla', 'payal_shukla62@gmail.com', '$2y$10$EctYH0qaqycuygr8vFAxE.yCynX2GX5Z1krxxnpTYrnDzd4B5SWru', 'student', '2026-06-13 07:42:57'),
(331, 'Rakesh Das', 'rakesh_das12@gmail.com', '$2y$10$u1ZyJUvu0/au6uIaGKoMzOSTh2Q0VCJDQ8Wr6OMBBGFdd9iBU.ei.', 'student', '2026-06-13 07:42:57'),
(332, 'Diya Patel', 'diya_patel47@gmail.com', '$2y$10$YGoeZGLjuyRqoJYtKkI4aOdpd7MlO23c/XVBqfumm2fY17YaHD20.', 'student', '2026-06-13 07:42:57'),
(333, 'Naira Chauhan', 'naira_chauhan54@gmail.com', '$2y$10$xlhCqJk3HQf4/TvCQ6lZxejqowhb5OdfgLCEGsZ6X4nwhJ2e9sy66', 'student', '2026-06-13 07:42:57'),
(334, 'Indira Pandey', 'indira_pandey90@gmail.com', '$2y$10$kHKWD4I.G5vGoFhAHuvxXOIaxmmfIfrjsPlVDsY2daBdk4J.hL.2u', 'student', '2026-06-13 07:42:57'),
(335, 'Amit Bera', 'amit_bera15@gmail.com', '$2y$10$tfGE2JlHnpghANmyyL7YoOKGcGnDH.sTaeN6Rzm2B/e6JhHkxFcL.', 'student', '2026-06-13 07:42:57'),
(336, 'Chandan Verma', 'chandan_verma70@gmail.com', '$2y$10$fFB0jVWDC/cBiDJVbuHs1.4MaPIX2YteNVlHHyeTIBuYgsSjb39Bm', 'student', '2026-06-13 07:42:57');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(337, 'Indrajit Bhattacharya', 'indrajit_bhattacharya59@gmail.com', '$2y$10$mP7g/XmjU1VhdXSxdrX75O3MaaBdugQGROMoJnJOb60jNfheWX.pu', 'student', '2026-06-13 07:42:58'),
(338, 'Payal Rao', 'payal_rao14@gmail.com', '$2y$10$XiHIsWEJn.xCn3OxEGKUG.09UcTPSU0EGjj65V3yuQqlDHZ5ptqPa', 'student', '2026-06-13 07:42:58'),
(339, 'Swastik Bose', 'swastikbose98@gmail.com', '$2y$10$98rz70sole5hAuOOi0gH8OxWo62zAsCCVrHY2jbW67wwr19TWiVVe', 'student', '2026-06-13 07:42:58'),
(340, 'Dheeraj Kumar', 'dheeraj_kumar39@gmail.com', '$2y$10$hp1nexm0vGSo57TTDPe3De1.MrBbUryzyjlIGv.jrzaIo.XxPaOo6', 'student', '2026-06-13 07:42:58'),
(341, 'Samaira Rao', 'samaira_rao81@gmail.com', '$2y$10$WCJkg71obr.KAPwWj0NmkOJq0O7T3TsB2flL0bYnFjJhyiUIpM30m', 'student', '2026-06-13 07:42:58'),
(342, 'Alok Das', 'alok_das8@gmail.com', '$2y$10$puJoNz4ZXpZH6DE0jiary.F13U7cv/GeSzSkgvUJL0pZdALExkU02', 'student', '2026-06-13 07:42:58'),
(343, 'Shad Warsi', 'shadwrsi9074@gmail.com', '$2y$10$KWupq2WIipoRY5wEfNSTW.nJrgYzMfFQ.YrIo9EBtp6k6/30SEWUm', 'student', '2026-06-13 07:42:58'),
(344, 'Neeraj Thakur', 'neeraj_thakur10@gmail.com', '$2y$10$R1SH4YtE7yO.0VxgN1DNYu27BF/LSeIM93Xp4wcQW6xQRRksIX6q6', 'student', '2026-06-13 07:42:58'),
(345, 'Koushiki Ghosh', 'koushiki_ghosh50@gmail.com', '$2y$10$6ic3fiq/dI6m/7ECe8KbMOslWvGePrESG.GP0UI/U2bSkEn6uq0ya', 'student', '2026-06-13 07:42:58'),
(346, 'Neeraj Chauhan', 'neeraj_chauhan7@gmail.com', '$2y$10$rApgOoHKV0fIfzDMkS1d6esMcxtpC0pfRRsMT48WleiIx70GoEugW', 'student', '2026-06-13 07:42:58'),
(347, 'Ankita Mazumdar', 'ankita_mazumdar91@gmail.com', '$2y$10$Vwq5lnuTNS2v043lbXvXx.RPz6x7f2ra0WhjWZCR8rYzezfGV5lKe', 'student', '2026-06-13 07:42:59'),
(348, 'Suman Chatterjee', 'suman_chatterjee69@gmail.com', '$2y$10$3RNCkU7ehPqldUJQO6PqvePmIyzZf/FjwK4Jt0bo6Q3u93w0lP9.K', 'student', '2026-06-13 07:42:59'),
(349, 'Debika Mandal', 'debika_mandal86@gmail.com', '$2y$10$PfCp15g/fhn6Z.BJ.7zGkunhDemPz/GJ39s0YcxvunWOVhViGqZOa', 'student', '2026-06-13 07:42:59'),
(350, 'Sumit kumar', 'kumarsumit84344@gmail.com', '$2y$10$ZIUsrsqUxeKseGXExOYg5exFTJvCYnUMJDu6u4ri2jvNesE9WjbE.', 'student', '2026-06-13 07:42:59'),
(351, 'Aparna Agarwal', 'aparna_agarwal62@gmail.com', '$2y$10$EetefroaTWimYJArPbSKqe2L1KRQfoYX9v6ur1WAWMZytKh8uzhk6', 'student', '2026-06-13 07:42:59'),
(352, 'Adarsh Rana', 'adarsh_rana13@gmail.com', '$2y$10$YqAbxxJC.zcqSIYH5Vvnv.uJsivaJhN0l378AwCtPI0bzFBbrqg5W', 'student', '2026-06-13 07:42:59'),
(353, 'Anupama Verma', 'anupama_verma63@gmail.com', '$2y$10$NTLY3sduHmK3MGQXXFkChunxqp6Nsih0HOPxkC54Wnfa1DXhsEHvC', 'student', '2026-06-13 07:42:59'),
(354, 'Nikhil Kumar Rai', 'nikhil_kumar_rai87@gmail.com', '$2y$10$mbWKZNfA9kfjzuzj/JRCDe2pWCGIvEG1L7kyuGNH8x4WyJnN.wfAq', 'student', '2026-06-13 07:42:59'),
(355, 'Harsh Patel', 'harshpatel28.ait@gmail.com', '$2y$10$sZ6LdEAP1QEctm6g8VimlOLbBsgVGMMYjKDEHfG/yLhlcHHOijlrC', 'student', '2026-06-13 07:42:59'),
(356, 'Naira Kapoor', 'naira_kapoor2@gmail.com', '$2y$10$D0zQ.a0KJV/vQ.2zGhyKzO.2WyKYvSYH1iceviWJ0sdfHzrS.TY6S', 'student', '2026-06-13 07:42:59'),
(357, 'Abhirup Chatterjee', 'abhirup_chatterjee68@gmail.com', '$2y$10$2IJe9B3SHhxKYPoXoRyLJ.dGafW1kqNoLhA9cGaQbWQlc1hZdtTsG', 'student', '2026-06-13 07:42:59'),
(358, 'Suhana Sharma', 'suhana_sharma95@gmail.com', '$2y$10$rdHGYJ91gHrIyTx/XzCvEeyv5qNwWAYeeref.vYZ76XUJMI0PREIm', 'student', '2026-06-13 07:43:00'),
(359, 'Indrajit Bera', 'indrajit_bera80@gmail.com', '$2y$10$4ZrMv271haxpJ78IjzXPDuXYLhZi70.0brFeKrpNown6Bm4ar/TXG', 'student', '2026-06-13 07:43:00'),
(360, 'Piyali Sharma', 'piyali_sharma62@gmail.com', '$2y$10$saAdjQC5DW6ZP6bzE7RatOwWAD1tIlT5a8OLhm46AKyPui1csM8Aa', 'student', '2026-06-13 07:43:00'),
(361, 'Suvankar Sen', 'suvankar_sen65@gmail.com', '$2y$10$WGbnRsBLa3Ia8OCuYvO57ezwjgmSfvR48ieO9RscY.yL.NIQSdNQG', 'student', '2026-06-13 07:43:00'),
(362, 'Diya Mandal', 'diya_mandal77@gmail.com', '$2y$10$H7/iH6YeJhOkjqFziwd2VOj2U9/TMErThbBSEhO5OdrCoADDKkVS6', 'student', '2026-06-13 07:43:00'),
(363, 'Ananya Ganguly', 'ananya_ganguly41@gmail.com', '$2y$10$uAiJ4B1CyGR2WS5sBbMy3uoXPRbO3ddS/A6Lhob7FFUiwVMcMwbN2', 'student', '2026-06-13 07:43:00'),
(364, 'Shubham Kumar', 'shubham_kumar27@gmail.com', '$2y$10$Dm0qNogG2qoMzaqamwWVPu0JNifAitmNQLL7zcvSdO/bqi6jzjxse', 'student', '2026-06-13 07:43:00'),
(365, 'Vikas Thakur', 'vikas_thakur55@gmail.com', '$2y$10$yacxhhsmtYVHvrlRDcQYhuLFVPuRJFXq1IoDDewQ.nZ6wmfLzeyxO', 'student', '2026-06-13 07:43:00'),
(366, 'Manoj Mukherjee', 'manoj_mukherjee81@gmail.com', '$2y$10$CHh.tsAXBWFAkaQ.ui4KwuURiJOv.TG7/yWnKI1gA5gOrBR/m53IC', 'student', '2026-06-13 07:43:00'),
(367, 'Payal Yadav', 'payal_yadav62@gmail.com', '$2y$10$sNnqXxRyaZSL5vs3bNQBEOwYqP6/PnBNlKpfp0REQZ1C6zaJTT.R2', 'student', '2026-06-13 07:43:00'),
(368, 'Mousumi Mahato', 'mousumi_mahato5@gmail.com', '$2y$10$yWPn7c9PbWoOQTQKE/1DyeK/HTOnMkDP3Okbfp.ws83crB0hdK1AG', 'student', '2026-06-13 07:43:00'),
(369, 'Rina Paul', 'rina_paul51@gmail.com', '$2y$10$Oqa2fzQne6lcMS0pHsb32eqGG.YcDYurHzrSmyHvoRdijbC.kXxCm', 'student', '2026-06-13 07:43:00'),
(370, 'Nisha Yadav', 'nisha_yadav90@gmail.com', '$2y$10$E7ujwu3wC5eVBISTRfR8aeK2zyGjQy8Lg.5d7QZpcL/wIvsmN2RS.', 'student', '2026-06-13 07:43:01'),
(371, 'Joy Das', 'joy_das44@gmail.com', '$2y$10$.TVqP0bNF04IXOeuCZhpnOw.nxOhfOecIwOq0LHwy.1qeVHsnZ3IW', 'student', '2026-06-13 07:43:01'),
(372, 'Manish Sharma', 'manish_sharma45@gmail.com', '$2y$10$HzogeiLmWk324vDPQ/6wruhIXmmdcRYMO9wN8XZVWgFfqMoDkCASq', 'student', '2026-06-13 07:43:01'),
(373, 'Urmi Mahato', 'urmi_mahato93@gmail.com', '$2y$10$1RxGVQ.MmbveU2BpKxcQo.UvHVLpP3k61xC6FoWxVAOizZFrDGg3O', 'student', '2026-06-13 07:43:01'),
(374, 'Samik Nandi', 'samik_nandi60@gmail.com', '$2y$10$84p4OromyjkOJzGEph2E5uPwOwuk1r0TRnFrACZSBCW0axP60MgIW', 'student', '2026-06-13 07:43:01'),
(375, 'Arpita Dey', 'arpita_dey16@gmail.com', '$2y$10$KAtOjOqG.nTjOP4qU9ejuOuxbRNpN0ckBJ.94YCDD.yhzpZJUysnq', 'student', '2026-06-13 07:43:01'),
(376, 'Susmita Kapoor', 'susmita_kapoor60@gmail.com', '$2y$10$3cKDdNFSbMlGdocphd7bcuJOzP2CgKoQRT3frnGiFRWVlxz08sxua', 'student', '2026-06-13 07:43:01'),
(377, 'Trisha Modi', 'trisha_modi81@gmail.com', '$2y$10$2Hybljg8RVtGQP1bL12.4OMaqZ/9MYh34bmYCQ.d4JheIEHgfxQl6', 'student', '2026-06-13 07:43:01'),
(378, 'Joy Banerjee', 'joy_banerjee15@gmail.com', '$2y$10$FFfEISQ9Ax9PCCGOjOLf1etyZqZDJcgYn.XVr1iBU04Rx14svLRUq', 'student', '2026-06-13 07:43:01'),
(379, 'Smita Bhat', 'smita_bhat61@gmail.com', '$2y$10$uKf9rIxFsRvJHkEmwpeZfOZ5RpStrXfUPyw.afBzfpRi.GSX1wgq6', 'student', '2026-06-13 07:43:01'),
(380, 'Madhurima Pal', 'madhurima_pal56@gmail.com', '$2y$10$Z7gjtpEhyUqWNl3h5FrAqeZp3hhjSKkM.UTj1nXCxK0Xj5nV3DVXq', 'student', '2026-06-13 07:43:01'),
(381, 'Rakesh Kumar Singh', 'rakesh_kumar_singh33@gmail.com', '$2y$10$D76VD24ZpiVaqjGfTgq/a.BuWrz2fGu1oQC9yVDOgtdWdcPErBY0a', 'student', '2026-06-13 07:43:02'),
(382, 'Rina Mitra', 'rina_mitra18@gmail.com', '$2y$10$JCkq4r.kQLKDm6IpWtWc6e0dp..hNugtFy/tsmac0yO2pU7BR.mvq', 'student', '2026-06-13 07:43:02'),
(383, 'Soma Mitra', 'soma_mitra76@gmail.com', '$2y$10$6Zyrbhj1o.AswhSTHMbDG.MYLl8v61RBVpKVSfOb2dHhbo4BOZ/HO', 'student', '2026-06-13 07:43:02'),
(384, 'Samik Kar', 'samik_kar21@gmail.com', '$2y$10$c3ldw.QyYFe5T.e5HHMF4.KQyEgaJQrSuyIx9O6wYDoowT7sjBxR.', 'student', '2026-06-13 07:43:02'),
(385, 'Tapan Sengupta', 'tapan_sengupta32@gmail.com', '$2y$10$9bu6qpcrzMZ0F5OZb8JKr.UT6kLe.onoFbcfLAn11FiY.qOJZrBKG', 'student', '2026-06-13 07:43:02'),
(386, 'Vidya Mahato', 'vidya_mahato8@gmail.com', '$2y$10$rpuYqGjnEyasbn0jJH9/JelfLiBiT376wnAEO6QNIxiSdKmyLooU.', 'student', '2026-06-13 07:43:02'),
(387, 'Debika Bhowmick', 'debika_bhowmick14@gmail.com', '$2y$10$XCKnpn1fIXSFL5fFl70rXuhYQhjqPjL8VHsvAi79rxf/4VB.G9JeW', 'student', '2026-06-13 07:43:02'),
(388, 'Shraddha nigam', 'nshreds18@gmail.com', '$2y$10$R.DTIP.WhuObWPm4AN30SudZfP.zHcbTRDLoTfdPpTMlSPJ3OGfM6', 'student', '2026-06-13 07:43:02'),
(389, 'Rajesh Sinha', 'rajesh_sinha26@gmail.com', '$2y$10$fU6CPD.H6yAVm43/UCsA0uEcAmiFeRUM/chGRsmVnM.iSX1i7U/ZC', 'student', '2026-06-13 07:43:02'),
(390, 'Biswajit Ghosh', 'ghoshbiswajit2406@gmail.com', '$2y$10$eRjSDf1hSEIkiOxdH2X/BuAagUixZp5s79pW.gPbNDRenMjWSXOmS', 'student', '2026-06-13 07:43:02'),
(391, 'Jay Kumar Ray', 'jaykumarjk3235@gmail.com', '$2y$10$we0/OorPycCMl2HdN/MnQOtFWeIrB/LESC0TbVSConpMe4G92O94C', 'student', '2026-06-13 07:43:02'),
(392, 'Shubham Kumar Singh', 'shubham_kumar_singh61@gmail.com', '$2y$10$BzzpQ1q9JofNGMpu3R.OYuAUcFPxI7vsNaHPb2WvCBeCQFZnYcFzC', 'student', '2026-06-13 07:43:03'),
(393, 'Trisha Singh', 'trisha_singh54@gmail.com', '$2y$10$VBt3VBuTrYcWAKGLLOFSg.YoTguJj//8muSoi6zokirIgyWdrxoES', 'student', '2026-06-13 07:43:03'),
(394, 'Suvankar Dey', 'suvankar_dey42@gmail.com', '$2y$10$JjjuBcG8i02B8XMZcTwo4O959YmX4Fw2xwhvoaN.RgNsjmwZtf4s.', 'student', '2026-06-13 07:43:03'),
(395, 'UTPAL MAJEE', 'utpalmajee91@gmail.com', '$2y$10$qzVBWqDR3WyyD0M5WtgOFeDQBqUx2FI.E4JuJypPyq/GYx7BKrJ9K', 'student', '2026-06-13 07:43:03'),
(396, 'Sankar Nayak', 'sankaranimesh1234@gmail.com', '$2y$10$I27ppWyxwiLmKjipWYvDoeKnEm6/wXSgYGjRLb60uZDbaQNXEAE5i', 'student', '2026-06-13 07:43:03'),
(397, 'Arnab Choudhury', 'arnab_choudhury87@gmail.com', '$2y$10$J7dMPGnqL3vS45Lol4JsreYFOQX53pYO8RubAtwdP/FiQvT7Z89YG', 'student', '2026-06-13 07:43:03'),
(398, 'Manoj Bhowmick', 'manoj_bhowmick98@gmail.com', '$2y$10$6GrInier5ZRhsZfF2c2viuTa/8JX/OU57GbOekPJzAb6C9lg79qAO', 'student', '2026-06-13 07:43:03'),
(399, 'Tithi Goswami', 'tithi_goswami59@gmail.com', '$2y$10$lnnr86sVHwg3vMiheOQi1OuX0D3yU3RJDK5LZqcuds29gwb7r71Va', 'student', '2026-06-13 07:43:03'),
(400, 'Samir Roy', 'samir_roy25@gmail.com', '$2y$10$A4Dq3039cPxnFf1/W6T5R.AWmmhO6P9SI.SFFmzx4uB6SP1Os1t62', 'student', '2026-06-13 07:43:03'),
(401, 'Jitesh Sinha', 'jitesh_sinha49@gmail.com', '$2y$10$66zsNbmbbZwa26.B1fGXvuwMf0p/a3mjCwomfFAkADIfLunfoUQVO', 'student', '2026-06-13 07:43:03'),
(402, 'Mita Dutta', 'mita_dutta21@gmail.com', '$2y$10$KEh4kBbPBQk1WQxiducn9esBUQqvhyMTBhK9VdPEDkKE4Udttvop.', 'student', '2026-06-13 07:43:03'),
(403, 'Adarsh Das', 'adarsh_das18@gmail.com', '$2y$10$LkE.tGekBdPye2kkUPVleuzCrBoHBZ/lMdtfePmVLYi9rUrBm8BPq', 'student', '2026-06-13 07:43:03'),
(404, 'Mandira Kapoor', 'mandira_kapoor63@gmail.com', '$2y$10$L7KTz2dp7yCf2KLN/CMOxeG49k2co4D64fpKeLUBnxaQRsDX2abDa', 'student', '2026-06-13 07:43:04'),
(405, 'Smita Yadav', 'smita_yadav92@gmail.com', '$2y$10$oVqJNaEBam35GAkluEuLgul0.Al7cvHuSK4NF9gAIadDF4Z/pt9KC', 'student', '2026-06-13 07:43:04'),
(406, 'Manish Sinha', 'manish_sinha90@gmail.com', '$2y$10$/4Q8aXzXpckblR3HEUAAjOnqUpHgtWCYglhls9.EXJ1P09sT90g2W', 'student', '2026-06-13 07:43:04'),
(407, 'Pawan Sah', 'pawan_sah12@gmail.com', '$2y$10$U.cOB0hARQpMpdVlRE0XbuEA7Qo9aaQAzPOf3PSJp9ej8gJ0rwUzO', 'student', '2026-06-13 07:43:04'),
(408, 'Abhijit Chatterjee', 'abhijit_chatterjee6@gmail.com', '$2y$10$CoQkOr34dgYBQv8n4UnolO2gUWHejOuDV5V.oZGDQF5M5.vAFzbIi', 'student', '2026-06-13 07:43:04'),
(410, 'harika', '245621733094.glwec@gmail.com', '$2y$10$RTJZnh36ldmUc0OWFFNsLuUuTIR.Ey8FNMgmKcfYWB4kWzp.Z29DW', 'recruiter', '2026-06-14 06:39:40'),
(412, 'harika', 'harikakondaparthi2124@gmail.com', '$2y$10$CIQrFiB1zBURVYZG8ynTJO3rAPxohAOCkpe1WJMFZ7gWDZJU.cU.y', 'student', '2026-06-14 18:35:54');

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_badges`
--

INSERT INTO `user_badges` (`id`, `user_id`, `badge_id`, `earned_at`) VALUES
(1, 412, 1, '2026-06-14 20:15:48'),
(2, 412, 11, '2026-06-14 20:15:48'),
(3, 412, 12, '2026-06-14 20:15:48'),
(7, 20, 1, '2026-06-15 18:36:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alumni_mentorship`
--
ALTER TABLE `alumni_mentorship`
  ADD PRIMARY KEY (`id`),
  ADD KEY `alumni_id` (`alumni_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `alumni_profiles`
--
ALTER TABLE `alumni_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `alumni_referrals`
--
ALTER TABLE `alumni_referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `alumni_id` (`alumni_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`job_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `chatbot_logs`
--
ALTER TABLE `chatbot_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coding_problems`
--
ALTER TABLE `coding_problems`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coding_submissions`
--
ALTER TABLE `coding_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `problem_id` (`problem_id`);

--
-- Indexes for table `coding_test_cases`
--
ALTER TABLE `coding_test_cases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `problem_id` (`problem_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `eligibility_criteria`
--
ALTER TABLE `eligibility_criteria`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forum_categories`
--
ALTER TABLE `forum_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forum_likes`
--
ALTER TABLE `forum_likes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `internships`
--
ALTER TABLE `internships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `internship_applications`
--
ALTER TABLE `internship_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_intern_app` (`internship_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `interview_experiences`
--
ALTER TABLE `interview_experiences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `login_activity`
--
ALTER TABLE `login_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `placement_rounds`
--
ALTER TABLE `placement_rounds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `resume_analysis`
--
ALTER TABLE `resume_analysis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `round_eligible`
--
ALTER TABLE `round_eligible`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_re` (`round_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `scheduled_tests`
--
ALTER TABLE `scheduled_tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `test_id` (`test_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_id` (`user_id`);

--
-- Indexes for table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `test_answers`
--
ALTER TABLE `test_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `test_attempts`
--
ALTER TABLE `test_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `test_id` (`test_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `test_questions`
--
ALTER TABLE `test_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `test_id` (`test_id`);

--
-- Indexes for table `two_factor_settings`
--
ALTER TABLE `two_factor_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_badge` (`user_id`,`badge_id`),
  ADD KEY `badge_id` (`badge_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alumni_mentorship`
--
ALTER TABLE `alumni_mentorship`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alumni_profiles`
--
ALTER TABLE `alumni_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alumni_referrals`
--
ALTER TABLE `alumni_referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot_logs`
--
ALTER TABLE `chatbot_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coding_problems`
--
ALTER TABLE `coding_problems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `coding_submissions`
--
ALTER TABLE `coding_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coding_test_cases`
--
ALTER TABLE `coding_test_cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `eligibility_criteria`
--
ALTER TABLE `eligibility_criteria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `forum_categories`
--
ALTER TABLE `forum_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `forum_likes`
--
ALTER TABLE `forum_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_replies`
--
ALTER TABLE `forum_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internships`
--
ALTER TABLE `internships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `internship_applications`
--
ALTER TABLE `internship_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interview_experiences`
--
ALTER TABLE `interview_experiences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `login_activity`
--
ALTER TABLE `login_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=436;

--
-- AUTO_INCREMENT for table `placement_rounds`
--
ALTER TABLE `placement_rounds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `resume_analysis`
--
ALTER TABLE `resume_analysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `round_eligible`
--
ALTER TABLE `round_eligible`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scheduled_tests`
--
ALTER TABLE `scheduled_tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=536;

--
-- AUTO_INCREMENT for table `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `test_answers`
--
ALTER TABLE `test_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `test_attempts`
--
ALTER TABLE `test_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `test_questions`
--
ALTER TABLE `test_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `two_factor_settings`
--
ALTER TABLE `two_factor_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=413;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alumni_mentorship`
--
ALTER TABLE `alumni_mentorship`
  ADD CONSTRAINT `alumni_mentorship_ibfk_1` FOREIGN KEY (`alumni_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alumni_mentorship_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `alumni_profiles`
--
ALTER TABLE `alumni_profiles`
  ADD CONSTRAINT `alumni_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `alumni_referrals`
--
ALTER TABLE `alumni_referrals`
  ADD CONSTRAINT `alumni_referrals_ibfk_1` FOREIGN KEY (`alumni_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alumni_referrals_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coding_submissions`
--
ALTER TABLE `coding_submissions`
  ADD CONSTRAINT `coding_submissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coding_submissions_ibfk_2` FOREIGN KEY (`problem_id`) REFERENCES `coding_problems` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coding_test_cases`
--
ALTER TABLE `coding_test_cases`
  ADD CONSTRAINT `coding_test_cases_ibfk_1` FOREIGN KEY (`problem_id`) REFERENCES `coding_problems` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `companies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD CONSTRAINT `forum_posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD CONSTRAINT `forum_replies_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `internships`
--
ALTER TABLE `internships`
  ADD CONSTRAINT `internships_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `internship_applications`
--
ALTER TABLE `internship_applications`
  ADD CONSTRAINT `internship_applications_ibfk_1` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `internship_applications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `interviews_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interviews_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interviews_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interview_experiences`
--
ALTER TABLE `interview_experiences`
  ADD CONSTRAINT `interview_experiences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_activity`
--
ALTER TABLE `login_activity`
  ADD CONSTRAINT `login_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notices`
--
ALTER TABLE `notices`
  ADD CONSTRAINT `notices_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `placement_rounds`
--
ALTER TABLE `placement_rounds`
  ADD CONSTRAINT `placement_rounds_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resume_analysis`
--
ALTER TABLE `resume_analysis`
  ADD CONSTRAINT `resume_analysis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `round_eligible`
--
ALTER TABLE `round_eligible`
  ADD CONSTRAINT `round_eligible_ibfk_1` FOREIGN KEY (`round_id`) REFERENCES `placement_rounds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `round_eligible_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scheduled_tests`
--
ALTER TABLE `scheduled_tests`
  ADD CONSTRAINT `scheduled_tests_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scheduled_tests_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD CONSTRAINT `student_attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `tests_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `test_answers`
--
ALTER TABLE `test_answers`
  ADD CONSTRAINT `test_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `test_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `test_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `test_attempts`
--
ALTER TABLE `test_attempts`
  ADD CONSTRAINT `test_attempts_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test_attempts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `test_questions`
--
ALTER TABLE `test_questions`
  ADD CONSTRAINT `test_questions_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `two_factor_settings`
--
ALTER TABLE `two_factor_settings`
  ADD CONSTRAINT `two_factor_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
