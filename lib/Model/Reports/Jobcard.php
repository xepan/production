<?php

namespace xepan\production;

class Model_Report_Jobcard extends \xepan\hr\Model_Department{
	
	public $from_date=null;
	public $to_date=null;

	function init(){
		parent::init();

		$this->addExpression('total_jobcards')->set(function($m,$q){
			return $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_job_count'])
						->addCondition('department_id',$m->getElement('id'))
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->app->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('ToReceived')->set(function($m,$q){	
			return $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_to_received_count'])
			            ->addCondition('department_id',$m->getElement('id'))
						->addCondition('status','ToReceived')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->app->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('Received')->set(function($m,$q){	
			return $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_received_count'])
			            ->addCondition('department_id',$m->getElement('id'))
						->addCondition('status','Received')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->app->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('Processing')->set(function($m,$q){	
			return $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Processing_count'])
			            ->addCondition('department_id',$m->getElement('id'))
						->addCondition('status','Processing')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->app->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('Forwarded')->set(function($m,$q){	
			return $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Forwarded_count'])
			            ->addCondition('department_id',$m->getElement('id'))
						->addCondition('status','Forwarded')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->app->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('Completed')->set(function($m,$q){	
			return $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Completed_count'])
			            ->addCondition('department_id',$m->getElement('id'))
						->addCondition('status','Completed')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->app->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('Cancelled')->set(function($m,$q){	
			return $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Cancelled_count'])
			            ->addCondition('department_id',$m->getElement('id'))
						->addCondition('status','Cancelled')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->app->nextDate($this->to_date))
						->count();
		});

		$this->addExpression('Rejected')->set(function($m,$q){	
			return $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Rejected_count'])
			            ->addCondition('department_id',$m->getElement('id'))
						->addCondition('status','Rejected')
						->addCondition('created_at','>=',$this->from_date)
						->addCondition('created_at','<',$this->app->nextDate($this->to_date))
						->count();
		});
	}
}	