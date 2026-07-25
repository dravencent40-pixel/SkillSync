<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
session_destroy();
session_start();
flash('success', 'Kamu telah keluar.');
redirect('login.php');
