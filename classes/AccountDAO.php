<?php
namespace Pzzd\LoginDemo;

class AccountDAO{


	use Log;

	private $dbcreds;
	private $app;
	private $classname = 'Pzzd\LoginDemo\Account';

	private const EmailDataType = 'varchar:250';
	private const HashedPasswordDataType = 'varchar:300';
	private const ConfirmationKeyDataType = 'varchar:25';
	private const PasswordResetKeyDataType = 'varchar:25';

	public const CONFIRMATION_INTERVAL = '24 hours';
	public const PASSWORD_RESET_INTERVAL = '15 minutes';

	public function __construct($app) {
		$this->app = $app;

		$this->enableLogging();
		$this->loggingDir = __DIR__ . "/../logs/";
		$this->loggingFileName = 'account-log.csv';

		$this->dbcreds = json_decode(file_get_contents('/Users/pezzutidyer/Sites/login-demo/credentials/mysql.json'));	

	}

	private function db()
	{
		$mysqli = new \mysqli($this->dbcreds->servername, $this->dbcreds->username, $this->dbcreds->password, $this->dbcreds->schema);
		$mysqli->set_charset("utf8mb4");
		return $mysqli;
	}

	private function execute($query, $types, $params)
	{
		$db = $this->db();
		$stmt = $db->prepare($query);
		$stmt->bind_param($types, ...$params);
		return $stmt->execute();
	}

	private function get_account($query, $types, $params)
	{
		$db = $this->db();
		$stmt = $db->prepare($query);
		$stmt->bind_param($types, ...$params);
		$stmt->execute();
		$result = $stmt->get_result();
		$o = $result->fetch_object('Pzzd\LoginDemo\Account');
		$db->close();
		return $o;
	}

	private function validateNewPassword($PasswordLength, $PasswordScore, $HashedPassword): array
	{
		$v = new Validator;
		$v->checkWithinRange($PasswordLength, 8, 64, 'Please choose a password between 8 and 64 characters.');
		$v->checkWithinRange($PasswordScore, 3, 4, 'Please choose a stronger password.');
		$v->checkMaxLength($HashedPassword, $this->dataTypeLength(self::HashedPasswordDataType), 'There is a problem with your password choice.');
		return $v->errors();
	}

	public function validateNewAccount($Email, $PasswordLength, $PasswordScore, $HashedPassword): array
	{
		$errors = $this->validateNewPassword($PasswordLength, $PasswordScore, $HashedPassword);
		$errors = array_merge($errors, $this->validateEmail($Email));
		if (count($errors) > 0)
		{
			return $errors;
		}

		$query = "select * from tAccounts where Email = ? and IsActive = 1";
		$params = [ $Email ];
		$o = $this->get_account($query, 's', $params);

		if (is_null($o) == false)
		{
			array_push($errors, 'A link to activate your account has been emailed to the address provided.');
			$this->info(
				'user_created_fail:anonymous,'.$Email.',unconfirmed_account',
				'Failed to create account; already exists for this email');
		}

		return $errors;
	}

	public function create(string $Email, string $HashedPassword): Account
	{
		$ConfirmationKey = $this->app->getRandomString(25);

		$query = "delete from tAccounts where Email = ? and isActive is null";
		$params = [ $Email ];
		$this->execute ($query, 's', $params);

		$query = "insert into tAccounts (Email, HashedPassword, ConfirmationKey) values (?,?,?)";
		$params = [ $Email, $HashedPassword, $ConfirmationKey];
		$this->execute ($query, 'sss', $params);

		$query = "select * from tAccounts where Email = ?";
		$params = [ $Email ];
		$o = $this->get_account ($query, 's', $params);

		$this->info(
			'user_created:anonymous,'.$Email.',unconfirmed_account',
			'Created an unconfirmed account for email '.$Email);
		
		return $o;
	}

