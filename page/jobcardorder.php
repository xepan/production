<?php

namespace xepan\production;

/**
* 
*/
class page_jobcardorder extends \xepan\base\Page{
	public $title="Jobcard Orders";
	function init(){
		parent::init();
		$g=$this->add('xepan\hr\Grid',null,null,['view/jobcard/ordergrid']);

		$order=$this->add('xepan\commerce\Model_SalesOrder');
		$g->setModel($order);
		$g->addPaginator(50);
		$g->addQuickSearch(['document_no','contact']);
		$this->vp = $this->add('VirtualPage')->set(function($p){
			// throw new \Exception("Error Processing Request", 1);
			
			$order_id = $p->api->stickyGET('sales_order_clicked');
			$jobcard_m=$p->add('xepan\production\Model_Jobcard')->addCondition('order_no',$order_id);
				
			$crud=$p->add('xepan\hr\CRUD',null,null,['view/grid/jobcard']);
			$crud->setModel($jobcard_m);
		});

		$g->addMethod('format_document_no',function($g,$field){
			$g->current_row_html[$field] = '<a href="#na" onclick="javascript:'.$this->js()->univ()->frameURL('Sale Order Detail', $this->api->url($this->vp->getURL(),array('sales_order_clicked'=>$g->model->id))).'">'. $g->current_row[$field]. "</a>";
		});
		$g->addFormatter('document_no','document_no');
	}
}
