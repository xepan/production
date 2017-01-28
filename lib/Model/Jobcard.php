<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\hr\Model_Document{
	public $status=['ToReceived','Received','Processing','Forwarded','Completed','Cancelled','Rejected'];

	public $actions=[
				'ToReceived'=>['view','edit','delete','receive','reject'],
				'Received'=>['view','edit','delete','processing','assign','complete','cancel','print'],
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
		$job_j->hasOne('xepan\hr\Employee','assign_to_id')->sortable(true); 

		$job_j->addField('due_date')->type('datetime')->sortable(true);

		$job_j->hasMany('xepan\production\Jobcard_Detail','jobcard_id');
		$job_j->hasMany('xepan\production\Jobcard','parent_jobcard_id',null,'SubJobcard');
		$job_j->hasMany('xepan\commerce\Store_Transaction','jobcard_id');

		$this->addCondition('type','Jobcard');
		$this->addHook('beforeDelete',[$this,'checkExistingRelatedTransaction']);

		$this->addExpression('order_no')->set(function($m,$q){
			$sales_order =  $m->add('xepan/commerce/Model_SalesOrder',['table_alias'=>'order_no']);
			$order_detail_j = $sales_order->join('qsp_detail.qsp_master_id');
			$order_detail_j->addField('details_id','id');
			$sales_order->addCondition('details_id',$m->getElement('order_item_id'));
			return $sales_order->fieldQuery('document_no');
		})->sortable(true);

		$this->addExpression('qsp_item_narration')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('narration');
			
		});

		$this->addExpression('order_document_id')->set(function($m,$q){
			$sales_order =  $m->add('xepan/commerce/Model_SalesOrder',['table_alias'=>'order_no']);
			$order_detail_j = $sales_order->join('qsp_detail.qsp_master_id');
			$order_detail_j->addField('details_id','id');
			$sales_order->addCondition('details_id',$m->getElement('order_item_id'));
			return $sales_order->fieldQuery('id');
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

		$this->addExpression('order_item_unit_id')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('qty_unit_id');
		})->sortable(true);
		
		$this->addExpression('order_item_unit')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('qty_unit');
		})->sortable(true);

		$this->addExpression('item_base_unit_id')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('item_qty_unit_id');
		})->sortable(true);

		$this->addExpression('item_base_unit')->set(function($m,$q){
			return $m->refSQL('order_item_id')->fieldQuery('item_qty_unit');
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

	// function print(){
	// 	// $this->api->redirect($this->api->url('xepan_commerce_printqsp',['document_id'=>$this->id]));
	// 	$js=$this->app->js()->univ()->newWindow($this->app->url('xepan_production_test',['jobcard_id'=>$this->id]),'Print'.$this['type']);
	// 	$this->app->js(null,$js)->univ()->execute();
	// }

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
			// $jobcard['outsourceparty_id'] = $this['outsource_party_id'];
			$new_jobcard = $jobcard->save();

			//Create New Jobcard Detail /Transactin Row Entry
			$new_jobcard->createJobcardDetail("ToReceived",$oi['quantity'],null,$unit_conversion = true);
	}

	function generatePDF($action ='return'){

		if(!in_array($action, ['return','dump']))
			throw $this->exception('Please provide action as result or dump');

		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('xEpan ERP');
		$pdf->SetTitle($this['type']. ' '. $this['id']);
		$pdf->SetSubject($this['type']. ' '. $this['id']);
		$pdf->SetKeywords($this['type']. ' '. $this['id']);

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		// set font
		$pdf->SetFont('dejavusans', '', 10);
		// add a page
		$pdf->AddPage();
		
		$config_m = $this->add('xepan\base\Model_ConfigJsonModel',
		[
			'fields'=>[
						'subject'=>'Line',
						'body'=>'xepan\base\RichText',
						'master'=>'xepan\base\RichText',

						],
				'config_key'=>'PRODUCTION_JOBCARD_SYSTEM_CONFIG',
				'application'=>'production'
		]);
		$config_m->tryLoadAny();
		

		$jobcard_config = $config_m['master'];
		
		if(!$config_m->loaded()){
			$jobcard_config = file_get_contents(realpath("../vendor/xepan/production/templates/default/jobcard-received-print.html"));
		}
		$jobcard_layout = $this->add('GiTemplate');
		$jobcard_layout->loadTemplateFromString($jobcard_config);	
		

		$new = $this->add('xepan\production\Model_Jobcard');
		$new->load($this->id);
		$view = $this->app->add('View',null,null,$jobcard_layout);
		$view->setModel($new);

		$order_item_detail=$this->add('xepan\commerce\Model_QSP_Detail');
		$order_item_detail->tryLoadBy('id',$this['order_item_id']);

		$array = json_decode($order_item_detail['extra_info']?:"[]",true);
		$cf_html = "";

		foreach ($array as $department_id => &$details) {
			$department_name = $details['department_name'];
			$cf_list = $view->add('CompleteLister',null,'extra_info',['view\qsp\extrainfo']);
			$cf_list->template->trySet('department_name',$department_name);
			unset($details['department_name']);
			
			$cf_list->setSource($details);

			$cf_html  .= $cf_list->getHtml();	
		}		

		$view->template->trySetHtml('extra_info',$cf_html);

		
		// $view = $this->add('View')->set("Production Jobcard Received View TODO");
		$html = $view->getHTML();
		// echo "<pre>";
		// print_r($html);
		// echo "</pre>";
		// output the HTML content
		$pdf->writeHTML($html, false, false, true, false, '');
		// set default form properties
		$pdf->setFormDefaultProp(array('lineWidth'=>1, 'borderStyle'=>'solid', 'fillColor'=>array(255, 255, 200), 'strokeColor'=>array(255, 128, 128)));
		// reset pointer to the last page
		$pdf->lastPage();
		//Close and output PDF document
		switch ($action) {
			case 'return':
				return $pdf->Output(null, 'S');
				break;
			case 'dump':
				return $pdf->Output(null, 'I');
				exit;
			break;
		}
	}

	function page_receive($page){
		$dep=$this->add('xepan\hr\Model_Department')->load($this['department_id']);
		$grid_jobcard_row = $page->add('xepan\hr\Grid',['action_page'=>'xepan_production_jobcard'],null,['view/jobcard/transactionrow']);

		$form = $page->add('Form');
		$form->add('View')->setElement('H4')->set('You Can Assign It To An Employee')->addClass('project-box-header green-bg well-sm')->setstyle('color','white');
		$employee = $form->addField('DropDown','assign_to_employee')->setEmptyText('Please select an employee');
		$employee->setModel('xepan\hr\Employee');
		$jobcard_field = $form->addField('hidden','jobcard_row');
		$grid_jobcard_row->addSelectable($jobcard_field);

		$jobcard = $this->add('xepan\production\Model_Jobcard_Detail');
		$jobcard->addCondition('jobcard_id',$this->id);
		$jobcard->addCondition('status','ToReceived');

		$grid_jobcard_row->setModel($jobcard);

		//$grid_jobcard_row = $page->add('Grid');

		
		if($dep['is_outsourced']){
			$notify_to = $form->addField('Checkbox','notify_via_email');
			$outsource_partyfield=$form->addField('DropDown','outsource_party');
			$outsource_partyfield->setEmptyText('Please Select');
			$outsource_partyfield->setModel('xepan\production\OutsourceParty');

			$email_to_field = $form->addField('line','email_to');

			if($this->app->stickyGET('outsource_id')){
				$outsource_m = $this->add('xepan\production\Model_OutsourceParty')->load($_GET['outsource_id']);
				$email_to_field->set(str_replace("<br/>", ", ",$outsource_m['emails_str']));
			}

			$config_m = $this->add('xepan\base\Model_ConfigJsonModel',
			[
				'fields'=>[
							'subject'=>'Line',
							'body'=>'xepan\base\RichText',
							'master'=>'xepan\base\RichText',
							],
					'config_key'=>'PRODUCTION_JOBCARD_SYSTEM_CONFIG',
					'application'=>'production'
			]);
			$config_m->add('xepan\hr\Controller_ACL');
			$config_m->tryLoadAny();

			$email_subject = $config_m['subject'];
			$email_body = $config_m['body'];
			// $config_model=$this->add('xepan\base\Model_Epan_Configuration');
			// $config_model->addCondition('application','crm');
			// $email_subject=$config_model->getConfig('SUPPORT_EMAIL_CLOSED_TICKET_SUBJECT');
			// $email_body=$config_model->getConfig('SUPPORT_EMAIL_CLOSED_TICKET_BODY');
			
			$subject=$this->add('GiTemplate');
			$subject->loadTemplateFromString($email_subject);

			$temp=$this->add('GiTemplate');
			$temp->loadTemplateFromString($email_body);
			$body = $this->add('View',null,null,$temp);
			$form->addField('xepan\base\RichText','extra_notes');
			$body->template->trySetHTML('extra_notes',$form['extra_notes']);

			$form->addField('line','subject')->set($subject->render());
			$form->addField('xepan\base\RichText','message')->set($body->getHTML());

			$notify_to->js(true)->univ()->bindConditionalShow([
				''=>[],
				'*'=>['email_to','subject','message'],
			],'div.atk-form-row');

			$outsource_partyfield->js('change',$email_to_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$email_to_field->name]),'outsource_id'=>$outsource_partyfield->js()->val()]));
		}
			
		$form->addSubmit('Receive Jobcard');
		if($form->isSubmitted()){
			if($form['notify_via_email']){
				if(!$form['outsource_party'])
					$form->displayError('outsource_party','OutsourceParty is Required');
				if(!$form['email_to'])
					$form->displayError('email_to','Email To is Required');
				if(!$form['subject'])
					$form->displayError('message','Subject is Required');
				if(!$form['message'])
					$form->displayError('message','Message is Required');

				$communication = $this->add('xepan\communication\Model_Communication_Abstract_Email');					
				$communication->getElement('status')->defaultValue('Draft');
				$communication['direction']='Out';

				$email_setting = $this->add('xepan\communication\Model_Communication_EmailSetting')->tryLoadAny();
				$communication->setfrom($email_setting['from_email'],$email_setting['from_name']);
				$communication->addCondition('communication_type','Email');
					
				$to_emails=explode(',', trim($form['email_to']));
				foreach ($to_emails as $to_mail) {
					$communication->addTo($to_mail);
				}
				$communication->setSubject($form['subject']);
				$communication->setBody($form['message']);
				$communication['to_id']=$this['outsourceparty_id'];
				$communication->save();

				// Attach Jobcard Details
				$file =	$this->add('xepan/filestore/Model_File',array('policy_add_new_type'=>true,'import_mode'=>'string','import_source'=>$this->generatePDF('return')));
				$file['filestore_volume_id'] = $file->getAvailableVolumeID();
				$file['original_filename'] =  strtolower($this['type']).'_'.$this['document_no_number'].'_'.$this->id.'.pdf';
				$file->save();

				$communication->addAttachment($file->id);
				$communication->findContact('to');
				$communication->send($email_setting);
			}
			
			//Saving Assign To Employee Id In Jobcard
			if($form['assign_to_employee']){
				
				$this['assign_to_id'] = $form['assign_to_employee'];
				$this->save();

				// Assigning Activity Message
				$model_emp = $this->add('xepan\hr\Model_Employee');
				$model_emp->load($this['assign_to_id']);
				if($model_emp->loaded())
					$emp_name = $model_emp['name'];

				$assignjobcard_notify_msg = ['title'=>'New Jobcard','message'=>" JobCard No . " . $this['id'] ."' Assigned To You' by '". $this->app->employee['name'] ."' ",'type'=>'info','sticky'=>true,'desktop'=>true];
				$this->app->employee
		            ->addActivity("Jobcard No. '".$this['id']."' assigned to '". $emp_name ."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,"xepan_production_jobcarddetail&document_id=".$this->id."")
		            ->notifyTo([$this['assign_to_id']],$assignjobcard_notify_msg);
			}
			//doing jobcard detail/row received
			foreach (json_decode($form['jobcard_row']) as $transaction_row_id) {
				$jobcard_row_model = $this->add('xepan\production\Model_Jobcard_Detail')->load($transaction_row_id);
				$jobcard_row_model->received();
			}
			$this->app->employee
			->addActivity("Jobcard No : ".$this->id." Successfully Received By Department : '".$this['department']."'", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/,null,null,"xepan_production_jobcarddetail&document_id=".$this->id."")
			->notifyWhoCan('processing,complete,cancel','Received');
			// calling jobcard receive function 
			if($this->receive($form['outsource_party']))
				return $form->js()->univ()->successMessage('Received Successfully')->closeDialog();
			else
				return $form->js()->univ()->errorMessage('Not Received');
		}
	}

	function receive($outsource_party=null){
		
		
		//Mark Complete the Previous Department Jobcard if exist
		$this->add('xepan\commerce\Model_SalesOrder')
			->addCondition('document_no',$this['order_no'])
			->tryLoadAny()
			->inprogress();

		if($this['parent_jobcard_id'] and $this->parentJobcard()->checkAllDetailComplete()){
			$this->parentJobcard()->complete();
		}

	    if($outsource_party){
		$this['outsourceparty_id']=$outsource_party;
	    }        
		$this['status']='Received';
		$this->saveAndUnload();
		return true;
	}

	function parentJobcard(){
		if(!$this->loaded())
			throw new \Exception("Model Must Loaded", 1);
		if(!$this['parent_jobcard_id'])
			throw new \Exception("Parent Jobcard not found ", 1);

		return $this->add('xepan\production\Model_Jobcard')->load($this['parent_jobcard_id']);		

		// return $this->refSQL('parent_jobcard_id');
			
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
		$department_m = $this->add('xepan\hr\Model_Department');
		$department_m->load($this['department_id']);

		$jobcard = $this->add('xepan\production\Model_Jobcard')
					->addCondition('status','Processing')
					->addCondition('department_id',$department_m->id);

		$count = $jobcard->count()->getOne();

		if(
			$department_m['simultaneous_no_process_allowed'] && 
			$department_m['simultaneous_no_process_allowed'] > 0 && 
			$department_m['simultaneous_no_process_allowed'] == $count
			)
			throw $this->exception('Already "'.$count.'" No. Of Jobcards In Processing In "'.$department_m['name'].'" Department, so Jobcard No. "'.$this['id'].'" could not be processed');

		$this['status']='Processing';
		$this->save();
	}

	function page_assign($page){
		$page->add('View')->setElement('H4')->set('Please select an employee to assign ' .$this['id'] .' no. jobcard')->addClass('project-box-header green-bg well-sm')->setstyle('color','white');
		$form = $page->add('Form');
		$employee = $form->addField('DropDown','assign_to_employee')->setEmptyText('Please select');
		$employee->setModel('xepan\hr\Employee');
		$form->addSubmit('Assign')->addClass('btn btn-primary');
		if($form->isSubmitted()){
			if($this->loaded()){
				$this['assign_to_id'] = $form['assign_to_employee'];
				$this->save();
			}
			$this->app->page_action_result = $form->js(null,$form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Jobcard assigned successfully to Employee :' .$this['assign_to']);
			
			//Activity Message
			$model_emp = $this->add('xepan\hr\Model_Employee');
			$model_emp->load($this['assign_to_id']);
			if($model_emp->loaded())
				$emp_name = $model_emp['name'];

			$assignjobcard_notify_msg = ['title'=>'New Jobcard','message'=>" JobCard No . " . $this['id'] ."' Assigned To You' by '". $this->app->employee['name'] ."' ",'type'=>'info','sticky'=>true,'desktop'=>true];
			$this->app->employee
	            ->addActivity("Jobcard No. '".$this['id']."' assigned to '". $emp_name ."'",null, $this['assign_to_id'] /*Related Contact ID*/,null,null,"xepan_production_jobcarddetail&document_id=".$this->id."")
	            ->notifyTo([$this['assign_to_id']],$assignjobcard_notify_msg); 
			
		}
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
		$form->addSubmit('Forward to Next Department : '.$next_dept['name']);

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
            ->addActivity("Jobcard ".$this['id']. " forwarded", $this->id /* Related Document ID*/,$order_item['customer'] /*Related Contact ID*/)
            ->notifyWhoCan('reject,convert,open etc Actions perform on','Converted Any Status');

        $this->unload();
        return true;
	}

	function createJobcardDetail($status,$qty,$parent_detail_id=null,$check_unit_conversion = false){
		if(!$this->loaded())
			throw new \Exception("jobcard must loaded for creating it's detail");

		// convert quantity to base quantity
		if($check_unit_conversion)
			$qty = $this->app->getConvertedQty($this['item_base_unit_id'],$this['order_item_unit_id'],$qty);
		
		$detail = $this->add('xepan\production\Model_Jobcard_Detail');
		$detail['jobcard_id'] = $this->id;
		$detail['quantity'] = $qty;
		$detail['parent_detail_id'] = $parent_detail_id;
		$detail['status'] = $status?:"ToReceived";
		return $detail->save();
	}

	function page_complete($page){


		$consum_qty = $this->app->stickyGET('consumption_qty');
		$qty_to_complete = $this['processing'];

		$form = $page->add('Form');
		$template = $this->add('GiTemplate');
        $template->loadTemplate('view/form/jobcard-complete-form');
		
		$dept_assos = $page->add('xepan\commerce\Model_Item_Department_Association')
								->addCondition('department_id',$this['department_id'])
								->addCondition('item_id',$this['item_id'])
								->tryLoadAny();
														
		// if(!$dept_assos->loaded()){
		// 	$page->add('View_Error')->set('Please define item\'s association with this department first');
		// 	return;
		// }

		$model_item_consumption = $this->add('xepan\commerce\Model_Item_Department_Consumption')
											->addCondition('item_department_association_id',$dept_assos->id)->tryLoadAny();

        $template->trySetHTML('total_qty_to_complete','{$total_qty_to_complete}');
        $template->trySetHTML('qty_to_complete','{$qty_to_complete}');
        $template->trySetHTML('warehouse','{$warehouse}');
		
		foreach ($model_item_consumption as $m) {
		// throw new \Exception($m['quantity'], 1);
			$item_template = $this->add('GiTemplate');
            $item_template->loadTemplate('view/form/jobcard-complete-items-row');
            $item_template->trySetHTML('item','{$item_'.$m->id.'}');
            $item_template->trySetHTML('qty','{$qty_'.$m->id.'}');
            $item_template->trySetHTML('extra_info','{$extra_info_'.$m->id.'}');
            $item_template->trySetHTML('view_extra_info','{$consumption_item_view_extra_info_'.$m->id.'}');
			$template->appendHTML('items',$item_template->render());
		}
		
		for ($m=1; $m < 6; $m++) { 
			$item_template = $this->add('GiTemplate');
            $item_template->loadTemplate('view/form/jobcard-complete-items-row');
            $item_template->trySetHTML('item','{$item_x_'.$m.'}');
            $item_template->trySetHTML('qty','{$qty_x_'.$m.'}');
            $item_template->trySetHTML('extra_info','{$extra_info_x_'.$m.'}');
            $item_template->trySetHTML('view_extra_info','{$view_extra_info_'.$m.'}');

			$template->appendHTML('items',$item_template->render());
		}
		
		$template->loadTemplateFromString($template->render());
        $form->setLayout($template);
		
		$form->addField('line','total_qty_to_complete')->setAttr('readonly','true')->set($qty_to_complete);
		$qty_to_com_field = $form->addField('Number','qty_to_complete')->set($consum_qty?:$qty_to_complete);
		$warehouse = $form->addField('xepan\base\DropDownNormal','warehouse')->setEmptyText('Please Select');
		$warehouse->setModel('xepan\commerce\Store_Warehouse');
		
		foreach ($model_item_consumption as $m) {
	      	$item_field = $form->addField('xepan\commerce\Form_Field_Item','item_'.$m->id)->set($m['composition_item_id']);
			$item_field->setModel('xepan\commerce\Item');
			$item_field->custom_field_element = 'extra_info_'.$m->id;
			$item_field->custom_field_btn_class = 'extra_info_'.$m->id;
			$item_field->is_mandatory = false;

			$form->layout->add('View',null,'consumption_item_view_extra_info_'.$m->id)->set('Extra Info')->addClass('btn btn-primary extra_info_'.$m->id );
			$extra_info = $form->addField('text','extra_info_'.$m->id);

			if($consum_qty){
				$set_qty = $m['quantity'] * $consum_qty;
			}else{
				$set_qty = $m['quantity'] * $form['qty_to_complete'];
			}

			$qty_field = $form->addField('line','qty_'.$m->id,'Quantity')->set($set_qty);
			$qty_to_com_field->js('change',$page->js()->reload(null,null,[
										$this->app->url(),
										'consumption_qty'=>$qty_to_com_field->js()->val()
								]));

		}

		for ($m=1; $m < 6; $m++) { 
			$item_field = $form->addField('xepan\commerce\Form_Field_Item','item_x_'.$m);
			$item_field->setModel('xepan\commerce\Item');
			$item_field->custom_field_element = 'extra_info_x_'.$m;
			$item_field->custom_field_btn_class = 'extra_info_x_'.$m;
			$item_field->is_mandatory = false;
			$extra_info = $form->addField('text','extra_info_x_'.$m);
			$qty_field = $form->addField('line','qty_x_'.$m,'Quantity');
			$form->layout->add('View',null,'view_extra_info_'.$m)->set('Extra Info')->addClass('btn btn-primary extra_info_x_'.$m );
		}

		
		$form->addSubmit('mark completed')->addClass('btn btn-primary');

		if($form->isSubmitted()){
			
			if($form['qty_to_complete'] > $form['total_qty_to_complete'])
				$form->displayError('qty_to_complete',"qty cannot be more than ".$form['total_qty_to_complete']);
			// create One New Transaction row of Completed in self jobcard
			$jd = $this->createJobcardDetail("Completed",$form['qty_to_complete']);
							
				foreach ($model_item_consumption as $m) {
					if($form['item_'.$m->id]){
						if(!$form['warehouse'])
							$form->displayError('warehouse',"Please Select Warehouse ");
					}


					if(!isset($this->warehouse)){
						$this->warehouse = $warehouse = $this->add('xepan\commerce\Model_Store_Warehouse')->tryLoadBy('id',$form['warehouse']);
						$this->transaction= $transaction = $warehouse->newTransaction($this['order_no'],$this->id,$warehouse->id,'Consumed',$this['department_id']);
					}else{
						$warehouse = $this->warehouse;
						$transaction = $this->transaction;
					}
					

					if($form['item_'.$m->id]){
						if(!$form['qty_'.$m->id]){
							$form->displayError('qty_'.$m->id,'Quantity Must not be Empty');
						}
					}
					$transaction->addItem($this['order_item_id'],$form['item_'.$m->id],$form['qty_'.$m->id],$jd->id,$form['extra_info_'.$m->id],'Consumed',null,null,false);

					$tr_row = $this->add('xepan\commerce\Model_Store_TransactionRow');
					$tr_row->addCondition('type',"Consumption_Booked");
					$tr_row->addCondition('qsp_detail_id',$this['order_item_id']);
					$tr_row->addCondition('department_id',$this['department_id']);
					$tr_row->addCondition('item_id',$form['item_'.$m->id]);
					$tr_row->tryLoadAny();
					if($tr_row->loaded()){
						
						if($form['qty_'.$m->id] >= $tr_row['quantity']){
							$tr_row->delete();
						}else{
							$available_qty = $tr_row['quantity'] - $form['qty_'.$m->id];
							$tr_row['quantity'] = $available_qty;
							$tr_row->save();
						}
					}
				}
				
				for ($m=1; $m < 6; $m++) {
					if($form['item_x_'.$m]){
						if(!$form['warehouse'])
							$form->displayError('warehouse',"Please Select Warehouse ");

						if(!isset($this->warehouse)){
							$this->warehouse = $warehouse = $this->add('xepan\commerce\Model_Store_Warehouse')->tryLoadBy('id',$form['warehouse']);
							$this->transaction = $transaction = $warehouse->newTransaction($this['order_no'],$this->id,$warehouse->id,'Consumed',$this['department_id']);
						}else{
							$warehouse = $this->warehouse;
							$transaction = $this->transaction;
						}

						$transaction->addItem($this['order_item_id'],$form['item_x_'.$m],$form['qty_x_'.$m],$jd->id,$form['extra_info_x_'.$m],'Consumed');

						$tr_row = $this->add('xepan\commerce\Model_Store_TransactionRow');
						$tr_row->addCondition('type',"Consumption_Booked");
						$tr_row->addCondition('qsp_detail_id',$this['order_item_id']);
						$tr_row->addCondition('department_id',$this['department_id']);
						$tr_row->addCondition('item_id',$form['item_x_'.$m]);
						$tr_row->tryLoadAny();
						if($tr_row->loaded()){
							if($form['qty_x_'.$m] >= $tr_row['quantity']){
								$tr_row->delete();
							}else{
								$available_qty = $tr_row['quantity'] - $form['qty_x_'.$m];
								$tr_row['quantity'] = $available_qty;
								$tr_row->save();
							}
						}
					}
				}
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

			$sale_order_model = $this->add('xepan\commerce\Model_SalesOrder')->addCondition('document_no',$this['order_no'])->tryLoadAny();
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
				if ($oi['quantity'] > $oi['total_dispacthed'] ) return false;
			}else{

				$io_json=json_decode($oi['extra_info'],true);
				
				if(!count($io_json)){
					return true;
				}

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
		$transaction = $warehouse->newTransaction($this['order_document_id'],$this->id,$this['customer_id'],'Store_DispatchRequest');
		$transaction->addItem($this['order_item_id'],$this['item_id'],$qty,$jobcard_detail->id,null,'ToReceived',null,null,false);

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
