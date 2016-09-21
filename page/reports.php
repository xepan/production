<?php

namespace xepan\production;

class page_reports extends \xepan\base\Page{
	public $title = "Reports";
	function init(){
		parent::init();

		$to_date = $this->app->stickyGET('to_date')?:$this->app->today;
		$from_date = $this->app->stickyGET('from_date')?:$this->app->today;
		
		$f=$this->add('Form');
		$f->addField('DatePicker','from_date')->set($this->app->today);
		$f->addField('DatePicker','to_date')->set($this->app->today);
		$f->addSubmit('Get Report');

		$view = $this->add('View');
		$jobcard = $view->add('xepan\production\Model_Report_Jobcard',['from_date'=>$from_date,'to_date'=>$to_date]);
		$grid = $view->add('xepan\base\Grid');
		$grid->setModel($jobcard,['name','total_jobcards','ToReceived','Received','Processing','Forwarded','Completed','Cancelled','Rejected']);
		if($f->isSubmitted()){
			$js=[
				$view->js()->reload(['from_date'=>$f['from_date'],'to_date'=>$f['to_date']])
			];
			$f->js(null,$js)->execute();
		}	
	}
}