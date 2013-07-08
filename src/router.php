<?php
$_GET['q'] = $_SERVER['SCRIPT_NAME'];
if($_GET['q'] != "/" && file_exists(".".$_GET['q'])) {
    return false;
} else {
    chdir('src');
    include_once 'index.php';
}
