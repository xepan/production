<?php

namespace xepan\production;

class page_jobcard extends \Page {
	
	public $title='Jobcard';

	function init(){
		parent::init();

		$job_j=$this->add('xepan\production\Model_Jobcard');
		
		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_production_jobcarddetail'],null,['view/jobcard/grid']);

		$crud->setModel($job_j);
		$crud->grid->addQuickSearch(['name']);

		//$crud->gird->addPaginator(10);
	}
	
}