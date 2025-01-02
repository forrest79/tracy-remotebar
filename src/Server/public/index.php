<?php declare(strict_types=1);

use Forrest79\TracyRemoteBar\Server;
use Tracy\Debugger;

if (!@include_once __DIR__ . '/../../../../../autoload.php') { // intentionally @ - file may not exists in package development
	assert(is_string($_SERVER['DOCUMENT_ROOT']));
	$vendorPosition = strpos($_SERVER['DOCUMENT_ROOT'], '/vendor/');
	if ($vendorPosition === FALSE) {
		throw new RuntimeException('Unable to locate vendor directory');
	}
	include_once substr($_SERVER['DOCUMENT_ROOT'], 0, $vendorPosition + 7) . '/autoload.php';
}

ini_set('memory_limit', '1024M');

$requestUri = $_SERVER['REQUEST_URI'] ?? 'not-found';
assert(is_string($requestUri));

$parsedUrl = parse_url($requestUri);
assert(is_array($parsedUrl));
$path = $parsedUrl['path'] ?? throw new RuntimeException('Unable to parse path');

switch (strtolower(trim($path, '/'))) {
	case '':
		require __DIR__ . '/../assets/app.phtml';
		exit;

	case 'api':
		$barData = new Server\BarData();
		$barData->load();

		// Enable CORS for origin
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: POST, GET, DELETE');

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$barHtml = file_get_contents('php://input');
			if ($barHtml !== FALSE) {
				$barData->addBar($barHtml);
			}
		} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			if (isset($_GET['id'])) {
				assert(is_string($_GET['id']));

				$html = $barData->getBar((int) $_GET['id']);
				if ($html === NULL) {
					http_response_code(403);
				} else {
					if (Debugger::getStrategy()->sendAssets()) {
						return;
					}
					echo $html;
				}
			} else {
				echo $barData->barCount();
			}
		} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
			$barData->clear();
		}

		$barData->write();

		exit;

	case 'tracy-assets':
		Debugger::getStrategy()->sendAssets();
		exit;
}

http_response_code(404);
