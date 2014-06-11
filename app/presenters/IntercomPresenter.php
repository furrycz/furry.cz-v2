<?php

use Nette\Application\UI;
use Nette\Application\Responses\JsonResponse;

class IntercomPresenter extends BasePresenter
{

	public function renderDefault($name){
		if(!$this->user->isInRole('approved')){
			//throw new Nette\Application\BadRequestException('Musíš být přihlášen!');
			$this->redirect("Homepage:default");
		}
		$database = $this->context->database;
		
		$allUsers = NULL;$allUserId = NULL;$allUserWithInfo = NULL;$allUserName = NULL;
		
		$users = $database->table('Users')->order('Nickname');
		foreach($users as $user){
			if($this->user->identity->nickname!=$user["Nickname"])
			$allUsers[] = array($user["Id"],$user["Username"]);
			$allUserId[$user["Id"]] = $user["Username"];
			$allUserName[$user["Username"]] = $user["Id"];
			if($user["AvatarFilename"]==""){$user["AvatarFilename"]="0.jpg";}
			$allUserWithInfo[$user["Id"]] = array($user["Nickname"], $user["AvatarFilename"], date("d. m. Y H:i", strtotime($user["LastLogin"])));
		}
				
		if($allUsers==NULL){$allUsers=array();}
		$this->template->allUsers = $allUsers;
		
		if($name!=NULL){
			$ID=NULL;
			foreach($allUsers as $us){if($us[1]==$name){$ID=$us[0];}}
			$this->template->idAdresser = $ID;
			$this->template->selUser = $allUserWithInfo[$allUserName[$name]][0];
			$this->template->selUserNick = $name;
			$this->template->selUserLastLogin = $allUserWithInfo[$allUserName[$name]][2];
			$this->template->selAvatar = $allUserWithInfo[$allUserName[$name]][1];
			$this->template->SelectSID = 9999;
			$this->template->messageCount = NULL;
			$this->template->messageCount[9999] = 0;
		}else{
			$this->template->selUser = "";
		}
		
		$allMessages = NULL;$msgFrom = NULL;$messageActualShow = NULL;$messageCount = NULL;$notReaded = NULL;
		
		$messages = $database->table('PrivateMessages')->where("(SenderId = ? OR AddresseeId = ?) AND Deleted=0",$this->user->identity->id, $this->user->identity->id)->order('TimeSent DESC');
		foreach($messages as $message){
			if($message["SenderId"] == $this->user->identity->id){$name_=$allUserId[$message["AddresseeId"]];$id=$message["AddresseeId"];$SID = $message["AddresseeId"].$message["SenderId"];}
			else{$name_=$allUserId[$message["SenderId"]];$id=$message["SenderId"];$SID = $message["SenderId"].$message["AddresseeId"];}
			
			if(!isset($msgFrom[$SID])){
				$msgFrom[$message["SenderId"].$message["AddresseeId"]] = true;
				$msgFrom[$message["AddresseeId"].$message["SenderId"]] = true;	
				$ti = Fcz\CmsUtilities::getTimeElapsedString(strtotime($message["TimeSent"]));
				//$ti = Fcz\SecurityUtilities::processCmsHtml(strtotime($message["TimeSent"]));
				//date("j. m. Y H:i:s",strtotime($message["TimeSent"]))
				$allMessages[] = array(1, $allUserWithInfo[$id][0], $ti, $message["Text"], $allUserWithInfo[$id][1], $SID, $name_);	
				$notReaded[$SID] = 0;
				$messageCount[$SID] = 0;
				if($name==$name_){$this->template->SelectSID = $SID;}
			}			
			$messageCount[$SID]++;
			
			if($allUserName[$name_] == $message["SenderId"] or $allUserName[$name_] == $message["AddresseeId"]){
				if($message["SenderId"]!=$this->user->identity->id){
					if($message["Read"]=="0"){
						$notReaded[$SID]++;
						if($name==$name_){$database->table('PrivateMessages')->where('Id', $message["Id"])->update(array("Read" => 1));}
					}				
				}
				
				if($name_==$allUserId[$message["SenderId"]]){$typ=1;}else{$typ=0;} // 1 -> odesilatel on; 0 -> odesilatel ja				
				if($name==$name_){
				$messageActualShow[] = array(
											"id" => $message["Id"],
											"typ" => $typ, 
											"senderID" => $message["SenderId"], 
											"senderName" => $allUserWithInfo[$message["SenderId"]][0], 
											"senderAvatar" => $allUserWithInfo[$message["SenderId"]][1],
											"date" => date("j. m. Y H:i:s",strtotime($message["TimeSent"])), 
											"text" => $message["Text"],
											"read" => $message["Read"]
											);
											}
			}
		}
		if($allMessages==NULL){$allMessages[] = array(0,"<div style='padding:5px;'></div>Žádná zpráva nebyla nalezena!","","","","","");}
		$this->template->allMessages = $allMessages;
		$this->template->messageActualShow = $messageActualShow;
		$this->template->messageCount = $messageCount;
		$this->template->notReaded = $notReaded;
		
		$user = $database->table('Users')->where('Username',$name)->fetch();
		$ignore = $database->table('Ignorelist')->where("IgnoringUserId",$user["Id"])->where("IgnoredUserId",$this->user->identity->id)->where("IgnoreType",1);
		$ignor2 = $database->table('Ignorelist')->where("IgnoredUserId",$user["Id"])->where("IgnoringUserId",$this->user->identity->id)->where("IgnoreType",1);
		if(count($ignore)>0){$this->template->ignore = 1;}
		elseif(count($ignor2)>0){$this->template->ignore = 2;}
		else{$this->template->ignore = 0;}
	}
	
