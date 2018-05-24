<?php

namespace xepan\production;

class page_outsourceparties extends \xepan\base\Page {
	public $title='OutsourceParties';

	function init(){
		parent::init();

		$os=$this->add('xepan\production\Model_OutsourceParty');
		$os->add('xepan\base\Controller_TopBarStatusFilter');

		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_production_outsourcepartiesdetails'],null,['view/outsourceparty/grid']);

		$crud->setModel($os);
		$crud->grid->addQuickSearch(['name']);
		//$crud->gird->addPaginator(10);

		$crud->add('xepan\base\Controller_Avatar');
		$crud->add('xepan\base\Controller_MultiDelete');

		if(!$crud->isEditing()){
			$crud->grid->js('click')->_selector('.do-view-outsourceparties')->univ()->frameURL('Outsource Parties Details',[$this->api->url('xepan_production_outsourcepartiesdetails'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		}

		
	}
}



