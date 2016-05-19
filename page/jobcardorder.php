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
	}
}