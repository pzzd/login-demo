<?php
namespace Pzzd\LoginDemo;
require __DIR__ . "/../../vendor/autoload.php";

$app = Application::app();
$accountdao = new AccountDAO($app);
$accountdao->enableLogging();

$email = new Email($app);                        
$keyerrors = [];
$loginerrors = [];

unset($_SESSION['Account']);

if (array_key_exists('key', $_GET) == false && array_key_exists('ConfirmationKey', $_SESSION) == false)
{
	$app->redirect($app->base());
}

if (array_key_exists('key', $_GET))
{
	$ConfirmationKey = $_GET['key'];
	$keyerrors = $accountdao->validateConfirmationKey($ConfirmationKey);
	if (count($keyerrors) == 0)
	{
		$_SESSION['ConfirmationKey'] = $ConfirmationKey;
	}

}

if (sizeof($_POST) > 0)
{
	$Email = $_POST['Email'];
	$ConfirmationKey = $_SESSION['ConfirmationKey'];
	
	$loginerrors = $accountdao->validateConfirmation($Email, $_POST['Password'], $ConfirmationKey);

	if (count($loginerrors) == 0)
	{
		$account = $accountdao->confirm($Email, $ConfirmationKey);
		
		if (is_null($account))
		{
			array_push($loginerrors, 'An error occurred when confirming your account.');
		}
		else
		{
			unset($_SESSION['ConfirmationKey']);
			$app->logIn('Account', $account);

			$email->sendConfirmedAccountNotice($Email);
			$app->redirect('../app/indexphp');
		}
	
	}
}
 


$templatedata = [];
$templatedata['keyerrors'] = $keyerrors;
$templatedata['loginerrors'] = $loginerrors;
$template = $app->twigtemplate("account/confirm.html");
echo $template->render($templatedata);
