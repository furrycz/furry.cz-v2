<?php

namespace Fcz;

class UserUtilities extends \Nette\Object
{
	protected $presenter;

	public function __construct(\Nette\Application\IPresenter $presenter)
	{
		$this->presenter = $presenter;
	}
	
	public $UserDataDefault = array(
			"postsOrdering" => "NewestOnTop",
			"postsPerPage" => 25
		);
		
	public function getData($userId,$DataName){
		$data = $this->presenter->context->database->table('UserSettings')->where("UserId = ? AND Name = ?",$userId,$DataName)->fetch();
		if(!$data){return @$this->UserDataDefault[$DataName];}
		return $data["Value"];
	}
	
	public function setData($userId,$DataName,$DataValue=""){
		if($DataValue==""){$DataValue = @$this->UserDataDefault[$DataName];}
		$data = $this->presenter->context->database->table('UserSettings')->where("UserId = ? AND Name = ?",$userId,$DataName)->fetch();
		if(!$data){ return $this->presenter->context->database->table('UserSettings')->insert(array("Value" => $DataValue, "UserId" => $userId, "Name" => $DataName)); }
		else{ return $this->presenter->context->database->table('UserSettings')->where("UserId = ? AND Name = ?",$userId,$DataName)->update(array("Value" => $DataValue)); }
	}
}