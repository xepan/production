<?php

namespace xepan\production;

class Model_Reports_Customer extends \xepan\commerce\Model_Customer{
	public $from_date;
	public $to_date;
	public $customer_id;
	

	function init(){
		parent::init();
		
		if($this->customer_id)									
			$this->addCondition('id',$this->customer_id);

		$this->addExpression('total_jobcards')->set(function($m,$q){
			 $jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_job_count'])
						->addCondition('customer_id',$m->getElement('id'));

			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}
			
			return $jc->count();
		});

		$this->addExpression('ToReceived')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_to_received_count'])
			            ->addCondition('customer_id',$m->getElement('id'))
						->addCondition('status','ToReceived');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}			

			return $jc->count();
		});

		$this->addExpression('Received')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_received_count'])
			            ->addCondition('customer_id',$m->getElement('id'))
						->addCondition('status','Received');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}			

			return $jc->count();
		});

		$this->addExpression('Processing')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Processing_count'])
			            ->addCondition('customer_id',$m->getElement('id'))
						->addCondition('status','Processing');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}

			return $jc->count();
		});

		$this->addExpression('Forwarded')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Forwarded_count'])
			            ->addCondition('customer_id',$m->getElement('id'))
						->addCondition('status','Forwarded');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}
			
			return $jc->count();
		});

		$this->addExpression('Completed')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Completed_count'])
			            ->addCondition('customer_id',$m->getElement('id'))
						->addCondition('status','Completed');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}

			return $jc->count();
		});

		$this->addExpression('Cancelled')->set(function($m,$q){	
			$jc =  $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Cancelled_count'])
			            ->addCondition('customer_id',$m->getElement('id'))
						->addCondition('status','Cancelled');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}
			
			return $jc->count();
		});

		$this->addExpression('Rejected')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Rejected_count'])
			            ->addCondition('customer_id',$m->getElement('id'))
						->addCondition('status','Rejected');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));	
			}

			return $jc->count();
		});
	}
}