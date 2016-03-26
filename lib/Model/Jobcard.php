<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\base\Model_Table{
	public $table = "jobcard";
	public $status=['Draft', 'Submitted', 'Approved', 'Reject'];

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
		
		// $this->addExpression('order_number')->set(function($m,$q){
		// 	return $m->ref('order_item_departmental_status_id')->ref('qsp_detail_id')->fieldQuery('qsp_master_id');
		// });

		$this->addExpression('order_item')->set(function($m,$q){
			return $m->refSQL('order_item_departmental_status_id')->fieldQuery('qsp_detail_id');
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
}
