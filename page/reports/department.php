<?php

namespace xepan\production;

class page_reports_department extends \xepan\production\page_reports_reportsidebar{
	public $title = 'Department Report';

	function init(){
		parent::init();

		$from_date = $this->app->stickyGET('to_date');
		$to_date = $this->app->stickyGET('from_date');
		$department_id = $this->app->stickyGET('department_id');
		$jobcard_status = $this->app->stickyGET('jobcard_status');
		$order = $this->app->stickyGET('order');

		$status_array = ['total_jobcards'=>'Total Jobcards',
						 'ToReceived'=>'ToReceived',
						 'Received'=>'Received',
						 'Processing'=>'Processing',
						 'Forwarded'=>'Forwarded',
						 'Completed'=>'Completed',
						 'Cancelled'=>'Cancelled',
						 'Rejected'=>'Rejected'
						];

		$toggle_button = $this->add('Button',null,'toggle')->set('Show/Hide form')->addClass('btn btn-primary btn-sm');
		$form = $this->add('xepan\production\Reports_FilterForm',['extra_fields'=>true,'status_array'=>$status_array],'filterform');
		$this->js(true,$form->js()->hide());
		$toggle_button->js('click',$form->js()->toggle());
		
		$jobcard_m = $this->add('xepan\production\Model_Reports_Jobcard',['from_date'=>$from_date,'to_date'=>$to_date,'department_id'=>$department_id]);

		if($jobcard_status)
			$jobcard_m->setOrder($jobcard_status,$order);

		$grid = $this->add('xepan\hr\Grid',null,'view',['reports\productiondepartment']);
		$grid->setModel($jobcard_m);	
		$grid->addPaginator(30);
		$grid->addQuickSearch(['name']);
		
		$grid->js('click')->_selector('.xepan-production-report')->univ()->frameURL('Jobcards',[$this->api->url('xepan_production_jobcard'),'status'=>$this->js()->_selectorThis()->closest('[data-status]')->data('status'),'department_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
		
		$grid->js('click')->_selector('.xepan-production-report1')->univ()->frameURL('Jobcards',[$this->api->url('xepan_production_jobcard'),'department_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);

		if($form->isSubmitted()){
			$form->validateFields()
				 ->reloadView($grid);
		}
	}

	function defaultTemplate(){
		return ['reports\productionpagetemplate'];
	}
}