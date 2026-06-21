<?php
require_once '../includes/config.php';
requireLogin('admin');
session_destroy();
header("Location: ../index.php");
exit();
