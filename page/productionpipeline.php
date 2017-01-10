<?php

namespace xepan\production;

class page_productionpipeline extends \xepan\base\Page{
	public $title="Order Item Production TimeLine";

	function page_index(){
		// parent::init(); 
		$selected_order_id = $this->api->stickyGET('order_id');
		$selected_customer_id = $this->api->stickyGET('customer_id');

		$form = $this->add('Form');
		$customer_field = $form->addField('xepan\base\Basic','customer');
		$customer_field->setModel('xepan\commerce\Customer');
		
		$order_field = $form->addField('xepan\base\Basic','approved_orders','Sale Order');
		$order_m = 	$this->add('xepan\commerce\Model_SalesOrder');
		$order_m->addCondition('status',['Approved','InProgress','Completed','Canceled','OnlineUnpaid','Redesign']);
		$order_m->addExpression('name_with_info')->set(function($m,$q){
			return $q->expr('CONCAT([0]," - ",[1]," - ",[2]," - ",[3])',
								[
									$m->getElement('document_no'),
									$m->refSQL('contact_id')->fieldQuery('organization'),
									$m->getElement('status'),
									$m->getElement('created_at')
								]);
		});

		if($selected_customer_id){
			$order_m->addCondition('contact_id',$selected_customer_id);
		}
		$order_m->setOrder('created_at','desc');
		$order_m->title_field = 'name_with_info';
		$order_field->setModel($order_m);
		
		// change auto complete
		$order_field->send_other_fields = [$customer_field];
		if($customer_id = $_GET['o_'.$customer_field->name]){
			$order_field->getModel()->addCondition('contact_id',$customer_id)->setOrder('id','desc');
		}

		$form->addSubmit('Submit')->addClass('btn btn-primary');

		$wrapper_view = $this->add('View');

		if($selected_order_id or $selected_customer_id){

			$sale_order = $wrapper_view->add('xepan\commerce\Model_SalesOrder');
			$sale_order->setOrder('document_no','desc');
			if($selected_order_id)
				$sale_order->addCondition('id',$selected_order_id);
			if($selected_customer_id)
				$sale_order->addCondition('contact_id',$selected_customer_id);

			$order_pipeline = $wrapper_view->add('xepan\production\View_OrderPipeline');
			$order_pipeline->setModel($sale_order);
			$order_pipeline->addPaginator($ipp=10);
			
			$order_pipeline->js('click')->_selector('.do-view-jobcard-details')
					->univ()->frameURL('Jobcard Details',
					 	[
					 	$this->api->url('xepan_production_jobcarddetail'),
					 	'document_id'=>$this->js()->_selectorThis()->data('jobcard-id')
					 	]);
			// $order_items = $this->add('xepan\commerce\Model_QSP_Detail')
			// 				->addCondition('qsp_master_id',$_GET['order_id']);
			// $grid = $wrapper_view->add('Grid');
			// $grid->setModel($order_items,['item','quantity']);
			// $grid->addColumn('expander','timeline');

		}else{
			$wrapper_view->set('no record found');
		}

		if($form->isSubmitted()){
			$js=[
				$wrapper_view->js()->reload(['order_id'=>$form['approved_orders'],'customer_id'=>$form['customer']])
			];
			$form->js(null,$js)->execute();
		}
	}

	function page_timeline(){
		$qsp_detail_id = $_GET['qsp_detail_id'];
		if(!$qsp_detail_id){
			$this->add('View_Error')->set('no record found');
			return;
		}

		$order_detail = $this->add('xepan\commerce\Model_QSP_Detail')->load($qsp_detail_id);
		// $production_phases = $order_detail->getProductionDepartment();


		$array = json_decode($order_detail['extra_info']?:"[]",true);

		$job_pipeline = $this->add('xepan\production\View_JobcardPipeline',['order_detail_id'=>$qsp_detail_id]);
		unset($array[0]);
		$job_pipeline->setSource($array);

	}
}