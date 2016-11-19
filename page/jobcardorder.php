<?php

namespace xepan\production;

class page_jobcardorder extends \xepan\base\Page{
	public $title="Jobcard Orders";
	function init(){
		parent::init();
	
		$jobcard_model = $this->add('xepan\production\Model_Jobcard');
		$jobcard_model->setOrder('id','desc');
		
		if($order_id = $this->app->stickyGET('order_id')){
			$jobcard_model->addCondition('order_document_id',$order_id);
		} 
		
		$form = $this->add('Form',null,null,['form/stacked']);
		$contact_field = $form->addField('xepan\base\Basic','contact');
		$contact_field->setModel('xepan\base\Contact');

		
		$order_field = $form->addField('xepan\base\Basic','approved_orders');
							// ->setEmptyText('Please select an order');


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
		$order_m->setOrder('created_at','desc');
		$order_m->title_field = 'name_with_info';

		if($contact_id = $this->app->stickyGET('contact_id')){			
			$order_m->addCondition('contact_id',$contact_id);
		}
		
		$order_field->setModel($order_m);
		
		$contact_field->other_field->js('change',$order_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$order_field->name]),'contact_id'=>$contact_field->other_field->js()->val()]));

		$form->addSubmit('Apply Filter')->addClass('btn btn-primary');

		$grid = $this->add('xepan\hr\Grid',null,null,['view/grid/jobcard']);
		$grid->setModel($jobcard_model);

		if($form->isSubmitted()){
			$js=[
					$grid->js()->reload(['order_id'=>$form['approved_orders']])
			];

			$form->js(null,$js)->execute();	
		}
		
		$grid->addPaginator(50);
		// $grid->addQuickSearch(['','contact']);
		$this->vp = $this->add('VirtualPage')->set(function($p){
			$order_id = $p->api->stickyGET('sales_order_clicked');
			$jobcard_m = $p->add('xepan\production\Model_Jobcard')->addCondition('order_no',$order_id);
			$crud = $p->add('xepan\hr\CRUD',['allow_add'=>false],null,['view/grid/jobcard']);
			$crud->setModel($jobcard_m);
		});

		$grid->js('click')->_selector('.do-view-frame')->univ()->frameURL('Jobcard Order Details',[$this->api->url('xepan_commerce_customerdetail'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-contact-id]')->data('contact-id')]);
	}
}