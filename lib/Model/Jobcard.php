<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\base\Model_Document{
	public $status=['ToReceived','Received','Processing','Forwarded','Completed','Cancelled','Rejected'];

	public $actions=[
				'ToReceived'=>['view','edit','delete','receive','reject'],
				'Received'=>['view','edit','delete','processing','forward','complete','cancel'],
				'Processing'=>['view','edit','delete','sendToDispatch','forward','complete','cancel'],
				'Forwarded'=>['view','edit','delete','sendToDispatch','complete','cancel'],
				'Completed'=>['view','edit','delete','forward','sendToDispatch','cancel'],
				'Cancelled'=>['view','edit','delete','processing'],
				'Rejected'=>['view','edit','delete','processing']
			];
	
	function init(){
		parent::init();
		
		$job_j=$this->join('jobcard.document_id');
		$job_j->hasOne('xepan\hr\Department','department_id')->sortable(true);
		$job_j->hasOne('xepan\production\ParentJobcard','parent_jobcard_id')->defaultValue(0)->sortable(true);

		$job_j->hasOne('xepan\production\OutsourceParty','outsourceparty_id')->sortable(true); //it show current department
		$job_j->hasOne('xepan\commerce\QSP_Detail','order_item_id')->sortable(true);

		$job_j->addField('due_date')->type('datetime')->sortable(true);

		$job_j->hasMany('xepan\production\Jobcard_Detail','jobcard_id');
		$job_j->hasMany('xepan\production\Jobcard','parent_jobcard_id',null,'SubJobcard');
		$job_j->hasMany('xepan\commerce\Store_Transaction','jobcard_id');

		$this->addCondition('type','Jobcard');
		$this->addCondition('created_by_id',$this->app->employee->id);
		$this->addHook('beforeDelete',[$this,'checkExistingRelatedTransaction']);

		$this->addExpression('order_no')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('qsp_master_id');
		})->sortable(true);

		$this->addExpression('customer_id')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('customer_id');
		})->sortable(true);

		$this->addExpression('customer_name')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('customer');
		})->sortable(true);

		$this->addExpression('order_item_name')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('name');
		})->sortable(true);

		$this->addExpression('order_item_quantity')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('quantity');
		})->sortable(true);

		$this->addExpression('toreceived')->set(function($m,$q){
			$to_received = $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','ToReceived')
					->sum('quantity');
			return $q->expr("IFNULL ([0], 0)",[$to_received]);
		})->sortable(true);


		$this->addExpression('forwarded')->set(function($m,$q){
			$forwarded = $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','Forwarded')
					->sum('quantity');
			return $q->expr("IFNULL([0],0)",[$forwarded]);
		})->sortable(true);


		$this->addExpression('completed')->set(function($m,$q){
			$completed  = $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','Completed')
					->sum('quantity');
			return $q->expr("IFNULL( [0], 0)",[$completed]);
		})->sortable(true);

		$this->addExpression('processing')->set(function($m,$q){
			$processing = $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','Received')
					->sum('quantity');

			return $q->expr("IFNULL( ([0]- IFNULL([1],0) - IFNULL([2],0) ),0)",[$processing,$m->getElement('forwarded'),$m->getElement('completed')]);
		})->sortable(true);

		$this->addExpression('days_elapsed')->set(function($m,$q){
			// return "'Todo'";
			$date=$m->add('\xepan\base\xDate');
			$diff = $date->diff(
						date('Y-m-d H:i:s',strtotime($m['created_at'])
							),
						date('Y-m-d H:i:s',strtotime($m['due_date']?$m['due_date']:$this->app->today)),'Days'
					);

			return "'".$diff."'";
		})->sortable(true);

		$this->addHook('beforeDelete',$this);
		$this->addHook('beforeSave',[$this,'updateSearchString']);

	}

	function beforeDelete(){

		$job_details = $this->add('xepan\production\Model_Jobcard_Detail')->addCondition('jobcard_id',$this->id);
		foreach ($job_details as $job_detail) {
			$job_details->delete();
		}
	}

	function checkExistingRelatedTransaction(){
		$this->ref('xepan\commerce\Store_Transaction')->each(function($m){$m->delete();});
	}

	function createFromOrder($app,$order){
		if(!$order->loaded())
			throw new \Exception("sale order must be loaded");

		$ois = $app->add('xepan\commerce\Model_QSP_Detail');
		$ois->addCondition('qsp_master_id',$order->id);
		//create jobcard of each item in associated first department
		$jobcard = $app->add('xepan\production\Model_Jobcard');
		foreach ($ois as $oi) {
			$jobcard->createFromOrderItem($oi);
		}
	}

	function createFromOrderItem($oi){
		//get first department
			$first_department = $oi->firstProductionDepartment();

			//Creating new Jobcard
			$jobcard = $this->add('xepan\production\Model_Jobcard');

			$jobcard['department_id'] = $first_department->id;
			$jobcard['order_item_id'] = $oi->id;
			$jobcard['status'] = "ToReceived";
			$new_jobcard = $jobcard->save();

			//Create New Jobcard Detail /Transactin Row Entry
			$new_jobcard->createJobcardDetail("ToReceived",$oi['quantity']);
	}

	function page_receive($page){
		$form = $page->add('Form');
		$jobcard_field = $form->addField('hidden','jobcard_row');
		$form->addSubmit('Receive Jobcard');

		//$grid_jobcard_row = $page->add('Grid');
		$grid_jobcard_row = $page->add('xepan\hr\Grid',['action_page'=>'xepan_production_jobcard'],null,['view/jobcard/transactionrow']);

		$grid_jobcard_row->addSelectable($jobcard_field);

		$jobcard = $this->ref('xepan\production\Jobcard_Detail');
		$jobcard->addCondition('status','ToReceived');

		$grid_jobcard_row->setModel($jobcard);

		if($form->isSubmitted()){
			
			//doing jobcard detail/row received
			foreach (json_decode($form['jobcard_row']) as $transaction_row_id) {
				$jobcard_row_model = $this->add('xepan\production\Model_Jobcard_Detail')->load($transaction_row_id);
				$jobcard_row_model->received();
			}

			// calling jobcard receive function 
			if($this->receive())
				return $form->js()->univ()->successMessage('Received Successfully');
			else
				return $form->js()->univ()->errorMessage('Not Received');
		}
	}

	function receive(){
		
		//Mark Complete the Previous Department Jobcard if exist
		// throw new \Exception($this['parent_jobcard_id']);
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
		if(!$this->loaded())
			throw new \Exception("jobcrad model must loaded");
				
		$all_complete = false;
		
		$jd_detail = $this->add('xepan\production\Model_Jobcard_Detail')
			->addCondition('jobcard_id',$this->id)->getRows();

		$total_received_qty = 0;
		$total_complete_qty = 0;
		foreach ($jd_detail as $temp) {
			if($temp['status'] == "Received")
				$total_received_qty += $temp['quantity'];
			 
			if($temp['status'] == "Completed")
				$total_complete_qty += $temp['quantity'];
		}
				
		if($total_received_qty == $total_complete_qty ){
			$all_complete = true;
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
		if(!$next_dept){
			$this->add('View_Warning')->set('next department not found');			
			return;
		}

		//total item to forward =)
		$qty_to_forward = $this['processing'];

		$form = $page->add('Form');
		$form->addField('line','total_quantity_to_forward')->setAttr('readonly','true')->set($qty_to_forward);
		$form->addField('Number','quantity_to_forward')->set($qty_to_forward);
		$form->addSubmit('forward to '.$next_dept['name']);

		if($form->isSubmitted()){
			if($form['quantity_to_forward'] > $form['total_quantity_to_forward'])
				$form->displayError('quantity_to_forward',"qty cannot be more than ".$form['total_quantity_to_forward']);

			// create One New Transaction row of forward in self jobcard
			$jd = $this->createJobcardDetail("Forwarded",$form['quantity_to_forward']);
			//create/Load Next Department Jobcard and create new received transactio
			$result = $this->forward($next_dept,$form['quantity_to_forward'],$jd->id);

			if($result)
				return $form->js()->univ()->successMessage('Forwarded Successfully');
			else
				return $form->js()->univ()->successMessage('something wrong');
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

	function page_complete($page){
		if(!$this->nextProductionDepartment()){
			
			$qty_to_complete = $this['processing'];

			$form = $page->add('Form');
			$form->addField('line','total_qty_to_complete')->setAttr('readonly','true')->set($qty_to_complete);
			$form->addField('Number','qty_to_complete')->set($qty_to_complete);
			$form->addSubmit('mark completed');

			if($form->isSubmitted()){
				if($form['qty_to_complete'] > $form['total_qty_to_complete'])
					$form->displayError('qty_to_complete',"qty cannot be more than ".$form['total_qty_to_complete']);
				// create One New Transaction row of Completed in self jobcard
				$jd = $this->createJobcardDetail("Completed",$form['qty_to_complete']);
				$this->complete();
				return $form->js()->univ()->successMessage($form['qty_to_complete']." Completed");		
			}
		}else{
			return $this->complete();		
		}
	}
	
	function complete(){

		$this['status']="Processing";			
		if($this->checkAllDetailComplete()){
			$this['status']='Completed';
		}
		$this->save();
		//create activity of jobcrad complete
		$this->app->employee
			->addActivity("Jobcard no. ".$this['document_no']." has been completed", $this->id/* Related Document ID*/, $this['customer_id'] /*Related Contact ID*/)
			->notifyWhoCan('edit,delete',"Jobcard ".$this['document_no']." Completed",$this);


		$sale_order_model = $this->add('xepan\commerce\Model_SalesOrder')->load($this['order_no']);
		//check for the mark order complete
		if(!$sale_order_model->isCompleted()){
			//if all jobcard of order are complete
			if($this->checkAllDetailComplete()){
				//then mark sale order complete
				$sale_order_model->complete();
			}
		}
		
		$this->saveAndUnload();

	}

	function cancel(){
		$this['status']='Cancelled';
		$this->saveAndUnload();
	}

	function reject(){
		$this['status'] = 'Rejected';
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

	function page_sendToDispatch($page){
        $page->add('View')->setElement('H4')->set($this['order_item_name']);
		
		// $next_dept = $this->nextProductionDepartment();
        
		//total item to forward =)
		$qty_to_send = $this['processing'] - ($this['forwarded'] + $this['completed']) ;

		$form = $page->add('Form');
		$form->addField('line','total_qty')->set($qty_to_send);
		$form->addField('Number','qty')->set($qty_to_send);
        $warehouse_f=$form->addField('DropDown','warehouse');
        $warehouse=$page->add('xepan\commerce\Model_Store_Warehouse');
    	$warehouse_f->setModel($warehouse);

        $form->addSubmit('Send To Dispatch');

   
    	if($form->isSubmitted()){
        // throw new \Exception($this->orderItem()->getElement('qsp_master_id'), 1);
    		// throw new \Exception($this['order_item_id'], 1);
	        $jd = $this->createJobcardDetail("Forwarded",$form['qty']);
	        // throw new \Exception($jd->id, 1);
			$this->sendToDispatch($form['qty'],$form['warehouse'],$jd->id);

			return $form->js()->univ()->successMessage('Send To Dispatch Successfully');

            // return true;
        }
    }	
        
    function sendToDispatch($qty,$warehouse,$jobcard_detail){
    	$order=$this->orderItem()->ref('qsp_master_id');
    	// throw new \Exception($order['contact_id'], 1);
		
    	$warehouse = $this->add('xepan\commerce\Model_Store_Warehouse')->load($warehouse);
			$transaction = $warehouse->newTransaction($this['order_no'],$this->id,$order['contact_id'],'Dispatch');

			$transaction->addItem($this['order_item_id'],$qty,$jobcard_detail,null,null);

		$this['status']='Processing';

		if($this['processing'] === $qty)
			$this['status']='Completed';
		
		$this->save();
	    $this->unload();
	    return true;
    }

    //Catch Hook:: qsp_detail_qty_changed
    function updateJobcard($app,$orderItem){
    	
    	if(!in_array($orderItem['qsp_status'], ['Approved','InProgress','Completed']))
    		return false;

    	$old_oi = $app->add('xepan\commerce\Model_QSP_Detail')->load($orderItem->id);
    	$old_qty = $old_oi['quantity'];

		$jobcard = $app->add('xepan\production\Model_Jobcard')
					->addCondition('order_item_id',$old_oi->id)
					->addCondition('parent_jobcard_id',null)
					->setOrder('id','asc')
					->setLimit(1)
					->tryLoadAny()
					;

		if($jobcard->count()->getOne()!=1){
			$jobcard->save();			
			// throw new \Exception("Jobcard not found");
		}

		$qty = $orderItem['quantity'] - $old_qty;
		$jobcard->createJobcardDetail("ToReceived",$qty);
    }

    //Catch Hook:: qsp_detail_insert
    function createJobcard($app,$orderItem){

    	if(!in_array($orderItem['qsp_status'], ['Approved','InProgress','Completed']))
    		return false;

    	$jobcard = $app->add('xepan\production\Model_Jobcard');
    	$jobcard->createFromOrderItem($orderItem);
    }

    //Catch Hook:: qsp_detail_delete
    function deleteJobcard($app,$orderItem){
    	if(!$orderItem->loaded())
    		throw new \Exception("order item must defined");
    	
    	if(!in_array($orderItem['qsp_status'], ['Approved','InProgress','Completed']))
    		return false;
    		
    	$jobcards = $app->add('xepan\production\Model_Jobcard')->addCondition('order_item_id',$orderItem->id);
    	foreach ($jobcards as $jobcard) {
 			$jobcard->delete();   		
    	}
    }

    function updateSearchString($m){

		$search_string = ' ';
		$search_string .=" ". $this['order_no'];
		$search_string .=" ". $this['customer_id'];
		$search_string .=" ". $this['customer_name'];
		$search_string .=" ". $this['order_item_name'];
		$search_string .=" ". $this['order_item_quantity'];
		$search_string .=" ". $this['days_elapsed'];
		$search_string .=" ". $this['toreceived'];
		$search_string .=" ". $this['processing'];
		$search_string .=" ". $this['forwarded'];
		$search_string .=" ". $this['completed'];
		$search_string .=" ". $this['type'];

		$this['search_string'] = $search_string;
	}

    
}
