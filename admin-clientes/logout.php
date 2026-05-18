<?php
/**
 * Logout Handler
 */
require_once 'config.php';

// Destroy session
session_destroy();

// Redirect to login
header('Location: ' . BASE_PATH . 'index.php');
exit();
