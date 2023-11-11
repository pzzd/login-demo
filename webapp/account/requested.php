<?php
namespace Pzzd\LoginDemo;
require __DIR__ . "/../../vendor/autoload.php";

$app = Application::app();

if (strpos($_SERVER['HTTP_REFERER'], 'account/requestreset.php') === false)
{
	$app->redirect($app->base());
}

$template = $app->twigtemplate("account/requested.html");
echo $template->render([]);
