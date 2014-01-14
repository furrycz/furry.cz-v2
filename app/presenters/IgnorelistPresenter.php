<?php

/**
 * Ignorelist editor presenter.
 */
class IgnorelistPresenter extends BasePresenter
{

	public function renderDefault()
	{
		$this->redirect('Ignorelist:edit');
	}



	public function renderEdit()
	{
		if ($this->user->isInRole('approved') == false)
		{
			throw new Nette\Application\ForbiddenRequestException("Sem nemáte přístup");
		}
	}



	public function processValidatedEditIgnorelistForm($form)
	{
		$database = $this->context->database;

		$users = $database->table("Users")->select("Id");
		$values = $form->getValues();
		$deleteList = array();
		$insertList = array();
		foreach ($users as $user)
		{
			$checkboxId = 'UserId_' . $user['Id'] . '_Ignored';
			$hiddenId = 'UserId_' . $user['Id'] . '_PreviouslyIgnored';

			if (isset($values[$checkboxId]) && isset($values[$hiddenId]))
			{
				$ignored = $values[$checkboxId] == true;
				$previouslyIgnored = $values[$hiddenId] == true;

				if ($ignored != $previouslyIgnored)
				{
					if ($previouslyIgnored)
					{
						$deleteList[] = $user["Id"];
					}
					else
					{
						$insertList[] = $user["Id"];
					}
				}
			}
		}

		if (count($deleteList) > 0)
		{
			$database->table("Ignorelist")->delete(array(
				"IgnoringUserId" => $this->user->id,
				"IgnoredUserId IN" => $deleteList
			));
		}

		if (count($insertList) > 0)
		{
			foreach ($insertList as $ignoredUserId)
			{
				$database->table("Ignorelist")->insert(array(
					"IgnoringUserId" => $this->user->id,
					"IgnoredUserId" => $ignoredUserId
				));
			}
		}

		$this->flashMessage("Ignorelist byl uložen");
	}



	public function createComponentEditIgnorelistForm()
	{

		$form = new Nette\Application\UI\Form;
		$database = $this->context->database;

		// Create a checkbox + hidden field for each user
		$users = $database
			->table("Users")
			->select("Nickname, AvatarFilename, Id");

		foreach ($users as $user)
		{

			$html = Nette\Utils\Html::el("span");
			$html->add(Nette\Utils\Html::el("img")
				->alt("Avatar")
				->src("{$this->context->parameters["baseUrl"]}/images/avatars/{$user['AvatarFilename']}")
				->class("Avatar")
				);
			$html->add($user["Nickname"]);
			$checkbox = $form->addCheckbox('UserId_' . $user['Id'] . '_Ignored', $html);

			$hidden = $form->addHidden('UserId_' . $user['Id'] . '_PreviouslyIgnored', '0');

			// Set status
			$ignored = $user->related("Ignorelist", "IgnoredUserId")->where("IgnoringUserId", $this->user->id);
			if (count($ignored) > 0)
			{
				$checkbox->setValue(true);
				$hidden->setValue(1);
			}
		}

		$form->addSubmit("submit", "Uložit");
		$form->onSuccess[] = $this->processValidatedEditIgnorelistForm;
		return $form;
	}

}
