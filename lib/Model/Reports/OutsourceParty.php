<?php

namespace xepan\production;

class Model_Reports_OutsourceParty extends \xepan\production\Model_OutsourceParty{
	public $from_date;
	public $to_date;
	public $outsource_party_id;
	
	function init(){
		parent::init();
		
		if($this->outsource_party_id)									
			$this->addCondition('id',$this->outsource_party_id);

		$this->addExpression('total_jobcards')->set(function($m,$q){
			 $jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_job_count'])
						->addCondition('outsourceparty_id',$m->getElement('id'));

			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}
			
			return $jc->count();
		});

		$this->addExpression('ToReceived')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_to_received_count'])
			            ->addCondition('outsourceparty_id',$m->getElement('id'))
						->addCondition('status','ToReceived');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}			

			return $jc->count();
		});

		$this->addExpression('Received')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_received_count'])
			            ->addCondition('outsourceparty_id',$m->getElement('id'))
						->addCondition('status','Received');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}			

			return $jc->count();
		});

		$this->addExpression('Processing')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Processing_count'])
			            ->addCondition('outsourceparty_id',$m->getElement('id'))
						->addCondition('status','Processing');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}

			return $jc->count();
		});

		$this->addExpression('Forwarded')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Forwarded_count'])
			            ->addCondition('outsourceparty_id',$m->getElement('id'))
						->addCondition('status','Forwarded');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}
			
			return $jc->count();
		});

		$this->addExpression('Completed')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Completed_count'])
			            ->addCondition('outsourceparty_id',$m->getElement('id'))
						->addCondition('status','Completed');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}

			return $jc->count();
		});

		$this->addExpression('Cancelled')->set(function($m,$q){	
			$jc =  $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Cancelled_count'])
			            ->addCondition('outsourceparty_id',$m->getElement('id'))
						->addCondition('status','Cancelled');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));
			}
			
			return $jc->count();
		});

		$this->addExpression('Rejected')->set(function($m,$q){	
			$jc = $this->add('xepan\production\Model_Jobcard',['table_alias'=>'total_Rejected_count'])
			            ->addCondition('outsourceparty_id',$m->getElement('id'))
						->addCondition('status','Rejected');
			if($this->from_date){
				$jc->addCondition('created_at','>=',$this->from_date);
				$jc->addCondition('created_at','<',$this->app->nextDate($this->to_date));	
			}

			return $jc->count();
		});
	}
}