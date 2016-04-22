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

			//Order Item Modification related Jobcard
			$this->app->addHook('qsp_detail_insert',['xepan\production\Model_Jobcard','createJobcard']);
			$this->app->addHook('qsp_detail_qty_changed',['xepan\production\Model_Jobcard','updateJobcard']);
			$this->app->addHook('qsp_detail_delete',['xepan\production\Model_Jobcard','deleteJobcard']);


		}
		
	}

	function resetDB(){
		// Clear DB
		if(!isset($this->app->old_epan)) $this->app->old_epan = $this->app->epan;
        if(!isset($this->app->new_epan)) $this->app->new_epan = $this->app->epan;
                
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
