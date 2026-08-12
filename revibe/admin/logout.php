<?php
/**
 * Admin-Logout
 */
require_once '../config/config.php';

setSecurityHeaders();

logoutAdmin();
redirect('/admin/login.php');
