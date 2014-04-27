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
	public $UserListSelect = 0;	
		
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
	
	public function drawUserSelect($input, $data = 0, $width = 200, $without = array(), $delete = ""){
		echo '<span href=# class="JS ContextMenu" dropdown="UserListSelect_'.$this->UserListSelect.'" dropdown-open="left" selectType="2" onChange="$(\'#'.$input.'\').val(value_);" dropdown-absolute="true" width='.$width.'>Zadej jmeno...</span>';
		echo '<div class="listDiv" id="UserListSelect_'.$this->UserListSelect.'">';
			echo '<div class="listBox" style="width:'.$width.'px;">';
				echo "<ul>";
					$userlist = $this->presenter->context->database->table('Users')
					->select('Id,Nickname,Username')
					->order('Nickname ASC');
					//->where('IsApproved = 1');
					foreach($userlist as $user){
						if($data==0){$val=$user["Id"];}elseif($data==1){$val=$user["Username"];}else{$val=$user["Nickname"];}
						if(!in_array($val, $without)) {							
							echo "<li value_='".$val."'><a>".$user["Nickname"]."</a></li>";
						}
					}
				echo "</ul>";
			echo "</div>";
		echo "</div>";
		echo '<input type="hidden" name="'.$input.'__JS" id="'.$input.'" value="">';
		echo "<select name='".$input."' id='select_".$input."' style='width:".$width."px;'>";
			foreach($userlist as $user){
				if($data==0){$val=$user["Id"];}elseif($data==1){$val=$user["Username"];}else{$val=$user["Nickname"];}
				if(!in_array($val, $without)) {		
					echo "<option value='".$val."'>".$user["Nickname"]."</option>";
				}
			}
		echo "</select>";
		echo "<script>$(\"#select_".$input."\").remove();setTimeout(function(){ $(\"#".$delete."\").remove(); },100);$(\"#".$input."\").attr(\"name\",\"".$input."\")</script>";		
		$this->UserListSelect++;
	}
	
	public function getAllUsers(){
		$database = $this->presenter->context->database;
		$users = $database->table('Users')->order('Nickname');
		foreach($users as $user){
			if($user["AvatarFilename"]==""){$user["AvatarFilename"]="0.jpg";}
			$allUserWithInfo[$user["Id"]] = array($user["Nickname"], $user["AvatarFilename"], $user["Id"], $user["Username"]);
			$allUserWithInfo[$user["Username"]] = array($user["Nickname"], $user["AvatarFilename"], $user["Id"], $user["Username"]);
		}
		return $allUserWithInfo;
	}
}