	public function handleDelete($name, $id){
		$database = $this->presenter->context->database;
		if($id==-1){
			$users = $database->table('Users');
			foreach($users as $user){
				$allUserName[$user["Username"]] = $user["Id"];
			}
		
			$database->table('PrivateMessages')->where("(SenderId = ? AND AddresseeId = ?) OR (SenderId = ? AND AddresseeId = ?)",$this->user->identity->id,$allUserName[$name],$this->user->identity->id,$allUserName[$name])->update(array("Deleted"=>1));
		}else{
			$message = $database->table('PrivateMessages')->where("Id",$id)->fetch();
			if($message["SenderId"] == $this->user->identity->id or $message["AddresseeId"] == $this->user->identity->id){
				$database->table('PrivateMessages')->where("Id",$id)->update(array("Deleted"=>1));
			}else{
				$this->flashMessage('Tato zpráva nepatří tobě! Nebyla napsána tebou a ani nebyla určena tobě!', 'error');
			}
		}	
		$this->redirect("Intercom:default", $name);
	}
	
	public function handleExport($name){
		$database = $this->presenter->context->database;
		
		$allUsers = NULL;$allUserId = NULL;$allUserWithInfo = NULL;$allUserName = NULL;
		
		$users = $database->table('Users')->order('Nickname');
		foreach($users as $user){
			if($this->user->identity->nickname!=$user["Nickname"])
			$allUsers[] = array($user["Id"],$user["Username"]);
			$allUserId[$user["Id"]] = $user["Username"];
			$allUserName[$user["Username"]] = $user["Id"];
			$allUserWithInfo[$user["Id"]] = array($user["Nickname"], $user["AvatarFilename"]);
		}
		
		$user = $database->table('Users')->where('Username',$name)->fetch();
		
		$filename = $this->user->identity->nickname.'-'.$user["Nickname"].'-'.time().'.html';
		
		$file = NULL;
		
		$messages = $database->table('PrivateMessages')->where("(SenderId = ? OR AddresseeId = ?) AND (SenderId = ? OR AddresseeId = ?) AND Deleted=0",$this->user->identity->id, $this->user->identity->id,$user["Id"],$user["Id"])->order('TimeSent DESC');
		foreach($messages as $message){
			if($message["SenderId"] == $this->user->identity->id){$name_=$allUserId[$message["AddresseeId"]];$id=$message["AddresseeId"];$SID = $message["AddresseeId"].$message["SenderId"];}
			else{$name_=$allUserId[$message["SenderId"]];$id=$message["SenderId"];$SID = $message["SenderId"].$message["AddresseeId"];}
			if($message["Read"]==1){$read="<span style='color:green'>[Přečteno]</span>";}else{$read="";}
			$file.=$read." <b>".$allUserWithInfo[$message["SenderId"]][0]."</b> < <span style='color:silver;'>".date("j. m. Y H:i:s",strtotime($message["TimeSent"]))."</span> ><div style='border:1px solid silver;padding:8px;margin:5px;background-color:#414141;'>".$message["Text"]."</div>";
		}
	
		$file = '<meta http-equiv="content-type" content="text/html;charset=utf-8"><style>* {padding:0px;margin:0px;}</style><body style="background:#44404A;color:white;">'.$file.'</body>';
		
		$response = Nette\Environment::getHttpResponse();
		$response->setHeader('Content-Description', 'File Transfer');
		$response->setContentType('text/html', 'UTF-8');
		$response->setHeader('Content-Disposition', 'attachment; filename=' . $filename);
		$response->setHeader('Content-Transfer-Encoding', 'binary');
		$response->setHeader('Expires', 0);
		$response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
		$response->setHeader('Pragma', 'public');
		$response->setHeader('Content-Length', strlen($file));
		

		ob_clean();
		flush();
		echo $file;

		$this->terminate();
	}
	
	public function handleBlock($name){
		$database = $this->presenter->context->database;
		$user = $database->table('Users')->where('Username',$name)->fetch();
		$ignore = $database->table('Ignorelist')->where("IgnoringUserId",$user["Id"])->where("IgnoredUserId",$this->user->identity->id)->where("IgnoreType",1);
		if(count($ignore)>0){
			$ignore->delete();
		}else{
			$database->table('Ignorelist')->insert(array(
				'IgnoringUserId' => $user["Id"],
				'IgnoredUserId' => $this->user->identity->id,
				'IgnoreType' => 1
			));
		}
		$this->redirect("this");
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
			$database = $this->presenter->context->database;
			$user = $database->table('Users')->where('Nickname',$nam)->fetch();
			if($user["Username"]!=""){
				$nam = $user["Username"];
				$this->redirect("Intercom:default", $nam);
			}else{
				$this->flashMessage('Tento tvor neexistuje!', 'error');
				$this->redirect("Intercom:default");
			}
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
			
			$database->table('PrivateMessages')->insert(array(
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
