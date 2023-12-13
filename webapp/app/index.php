<?php
namespace Pzzd\LoginDemo;
require __DIR__ . "/../../vendor/autoload.php";

$app = Application::app();
var_dump($_SESSION);

// TODO: if no session, redirect to hom page.
// TODO: if session, write out account info.