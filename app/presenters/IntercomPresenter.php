<?php

use Nette\Application\UI;
use Nette\Application\Responses\JsonResponse;

class IntercomPresenter extends BasePresenter
{

	public function renderDefault($name){
		$database = $this->context->database;
		
		$allUsers = NULL;$allUserId = NULL;$allUserWithInfo = NULL;$allUserName = NULL;
		
		$users = $database->table('Users')->order('Nickname');
		foreach($users as $user){
			if($this->user->identity->nickname!=$user["Nickname"])
			$allUsers[] = array($user["Id"],$user["Nickname"]);
			$allUserId[$user["Id"]] = $user["Nickname"];
			$allUserName[$user["Nickname"]] = $user["Id"];
			$allUserWithInfo[$user["Id"]] = array($user["Nickname"], $user["AvatarFilename"]);
		}
				
		if($allUsers==NULL){$allUsers=array();}
		$this->template->allUsers = $allUsers;			
		
		if($name!=NULL){		
			$ID=NULL;
			foreach($allUsers as $us){if($us[1]==$name){$ID=$us[0];}}
			$this->template->idAdresser = $ID;		
			$this->template->selUser = $name;
			$this->template->selAvatar = $allUserWithInfo[$allUserName[$name]][1];
		}else{
			$this->template->selUser = "";
		}
		
		$allMessages = NULL;$msgFrom = NULL;$messageActualShow = NULL;$messageCount = NULL;$notReaded = NULL;
		
		$messages = $database->table('Privatemessages')->where("SenderId = ? OR AddresseeId = ?",$this->user->identity->id, $this->user->identity->id)->order('TimeSent DESC');
		foreach($messages as $message){
			if($message["SenderId"] == $this->user->identity->id){$name=$allUserId[$message["AddresseeId"]];$id=$message["AddresseeId"];$SID = $message["AddresseeId"].$message["SenderId"];}
			else{$name=$allUserId[$message["SenderId"]];$id=$message["SenderId"];$SID = $message["SenderId"].$message["AddresseeId"];}
			
			if(!isset($msgFrom[$SID])){
				$msgFrom[$message["SenderId"].$message["AddresseeId"]] = true;
				$msgFrom[$message["AddresseeId"].$message["SenderId"]] = true;				
				$allMessages[] = array(1, $name, date("j. m. Y H:i:s",strtotime($message["TimeSent"])), $message["Text"], $allUserWithInfo[$id][1], $SID);	
				$notReaded[$SID] = 0;
				$messageCount[$SID] = 0;
				if($allUserName[$name] == $message["SenderId"] or $allUserName[$name] == $message["AddresseeId"]){$this->template->SelectSID = $SID;}
			}
			if($message["SenderId"]!=$this->user->identity->id){
				if($message["Read"]=="0"){
					$notReaded[$SID]++;
					$database->table('Privatemessages')->where('Id', $message["Id"])->update(array("Read" => 1));		
				}				
			}
			$messageCount[$SID]++;
			
			if($allUserName[$name] == $message["SenderId"] or $allUserName[$name] == $message["AddresseeId"]){
				if($name==$allUserId[$message["SenderId"]]){$typ=1;}else{$typ=0;} // 1 -> odesilatel on; 0 -> odesilatel ja				
				$messageActualShow[] = array(
											"id" => $message["Id"],
											"typ" => $typ, 
											"senderID" => $message["SenderId"], 
											"senderName" => $allUserId[$message["SenderId"]], 
											"senderAvatar" => $allUserWithInfo[$message["SenderId"]][1],
											"date" => date("j. m. Y H:i:s",strtotime($message["TimeSent"])), 
											"text" => $message["Text"],
											"read" => $message["Read"]
											);
			}
		}
		if($allMessages==NULL){$allMessages[] = array(0,"<div style='padding:5px;'></div>Žádná zpráva nebyla nalezena!");}
		$this->template->allMessages = $allMessages;
		$this->template->messageActualShow = $messageActualShow;
		$this->template->messageCount = $messageCount;
		$this->template->notReaded = $notReaded;
	}
	
	public function handleDelete($name, $id){
		$database = $this->presenter->context->database;
		$message = $database->table('Privatemessages')->where("id",$id)->fetch();
		if($message["SenderId"] == $this->user->identity->id or $message["AddresseeId"] == $this->user->identity->id){
			$database->table('Privatemessages')->where("id",$id)->delete();
		}else{
			$this->flashMessage('Tato zpráva nepatří tobě! Nebyla napsána tebou a ani nebyla určena tobě!', 'error');
		}
		$this->redirect("Intercom:default", $name);
	}
	
	public function createComponentChangeForForm()
	{
		$form = new \Nette\Application\UI\Form;
		$form->addSubmit('go', 'Přejít');
		$form->onSuccess[] = $this->processValidatedPermisionForm;
		return $form;
	}
	
	public function processValidatedPermisionForm($form)
	{
			$values = $form->getValues();
			$database = $this->presenter->context->database;
			if($_POST["forName"]!=""){$nam=$_POST["forName"];}else{$nam=$_POST["userFor"];}
			$this->redirect("Intercom:default", $nam);
	}		
	
	public function createComponentSendMessageForm()
	{
		$form = new \Nette\Application\UI\Form;
		$form->addSubmit('send', 'Odeslat');
		$form->onSuccess[] = $this->processSendMessageForm;
		return $form;
	}
	
	public function processSendMessageForm($form)
	{
			$values = $form->getValues();
			$database = $this->presenter->context->database;
			$text = $_POST["textSend"];
			
			$database->table('Privatemessages')->insert(array(
				'SenderId' => $this->user->identity->id,
				'AddresseeId' => $_POST['idAdresser'],
				'Text' => $_POST['textSend'],
				'TimeSent' => date("Y-m-d H:i:s",time()),
				'Deleted' => 0,
				'Read' => 0,
				'ReadTime' => "",
				'File' => ""
			));
			
			$this->redirect("this");
	}	
	
}	