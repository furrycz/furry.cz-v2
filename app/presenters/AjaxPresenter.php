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
		
		$messages = $database->table('PrivateMessages')->where("AddresseeId = ? AND Read = 0 AND Deleted=0",$this->user->identity->id);
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
		
		$messages = $database->table('PrivateMessages')->where("AddresseeId = ? AND Read = 0 AND Deleted=0",$this->user->identity->id);
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
	
	public function renderNotifications($jak){
		$database = $this->context->database;
		$data = array("length" => 0);
		
		$users = $database->table('Users')->order('Nickname');
		foreach($users as $user){
			if($this->user->identity->nickname!=$user["Nickname"])
			$allUsers[] = array($user["Id"],$user["Username"]);
			$allUserId[$user["Id"]] = $user["Username"];
			$allUserName[$user["Username"]] = $user["Id"];
			if($user["AvatarFilename"]==""){$user["AvatarFilename"]=(\Nette\Environment::getHttpRequest()->getUrl()->getBasePath())."/images/f_nopix.gif";}
			$allUserWithInfo[$user["Id"]] = array($user["Nickname"], $user["AvatarFilename"]);
		}
		
		if($jak==0){
			$count = 0;
			$messages = $database->table('PrivateMessages')->where("(SenderId = ? OR AddresseeId = ?) AND Deleted=0",$this->user->identity->id, $this->user->identity->id)->order('TimeSent DESC');
			foreach($messages as $message){
				if($message["SenderId"] == $this->user->identity->id){$name_=$allUserId[$message["AddresseeId"]];$id=$message["AddresseeId"];$SID = $message["AddresseeId"].$message["SenderId"];}
				else{$name_=$allUserId[$message["SenderId"]];$id=$message["SenderId"];$SID = $message["SenderId"].$message["AddresseeId"];}
			
				if(!isset($msgFrom[$SID])){
					$msgFrom[$SID]=1;
					if($message["SenderId"] == $this->user->identity->id){$read = 1;}else{$read = $message["Read"];}
					$text = strip_tags($message["Text"]);
					$pext = substr($text,0,57);if($pext != $text){$text = $pext."...";}
					$data[] = array(
						"Url" => $this->link("Intercom:default",$allUserId[$id]), 
						"Class" => "Read_".$read, 
						"Id" => $id,
						"Image" => $allUserWithInfo[$id][1], 
						"Info" => Fcz\CmsUtilities::getTimeElapsedString(strtotime($message["TimeSent"]))."".($read==0?" <b style='color:red;'>NOVÉ!</b>":""),
						"Text" => "<div style='font-weight:bold;'>".$allUserWithInfo[$id][0]."</div>".$text
						);
					$count++;
				}
			}	
			$data["length"] = $count;
		}
		
		$this->sendResponse(new JsonResponse($data));
	}
	
	public function renderRatepost($PostId, $ContentId, $Rating){
		$database = $this->context->database;
		$data = array();
		
		if($Rating>1 or $Rating<-1){$Rating=0;}//Kontrola kdyby chtěl někdo podvádět ^^
		
		$ratTop = 0;$mam=0;
		$rating = $database->table('RatingsPost')->Where("PostId = ?", $PostId);
		foreach($rating as $rat){
			if($rat["UserId"] == $this->user->identity->id){
				if($rat["Rating"] == $Rating){$rat["Rating"]=0;}
				else{$rat["Rating"] = $Rating;}		
				$database->table('RatingsPost')->where("PostId = ? AND UserId = ?",$rat["PostId"],$this->user->identity->id)->update(array("Rating" => $rat["Rating"]));
				$mam=1;
			}			
			$ratTop += $rat["Rating"];
		}
		if($mam==0){
			$database->table('RatingsPost')->insert(array("ContentId" => $ContentId, "PostId" => $PostId, "UserId" => $this->user->identity->id, "Rating" => $Rating));
			$ratTop += $Rating;
		}
		
		if($ratTop<0){$c="Red";}elseif($ratTop>0){$c="Green";}else{$c="Orange";}
		
		$data = array("Class" => $c, "Rating" => $ratTop, "PostId" => $PostId);
		
		$this->sendResponse(new JsonResponse($data));
	}

	public function renderAttendanceschange($EventId, $Attendances = "Maybe"){
		$database = $this->context->database;
		$data = array("Id"=>$Attendances);

		$uca = $database->table('EventAttendances')->where('EventId', $EventId)->where('UserId', $this->user->id)->fetch();
		if($uca == false){
			$database->table('EventAttendances')->insert(array(
				'EventId' => $EventId,
				'UserId' => $this->user->id,
				"Attending" => $Attendances
			));
		}else{
			$database->table('EventAttendances')->where('EventId', $EventId)->where('UserId', $this->user->id)->update(array(
				"Attending" => $Attendances
			));
		}

		$this->sendResponse(new JsonResponse($data));
	}
	public function renderGethostevent($Data){
		$database = $this->context->database;
		$data = array("EventId"=>$Data);
		
		$users = $database->table('Users')->order('Nickname');
		foreach($users as $user){
			if($this->user->identity->nickname!=$user["Nickname"])
			$allUsers[] = array($user["Id"],$user["Username"]);
			$allUserId[$user["Id"]] = $user["Username"];
			$allUserName[$user["Username"]] = $user["Id"];
			if($user["AvatarFilename"]==""){$user["AvatarFilename"]="no_avatar.png";}
			$allUserWithInfo[$user["Id"]] = array($user["Nickname"], $user["AvatarFilename"]);
		}
		
		$data["width"] = 170;
		$data["title"] = "Seznam pozvaných";
		$data["sekcion"] = array("Učastní se","Možná se účastní","Neúčastní se","-","Odmítnuto");
		
		$ucastnici = $database->table('EventAttendances')->where('EventId', $Data);
		foreach($ucastnici as $ucastnik){
			if($ucastnik["Attending"]=="Yes"){$u=0;}elseif($ucastnik["Attending"]=="No"){$u=2;}else{$u=1;}			
			$data["users"][$u][] = array("Name" => $allUserWithInfo[$ucastnik["UserId"]][0], "Avatar" => $allUserWithInfo[$ucastnik["UserId"]][1], "Id" => $ucastnik["UserId"]);
		}
		$this->sendResponse(new JsonResponse($data));
	}
}	