<?php
require_once '../includes/functions_fixed.php';

// Destroy the session
session_destroy();

// Redirect to home page
redirect('../index.php');
?> 