<?php declare(strict_types=1);

use Tracy\Debugger;
use Tracy\Remote;

require __DIR__ . '/../../tracy.php';
require __DIR__ . '/../src/Bar.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? 'not-found';

['path' => $path] = parse_url($requestUri);

switch (strtolower(trim($path, '/'))) {
	case '':
		require __DIR__ . '/../src/assets/app.phtml';
		exit();

	case 'api':
		$bar = new Remote\Bar();
		$bar->load();

		// Enable CORS for origin
		header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, DELETE');

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$bar->addBar(file_get_contents('php://input'));
		} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			if (isset($_GET['id'])) {
				$html = $bar->getBar((int) $_GET['id']);
				if ($html === null) {
					http_response_code(403);
				} else {
					if (Debugger::getStrategy()->sendAssets()) {
						return;
					}
					echo $html;
				}
			} else {
				echo $bar->barCount();
			}
		} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
			$bar->clear();
		}

		$bar->write();

		exit();

	case 'tracy-assets':
		Debugger::getStrategy()->sendAssets();
		exit();
}

http_response_code(404);
