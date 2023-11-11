<?php
namespace Pzzd\LoginDemo;
require __DIR__ . "/../../vendor/autoload.php";

$app = Application::app();

if (strpos($_SERVER['HTTP_REFERER'], 'account/reset.php') === false)
{
	$app->redirect($app->base());
}

$template = $app->twigtemplate("account/wasreset.html");
echo $template->render([]);
