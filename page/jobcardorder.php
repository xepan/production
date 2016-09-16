<?php

namespace xepan\production;

/**
* 
*/
class page_jobcardorder extends \xepan\base\Page{
	public $title="Jobcard Orders";
	function init(){
		parent::init();
		
		$order=$this->add('xepan\commerce\Model_SalesOrder');
		$order->addCondition('status','Approved');
		$order->title_field='document_no';

		$f=$this->add('Form',null,null,['form/stacked']);
		$contact_field = $f->addField('autocomplete/Basic','contact')->setModel('xepan\base\Contact');
		$f->addField('DropDown','approved_orders')->setEmptyText('All')->setModel($order);
		$f->addSubmit('Apply Filter')->addClass('btn btn-primary');

		$g=$this->add('xepan\hr\Grid',null,null,['view/jobcard/ordergrid']);

		if($f->isSubmitted()){
			return $g->js()->reload(['contact_id'=>$f['contact']?:null])->execute();
		}


		$g->setModel($order);
		$g->addPaginator(50);
		$g->addQuickSearch(['document_no','contact']);
		$this->vp = $this->add('VirtualPage')->set(function($p){
			$order_id = $p->api->stickyGET('sales_order_clicked');
			$jobcard_m=$p->add('xepan\production\Model_Jobcard')->addCondition('order_no',$order_id);
				
			$crud=$p->add('xepan\hr\CRUD',['allow_add'=>false],null,['view/grid/jobcard']);
			$crud->setModel($jobcard_m);
		});


		$g->addMethod('format_document_no',function($g,$field){
			$g->current_row_html[$field] = '<a href="#na" onclick="javascript:'.$this->js()->univ()->frameURL('Sale Order Detail', $this->api->url($this->vp->getURL(),array('sales_order_clicked'=>$g->model->id))).'">'. $g->current_row[$field]. "</a>";
		});
		$g->addFormatter('document_no','document_no');
		$g->js('click')->_selector('.do-view-frame')->univ()->frameURL('Jobcard Order Details',[$this->api->url('xepan_commerce_customerdetail'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-contact-id]')->data('contact-id')]);
	}
}
