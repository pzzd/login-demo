<?php
namespace Pzzd\LoginDemo;

class Application {

	static private $app;
	private $twig;

	public function __construct()
	{
		date_default_timezone_set('America/Denver');

		self::$app = $this;
	}

	public static function app() 
	{
		session_start();

		if(!self::$app) {
			self::$app = new Application();
		}
		return self::$app;
	}



	public function formattedDates($style = 'short')
	{
		$eastern = new \DateTimeZone('America/New_York');
		$d = [];

		switch ($style)
		{
			case 'long':
				$format = 'F j, Y, h:i:s a T';
				break;
			case 'date only':
				$format = 'F j, Y';
				break;
			default:
				$format = 'n/j/Y g:i:s a T';
		}		

		foreach ($this->systemDates() as $key => $value)
		{
			$date = \DateTime::createFromFormat('n/j/Y g:i:s A T', $value);
			$date->setTimezone($eastern);
			$d[$key] = $date->format($format);
		}
		return $d;		
	}

	public function logIn($className, $object)
	{
		$_SESSION[$className] = $object;
		return true;
	}

	public function logOut()
	{
		foreach ($_SESSION as $key =>  $value)
		{
			unset($_SESSION[$key]);
		}
		return true;
	}

	public function base()
	{
		return 'https://login-demo:8890/webapp/';
	}


	public function systemEmailAddress()
	{
		return 'pzzd@fake.eml';
	}

	public function redirect($url)
	{
		header("Location: $url");
		exit;
	}

	public function twigtemplate($templateFile)
	{
		if (! $this->twig)
		{
			$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . "/../templates");
			$this->twig = new \Twig\Environment($loader, [ "charset" => "utf-8" ]);

			$this->twig->addGlobal('base', $this->base());
			$this->twig->addGlobal('WebAppName', 'Initech Systems');
			$this->twig->addGlobal('CacheBuster', '20230724');
		}

		return $this->twig->load($templateFile);
	}

	function getRandomString($n)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';

		for ($i = 0; $i < $n; $i++) 
		{
			$index = rand(0, strlen($characters) - 1);
			$randomString .= $characters[$index];
		}

		return $randomString;
	}

}
