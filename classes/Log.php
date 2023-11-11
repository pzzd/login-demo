<?php
namespace Pzzd\LoginDemo;

trait Log 
{
	private $logFields = [
		'DateTime',
		'Event',
		'Level',
		'Description',
		'SourceIp',
		'HostIp',
		'HostProtocol',
		'HostPort',
		'RequestUri',
		'RequestMethod'
	];

	private $loggingIsEnabled = false;
	private $loggingDir = '';
	private $loggingFileName = '';

	public function enableLogging (): bool
	{
		$this->loggingIsEnabled = true;
		return true;
	}
 
	private function logFilePath()
	{
		return $this->loggingDir.$this->loggingFileName;
	}

	private function warn (string $event, string $description): bool
	{
		return $this->write(event: $event, level: 'WARN', description: $description);
	}

	private function info (string $event, string $description): bool
	{
		return $this->write(event: $event, level: 'INFO', description: $description);
	}

	private function write (string $event, string $level, string $description): bool
	{
		if ($this->loggingIsEnabled != true) { return false; }
		if (empty(trim($event))) { return false; }

		// values here must match count and order of logFields
		$values = [
			date('c'),
			$event,
			$level,
			$description,
			$_SERVER['HTTP_USER_AGENT'],
			$_SERVER['REMOTE_ADDR'],
			$_SERVER['REQUEST_SCHEME'],
			$_SERVER['SERVER_PORT'],
			$_SERVER['SCRIPT_FILENAME'],
			$_SERVER['REQUEST_METHOD']
		];


		if (is_dir($this->loggingDir) == false)
		{
			mkdir($this->loggingDir);
		}
		$fp = fopen($this->logFilePath(), 'a');
		if (filesize($this->logFilePath()) == 0)
		{
			fputcsv(
				stream: $fp, 
				fields: $this->logFields, 
				separator: ",",
				enclosure: "\"",
				escape: "\\",
				eol: "\n"
			);
		}

		fputcsv($fp, $values);

		fclose($fp);
		return true;
	}
}
