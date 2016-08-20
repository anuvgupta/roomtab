<?php

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
    $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header("Location: $url");
    die();
}

?>
