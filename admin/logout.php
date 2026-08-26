<?php
require_once __DIR__ . '/../includes/session.php';
startSecureSession();
session_destroy();
header('Location: login.php');
exit;
