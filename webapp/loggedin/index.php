<?php
namespace Pzzd\LoginDemo;
require __DIR__ . "/../../vendor/autoload.php";

$app = Application::app();

if (count ($_SESSION) == 0)
{
	$app->redirect('../');
}

$templatedata = array();
$templatedata['Account'] = $_SESSION['Account'];

$template = $app->twigtemplate("loggedin/index.html");
echo $template->render($templatedata);

