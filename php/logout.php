<?php
require_once '../includes/functions.php';

// Destroy the session
session_destroy();

// Redirect to home page
redirect('../index.html');
?> 