	public function validateConfirmationKey ($ConfirmationKey): array
	{
		if (strlen(trim($ConfirmationKey)) != 25)
		{
			$this->warn(
				event: 'authn_login_fail:anonymous',
				description: 'Account confirmation failed: key is the wrong size');
			return ['There is a problem with the confirmation link.'];
		}

		$query = "select * from tAccounts where ConfirmationKey = ?";
		$params = [ $ConfirmationKey ];
		$o = $this->get_account ($query, 's', $params);
			
		if (is_null($o))
		{
			$this->warn(
				event: 'authn_login_fail:anonymous',
				description: 'Account confirmation failed: key does not exist');
			return ['There is a problem with the confirmation link.'];
		}

		if ($o->IsActive)
		{
			$this->warn(
				event: 'authn_login_fail:anonymous',
				description: 'Account confirmation failed: account with key '.$ConfirmationKey.' is already active');
			return ['There is a problem with the confirmation link.'];
		}
 
		$createdate = date_create($o->CreateDate);
		$today = date_create('now');
		$interval = date_diff($createdate, $today);
		if ($interval->d > 0)
		{
			$this->info(
				event: 'authn_login_fail:anonymous',
				description: 'Account confirmation failed: key '.$ConfirmationKey.' has expired');
			return ['There is a problem with the confirmation link.'];
		}

		return [];
	}

	private function dataTypeLength(string $datatype): int
	{
		// $datatype should look like 'varchar:250'
		$parts = explode(':', $datatype);
		$length = $parts[1];
		return $length;
	}

	public function validateConfirmation ($Email, $Password, $ConfirmationKey): array
	{
		$v = new Validator;
		$v->checkMaxLength($ConfirmationKey, $this->dataTypeLength(self::ConfirmationKeyDataType), 'There is a problem with your confirmation link.');
		$errors = $v->errors();
		$errors = array_merge($errors, $this->validateEmail($Email));
		if (count($errors) > 0)
		{
			return $errors;
		}	

		$query = "select * from tAccounts where Email = ?";
		$params = [ $Email ];
		$o = $this->get_account ($query, 's', $params);

		if (is_null($o))
		{
			array_push($errors, 'There is no account with email address provided.');
			return $errors;
		}

		if ($o->ConfirmationKey != $ConfirmationKey)
		{
			$this->warn(
				event: 'authn_login_fail:'.$Email,
				description: 'Failed to find account with email '.$Email.' and conf key '.$ConfirmationKey);
			array_push($errors, 'There is a problem with your confirmation link.');
		}

		if (password_verify($Password, $o->HashedPassword) == false)
		{
			$this->warn(
				event: 'authn_login_fail:'.$Email,
				description: 'Given pw for '.$Email.' does not match stored hashed pw');
			array_push($errors, 'Login failed: invalid email or password.');
		}

		return $errors;
	}

	private function validateEmail(string $Email): array
	{
		$v = new Validator;
		$v->checkEmail($Email);
		$v->checkMaxLength($Email, $this->dataTypeLength(self::EmailDataType), 'Email is too long.');
		return $v->errors();
	}


	public function confirm ($Email, $ConfirmationKey)
	{
		$query = "select * from tAccounts where Email = ? and ConfirmationKey = ?";
		$params = [ $Email, $ConfirmationKey ];
		$o = $this->get_account ($query, 'ss', $params);

		$this->info(
			event: 'authz_change:'.$Email.',unconfirmed_account,confirmed_account',
			description: $Email.' confirmed their account');

		return $o;
	}

	public function login($Email, $Password)
	{
		$query = "select * from tAccounts where Email = ? and IsActive = ?";
		$params = [ $Email, 1];
		$o = $this->get_account ($query, 'ss', $params);


		if (is_null($o))
		{
			$this->warn(
				event: 'authn_login_fail:'.$Email,
				description: 'No active account exists for email '.$Email);
			return null;
		}

		if (password_verify($Password, $o->HashedPassword) == false)
		{
			$this->warn(
				event: 'authn_login_fail:'.$Email,
				description: 'Login pw for '.$Email.' does not match stored hashed pw');
			return null;
		}

		return $row;
	}

