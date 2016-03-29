<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\base\Model_Document{
	public $status=['ToReceived','Received','Processing','Forwarded','Completed','Cancelled','Rejected'];

	public $actions=[
				'ToReceived'=>['view','edit','delete','receive','processing','forward','complete','cancel','reject'],
				'receive'=>['view','edit','delete','processing','forwarded','complete','cancel'],
				'processing'=>['view','edit','delete','forwarded','complete','cancel'],
				'forward'=>['view','edit','delete','complete','cancel'],
				'complete'=>['view','edit','delete','cancel'],
				'cancel'=>['view','edit','delete','processing'],
				'reject'=>['view','edit','delete','processing']
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

		$job_j->hasMany('xepan\production\Jobcard_Detail','jobcard_id');
		$job_j->hasMany('xepan\production\Jobcard','parent_jobcard_id',null,'SubJobcard');


		$this->addCondition('type','Jobcard');

		// $this->addExpression('order_no')->set(function($m,$q){
		// 	return $m->refSQL('order_item_id')->fieldQuery('qsp_master_id');
		// });

		// $this->addExpression('customer')->set(function($m,$q){
		// 	return $m->add('xepan\commerce\Model_SalesOrder')->load($q->getFieldQuery('order_no'))->fieldQuery('contact_id');
		// });

		$this->addExpression('order_item_quantity')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('quantity');
		});

		$this->addExpression('toreceived')->set(function($m,$q){
			return $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','ToReceived')
					->sum('quantity');
		});

		$this->addExpression('processing')->set(function($m,$q){
			return $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','Received')
					->sum('quantity');
		});

		$this->addExpression('forwarded')->set(function($m,$q){
			return $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','Forwarded')
					->sum('quantity');
		});

		$this->addExpression('completed')->set(function($m,$q){
			return $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','Completed')
					->sum('quantity');
		});

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

	function page_receive($page){
		$form = $page->add('Form');
		$jobcard_field = $form->addField('text','jobcard_row');
		$form->addSubmit('Receive Jobcard');

		$grid_jobcard_row = $page->add('Grid');
		$grid_jobcard_row->addSelectable($jobcard_field);

		$jobcard = $this->ref('xepan\production\Jobcard_Detail');
		$grid_jobcard_row->setModel($jobcard);

		if($form->isSubmitted()){
			//doing jobcard detail/row received
			foreach ($form['jobcard_row'] as $transaction_row_id) {
				$jobcard_row_model = $this->add('xepan\production\Model_Jobcard_Detail')->load($transaction_row_id);
				$jobcard_row_model->received();
			}
			
			// calling jobcard receive function 
			$this->receive();

		}
		

	}


	function receive(){
		//Mark Complete the Previous Department Jobcard if exist
		if($this['parent_jobcard_id'] and ($this['order_item_quantity'] == $this['toreceived'])){
			$this->markParentComplete();
		}

        $this->app->employee
	            ->addActivity("Jobcard Received", $this->id /* Related Document ID*/, $this['customer'] /*Related Contact ID*/)
	            ->notifyWhoCan('reject,receive','Jobcard Received');

		$this['status']='Received';
		$this->saveAndUnload();
	}

	function markParentComplete(){
		if(!$this->loaded()){
			 throw $this->exception("model must be loaded ")
				->addMoreInfo('jobcard model for mark Parent Complete');
		}

		$this->ref('parent_jobcard_id')->complete();

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
		$this->save();

		//check for the mark order complete
		//if all jobcard of order are complete
		//then mark sale order complete
		//create activity
	}

	function cancel(){
		$this['status']='Cancelled';
		$this->saveAndUnload();
	}

	function orderItem(){		
		return $this->add('xepan\commerce\Model_QSP_Detail')->load($this['order_item']);
	}


}
