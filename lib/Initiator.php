<?php

namespace xepan\production;

class Initiator extends \Controller_Addon {
	
	public $addon_name = 'xepan_production';

	function init(){
		parent::init();
		
		$this->routePages('xepan_production');

		$m = $this->app->top_menu->addMenu('Production');
		$m->addItem('outsourceparty','xepan_production_outsourceparties');
		$m->addItem('Departments','xepan_production_jobcard');
		
		$this->addLocation(array('template'=>'templates'));
	}
}
