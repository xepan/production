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
			$m->addItem(['OutsourceParty','icon'=>'fa fa-user'],'xepan_production_outsourceparties');
			
			$departments = $this->add('xepan\hr\Model_Department')->setOrder('production_level','asc');

			foreach ($departments as $department) {
				// $m->addItem($department['name'],'xepan_production_jobcard&department_id='.$department->id);
				$m->addItem(([$department['name'],'icon'=>'fa fa-empire
']),'xepan_production_jobcard&department_id='.$department->id);
			}


			$this->app->addHook('sales_order_approved',['xepan\production\Model_Jobcard','createFromOrder']);


		}
		
	}

	function generateInstaller(){
		// Clear DB
        $this->app->epan=$this->app->old_epan;
        $truncate_models = ['Jobcard_Detail','Jobcard','OutsourceParty'];
        foreach ($truncate_models as $t) {
            $m=$this->add('xepan\production\Model_'.$t);
            foreach ($m as $mt) {
                $mt->delete();
            }
        }
        
        $this->app->epan=$this->app->new_epan;
	}
}
