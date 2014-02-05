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
		$template = $this->presenter->template;
		$template->setFile(__DIR__ . '/../templates/components/furrafinitylinker.latte');		
		//$template->render();
	}
	
	public function renderFurrafinityget($url){
		$page = file_get_contents($url);
		$dd = preg_match('/\<u\>(.*)\<\/u\> has elected to make their content available to registered users only\./U',  $page, $pam);
		if(count($pam)>0){
			$data = array("Error" => "<b>".$pam[1]."</b> nastavil tento obrázek jako soukromý!");
		}else{
			$dd = preg_match('/full_url  \= \"\/\/(.*)\"\;/U',  $page, $mam);
			$img = explode("/",$mam[1]);
			$img = end($img);
	
			$fields = array(
						'URL' => urlencode("http://".$mam[1])
					);
			$fields_string="";			
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string, '&');			
	
			$cSession = curl_init(); 
			curl_setopt($cSession, CURLOPT_URL,"http://www.katedrala.cz/anonym/nph-agent.cgi/011110A/k-cebkl:/=2ffgneg");
			curl_setopt($cSession, CURLOPT_POST, count($fields));
			curl_setopt($cSession, CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($cSession, CURLOPT_RETURNTRANSFER,true);
			curl_setopt($cSession, CURLOPT_HEADER, true); 
			$result=curl_exec($cSession);
			$last_url = curl_getinfo($cSession, CURLINFO_EFFECTIVE_URL);
			curl_close($cSession);	
			$result = explode(":",$result);
			$res = $result[4];$i=5;
			while(isset($result[$i])){$res.=":".$result[$i];$i++;}	
			$res=trim($res);
			$data = array("Error" => 0, "urlImage" => $res, "urlDefault" => $url);
		}
		$this->sendResponse(new JsonResponse($data));
	}
	
	public function renderNotificationcount(){
		$database = $this->context->database;		
		$data = array("Notif" => 0, "Count" => 0);
		
		$users = $database->table('Users')->order('Nickname');
		foreach($users as $user){
			if($this->user->identity->nickname!=$user["Nickname"])
			$allUsers[] = array($user["Id"],$user["Username"]);
			$allUserId[$user["Id"]] = $user["Username"];
			$allUserName[$user["Username"]] = $user["Id"];
			$allUserWithInfo[$user["Id"]] = array($user["Nickname"], $user["AvatarFilename"]);
		}
		
		$messages = $database->table('Privatemessages')->where("AddresseeId = ? AND Read = 0",$this->user->identity->id);
		foreach($messages as $message){
			if($message["SenderId"] == $this->user->identity->id){$name_=$allUserId[$message["AddresseeId"]];$id=$message["AddresseeId"];$SID = $message["AddresseeId"].$message["SenderId"];}
			else{$name_=$allUserId[$message["SenderId"]];$id=$message["SenderId"];$SID = $message["SenderId"].$message["AddresseeId"];}
			if(!isset($msgFrom[$SID])){
				$msgFrom[$SID]=1;
				$data["Count"]++;
			}
		}
		$nottifications = $database->table('Notifications')->where("UserId = ? AND IsNotifed = 0",$this->user->identity->id);
		foreach($nottifications as $nottification){
			$data["Notif"]++;
		}
		
		$this->sendResponse(new JsonResponse($data));
	}
	
	public function renderNotificationnotif($time){
		$database = $this->context->database;
		$data = array("0" => array("time" => time()));
		
		$users = $database->table('Users')->order('Nickname');
		foreach($users as $user){
			if($this->user->identity->nickname!=$user["Nickname"])
			$allUsers[] = array($user["Id"],$user["Username"]);
			$allUserId[$user["Id"]] = $user["Username"];
			$allUserName[$user["Username"]] = $user["Id"];
			$allUserWithInfo[$user["Id"]] = array($user["Nickname"], $user["AvatarFilename"]);
		}
		
		$messages = $database->table('Privatemessages')->where("AddresseeId = ? AND Read = 0",$this->user->identity->id);
		foreach($messages as $message){
			$notif = $database->table('Notifications')->where("Parent = ?","chat_".$message["Id"]);
			if(count($notif)==0){
				$text = strip_tags($message["Text"]);
				$pext = substr($text,0,57);if($pext != $text){$text = $pext."...";}
				$data[] = array(
					"Text" => "<b>".$allUserId[$message["SenderId"]]."</b> ti posílá zprávu:<br>".$text,
					"Info" => Fcz\CmsUtilities::getTimeElapsedString(strtotime($message["TimeSent"])),
					"Href" => $this->link("Intercom:default",$allUserId[$message["SenderId"]]),
					"Image" => $allUserWithInfo[$message["SenderId"]][1],
				);
				$database->table('Notifications')->insert(array("Parent"=>"chat_".$message["Id"], "Time" => date("Y-m-d H:i:s",time()), "IsNotifed" => 1, "IsView" => 1, "UserId" => $this->user->identity->id));
			}
		}	
			
		$this->sendResponse(new JsonResponse($data));
	}
}	