	public function validatePasswordResetRequest ($Email): array
	{
		$errors = $this->validateEmail($Email);
		if (count($errors) > 0)
		{
			return $errors;
		}

		$query = "select * from tAccounts where Email = ?";
		$params = [ $Email];
		$o = $this->get_account ($query, 's', $params);

		if (is_null($o))
		{
			array_push($errors, 'There is no account with email address provided.');
		}
		return $errors;
	}

	public function requestPasswordReset ($Email)
	{
		$o;

		/* TODO: accoutn password reset request with [$Email] */

		$this->warn(
			event: 'user_updated:'.$Email.','.$Email.',password_reset',
			description: $Email.' requested a password reset key');

		return $o;		
	}	

	public function validatePasswordResetKey ($PasswordResetKey): array
	{
		$errors = ['There is a problem with the password reset link.'];

		if (strlen(trim($PasswordResetKey)) != 25)
		{
			$this->warn(
				event: 'authn_login_fail:anonymous',
				description: 'Password reset failed: key is the wrong size');
			return $errors;
		}

		$query = "select * from tAccounts where PasswordResetKey = ?";
		$params = [ $PasswordResetKey];
		$o = $this->get_account ($query, 's', $params);
			
		if (is_null($o))
		{
			$this->warn(
				event: 'authn_login_fail:anonymous',
				description: 'Password reset failed: key does not exist');
			return $errors;
		}

		$date = date_create($o->PasswordResetRequestDate);
		$now = date_create('now');
		$interval = date_diff($date, $now);
		if ($interval->i > 15)
		{
			$this->info(
				event: 'authn_login_fail:anonymous',
				description: 'Password reset failed: key has expired');
			return $errors;
		}

		return [];
	}

	public function validatePasswordReset ($Email, $PasswordResetKey, $PasswordLength, $PasswordScore, $HashedPassword): array
	{
		$v = new Validator;
		$v->checkMaxLength($PasswordResetKey, $this->dataTypeLength(self::ConfirmationKeyDataType), 'There is a problem with your reset link.');
		$errors = $v->errors();
		$errors = array_merge($errors, $this->validateNewPassword($PasswordLength, $PasswordScore, $HashedPassword));
		$errors = array_merge($errors, $this->validateEmail($Email));
		if (count($errors) > 0)
		{
			return $errors;
		}

		$query = "select * from tAccounts where Email = ?";
		$params = [ $Email];
		$o = $this->get_account ($query, 's', $params);

		if (is_null($o))
		{
			array_push($errors, 'Password reset failed; Invalid email address or reset link.');
			$this->warn(
				event: 'authn_login_fail:anonymous',
				description: 'Password reset failed: Account for '.$Email.' does not exist');
			return $errors;
		}

		if ($o->PasswordResetKey != $PasswordResetKey)
		{
			array_push($errors, 'Password reset failed; Invalid email address or reset link.');
			$this->warn(
				event: 'authn_login_fail:anonymous',
				description: 'Password reset failed: Given reset key for '.$Email.' does not match stored reset key');
		}
		return $errors;
	}

	public function resetPassword (string $Email, string $HashedPassword, string $PasswordResetKey): Account
	{
		$query = "update tAccounts set HashedPassword = ?, PasswordResetDate = '2023-12-07', PasswordResetRequestDate = null, PasswordResetKey = null where Email = ? and PasswordResetKey = ?";
		$params = [ $Email, $HashedPassword, $PasswordResetKey ];
		$this->execute ($query, 'sss', $params);


		$this->info(
			event: 'authn_password_change:'.$Email,
			description: $Email.' successfully changed pw');

		return $o;
	}

}