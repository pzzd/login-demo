<?php
namespace Pzzd\LoginDemo;
require __DIR__ . "/../vendor/autoload.php";

$app = Application::app();

$app->logOut();

$template = $app->twigtemplate("index.html");
echo $template->render([]);
