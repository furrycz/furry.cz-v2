<?php

use Nette\Application\UI;

class EventsPresenter extends BasePresenter
{
	/** @persistent */
    public $month;

    /** @persistent */
    public $year;

/*
    public function __construct(\Nette\ComponentModel\IContainer $parent = NULL, $name = NULL)
    {
		parent::__construct($parent, $name);

		//$this->year = $this->getParam('year', date("Y"));
		//$this->month = $this->getParam('month', date("n"));
    }
	*/


    public function renderDefault($year=0,$month=0)
    {
	
	if($year==0){$this->year=date("Y");}
	if($month==0){$this->month=date("n");}
	
	$template = $this->template;
	$database = $this->context->database;
    //$template = parent::createTemplate();
    //$template->setFile(__DIR__ . '/calendar.latte');

    /* days and weeks vars now ... */
	
	$template->events = "";
	$eventy = $database->table('events')->order('StartTime DESC');
	foreach($eventy as $event){
		$date = strtotime($event["StartTime"]);	
		$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][0][] = $event["Id"];
		$template->events[(int)date('Y', $date)][(int)date('m', $date)][(int)date('d', $date)][1][] = $event["Name"];
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

    //$template->render();
    }
	
	public function renderNew(){
		
		if (!($this->user->isInRole('member') || $this->user->isInRole('admin')))
		{
			throw new Nette\Application\ForbiddenRequestException(
				'Pouze registrovaní uživatelé mohou zakládat nová diskusní témata');
		}
		
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
		$form->addText('StartTime', 'Začátek')->setRequired('Zadejte prosím začátek udalosti 13-08-2014')->setType('date')->setValue($this->year."-".$m."-".date("m",time()));
		$form->addText('EndTime', 'Konec')->setRequired('Zadejte prosím konec udalosti 13-08-2014')->setType('date')->setValue($this->year."-".$m."-".date("m",time()));
		$form->addText('GPS', 'GPS souřadnice')->getControlPrototype()->setValue("(49.84019666664545, 18.287429809570312)")->class = 'Wide';
		$form->addSubmit('Create', 'Vytvořit');
		$form->onSuccess[] = $this->processValidatedNewEventForm;
		return $form;
	}
	
	public function processValidatedNewEventForm($form)
	{
		$values = $form->getValues();
		$database = $this->context->database;
		$database->beginTransaction();
		
		// Create default permission
		$defaultPermission = $database->table('Permissions')->insert(array(
			'CanListContent' => 1,
			'CanViewContent' => 1,
			'CanEditContentAndAttributes' => 1,
			'CanEditHeader' => 0,
			'CanEditOwnPosts' => 1,
			'CanDeleteOwnPosts' => 1,
			'CanDeletePosts' => 0,
			'CanWritePosts' => 0,
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
		$database->table('Events')->insert(array(
				'ContentId' => $content['Id'],
				'Name' => $values['Name'],
				'Description' => $values['Description'],
				'StartTime' => $values["StartTime"]." 00:00:00",
				'EndTime' => $values["EndTime"]." 00:00:00",
				'Capacity' => $values['Kapacita'],
				'Place' => $values['Konani'],
				'GPS' => $values['GPS']
			));
		
		$database->commit();
		$this->flashMessage('Nová událost byla vytvořena', 'ok');
		$this->redirect('Events:default');
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

}