<?php declare(strict_types=1);

namespace Forrest79\TracyRemoteDevelopmentStrategy;

use Composer;

final class Patcher
{
	private const MIN_TRACY_VERSION = '2.10.9';


	public static function patch(): void
	{
		$dir = sys_get_temp_dir() . '/tracy-remotebar/patched';
		if (!is_dir($dir) && !mkdir($dir, recursive: TRUE) && !is_dir($dir)) {
			throw new \RuntimeException(sprintf('Directory "%s" was not created.', $dir));
		}

		$appDir = __DIR__ . '/../../../..';
		$composerLockMTime = self::composerLockMTime($appDir);
		if ($composerLockMTime === NULL) {
			$appDir = Helper::classDir(Composer\Autoload\ClassLoader::class) . '/../..';
			$composerLockMTime = self::composerLockMTime($appDir);
			if ($composerLockMTime === NULL) {
				throw new \RuntimeException('Can\'t detect composer lock file.');
			}
		}

		$dir = Helper::createTempDir('tracy-remotebar/patched/' . $composerLockMTime);
		$patchedDebuggerFile = $dir . '/Debugger.php';
		$patchedDevelopmentStrategyFile =  $dir . '/DevelopmentStrategy.php';
		if (!is_file($patchedDebuggerFile) || !is_file($patchedDevelopmentStrategyFile)) {
			$tracyVersion = self::detectTracyVersion($appDir);
			if (version_compare($tracyVersion, self::MIN_TRACY_VERSION, '<')) {
				throw new \RuntimeException(sprintf('Tracy minimal version is \'%s\', your version is \'%s\'.', self::MIN_TRACY_VERSION, $tracyVersion));
			}

			self::patchDebuggerPhp($appDir, $patchedDebuggerFile);
			self::patchDevelopmentStrategyPhp($appDir, $patchedDevelopmentStrategyFile);
		}

		require $patchedDebuggerFile;
		require $patchedDevelopmentStrategyFile;
	}


	private static function patchDebuggerPhp(string $appDir, string $patchedFile): void
	{
		$patchedDebuggerCode = file_get_contents($appDir . '/vendor/tracy/tracy/src/Tracy/Debugger/Debugger.php');

		$search1 = 'if (self::$showBar && !Helpers::isCli())';
		if (!str_contains($patchedDebuggerCode, $search1)) { // can't find where to put patch (new Tracy version?)
			throw new \RuntimeException(sprintf('Can\'t find code "%s" in \'Debugger.php\'.', $search1));
		}

		$patchedDebuggerCode = str_replace(
			$search1,
			'if (self::$showBar && (!Helpers::isCli() || \Forrest79\TracyRemoteDevelopmentStrategy\RemoteBar::isRemoteActive()))',
			$patchedDebuggerCode,
		);

		$search2 = 'new DevelopmentStrategy(';
		if (!str_contains($patchedDebuggerCode, $search2)) { // can't find where to put patch (new Tracy version?)
			throw new \RuntimeException(sprintf('Can\'t find code "%s" in \'Debugger.php\'.', $search2));
		}

		$patchedDebuggerCode = str_replace(
			$search2,
			'new \Forrest79\TracyRemoteDevelopmentStrategy\Tracy\DevelopmentStrategy(',
			$patchedDebuggerCode,
		);

		$search3 = 'require_once dirname(__DIR__) . "/$path.php";';
		if (!str_contains($patchedDebuggerCode, $search3)) { // can't find where to put patch (new Tracy version?)
			throw new \RuntimeException(sprintf('Can\'t find code "%s" in \'Debugger.php\'.', $search3));
		}

		file_put_contents($patchedFile, str_replace(
			$search3,
			'require_once "' . realpath($appDir . '/vendor/tracy/tracy/src/Tracy') . '/$path.php";',
			$patchedDebuggerCode,
		));
	}


	private static function patchDevelopmentStrategyPhp(string $appDir, string $patchedFile): void
	{
		$patchedDevelopmentStrategyCode = file_get_contents($appDir . '/vendor/tracy/tracy/src/Tracy/Debugger/DevelopmentStrategy.php');

		$search = 'final class DevelopmentStrategy';
		if (!str_contains($patchedDevelopmentStrategyCode, $search)) { // can't find where to put patch (new Tracy version?)
			throw new \RuntimeException(sprintf('Can\'t find code "%s" in \'DevelopmentStrategy.php\'.', $search));
		}

		file_put_contents($patchedFile, str_replace(
			$search,
			'class DevelopmentStrategy',
			$patchedDevelopmentStrategyCode,
		));
	}


	private static function detectTracyVersion(string $appDir): string
	{
		$composerLockData = file_get_contents($appDir . '/composer.lock');
		$composerLock = json_decode($composerLockData, associative: TRUE, flags: JSON_THROW_ON_ERROR);
		foreach ($composerLock['packages'] as $package) {
			if ($package['name'] === 'tracy/tracy') {
				return substr($package['version'], 1);
			};
		};

		throw new \RuntimeException('There is missing \'tracy/trayc\' package in your vendor.');
	}


	private static function composerLockMTime(string $appDir): int|NULL
	{
		$lockMtime = @filemtime($appDir . '/composer.lock'); // intentionally @ - file may not exist
		if ($lockMtime === FALSE) {
			return NULL;
		}

		return $lockMtime;
	}

}
