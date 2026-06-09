<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

session_unset();
session_destroy();

redirect('login.php');
