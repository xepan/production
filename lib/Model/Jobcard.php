<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\base\Model_Document{
	public $status=['ToReceived','Received','Processing','Forwarded','Completed','Cancelled','Rejected'];

	public $actions=[
				'ToReceived'=>['view','edit','delete','receive','processing','forward','complete','cancel','reject'],
				'Received'=>['view','edit','delete','processing','forward','complete','cancel'],
				'Processing'=>['view','edit','delete','forward','complete','cancel'],
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

		$job_j->hasMany('xepan\production\Jobcard_Detail','jobcard_id');
		$job_j->hasMany('xepan\production\Jobcard','parent_jobcard_id',null,'SubJobcard');


		$this->addCondition('type','Jobcard');

		// $this->addExpression('order_no')->set(function($m,$q){
		// 	return $m->refSQL('order_item_id')->fieldQuery('qsp_master_id');
		// });

		// $this->addExpression('customer')->set(function($m,$q){
		// 	return $m->add('xepan\commerce\Model_SalesOrder')->load($q->getFieldQuery('order_no'))->fieldQuery('contact_id');
		// });

		$this->addExpression('order_item_name')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('name');
		});

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

			// //Create New Jobcard Detail /Transactin Row Entry
			$new_jobcard->createJobcardDetail("ToReceived",$oi['quantity']);
			// $j_detail = $app->add('xepan\production\Model_Jobcard_Detail')->addCondition('jobcard_id',$new_jobcard->id);
			// $j_detail['direction'] = "In";
			// $j_detail['quantity'] = $oi['quantity'];
			// $j_detail['status'] = "ToReceived";
			// $j_detail->save();
		}
	}

	function page_receive($page){
		$form = $page->add('Form');
		$jobcard_field = $form->addField('text','jobcard_row');
		$form->addSubmit('Receive Jobcard');

		//$grid_jobcard_row = $page->add('Grid');
		$grid_jobcard_row = $page->add('xepan\hr\Grid',['action_page'=>'xepan_production_jobcard'],null,['view/jobcard/transactionrow']);

		$grid_jobcard_row->addSelectable($jobcard_field);

		$jobcard = $this->ref('xepan\production\Jobcard_Detail');
		$grid_jobcard_row->setModel($jobcard);

		if($form->isSubmitted()){
			
			//doing jobcard detail/row received
			foreach (json_decode($form['jobcard_row']) as $transaction_row_id) {
				$jobcard_row_model = $this->add('xepan\production\Model_Jobcard_Detail')->load($transaction_row_id);
				$jobcard_row_model->received();
			}

			// calling jobcard receive function 
			if($this->receive())
				$form->js()->univ()->successMessage('Received Successfully')->execute();
			else
				$form->js()->univ()->errorMessage('Not Received')->execute();
		}
	}

	function receive(){
		//Mark Complete the Previous Department Jobcard if exist
		if($this['parent_jobcard_id'] and $this->parentJobcard()->checkAllDetailComplete()){
			$this->parentJobcard()->complete();
		}

        $this->app->employee
	            ->addActivity("Jobcard Received", $this->id /* Related Document ID*/, $this['customer'] /*Related Contact ID*/)
	            ->notifyWhoCan('reject,receive,forward','Jobcard Received');

		$this['status']='Received';
		$this->saveAndUnload();
		return true;
	}

	function parentJobcard(){
		if(!$this->loaded())
			throw new \Exception("Model Must Loaded", 1);
		if(!$this['parent_jobcard_id'])
			throw new \Exception("Parent Jobcard not found ", 1);

		return $this->ref('parent_jobcard_id');
			
	}

	//return true or false
	//return true when all detail are complete else return fasle
	function checkAllDetailComplete(){
		if($this->loaded())

		$all_complete = true;
		foreach ($this->ref('xepan\production\Jobcard_Detail') as $jd) {
			if($jd['status'] != "Completed"){
				$all_complete = false;
				continue;
			}
		}

		return $all_complete;

	}

	function processing(){
		$this['status']='Processing';
		$this->saveAndUnload();
	}
	

	// Every Forward it create two transaction 
	// one in same detail of forward amount and 
	// other in next department with ToReceived
	function page_forward($page){
		
		$page->add('View')->setElement('H4')->set($this['order_item_name']);
		
		$next_dept = $this->nextProductionDepartment();

		//total item to forward =)
		$qty_to_forward = $this['processing'] - ($this['forwarded'] + $this['completed']) ;

		$form = $page->add('Form');
		$form->addField('line','total_quantity_to_forward')->set($qty_to_forward);
		$form->addField('Number','quantity_to_forward')->set($qty_to_forward);
		$form->addSubmit('forward to '.$next_dept['name']);

		if($form->isSubmitted()){
			// create One New Transaction row of forward in self jobcard
			$jd = $this->createJobcardDetail("Forwarded",$form['quantity_to_forward']);
			//create/Load Next Department Jobcard and create new received transactio
			$result = $this->forward($next_dept,$form['quantity_to_forward'],$jd->id);

			if($result)
				$form->js()->univ()->successMessage('Forwarded Successfully')->execute();
			else
				$form->js()->univ()->successMessage('something wrong')->execute();
		}
	}
	
	//$next_dept == it's the object of next department of current jobcard
	//parent_detail ==  it's the object of the jobcardDetail newly created for forward Transaction row of current jobcard
	function forward($next_dept,$qty,$parent_detail_id){

		if($next_dept and ($next_dept instanceof \xepan\hr\Model_Department)){
			
			$new_jobcard = $this->add('xepan\production\Model_Jobcard');
			$new_jobcard
				->addCondition('department_id',$next_dept->id)
				->addCondition('parent_jobcard_id',$this->id)
				->addCondition('order_item_id',$this['order_item_id'])
			;
			$new_jobcard->tryLoadAny();
			$new_jobcard['status'] = "ToReceived";
			$new_jobcard->save()->createJobcardDetail('ToReceived',$qty,$parent_detail_id);

		}

		$this['status']='Processing';
		
		if($this['processing'] === $qty)
			$this['status']='Forwarded';

		$this->save();

		$order_item = $this->orderItem();
        $this->app->employee
            ->addActivity("Jobcard ".$this['id']. "forwarded", $this->id /* Related Document ID*/,$order_item['customer'] /*Related Contact ID*/)
            ->notifyWhoCan('reject,convert,open etc Actions perform on','Converted Any Status');

        $this->unload();
        return true;
	}

	function createJobcardDetail($status,$qty,$parent_detail_id=null){
		if(!$this->loaded())
			throw new \Exception("jobcard must loaded for creating it's detail");

		$detail = $this->add('xepan\production\Model_Jobcard_Detail');
		$detail['jobcard_id'] = $this->id;
		$detail['quantity'] = $qty;
		$detail['parent_detail_id'] = $parent_detail_id;
		$detail['status'] = $status?:"ToReceived";
		return $detail->save();
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
		return $this->add('xepan\commerce\Model_QSP_Detail')->load($this['order_item_id']);
	}

	function nextProductionDepartment(){
		if(!$this->loaded())
			throw new \Exception("model must loaded for next department");
		
		$dept_array = $this->orderItem()->getProductionDepartment();
		$depts = $this->add('xepan\hr\Model_Department')
							->addCondition('id',$dept_array)
							->setOrder('production_level','asc');
		
		$find_current_dept = false;

		foreach ($depts as $dept) {
			//for next department
			if($find_current_dept)
				return $dept;

			if($dept['id'] == $this['department_id']){
				$find_current_dept = true;
			}

		}

		return false;
	}


}
