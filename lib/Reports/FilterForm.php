<?php

namespace xepan\production;

class Reports_FilterForm extends \Form{
	
	function init(){
		parent::init();

		$this->setLayout('reports\productionform');
		$this->date_range_field = $this->addField('DateRangePicker','date_range')
								 ->setStartDate($this->app->now)
								 ->setEndDate($this->app->now)
								 ->getBackDatesSet();
	    $this->addField('autocomplete/Basic','contact')->setModel('xepan\base\Contact');
		$this->addSubmit('Filter')->addClass('btn btn-primary btn-block');
	}

	function validateFields(){

	}

	function reloadView($view){
		$from_date = $this->date_range_field->getStartDate();
    	$to_date = $this->date_range_field->getEndDate();						
					
		$this->js(null,$view->js()
			 ->reload(
				[
					'from_date'=>$from_date,
					'to_date'=>$to_date,
					'contact_id'=>$this['contact']
				]))->univ()->successMessage('wait ... ')->execute();
	}
}