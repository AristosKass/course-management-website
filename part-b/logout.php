<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_post();
logout_user();
redirect_to('login.php');
