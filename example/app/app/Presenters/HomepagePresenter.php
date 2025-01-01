<?php declare(strict_types=1);

namespace App\Presenters;

final class HomepagePresenter extends BasePresenter
{

	public function actionRedirect(): void
	{
		$this->redirect('default');
	}


	public function renderAjax(int $error = 0): void
	{
		bdump('AJAX request ' . date('H:i:s'));

		if ($error > 0) {
			this_is_fatal_error();
		}

		$this->sendJson([rand(), rand(), rand()]);
	}


	public function renderApi(int $error = 0): void
	{
		bdump('API request ' . date('H:i:s'));

		if ($error > 0) {
			this_is_fatal_error();
		}

		$this->sendJson(['api' => [rand(), rand(), rand()]]);
	}

}
