<?php declare(strict_types=1);

namespace Tracy\Remote;

class Bar
{
	private const DATA_DIR = __DIR__ . '/../data';
	private const DATA_FILE = self::DATA_DIR . '/data.json';
	private const LOCK_FILE = self::DATA_DIR . '/data.json.lock';

	/** @var resource|NULL */
	private $lockHandle = NULL;

	/** @var array<int, string> */
	private array $data = [];


	public function load(): void
	{
		if ($this->lockHandle !== NULL) {
			throw new \RuntimeException('Data are already loaded.');
		}

		$this->lockHandle = \fopen(self::LOCK_FILE, 'c+');
		if (($this->lockHandle === FALSE) || !\flock($this->lockHandle, \LOCK_EX)) {
			throw new \RuntimeException(\sprintf('Unable to create or acquire exclusive lock on file \'%s\'.', self::LOCK_FILE));
		}

		if (file_exists(self::DATA_FILE)) {
			$this->data = json_decode(file_get_contents(self::DATA_FILE));
		}
	}


	public function barCount(): int
	{
		return count($this->data);
	}


	public function getBar(int $id): ?string
	{
		return $this->data[$id - 1] ?? NULL;
	}


	public function addBar(string $bar): void
	{
		$this->data[] = $bar;
	}


	public function clear(): void
	{
		$this->data = [];
	}


	public function write(): void
	{
		if ($this->lockHandle === NULL) {
			throw new \RuntimeException('Data are not loaded.');
		}

		file_put_contents(self::DATA_FILE, json_encode($this->data));

		\flock($this->lockHandle, \LOCK_UN);
		\fclose($this->lockHandle);
	}

}
