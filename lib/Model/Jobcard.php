<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\base\Model_Document{
	public $status=['ToReceived','Received','Processing','Forwarded','Completed','Cancelled','Rejected'];

	public $actions=[
				'ToReceived'=>['view','edit','delete','received','processing','forwarded','complete','cancel','reject'],
				'Received'=>['view','edit','delete','processing','forwarded','complete','cancel'],
				'Processing'=>['view','edit','delete','forwarded','complete','cancel'],
				'Forwarded'=>['view','edit','delete','complete','cancel'],
				'Completed'=>['view','edit','delete','cancel'],
				'Cancelled'=>['view','edit','delete','processing'],
				'Rejected'=>['view','edit','delete','processing']
			];
	
	function init(){
		parent::init();
		
		$job_j=$this->join('jobcard.document_id');
		$job_j->hasOne('xepan\hr\Department','department_id');
		$job_j->hasOne('xepan\production\ParentJobcard','parent_jobcard_id')->defaultValue(0);

		$job_j->hasOne('xepan\production\OutsourceParty','outsourceparty_id'); //it show current department
		$job_j->hasOne('xepan\commerce\QSP_Detail','order_item_id')->sortable(true);

		$job_j->addField('due_date')->type('datetime');
		// $job_j->addField('status')->defaultValue('ToReceived');

		$job_j->hasMany('xepan\production\Jobcard_detail','jobcard_id');
		$job_j->hasMany('xepan\production\Jobcard','parent_jobcard_id',null,'SubJobcard');


		$this->addCondition('type','Jobcard');

		$this->addExpression('toreceived')->set("'Todo'");
		$this->addExpression('processing')->set("'Todo'");
		$this->addExpression('forwarded')->set("'Todo'");
		$this->addExpression('completed')->set("'Todo'");

		$this->addExpression('days_elapsed')->set(function($m,$q){
			return "'Todo'";
			$date=$m->add('\xepan\base\xDate');
			$diff = $date->diff(
						date('Y-m-d H:i:s',strtotime($m['created_date'])),
						date('Y-m-d H:i:s',strtotime($this->app->now)),
						'Days'
					);
			return "'".$diff."'";
		});

	}

	function createFromOrder($app,$order){
		if(!$order->loaded())
			throw new \Exception("sale order must be loaded");

		$ois = $app->add('xepan\commerce\Model_QSP_Detail');
		$ois->addCondition('qsp_master_id',$order->id);
		//create jobcard of each item in associated first department
		foreach ($ois as $oi) {
			//get first department
			$first_department = $oi->firstProductionDepartment();

			//Creating new Jobcard
			$jobcard = $app->add('xepan\production\Model_Jobcard');

			$jobcard['department_id'] = $first_department->id;
			$jobcard['order_item_id'] = $oi->id;
			$jobcard['status'] = "ToReceived";
			$new_jobcard = $jobcard->save();

			//Create New Jobcard Detail /Transactin Row Entry
			$j_detail = $app->add('xepan\production\Model_Jobcard_Detail')->addCondition('jobcard_id',$new_jobcard->id);
			$j_detail['direction'] = "In";
			$j_detail['quantity'] = $oi['quantity'];
			$j_detail['status'] = "ToReceived";
			$j_detail->save();
		}
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
	function orderItem(){
		
		return $this->add('xepan\commerce\Model_QSP_Detail')->load($this['order_item']);
	}
}
