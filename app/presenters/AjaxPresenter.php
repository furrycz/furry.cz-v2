<?php

use Nette\Application\UI;
use Nette\Application\Responses\JsonResponse;

class AjaxPresenter extends BasePresenter
{

	public function renderDefault($name){
	
	}
	
	public function renderAutocomplete()
    {
		$database = $this->context->database;
		
		$data = NULL;
		
		$users = $database->table('Users')->order('Nickname');
		foreach($users as $user){
			$data[] = array("name" => $user["Nickname"], "id" => $user["Id"]);
		}
		
        //$matches = preg_grep("/$typedText/i", $data);
        $this->sendResponse(new JsonResponse($data));
    }
	
	public function renderAutocompleteInput($whichData, $typedText = '')
    {
		$database = $this->context->database;
		
		$data = NULL;
		
		$users = $database->table('Users')->order('Nickname');
		foreach($users as $user){
			if($this->user->identity->nickname!=$user["Nickname"])
			$data[] = $user["Nickname"];
		}
		
        $matches = preg_grep("/$typedText/i", $data);
        $this->sendResponse(new JsonResponse($matches));
    }
	
	public function renderFurrafinity(){
		
	}
	
}	