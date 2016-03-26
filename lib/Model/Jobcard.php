<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\hr\Model_Document{

	public $status=['Draft', 'Submitted', 'Approved', 'Reject'];

	public $action=[

				// 'Draft'=>['view','edit','delete','submit'],
				// 'Submitted'=>['view','edit','delete','reject'],
				// 'Approved'=>['view','edit','delete','reject'],
				// 'Reject'=>['view','edit','delete','submit']
					'*'=>['view','edit','delete']
	];
	
	function init(){
		parent::init();

		$job_j = $this->join('jobcard.qsp_master_id');

		$job_j->hasOne('xepan\hr\Department','department_id'); //it show current department
		$job_j->hasOne('xepan\production\OutsourceParty','outsourceparty_id');
		$job_j->hasOne('xepan\commerce\OrderItemDepartmentalStatus','order_item_departmental_status_id')->sortable(true);

		$job_j->addField('name')->caption('Job Number');
		$job_j->addField('created_date')->type('datetime')->defaultValue(date('Y-m-d H:i:s'));
		$job_j->addField('due_date')->type('datetime');
		
		$this->addCondition('type','Jobcard');
		
		$this->addExpression('order_number')->set(function($m,$q){
			return $m->ref('order_item_departmental_status_id')->ref('qsp_detail_id')->fieldQuery('qsp_master_id');
		});

		$this->addExpression('order_item_id')->set(function($m,$q){
			return $m->ref('order_item_departmental_status_id')->fieldQuery('qsp_detail_id');
		});

	}

	function createFromOrder($order_item, $order_dept_status ){
		$new_job_card = $this;

		$new_job_card->addCondition('order_item_departmental_status_id',$order_dept_status->id);
		$new_job_card->tryLoadAny();

		if($new_job_card->loaded())
			return false;

		$new_job_card['name']=rand(1000,9999);
		$new_job_card['status']='Approved';
		$new_job_card->save();
	}
}
