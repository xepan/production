<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\base\Model_Table{
	public $table = "jobcard";
	public $status=['Draft','Submitted','Approved',
					'Received','Assigned','Processing',
					'Forwarded','Completed','Cancelled'
				];

	public $actions=[
				'Draft'=>['view','edit','delete','submit'],
				'Submitted'=>['view','edit','delete','approve','cancel'],
				'Approved'=>['view','edit','delete','receive','cancel'],
				'Received'=>['view','edit','delete','assign','mark_processing','cancel'],
				'Assigned'=>['view','edit','delete','mark_processing'],
				'Processing'=>['view','edit','delete','forward','complete'],
				'Forwarded'=>['view','edit','delete','complete'],
			];
	
	function init(){
		parent::init();

		$this->hasOne('xepan\hr\Department','department_id'); //it show current department
		$this->hasOne('xepan\commerce\OrderItemDepartmentalStatus','order_item_departmental_status_id')->sortable(true);

		$this->addField('name')->caption('Job Number');
		$this->addField('created_date')->type('datetime')->defaultValue(date('Y-m-d H:i:s'));
		$this->addField('due_date')->type('datetime');
		
		$this->addField('type')->defaultValue('Jobcard');
		$this->addField('status')->setValueList($this->status)->defaultValue('Draft');
                 $this->addExpression('order_item')->set(function($m,$q){
			return $m->refSQL('order_item_departmental_status_id')->fieldQuery('qsp_detail_id');


		});

		$this->addExpression('days_elapsed')->set(function($m,$q){
			$date=$m->add('\xepan\base\xDate');
			$diff = $date->diff(
						date('Y-m-d H:i:s',strtotime($m['created_date'])),
						date('Y-m-d H:i:s',strtotime($this->app->now)),
						'Days'
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

	function submit(){
		$this['status']='Submitted';
		$this->saveAndUnload();
	}
	function approve(){
		$this['status']='Approved';
		$this->saveAndUnload();
	}
	function receive(){
		$this['status']='Received';
		$this->saveAndUnload();
	}
	function assign(){
		$this['status']='Assigned';
		$this->saveAndUnload();
	}
	function mark_processing(){
		$this['status']='Processing';
		$this->saveAndUnload();
	}
	function forward(){
		$this['status']='Forwarded';
		$this->saveAndUnload();
	}
	function complete(){
		$this['status']='Completed';
		$this->saveAndUnload();
	}
	function cancel(){
		$this['status']='Cancelled';
		$this->saveAndUnload();
	}
}
