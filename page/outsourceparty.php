<?php

namespace xepan\production;

class page_outsourceparty extends \Page {
	public $title='OutsourceParty';

	function init(){
		parent::init();

		$os=$this->add('xepan\production\Model_OutsourceParty');
		
		
		$crud=$this->add('xepan\hr\CRUD',
						[
							'action_page'=>'xepan_production_outsourceprofile',
							'grid_options'=>[
											'defaultTemplate'=>['grid/outsourceparty-grid']
											]
						]);

		$crud->setModel($os);
		$crud->grid->addQuickSearch(['name']);
		
		
	}
}
