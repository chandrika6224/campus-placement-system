<?php
require_once '../includes/config.php';
requireLogin('student');
session_destroy();
header("Location: ../index.php");
exit();
