<?php

namespace xepan\production;

class page_outsourceparties extends \Page {
	public $title='OutsourceParties';

	function init(){
		parent::init();

		$os=$this->add('xepan\production\Model_OutsourceParty');

		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_production_profile'],null,['view/outsourceparty/grid']);

		$crud->setModel($os);
		$crud->grid->addQuickSearch(['name']);
		//$crud->gird->addPaginator(10);

		
	}
}



