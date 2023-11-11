<?php
namespace Pzzd\LoginDemo;
require __DIR__ . "/../../vendor/autoload.php";

$app = Application::app();
$accountdao = new AccountDAO($app);
$accountdao->enableLogging();

$email = new Email($app);                        
$errors = [];
$created = false;
$messages = [];

if (sizeof($_POST) > 0)
{
	$Email = $_POST['Email'];

	$errors = $accountdao->validatePasswordResetRequest($Email);

	if (count($errors) == 0)
	{
		$account = $accountdao->requestPasswordReset($Email);
		if (is_null($account) == false)
		{
			$sendresult = $email->sendNewPasswordKey($account);
			$app->redirect('requested.php');
		}
	}
}


$templatedata = [];
$templatedata['errors'] = $errors;
$template = $app->twigtemplate("account/requestreset.html");
echo $template->render($templatedata);
