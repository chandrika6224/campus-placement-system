-- 5 Easy Questions
INSERT INTO coding_problems (title, description, difficulty, category, sample_input, sample_output, hints, tags, points, company_tag, year_asked, status) VALUES
('Reverse a String',
'Given a string, print it in reverse order.\n\nInput: A single string\nOutput: Reversed string',
'easy', 'Strings', 'hello', 'olleh', 'Use slicing s[::-1] in Python or a loop.', 'strings,basics', 10, 'TCS', 2023, 'active'),

('Sum of Array',
'Given n integers, find their sum.\n\nInput: First line is n, second line has n space-separated integers.\nOutput: Sum of all integers.',
'easy', 'Arrays', '5\n1 2 3 4 5', '15', 'Use a loop or built-in sum().', 'arrays,math', 10, 'Wipro', 2023, 'active'),

('Count Vowels',
'Count the number of vowels (a, e, i, o, u) in a given string (case-insensitive).\n\nInput: A single string\nOutput: Count of vowels',
'easy', 'Strings', 'Hello World', '3', 'Convert to lowercase, then check each character.', 'strings,counting', 10, 'Infosys', 2022, 'active'),

('Factorial',
'Find the factorial of a given non-negative integer n.\n\nInput: A single integer n (0 <= n <= 12)\nOutput: n!',
'easy', 'Math', '5', '120', 'Use a loop: result = 1, multiply 1 to n.', 'math,loops', 10, 'TCS', 2022, 'active'),

('Palindrome Check',
'Check if a given string is a palindrome (reads same forward and backward). Ignore case.\n\nInput: A single string\nOutput: "Yes" if palindrome, "No" otherwise',
'easy', 'Strings', 'Racecar', 'Yes', 'Convert to lowercase and compare with its reverse.', 'strings,palindrome', 10, 'Cognizant', 2023, 'active'),

-- 5 Medium Questions
('Two Sum',
'Given an array of integers and a target, find two indices i and j such that arr[i] + arr[j] == target. Print the indices (0-based), space-separated. Assume exactly one solution exists.\n\nInput: First line n, second line n integers, third line target\nOutput: Two indices space-separated',
'medium', 'Arrays', '4\n2 7 11 15\n9', '0 1', 'Use a hashmap: for each element, check if (target - element) exists in map.', 'arrays,hashing,two-pointers', 20, 'Amazon', 2023, 'active'),

('Balanced Brackets',
'Given a string containing only ()[]{}. Check if brackets are balanced.\n\nInput: A string\nOutput: "Balanced" or "Not Balanced"',
'medium', 'Stacks', '{[()]}', 'Balanced', 'Use a stack. Push opening brackets, pop and match on closing.', 'stacks,strings', 20, 'Microsoft', 2023, 'active'),

('Missing Number',
'Given an array of n-1 distinct integers in range [1, n], find the missing number.\n\nInput: First line n, second line n-1 space-separated integers\nOutput: The missing number',
'medium', 'Math', '5\n1 2 4 5', '3', 'Expected sum = n*(n+1)/2. Subtract actual sum.', 'math,arrays', 20, 'Infosys', 2023, 'active'),

('Maximum Subarray Sum',
'Find the contiguous subarray with the largest sum (Kadanes Algorithm).\n\nInput: First line n, second line n space-separated integers\nOutput: Maximum subarray sum',
'medium', 'Arrays', '8\n-2 1 -3 4 -1 2 1 -5 4', '6', 'Track current_sum and max_sum. If current_sum < 0, reset to 0.', 'arrays,dp,kadane', 20, 'Microsoft', 2022, 'active'),

('Anagram Check',
'Check if two strings are anagrams of each other (same characters, different order). Ignore case.\n\nInput: Two strings on separate lines\nOutput: "Yes" or "No"',
'medium', 'Strings', 'listen\nsilent', 'Yes', 'Sort both strings and compare, or use a frequency map.', 'strings,sorting,hashing', 20, 'Wipro', 2023, 'active'),

-- 5 Hard Questions
('Longest Common Subsequence',
'Find the length of the longest common subsequence (LCS) of two strings.\n\nInput: Two strings on separate lines\nOutput: Length of LCS',
'hard', 'Dynamic Programming', 'ABCBDAB\nBDCAB', '4', 'Use a 2D DP table. dp[i][j] = LCS of first i chars of s1 and first j chars of s2.', 'dp,strings,classic', 30, 'Google', 2023, 'active'),

('Number of Islands',
'Given a 2D grid of 1s (land) and 0s (water), count the number of islands. An island is surrounded by water and formed by connecting adjacent lands horizontally or vertically.\n\nInput: First line rows and cols, then the grid\nOutput: Number of islands',
'hard', 'Graphs', '4 5\n1 1 0 0 0\n1 1 0 0 0\n0 0 1 0 0\n0 0 0 1 1', '3', 'Use DFS/BFS. For each unvisited land cell, do DFS marking all connected land as visited.', 'graphs,dfs,matrix', 30, 'Amazon', 2023, 'active'),

('Trapping Rain Water',
'Given n non-negative integers representing an elevation map where the width of each bar is 1, compute how much water it can trap after raining.\n\nInput: First line n, second line n space-separated heights\nOutput: Total water trapped',
'hard', 'Arrays', '12\n0 1 0 2 1 0 1 3 2 1 2 1', '6', 'For each index, water = min(max_left, max_right) - height[i]. Use two pointer approach.', 'arrays,two-pointers,classic', 30, 'Google', 2022, 'active'),

('Word Break',
'Given a string s and a dictionary of words, determine if s can be segmented into a space-separated sequence of dictionary words.\n\nInput: First line the string s, second line n (dict size), next n lines are dict words\nOutput: "Yes" or "No"',
'hard', 'Dynamic Programming', 'leetcode\n3\nleet\ncode\nleetcode', 'Yes', 'Use DP. dp[i] = true if s[0..i-1] can be segmented. Check all substrings ending at i.', 'dp,strings,backtracking', 30, 'Microsoft', 2023, 'active'),

('LRU Cache',
'Implement an LRU (Least Recently Used) cache with get and put operations, both in O(1).\nCapacity c, then q queries. Each query is GET key or PUT key value. For each GET print the value (-1 if not found).\n\nInput: First line capacity, second line queries count, then queries\nOutput: Result of each GET',
'hard', 'Data Structures', '2\n5\nPUT 1 10\nPUT 2 20\nGET 1\nPUT 3 30\nGET 2', '10\n-1', 'Use OrderedDict in Python or doubly linked list + hashmap. On GET, move to front. On PUT, evict LRU if full.', 'data-structures,design,hashmap', 30, 'Amazon', 2022, 'active');
