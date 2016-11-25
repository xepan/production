<?php

namespace xepan\production;

class page_reports_customer extends \xepan\production\page_reports_reportsidebar{
	public $title = 'Customer Report';

	function init(){
		parent::init();
		
		$from_date = $this->app->stickyGET('to_date');
		$to_date = $this->app->stickyGET('from_date');
		$customer_id = $this->app->stickyGET('customer_id');
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
		$form = $this->add('xepan\production\Reports_FilterForm',['extra_fields'=>true,'status_array'=>$status_array,'entity'=>'customer'],'filterform');
		$this->js(true,$form->js()->hide());
		$toggle_button->js('click',$form->js()->toggle());
		
		$customer_m = $this->add('xepan\production\Model_Reports_Customer',['from_date'=>$from_date,'to_date'=>$to_date,'customer_id'=>$customer_id]);
		if($jobcard_status)
			$customer_m->setOrder($jobcard_status,$order);

		$grid = $this->add('xepan\hr\Grid',null,'view',['reports\productioncustomer']);
		$grid->setModel($customer_m);	
		$grid->addPaginator(30);
		$grid->addQuickSearch(['name']);
	
		$grid->js('click')->_selector('.col-md-3')->univ()->frameURL('Jobcards',[$this->api->url('xepan_production_jobcard'),'status'=>$this->js()->_selectorThis()->closest('[data-status]')->data('status'),'customer_id'=>$this->js()->_selectorThis()->closest('[data-customer]')->data('customer')]);
		
		if($form->isSubmitted()){
			$form->validateFields()
				 ->reloadView($grid);
		}
	}

	function defaultTemplate(){
		return ['reports\productionpagetemplate'];
	}
}