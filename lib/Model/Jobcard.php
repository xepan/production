<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\base\Model_Table{
	public $table = "jobcard";
	public $status=['Draft','Submitted','Approved',
					'Received','Assigned','Processing',
					'Forwarded','Completed','Cancelled'
				];

	public $action=[
				'*'=>['view','edit','delete']
			];
	
	function init(){
		parent::init();

		$this->hasOne('xepan\hr\Department','department_id'); //it show current department
		$this->hasOne('xepan\commerce\OrderItemDepartmentalStatus','order_item_departmental_status_id')->sortable(true);

		$this->addField('name')->caption('Job Number');
		$this->addField('created_date')->type('datetime')->defaultValue(date('Y-m-d H:i:s'));
		$this->addField('due_date')->type('datetime');
		
		$this->addField('type')->defaultValue('Jobcard');
		
		$this->addExpression('order_item')->set(function($m,$q){
			return $m->refSQL('order_item_departmental_status_id')->fieldQuery('qsp_detail_id');


		});

		$this->addExpression('days_elapsed')->set(function($m,$q){
			$date=$m->add('\xepan\base\xDate');
			$diff = $date->diff(
						date('Y-m-d H:i:s',$m['created_date']
							),
						date('Y-m-d H:i:s',$this->app->now),'Days'
					);

			return "'".$diff."'";
		});
	}

	function createFromOrder($order_item, $order_dept_status ){
		$new_job_card = $this;

		$new_job_card->addCondition('order_item_departmental_status_id',$order_dept_status->id);
		$new_job_card->tryLoadAny();

		if($new_job_card->loaded())
			return false;

		$new_job_card['name']=rand(1000,9999);
		$new_job_card['department_id']=$order_dept_status['department_id'];
		$new_job_card['status']='Approved';
		$new_job_card->save();
	}

	function Submitted(){
		$this['status']='Submitted';
		$this->saveAndUnload();
	}
	function Approved(){
		$this['status']='Approved';
		$this->saveAndUnload();
	}
	function Received(){
		$this['status']='Received';
		$this->saveAndUnload();
	}
	function Assigned(){
		$this['status']='Assigned';
		$this->saveAndUnload();
	}
	function Processing(){
		$this['status']='Processing';
		$this->saveAndUnload();
	}
	function Forwarded(){
		$this['status']='Forwarded';
		$this->saveAndUnload();
	}
	function Completed(){
		$this['status']='Completed';
		$this->saveAndUnload();
	}
	function Cancelled(){
		$this['status']='Cancelled';
		$this->saveAndUnload();
	}
}
