<?php

namespace xepan\production;

class page_jobcardorder extends \xepan\base\Page{
	public $title="Jobcard Orders";
	function init(){
		parent::init();
	
		$order=$this->add('xepan\commerce\Model_SalesOrder');
		$order->addCondition('status','Approved');

		if($order_id = $this->app->stickyGET('order_id')){
			$order->addCondition('id',$order_id);
		} 
		
		$form = $this->add('Form',null,null,['form/stacked']);
		$contact_field = $form->addField('autocomplete/Basic','contact');
		$contact_field->setModel('xepan\base\Contact');

		
		$order_field = $form->addField('DropDown','approved_orders')
							->setEmptyText('Please select an order');


		$order_m = 	$this->add('xepan\commerce\Model_SalesOrder');
		$order_m->addCondition('status','Approved');
		$order_m->title_field = 'document_no';

		if($contact_id = $this->app->stickyGET('contact_id')){			
			$order_m->addCondition('contact_id',$contact_id);
		}
			
		$order_field->setModel($order_m);
		
		$contact_field->other_field->js('change',$order_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$order_field->name]),'contact_id'=>$contact_field->other_field->js()->val()]));

		$form->addSubmit('Apply Filter')->addClass('btn btn-primary');

		$grid = $this->add('xepan\hr\Grid',null,null,['view/jobcard/ordergrid']);
		$grid->setModel($order);

		if($form->isSubmitted()){
			$js=[
					$grid->js()->reload(['order_id'=>$form['approved_orders']])
			];

			$form->js(null,$js)->execute();	
		}
		
		$grid->addPaginator(50);
		$grid->addQuickSearch(['document_no','contact']);
		$this->vp = $this->add('VirtualPage')->set(function($p){
			$order_id = $p->api->stickyGET('sales_order_clicked');
			$jobcard_m = $p->add('xepan\production\Model_Jobcard')->addCondition('order_no',$order_id);
				
			$crud = $p->add('xepan\hr\CRUD',['allow_add'=>false],null,['view/grid/jobcard']);
			$crud->setModel($jobcard_m);
		});

		$grid->addMethod('format_document_no',function($g,$field){
			$g->current_row_html[$field] = '<a href="#na" onclick="javascript:'.$this->js()->univ()->frameURL('Sale Order Detail', $this->api->url($this->vp->getURL(),array('sales_order_clicked'=>$g->model->id))).'">'. $g->current_row[$field]. "</a>";
		});

		$grid->addFormatter('document_no','document_no');
		$grid->js('click')->_selector('.do-view-frame')->univ()->frameURL('Jobcard Order Details',[$this->api->url('xepan_commerce_customerdetail'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-contact-id]')->data('contact-id')]);
	}
}