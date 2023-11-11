<?php
namespace Pzzd\LoginDemo;
require __DIR__ . "/../../vendor/autoload.php";

$app = Application::app();
$accountdao = new AccountDAO($app);
$accountdao->enableLogging();

$email = new Email($app);                        
$keyerrors = [];
$reseterrors = [];
$reset = false;
$messages = [];

unset($_SESSION['Account']);
if (array_key_exists('key', $_GET))
{
	$PasswordResetKey = $_GET['key'];
	$keyerrors = $accountdao->validatePasswordResetKey($PasswordResetKey);
	if (count($keyerrors) == 0)
	{
		$_SESSION['PasswordResetKey'] = $PasswordResetKey;
	}

}

if (sizeof($_POST) > 0)
{
	$Email = $_POST['Email'];
	$PasswordResetKey = $_SESSION['PasswordResetKey'];
	$PasswordScore = $_POST['PasswordScore'];

	ws_exception_notrace_begin();
	$PasswordLength = strlen($_POST['Password']);
	$HashedPassword = password_hash($_POST['Password'], PASSWORD_ARGON2I);
	ws_exception_notrace_end();

	$reseterrors = $accountdao->validatePasswordReset($Email, $PasswordResetKey, $PasswordLength, $PasswordScore, $HashedPassword);

	if (count($reseterrors) == 0)
	{
		$account = $accountdao->resetPassword ($Email, $HashedPassword, $PasswordResetKey);
		if (is_null($account) == false)
		{
			$sendresult = $email->sendNewPasswordConfirmation($account);
			$app->redirect('wasreset.php');
		}
	}
}


$templatedata = [];
$templatedata['keyerrors'] = $keyerrors;
$templatedata['reseterrors'] = $reseterrors;
$template = $app->twigtemplate("account/reset.html");
echo $template->render($templatedata);
