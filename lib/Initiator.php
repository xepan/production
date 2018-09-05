<?php

namespace xepan\production;

class Initiator extends \Controller_Addon {
	
	public $addon_name = 'xepan_production';

	function setup_admin(){
		
		$this->routePages('xepan_production');
		$this->addLocation(array('template'=>'templates'));

		if($this->app->inConfigurationMode)
	            $this->populateConfigurationMenus();
	        else
	            $this->populateApplicationMenus();

		


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
    	$this->app->addHook('collect_shortcuts',[$this,'collect_shortcuts']);

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

	function populateConfigurationMenus(){
		$m = $this->app->top_menu->addMenu('Production');
        $m->addItem(['Jobcard Receive Email layout','icon'=>'fa fa-cog'],$this->app->url('xepan_production_config'));
	}

	function populateApplicationMenus(){
		// if(!$this->app->getConfig('hidden_xepan_production',false)){
		// 	$m = $this->app->top_menu->addMenu('Production');
		// 	$m->addItem(['OutsourceParty','icon'=>'fa fa-user'],'xepan_production_outsourceparties');
		// 	$m->addItem(['Jobcard Orders','icon'=>'fa fa-pencil-square-o'],'xepan_production_jobcardorder');
		// 	$m->addItem(['Jobcard Order Timeline','icon'=>'fa fa-pencil-square-o'],'xepan_production_productionpipeline');
			
		// 	$departments = $this->add('xepan\hr\Model_Department')->setOrder('production_level','asc');

		// 	foreach ($departments as $department) {
		// 		$m->addItem(([$department['name'],'icon'=>'fa fa-empire']),$this->app->url('xepan_production_jobcard',['department_id'=>$department->id]),['department_id']);
		// 	}
			
		// 	$m->addItem(['Reports','icon'=>'fa fa-cog fa-spin'],'xepan_production_reports_customer');
		// 	$m->addItem(['Configuration','icon'=>'fa fa-cog fa-spin'],'xepan_production_config');
			
		// }
	}

		// used for custom menu
	function getTopApplicationMenu(){
		if($this->app->getConfig('hidden_xepan_production',false)){return [];}

		$arr =  ['Production'=>[
					[	'name'=>'OutsourceParty',
						'icon'=>'fa fa-user',
						'url'=>'xepan_production_outsourceparties'
					],
					[
						'name'=>'Jobcard Orders',
						'icon'=>'fa fa-pencil-square-o',
						'url'=>'xepan_production_jobcardorder'
					],
					[
						'name'=>'Jobcard Order Timeline',
						'icon'=>'fa fa-pencil-square-o',
						'url'=>'xepan_production_productionpipeline'
					]
				]
			];

		$departments = $this->add('xepan\hr\Model_Department')->setOrder('production_level','asc');

		foreach ($departments as $department) {
			$arr['Production'][]=['name'=>$department['name'],'icon'=>'fa fa-empire', 'url'=>'xepan_production_jobcard','url_param'=>['department_id'=>$department->id]];
		}
		$arr['Production'][]=['name'=>'Reports','icon'=>'fa fa-cog', 'url'=>'xepan_production_reports_customer'];

		return $arr;
	}

	function getConfigTopApplicationMenu(){
		if($this->app->getConfig('hidden_xepan_production',false)){return [];}
		
		return [
				'Production_Config'=>[
					[
						'name'=>'Jobcard Receive Email layout',
						'icon'=>'fa fa-cog',
						'url'=>'xepan_production_config'
					]
			    ]
			];

	} 

	function setup_frontend(){
		$this->routePages('xepan_production');
		$this->addLocation(array('template'=>'templates'));
		return $this;
	}

	function collect_shortcuts($app,&$shortcuts){
        $shortcuts[]=["title"=>"Outsourced parties","keywords"=>"out source job work","description"=>"Manage your out source vendors","normal_access"=>"Production -> OutsourceParty","url"=>$this->app->url('xepan_production_outsourceparties'),'mode'=>'frame'];
        $shortcuts[]=["title"=>"Jobcard Order Timeline","keywords"=>"timeline pipeline order status job","description"=>"TRack your order status with job cards","normal_access"=>"Production -> JObCard Order Timeline","url"=>$this->app->url('xepan_production_productionpipeline'),'mode'=>'frame'];

        $departments = $this->add('xepan\hr\Model_Department')->setOrder('production_level','asc');
		foreach ($departments as $department) {
	        $shortcuts[]=["title"=>"Jobcard for ".$department['name'],"keywords"=>"work status pending work jobwork jobcard for ".$department['name'],"description"=>"Jobs Status at ".$department['name'],"normal_access"=>"Production -> ".$department['name'],"url"=>$this->app->url('xepan_production_jobcard',['department_id'=>$department->id]),'mode'=>'frame'];
		}
        $shortcuts[]=["title"=>"Customer Production Report","keywords"=>"customer status orders all job cards works","description"=>"Track customers status for all works and job cards","normal_access"=>"Production -> Report","url"=>$this->app->url('xepan_production_reports_customer'),'mode'=>'frame'];
        $shortcuts[]=["title"=>"JobCard received email content","keywords"=>"jobcard received email content","description"=>"Job Card Received Email content","normal_access"=>"Production -> Configuration","url"=>$this->app->url('xepan_production_config'),'mode'=>'frame'];

    }

	function resetDB(){
	}

	function exportEntities($app,&$array){
        $array['OutsourceParty'] = ['caption'=>'OutsourceParty','type'=>'xepan\base\Basic','model'=>'xepan\production\Model_OutsourceParty'];
        $array['Jobcard'] = ['caption'=>'Jobcard','type'=>'xepan\base\Basic','model'=>'xepan\production\Model_Jobcard'];
        $array['Jobcard'] = ['caption'=>'Jobcard','type'=>'xepan\base\Basic','model'=>'xepan\production\Model_PRODUCTION_JOBCARD_SYSTEM_CONFIG'];
        $array['MaterialRequestSend'] = ['caption'=>'MaterialRequestSend','type'=>'xepan\base\Basic','model'=>'xepan\production\Model_MaterialRequestSend'];
        $array['MaterialRequestDispatch'] = ['caption'=>'MaterialRequestDispatch','type'=>'xepan\base\Basic','model'=>'xepan\production\Model_MaterialRequestDispatch'];

    }
}
