<?php
require_once '../includes/config.php';
requireLogin('recruiter');
session_destroy();
header("Location: ../index.php");
exit();
