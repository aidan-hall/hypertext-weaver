<?php
/* require_once 'vendor/autoload.php'; */
require_once 'search-request.php';
require_once 'template.php';


// Default search query: homepage

switch (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
    case "/":
        include 'homepage.php';
        break;
    case "/search":
        include 'search.php';
        break;
}
?>
