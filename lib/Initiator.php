<?php

namespace xepan\production;

class Initiator extends \Controller_Addon {
	
	public $addon_name = 'xepan_production';

	function init(){
		parent::init();
		
		$this->routePages('xepan_production');
		$this->addLocation(array('template'=>'templates'));


		if($this->app->is_admin){
			$m = $this->app->top_menu->addMenu('Production');
			$m->addItem('OutsourceParty','xepan_production_outsourceparties');
			
			$departments = $this->add('xepan\hr\Model_Department')->setOrder('production_level','asc');

			foreach ($departments as $department) {
				$m->addItem($department['name'],'xepan_production_jobcard&department_id='.$department->id);
			}


			$this->app->addHook('sales_order_approved',['xepan\production\Model_Jobcard','createFromOrder']);


		}
		
	}
}
