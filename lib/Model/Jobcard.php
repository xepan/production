<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\base\Model_Document{
	public $status=['ToReceived','Received','Processing','Forwarded','Completed','Cancelled','Rejected'];

	public $actions=[
				'ToReceived'=>['view','edit','delete','receive','reject'],
				'Received'=>['view','edit','delete','processing','complete','cancel'],
				'Processing'=>['view','edit','delete','complete','forward','sendToDispatch','cancel'],
				'Forwarded'=>['view','edit','delete','cancel'],
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

		$this->addExpression('item_id')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('item_id');
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

		$this->addExpression('receivedbynext')->set(function($m,$q){
			$forwarded = $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','ReceivedByNext')
					->sum('quantity');
			return $q->expr("IFNULL([0],0)",[$forwarded]);
		})->sortable(true);

		$this->addExpression('pendingbynext')->set(function($m,$q){
			return $q->expr(" IFNULL ([0] - [1],0)",[$m->getElement('forwarded'),$m->getElement('receivedbynext')]);
		});	

		$this->addExpression('receivedbydispatch')->set(function($m,$q){
			return $q->expr("IFNULL([0],0)",[$m->refSQL('xepan\commerce\Store_Transaction')->sum('received')]);
		})->sortable(true);

		$this->addExpression('pendingbydispatch')->set(function($m,$q){
			return $q->expr("IFNULL([0],0)",[$m->refSQL('xepan\commerce\Store_Transaction')->sum('toreceived')]);
		})->sortable(true);

		$this->addExpression('completed')->set(function($m,$q){
			$completed  = $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','Completed')
					->sum('quantity');
			return $q->expr("IFNULL([0], 0)",[$completed]);
		})->sortable(true);

		$this->addExpression('dispatched')->set(function($m,$q){
			$dispatched = $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','Dispatched')
					->sum('quantity');

			return $q->expr("IFNULL ([0], 0)",[$dispatched]);
		});

		$this->addExpression('processing')->set(function($m,$q){
			$processing = $m->refSQL('xepan\production\Jobcard_Detail')
					->addCondition('status','Received')
					->sum('quantity');

			return $q->expr("IFNULL(([0] - IFNULL([1],0)),0)",[$processing,$m->getElement('completed')]);
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
			if(!$first_department or !$first_department->loaded())
				return;
				
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

		$jobcard = $this->refSQL('xepan\production\Jobcard_Detail');
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
				return $form->js()->univ()->successMessage('Received Successfully')->closeDialog();
			else
				return $form->js()->univ()->errorMessage('Not Received');
		}
	}

	function receive(){
		
		//Mark Complete the Previous Department Jobcard if exist
		$this->add('xepan\commerce\Model_SalesOrder')
			->load($this['order_no'])
			->inprogress();

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

		return $this->refSQL('parent_jobcard_id');
			
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
			$page->add('View_Warning')->set('next department not found');
			return;
		}

		//total item to forward =)
		$qty_to_forward = $this['completed'] - $this['forwarded'] - $this['dispatched'];

		if(!$qty_to_forward){
			$page->add('View_Warning')->set(" no forward quantity found");
			return;
		}

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
				return $form->js()->univ()->successMessage('Forwarded Successfully')->closeDialog();
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


		if($this['status'] != "Completed")
			$this['status'] = "Processing";
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
			return $form->js()->univ()->successMessage($form['qty_to_complete']." Completed")->closeDialog();
		}
	}
	
	function complete(){

		$this['status']='Processing';		
		if($this->checkAllDetailComplete())
			$this['status']='Completed';
		
		$this->save();

		//check for the mark order complete
		if($this['status'] == "Completed"){

			$sale_order_model = $this->add('xepan\commerce\Model_SalesOrder')->load($this['order_no']);
			if($is_complete = $this->checkOrderComplete($sale_order_model)){
				$sale_order_model->complete();
			}

		}
		//create activity of jobcrad complete
		$this->app->employee
			->addActivity("Jobcard no. ".$this['document_no']." has been completed", $this->id/* Related Document ID*/, $this['customer_id'] /*Related Contact ID*/)
			->notifyWhoCan('edit,delete',"Jobcard ".$this['document_no']." Completed",$this);

		$this->saveAndUnload();

	}

	function checkOrderComplete($sale_order){
		// This is loaded JobCard Btw

		if(!$sale_order->loaded())
			throw new \Exception("jobcard order not found");

		/*
		For all order_items(qsp_detail)(where order_no is {sales_order_no})
			{
				if(item is dispachable){
					if (total item order quantity > total_dispatched ) return false;
				}else{
					if (total item order quantity > completed_in_last_department ) return false;
				}
			}
		return true;
		*/

		$order_items = $this->add('xepan\commerce\Model_QSP_Detail');
		$order_items->addExpression('is_dispatchable')->set($order_items->refSQL('item_id')->fieldQuery('is_dispatchable'));
		
		$order_items->addExpression('total_dispacthed')->set(function ($m,$q){
			$jd_detail_model = $m->add('xepan\production\Model_Jobcard_Detail');
			$jd_detail_model->addExpression('for_order_detail_id')->set($jd_detail_model->refSQL('jobcard_id')->fieldQuery('order_item_id'));
			$jd_detail_model->addCondition('for_order_detail_id',$q->getField('id'));
			$jd_detail_model->addCondition('status','Dispatched');
			return $jd_detail_model->sum('quantity');
		});

		$order_items->addCondition('qsp_master_id',$sale_order->id);

		foreach ($order_items->getRows() as $oi) {
			if($oi['is_dispatchable']){
				// echo "is Dispatchable ";
				// echo $oi['item']. '<pre>';
				// echo $oi['quantity'] .' > '. $oi['total_dispacthed'].'<br/>';
				if ($oi['quantity'] > $oi['total_dispacthed'] ) return false;
			}else{
				$io_json=json_decode($oi['extra_info'],true);
				$io_key=array_keys($io_json);
				$last_dept = array_pop($io_key);
				$order_items2 = $this->add('xepan\commerce\Model_QSP_Detail');

				$order_items2->addExpression('completed_in_last_department')->set(function($m,$q)use($last_dept){
					$item_m = $m->add('xepan\commerce\Model_Item');
					$jd_detail_model = $m->add('xepan\production\Model_Jobcard_Detail');
					$jd_detail_model->addExpression('for_order_detail_id')->set($jd_detail_model->refSQL('jobcard_id')->fieldQuery('order_item_id'));
					$jd_detail_model->addExpression('department_id')->set($jd_detail_model->refSQL('jobcard_id')->fieldQuery('department_id'));
					
					$jd_detail_model->addCondition('for_order_detail_id',$q->getField('id'));
					$jd_detail_model->addCondition('status','Completed');
					$jd_detail_model->addCondition('department_id',$last_dept);

					return $jd_detail_model->sum('quantity');
				});

				$order_items2->load($oi['id']);
				
				// echo "<br/>is not Dispatchable ";
				// echo $oi['item']. '<pre>';
				// echo $oi['quantity'] .' > '. $order_items2['completed_in_last_department'].'<br/>';
				if ($oi['quantity'] > $order_items2['completed_in_last_department'] ) return false;
			}
		}

		return true;
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
		
		$dispatchable_item = $this['completed'] - $this['forwarded'] - $this['dispatched'];
		
		if(!$dispatchable_item){
			$page->add('View_Warning')->set(" no dispatchable quantity found");
			return;
		}
        
		//total item to forward =)
		$qty_to_send = $dispatchable_item;

		$form = $page->add('Form');
		$form->addField('line','total_qty_to_dispatch')->set($qty_to_send)->setAttr('readonly',true);
		$form->addField('Number','qty_to_dispatch')->validate('required')->set($qty_to_send);
        $warehouse_f=$form->addField('DropDown','warehouse')->validate('required')->addClass('multiselect-full-width');
        $warehouse=$page->add('xepan\commerce\Model_Store_Warehouse');
    	$warehouse_f->setModel($warehouse);

        $form->addSubmit('Send To Dispatch');

    	if($form->isSubmitted()){
    		if($form['qty_to_dispatch'] > $form['total_qty_to_dispatch'])
    			$form->displayError('qty_to_dispatch','Qty cannot be dispatch more than '.$form['total_qty_to_dispatch']);

	        $jd = $this->createJobcardDetail("Dispatched",$form['qty_to_dispatch']);

			$this->sendToDispatch($form['qty_to_dispatch'],$form['warehouse'],$jd);
			return $form->js()->univ()->successMessage('Send To Dispatch Successfully')->closeDialog();
        }
    }	
        
    function sendToDispatch($qty,$warehouse_id,$jobcard_detail){
    	
    	$warehouse = $this->add('xepan\commerce\Model_Store_Warehouse')->load($warehouse_id);
		$transaction = $warehouse->newTransaction($this['order_no'],$this->id,$this['customer_id'],'Store_DispatchRequest');
		$transaction->addItem($this['order_item_id'],$qty,$jobcard_detail->id,null,null,'ToReceived');

		if($this['status'] != "Completed")
			$this['status']='Processing';

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

    	if(!count($orderItem->getProductionDepartment()))
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
		$search_string .=" ". $this['customer_name'];
		$search_string .=" ". $this['order_item_name'];
		$search_string .=" ". $this['order_item_quantity'];
		$search_string .=" ". $this['type'];

		$this['search_string'] = $search_string;
	}
    
}
