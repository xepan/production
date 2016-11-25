<?php

namespace xepan\production;

class Initiator extends \Controller_Addon {
	
	public $addon_name = 'xepan_production';

	function setup_admin(){
		
		$this->routePages('xepan_production');
		$this->addLocation(array('template'=>'templates'));
		$m = $this->app->top_menu->addMenu('Production');
		$m->addItem(['OutsourceParty','icon'=>'fa fa-user'],'xepan_production_outsourceparties');
		$m->addItem(['Jobcard Orders','icon'=>'fa fa-pencil-square-o'],'xepan_production_jobcardorder');
		$m->addItem(['Jobcard Order Timeline','icon'=>'fa fa-pencil-square-o'],'xepan_production_productionpipeline');
		
		$departments = $this->add('xepan\hr\Model_Department')->setOrder('production_level','asc');

		foreach ($departments as $department) {
			$m->addItem(([$department['name'],'icon'=>'fa fa-empire']),$this->app->url('xepan_production_jobcard',['department_id'=>$department->id]),['department_id']);
		}
		
		$m->addItem(['Reports','icon'=>'fa fa-cog fa-spin'],'xepan_production_reports_customer');
		$m->addItem(['Configuration','icon'=>'fa fa-cog fa-spin'],'xepan_production_config');

		$jobcard = $this->add('xepan\production\Model_Jobcard');
		$this->app->addHook('sales_order_approved',[$jobcard,'createFromOrder']);

		$jobcard_m=$this->add('xepan\production\Model_Jobcard');
		$this->app->addHook('qsp_detail_insert',[$jobcard_m,'createJobcard']);
		$jobcard_m->unload();
		$this->app->addHook('qsp_detail_qty_changed',[$jobcard_m,'updateJobcard']);
		$jobcard_m->unload();
		$this->app->addHook('qsp_detail_delete',[$jobcard_m,'deleteJobcard']);
		$jobcard_m->unload();

		$search_outsourceparty = $this->add('xepan\production\Model_OutsourceParty');
    	$this->app->addHook('quick_searched',[$search_outsourceparty,'quickSearch']);

    	$this->app->status_icon["xepan\production\Model_Jobcard"] = 
    							[
    								'All'=>' fa fa-globe',
    								'ToReceived'=>"fa fa-circle text-success",
    								'Received'=>'fa fa-circle text-danger',
    								'Processing'=>' fa fa-spinner',
    								'Forwarded'=>'fa-mail-forward',
    								'Completed'=>' fa fa-check text-success',
    								'Cancelled'=>' fa fa-ban text-danger',
    								'Rejected'=>' fa fa-times text-danger'
    							];
		
		return $this;
	}

	function setup_frontend(){
		$this->routePages('xepan_production');
		$this->addLocation(array('template'=>'templates'));
		return $this;
	}

	function resetDB(){
	}
}
