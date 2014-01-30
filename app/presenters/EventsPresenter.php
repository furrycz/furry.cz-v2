<?php

use Nette\Application\UI;

class EventsPresenter extends BasePresenter
{	
	/** @persistent */
    public $month;    
	/** @persistent */
    public $year;
	private $content;

	/*
	public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL)
    {
		parent::__construct($parent, $name);

		$this->year = $this->getParam('year', date("Y"));
		$this->month = $this->getParam('month', date("n"));
    }
	*/
	
    public function renderDefault($year=0,$month=0)
    {
	
	if($year==0){$this->year=date("Y",time());}//else{$this->year=$year;}
	if($month==0){$this->month=date("n",time());}//else{$this->month=$month;}
	
	$template = $this->template;
	$database = $this->context->database;
    //$template = parent::createTemplate();
    //$template->setFile(__DIR__ . '/calendar.latte');

    /* days and weeks vars now ... */
	
	$authorizator = new Authorizator($database);		
	
	$template->events = "";
	$eventy = $database->table('Events')->order('StartTime DESC');
	foreach($eventy as $event){
		$access = $authorizator->authorize($database->table('Content')->where('Id', $event["ContentId"])->fetch(), $this->user);
		if($access["CanListContent"]){
			$date = strtotime($event["StartTime"]);	
			$endDate = strtotime($event["EndTime"]);	
			$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][0][] = $event["Id"];
			$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][1][] = $event["Name"];
			$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][2][] = 0;
			$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][3][] = date('d.m.Y', $endDate);
			$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][5][] = $date;
			$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][6][] = $endDate;
			$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][7][] = $event["Place"];
			
			$ucasti = "";
			$ucastnici = $database->table('EventAttendances')->where('EventId', $event["Id"]);
			foreach($ucastnici as $ucastnik){
				if($ucastnik["Attending"]=="Yes"){$u=1;}elseif($ucastnik["Attending"]=="No"){$u=2;}else{$u=3;}
				$user = $database->table("Users")->where(array("Id"=> $ucastnik["UserId"]))->fetch();
				$ucasti[$u][] = array($user["Nickname"], $user["AvatarFilename"], $ucastnik["UserId"]);
			}
			if(!isset($ucasti[1][0][0])){$ucasti[1][0][0]="";}
			if(!isset($ucasti[2][0][0])){$ucasti[2][0][0]="";}
			if(!isset($ucasti[3][0][0])){$ucasti[3][0][0]="";}
			
			$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][8][] = $ucasti;
			
			
			if(date('d', $endDate)>date('d', $date) or date('m', $endDate)>date('m', $date)){				
				$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][4][] = 1;
				$m_ = date('m', $date);
				$d_ = date('d', $date)+1;
				if(date('m', $endDate)>date('m', $date)){ $ends = (date('d', $date)+date('m', $endDate))+1; }else{ $ends=date('d', $endDate); }				
				for($ds=date('d', $date);$ds<$ends;$ds++){
					if($d_>date('t',mktime(0,0,0,date('m', $date),1,date('Y', $date)))){$d_=1;$m_++;}
					$template->events[(int)date('Y', $date)][(int)$m_][(int)$d_][0][] = $event["Id"];
					$template->events[(int)date('Y', $date)][(int)$m_][(int)$d_][1][] = $event["Name"];
					$template->events[(int)date('Y', $date)][(int)$m_][(int)$d_][2][] = 1;
					$template->events[(int)date('Y', $date)][(int)$m_][(int)$d_][3][] = date('d.m.Y', $date);
					$d_++;
				}
			}else{
				$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][4][] = 0;
			}
		}
	}
	
	$MonthName = array(1=>"Leden",2=>"Unor",3=>"Březen",4=>"Duben",5=>"Květen",6=>"Červen",7=>"Červenec",8=>"Srpen",9=>"Září",10=>"Říjen",11=>"Listopad",12=>"Prosined");
	$thisDay = 0;if($this->year == date("Y",time()) and $this->month == date("m",time())){ $thisDay = date("d",time()); }
    $template->running_day = date('w',mktime(0,0,0,$this->month,1,$this->year))-1;
    $template->days_in_month = date('t',mktime(0,0,0,$this->month,1,$this->year));
    $template->days_in_this_week = 1;
    $template->day_counter = 0;
    $template->headings = array('Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle');
    $template->year = $this->year;
    $template->month = $this->month;
	$template->selectDay = $thisDay;
	$template->aktualCra = date('w',mktime(0,0,0,$this->month,1,$this->year))-1;
	$template->Mesic = $MonthName[$this->month];
	//$template->Mesic = ">>".$this->month;
    //$template->render();
	
	$day = date("d",time());$month = (int)date("m",time());$year = date("Y",time());
	$p=0;
	$template->show=NULL;
	for($i=0;$i<7;$i++){
		if(isset($template->events[$year][$month][$day][0][0])){
			$ds = date("w",strtotime("+".$i." day"));
			$ds--;if($ds<0){$ds=6;}
			$template->show[$p][] = $template->headings[$ds]." ".date('d. m. Y', strtotime("+".$i." day"));
			$s=1;
			for($a=0;$a<count($template->events[$year][$month][$day][0]);$a++){
				if($template->events[$year][$month][$day][2][$a]!=1){
					$template->show[$p][$s][0] = $template->events[$year][$month][$day][1][$a];
					$template->show[$p][$s][1] = date('H:m', $template->events[$year][$month][$day][5][$a]);					
					if($template->events[$year][$month][$day][4][$a]==1){$template->show[$p][$s][2]=date('d. m. Y H:m', $template->events[$year][$month][$day][6][$a]);}else{$template->show[$p][$s][2]=date('H:m', $template->events[$year][$month][$day][6][$a]);}
					$template->show[$p][$s][3] = $template->events[$year][$month][$day][7][$a];
					$template->show[$p][$s][4] = $template->events[$year][$month][$day][8][$a];
					$template->show[$p][$s][5] = $template->events[$year][$month][$day][0][$a];
					$s++;
				}
			}
			$p++;
		}
		$day++;
	}
	
    }
	
	 /**
     * Switch to next month.
     */
    public function handleNextMonth()
    {
		$this->month++;
		if ($this->month == 13) {
			$this->month = 1;
			$this->year++;
		}		
		$this->presenter->redirect('this');
    }
    /**
     * Switch to previous month.
     */
    public function handlePrevMonth()
    {
    $this->month--;
		if ($this->month == 0) {
			$this->month = 12;
			$this->year--;
		}
    $this->presenter->redirect('this');
    }
	
	public function renderNew(){
		
		if (!($this->user->isInRole('member') || $this->user->isInRole('admin')))
		{
			throw new Nette\Application\ForbiddenRequestException(
				'Pouze registrovaní uživatelé mohou vytvářet nové události');
		}		
		
	}
	
	public function renderDay($year=0,$month=0,$day=0){	
	
	}
	
	public function renderVisible($eventId){	
		
		$database = $this->context->database;
		$event = $database->table('Events')->where('Id', $eventId)->fetch();
		if ($event == false)
		{
			throw new Nette\Application\BadRequestException('Zadaná událost neexistuje');
		}
		
		$this->template->Name = $event["Name"];
		$this->template->EventId = $eventId;	
		$this->content = $database->table('Content')->where('Id', $event["ContentId"])->fetch();
	}
	
	public function createComponentPermissions()
	{
		$data = array(
							"Permisions" => array(  //Permision data
												"CanListContent" => array("","","","CanViewContent","",1), //$Zkratka 1 písmeno(""==Nezobrazí), $Popis, $BarvaPozadí, $Parent(""!=Nezobrazí), $Zařazení práv, $default check
												"CanViewContent" => array("V","Může událost vidět","","","Context",1),
												"CanEditContentAndAttributes" => array("E","Může událost upravit","","","Context - Správce",0),
												"CanEditHeader" => array("","","","","",0),
												"CanEditOwnPosts" => array("","","","","",1),
												"CanDeleteOwnPosts" => array("","","","","",1),
												"CanReadPosts" => array("","","","","",1),
												"CanDeletePosts" => array("","","","CanEditPermissions","",0),
												"CanEditPermissions" => array("S","Může upravit práva","","","Context - Správce",0),
												"CanWritePosts" => array("P","Může psát příspěvky","007AFF","","Téma",1),												
												"CanEditPolls" => array("","","","","",0)
												),
							"Description" => "!",
							"Visiblity" => array(
												"Public" => "Vidí všichni",
												"Private" => "Nevidí nikdo je třeba přidelit práva",
												"Hidden" => "Nezobrazí se v kalendáři, je třeba přidelit práva"
												),
							"DefaultShow" => false					
							);
		return new Fcz\Permissions($this, $this->content, new Authorizator($this->context->database), $data);
	}
	
	public function renderEdit($eventId){
		$database = $this->context->database;
		$event = $database->table('Events')->where('Id', $eventId)->fetch();
		if ($event == false)
		{
			throw new Nette\Application\BadRequestException('Zadaná událost neexistuje');
		}
		
		$authorizator = new Authorizator($database);
		$access = $authorizator->authorize($event["ContentId"], $this->user);
		
		if ($access['CanEditContentAndAttributes'] == true)
		{
			$this->template->Name = $event["Name"];
			$content = $database->table('Content')->where('Id', $event["ContentId"])->fetch();
			$this['newEventForm']->setDefaults(
				array(
					"EventId" => $eventId,
					"ContectId" => $event["ContentId"],
					"Name" => $event["Name"],
					"Description" => $event["Description"],
					"Kapacita" => $event["Capacity"],
					"IsOnlyAdult" => $content["IsForAdultsOnly"],
					"IsRegistrace" => $content["IsForRegisteredOnly"],
					"Konani" => $event["Place"],
					"StartTime" => Date("Y-m-d", strtotime($event["StartTime"])),
					"StartTimeMin" => Date("H:i", strtotime($event["StartTime"])),
					"EndTime" => Date("Y-m-d", strtotime($event["EndTime"])),
					"EndTimeMin" => Date("H:i", strtotime($event["EndTime"])),
					"GPS" => $event["GPS"]
					));
			$pat=explode(", ",substr($event["GPS"],1,-1));		
			$this->template->GPS = $pat;
			//$this['newEventForm']->onSuccess[0] = $this->processValidatedUpdateEventForm;
			$this->template->EventId = $eventId;
			
		}else { throw new Nette\Application\BadRequestException('Nemáte oprávnění k editaci události!'); }
	}
	
	public function renderView($eventId){
		
		if($this->year!=""){
			$this->month = "";
			$this->year = "";
			$this->presenter->redirect('this');
		} 
		
		
		$database = $this->context->database;
		$event = $database->table('Events')->where('Id', $eventId)->fetch();
		if ($event == false)
		{
			throw new Nette\Application\BadRequestException('Zadaná událost neexistuje');
		}
		
		$authorizator = new Authorizator($database);
		$access = $authorizator->authorize($database->table('Content')->where('Id', $event["ContentId"])->fetch(), $this->user);
		
		if ($access['CanViewContent'] == true)
		{
			$this->template->Name = $event["Name"];
			$this->template->Description = $event["Description"];
			SetLocale(LC_ALL, "Czech");
			$this->template->StartTime = Date("j. m, Y H:i", strtotime($event["StartTime"]));
			$this->template->EndTime = Date("j. m, Y H:i", strtotime($event["EndTime"]));
			$this->template->Place = $event["Place"];
			$pat=explode(", ",substr($event["GPS"],1,-1));
			$this->template->GPS = array($pat[0],$pat[1]);
			$this->template->Owner = $access["Owner"];
			$this->template->Edit = $access["CanEditContentAndAttributes"];
			$this->template->EditVisible = $access["CanEditPermissions"];
			$this->template->EventId = $event["Id"];
			$this->template->Kapacita = $event["Capacity"];
			
			$uca = $database->table('EventAttendances')->where('EventId', $event["Id"])->where('UserId', $this->user->id)->fetch();
			if($uca == false){$ucast=0;}
			else{if($uca["Attending"]=="Yes"){$ucast=1;}elseif($uca["Attending"]=="No"){$ucast=2;}else{$ucast=3;}}
			$this->template->Ucast = $ucast;
			$this['eventAttendForm']->setDefaults(array("Attend"=>$uca["Attending"], "EventId"=>$event["Id"]));
			
			$ucasti = "";
			$ucastnici = $database->table('EventAttendances')->where('EventId', $event["Id"]);
			foreach($ucastnici as $ucastnik){
				if($ucastnik["Attending"]=="Yes"){$u=1;}elseif($ucastnik["Attending"]=="No"){$u=2;}else{$u=3;}
				$user = $database->table("Users")->where(array("Id"=> $ucastnik["UserId"]))->fetch();
				$ucasti[$u][] = array($user["Nickname"], $user["AvatarFilename"], $ucastnik["UserId"]);
			}
			if(!isset($ucasti[1][0][0])){$ucasti[1][0][0]="";}
			if(!isset($ucasti[2][0][0])){$ucasti[2][0][0]="";}
			if(!isset($ucasti[3][0][0])){$ucasti[3][0][0]="";}
			$this->template->Ucastnici = $ucasti;
		}
		else
		{
			throw new Nette\Application\BadRequestException('Nemáte oprávnění ke čtení!');
		}
	}

	public function handleDelete($eventId)
    {
		$database = $this->context->database;
		$event = $database->table('Events')->where('Id', $eventId)->fetch();
		$authorizator = new Authorizator($database);
		$access = $authorizator->authorize($event["ContentId"], $this->user);
		if ($access['Owner'] == true)
		{
			$content = $database->table('Content')->where('Id', $event["ContentId"])->fetch();
			$database->table('EventAttendances')->where('EventId', $eventId)->delete();
			$database->table('Events')->where('Id', $eventId)->delete();
			$database->table('Ownership')->where('ContentId', $event["ContentId"])->delete();												
			//Smazat access
			$acclist = $database->table('Access')->where('ContentId', $event["Id"]);
			foreach($acclist as $acc){ 
				$database->table('Content')->where('ContentId', $acc["ContentId"])->where('UserId', $acc["UserId"])->delete(); 
				$database->table('Permissions')->where('Id', $acc["PermissionId"])->delete(); 
			}
			$database->table('Permissions')->where('Id', $content["DefaultPermissions"])->delete();						
			$database->table('Content')->where('Id', $event["ContentId"])->delete();
			$this->flashMessage('Událost byla smazána!', 'ok');
			$this->redirect('Events:default');
		}else{ throw new Nette\Application\BadRequestException('Nevytvořil jsi tuto událost, a proto ji nemužeš smazat!'); }
	}
	
	public function createComponentEventAttendForm()
	{			
		$form = new UI\Form;
		$form->addSelect('Attend', 'Moje účast: ', array("Yes" => "Účastním se", "Maybe" => "Možná se účastním", "No" => "Neúčastním se"))->setPrompt("- Vyber učast -");//->setDefaultValue($ucast-1);
		$form->addSubmit('Change', 'Nastavit');
		$form->addHidden('EventId');
		$form->onSuccess[] = $this->processValidatedEventAttendForm;
		return $form;
	}
	
	public function processValidatedEventAttendForm($form)
	{
		$values = $form->getValues();
		$database = $this->context->database;
		$eventId = $values["EventId"];
		$event = $database->table('Events')->where('Id', $eventId)->fetch();
		$uca = $database->table('EventAttendances')->where('EventId', $eventId)->where('UserId', $this->user->id)->fetch();
		if($uca == false){
			$database->table('EventAttendances')->insert(array(
				'EventId' => $eventId,
				'UserId' => $this->user->id,
				"Attending" => $values["Attend"]
			));			
		}else{
			$database->table('EventAttendances')->where('EventId', $eventId)->where('UserId', $this->user->id)->update(array(
				"Attending" => $values["Attend"]
			));
		}
		$this->redirect('Events:view',$eventId);
	}	
	
	public function createComponentNewEventForm()
	{
		$form = new UI\Form;
		$form->addText('Name', 'Název')->setRequired('Zadejte prosím název události')->getControlPrototype()->class = 'Wide';
		$form->addTextArea('Description', 'Popis události', 2, 5);
		$form->addText('Kapacita', 'Kapacita')
			->addRule(UI\Form::INTEGER, 'Kapacita musí být číslo')
			->setType('number')->setValue(0);
		$form->addCheckbox('IsOnlyAdult', 'Jen pro dospělé (18+)')->setValue(false);
		$form->addCheckbox('IsRegistrace', 'Pro vstup na akci je nutná registrace')->setValue(false);
		$form->addText('Konani', 'Místo konání')->setRequired('Zadejte prosím místo konání')->getControlPrototype()->class = 'Wide';
		if($this->month<10){$m="0".$this->month;}else{$m=$this->month;}
		$form->addText('StartTime', 'Začátek')->setRequired('Zadejte prosím začátek udalosti 13-08-2014')->setType('date')->setValue($this->year."-".$m."-".date("d",time()));
		$form->addText('StartTimeMin', 'ZačátekTime')->setRequired('Zadejte prosím začátek udalosti 12:20')->setType('time')->setValue(date("H",time()).":".date("i",time()));
		$form->addText('EndTime', 'Konec')->setRequired('Zadejte prosím konec udalosti 13-08-2014')->setType('date')->setValue($this->year."-".$m."-".date("d",time()));
		$form->addText('EndTimeMin', 'KonecTime')->setRequired('Zadejte prosím konec udalosti 13-08-2014 12:20')->setType('time')->setValue(date("H",time()).":".date("i",time()));
		$form->addText('GPS', 'GPS souřadnice')->getControlPrototype()->setValue("(49.84019666664545, 18.287429809570312)")->class = 'Wide';
		$form->addSubmit('Create', 'Vytvořit');
		$form->addSubmit('Update', 'Upravit');
		$form->addHidden('ContectId');
		$form->addHidden('EventId');
		//ContectId
		$form->onSuccess[] = $this->processValidatedNewEventForm;
		return $form;
	}
	
	public function processValidatedNewEventForm($form){
		$values = $form->getValues();
		$database = $this->context->database;
		
		if($values["EventId"]!=""){
		
			$this->processValidatedUpdateEventForm($form);
			
		}else{
		
		$database->beginTransaction();
		
		// Create default permission
		$defaultPermission = $database->table('Permissions')->insert(array(
			'CanListContent' => 1,
			'CanViewContent' => 1,
			'CanEditContentAndAttributes' => 0,
			'CanEditHeader' => 0,
			'CanEditOwnPosts' => 1,
			'CanDeleteOwnPosts' => 1,
			'CanDeletePosts' => 0,
			'CanWritePosts' => 1,
			'CanReadPosts' => 1,
			'CanEditPermissions' => 0,
			'CanEditPolls' => 0
		));
		// Create content
		$content = $database->table('Content')->insert(array(
			'Type' => 'Event',
			'TimeCreated' => new DateTime,
			'IsForRegisteredOnly' => $values['IsRegistrace'],
			'IsForAdultsOnly' => $values['IsOnlyAdult'],
			'DefaultPermissions' => $defaultPermission['Id']
		));
		$database->table('Ownership')->insert(array(
			'ContentId' => $content['Id'],
			'UserId' => $this->user->id
		));
		$database->table('Events')->insert(array(
			'ContentId' => $content['Id'],
			'Name' => $values['Name'],
			'Description' => $values['Description'],
			'StartTime' => $values["StartTime"]." ".$values["StartTimeMin"].":00",
			'EndTime' => $values["EndTime"]." ".$values["EndTimeMin"].":00",
			'Capacity' => $values['Kapacita'],
			'Place' => $values['Konani'],
			'GPS' => $values['GPS']
		));
		
		$database->commit();
		$this->flashMessage('Nová událost byla vytvořena', 'ok');
		$this->redirect('Events:default');
		
		}
	}
	
	public function processValidatedUpdateEventForm($form){
		$values = $form->getValues();
		$database = $this->context->database;
		
		$content = $database->table('Content')->where('Id', $values["ContectId"])->update(array(
			'IsForRegisteredOnly' => $values['IsRegistrace'],
			'IsForAdultsOnly' => $values['IsOnlyAdult']
		));
		
		$database->table('Events')->where('Id', $values["EventId"])->update(array(
			//'Name' => $values['Name'],
			'Description' => $values['Description'],
			'StartTime' => $values["StartTime"]." ".$values["StartTimeMin"].":00",
			'EndTime' => $values["EndTime"]." ".$values["EndTimeMin"].":00",
			'Capacity' => $values['Kapacita'],
			'Place' => $values['Konani'],
			'GPS' => $values['GPS']
		));
		
		$this->redirect('Events:view', $values["EventId"]);
	}

}