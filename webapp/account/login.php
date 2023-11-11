<?php
namespace Pzzd\LoginDemo;
require __DIR__ . "/../../vendor/autoload.php";

$app = Application::app();
$accountdao = new AccountDAO($app);
$accountdao->enableLogging();
$error = "";


$app->logOut();


if (sizeof($_POST) > 0)
{
	$account = $accountdao->login($_POST['Email'], $_POST['Password']);

	if ($account == null)
	{
		$error .= 'No account found.';
	}

	if ($error == "")
	{
		$app->logIn('Account',$account);
		$app->redirect('../loggedin/');
	}
}



$templatedata = array();
$templatedata['error'] = $error;

$template = $app->twigtemplate("account/login.html");
echo $template->render($templatedata);
