<?php declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class Error4xxPresenter extends BasePresenter
{

	protected function startup(): void
	{
		parent::startup();

		if ($this->getRequest() !== NULL && !$this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
			$this->error();
		}
	}


	public function renderDefault(Nette\Application\BadRequestException $exception): void
	{
		// load template 403.latte or 404.latte or ... 4xx.latte
		$file = sprintf('%s/templates/Error/%s.latte', __DIR__, $exception->getCode());
		$file = is_file($file) ? $file : __DIR__ . '/templates/Error/4xx.latte';
		$this->getTemplate()->setFile($file);
	}

}
