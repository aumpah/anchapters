<?php
require_once '../config/functions.php';
session_unset();
session_destroy();
redirect('auth/login.php');
