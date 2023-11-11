<?php
namespace Pzzd\LoginDemo;
require __DIR__ . "/../../vendor/autoload.php";

$app = Application::app();

if (strpos($_SERVER['HTTP_REFERER'], 'account/create.php') === false)
{
	$app->redirect($app->base());
}

$template = $app->twigtemplate("account/created.html");
echo $template->render([]);
