<?php
namespace Pzzd\LoginDemo;



class Email {

	use Log;

	private $app;

	private $ToAddress;
	private $FromAddress;
	private $Subject;
	private $Body;

	public function __construct($app) 
	{
		$this->app = $app;
		$this->enableLogging();
		$this->loggingDir = __DIR__ . "/../logs/";
		$this->loggingFileName = 'email-log.csv';
	}

	private function toString()
	{
		return "To: $this->ToAddress
From: $this->FromAddress
Subject: $this->Subject

$this->Body
";
	}

	private  function send()
	{
		$filename = __DIR__ . "/../inbox/".strtotime("now").".email";
		file_put_contents($filename, $this->toString());

		$sent = true;

		if ($sent)
		{
			$this->info(
				'email_success',
				$this->Subject.', from '.$this->FromAddress.', to '.$this->ToAddress);
		}

		if ($sent == false)
		{
			$this->warn(
				'email_failure',
				$this->Subject.', from '.$this->FromAddress.', to '.$this->ToAddress);
		}

		return $sent;
	}

	public function sendNewAccountLink($Email, $ConfirmationKey)
	{
		$this->ToAddress = $Email;
		$this->Subject = 'Account Created';

		$this->Body = 'Thank you for your interest in my app. Please confirm your email address by clicking the link below. The link will expire in '.AccountDAO::CONFIRMATION_INTERVAL.'.

'.$this->app->base().'account/confirm.php?key='.$ConfirmationKey.'

After confirming and loggin in you can begin using the app.';
		return $this->send();
	}

	public function sendConfirmedAccountNotice($Email)
	{
		$this->ToAddress = $Email;
		$this->Subject = 'Account Confirmed';

		$this->Body = 'Thank you for your interest in my app. Your account has been confirmed. You can now log in to use the app.';
		return $this->send();
	}

	public function sendNewPasswordKey($account)
	{
		$this->ToAddress = $account->Email;
		$this->Subject = 'Password Reset Request';

		$this->Body = 'This message is in response to your request to reset your password. To reset your password, please click on the following link:

'.$this->app->base().'account/reset.php?key='.$account->PasswordResetKey.'

The link will expire in '.AccountDAO::PASSWORD_RESET_INTERVAL.'.';
		return $this->send();
	}

	public function sendNewPasswordConfirmation($account)
	{
		$this->ToAddress = $account->Email;
		$this->Subject = 'Password Changed';

		$this->Body = 'This message is to notify you that your password has been reset.';
		return $this->send();
	}
}