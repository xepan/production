<?php

namespace xepan\production;

class Reports_FilterForm extends \Form{
	public $extra_fields;
	public $status_array;
	public $entity;

	function init(){
		parent::init();

		$this->setLayout('reports\productionform');
		$this->date_range_field = $this->addField('DateRangePicker','date_range')
								 ->setStartDate($this->app->now)
								 ->setEndDate($this->app->now)
								 ->getBackDatesSet();
		switch ($this->entity) {
					case 'customer':
						$this->addField('autocomplete/Basic','contact')->setModel('xepan\base\Contact');
						$this->layout->template->tryDel('department_wrapper');
						$this->layout->template->tryDel('outsourceparty_wrapper');
						break;
					case 'outsourceparty':
						$this->addField('autocomplete/Basic','outsource_party')->setModel('xepan\production\OutsourceParty');
						$this->layout->template->tryDel('department_wrapper');
						$this->layout->template->tryDel('contact_wrapper');
						break;	
					default:
						$this->addField('autocomplete/Basic','department')->setModel('xepan\hr\Department');
						$this->layout->template->tryDel('contact_wrapper');
						$this->layout->template->tryDel('outsourceparty_wrapper');
						break;
				}

		if($this->extra_fields){
			$this->addField('xepan\base\DropDown','status')->setValueList($this->status_array)->setEmptyText('Please Select');
			$this->addField('xepan\base\DropDown','order')->setValueList(['desc'=>'Highest','asc'=>'Lowest'])->setEmptyText('Please Select');
		}else{
			$this->layout->template->tryDel('extra_field_wrapper');
		}

		$this->addSubmit('Filter')->addClass('btn btn-primary btn-block');
	}

	function validateFields(){
		if(($this['status'] == null AND $this['order'] !=null) OR ($this['status'] != null AND $this['order'] ==null))
			$this->displayError('status','Please select order and status both');
		
		return $this;
	}

	function reloadView($view){
		$from_date = $this->date_range_field->getStartDate();
    	$to_date = $this->date_range_field->getEndDate();						
					
		$this->js(null,$view->js()
			 ->reload(
				[
					'from_date'=>$from_date,
					'to_date'=>$to_date,
					'customer_id'=>$this['contact'],
					'department_id'=>$this['department'],
					'outsource_party_id'=>$this['outsource_party'],
					'jobcard_status'=>$this['status'],
					'order'=>$this['order'],
				]))->univ()->successMessage('wait ... ')->execute();
	}
}