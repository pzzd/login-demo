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
	$PasswordScore = $_POST['PasswordScore'];

	$PasswordLength = strlen($_POST['Password']);
	$HashedPassword = password_hash($_POST['Password'], PASSWORD_ARGON2I);

	$errors = $accountdao->validateNewAccount($Email, $PasswordLength, $PasswordScore, $HashedPassword);

	if (count($errors) == 0)
	{
		$account = $accountdao->create($Email, $HashedPassword);
		if (is_null($account) == false)
		{
			$sendresult = $email->sendNewAccountLink($Email, $account->ConfirmationKey);
			$app->redirect('created.php');
		}
	}
}


$templatedata = [];
$templatedata['errors'] = $errors;
$templatedata['created'] = $created;
$template = $app->twigtemplate("account/create.html");
echo $template->render($templatedata);
