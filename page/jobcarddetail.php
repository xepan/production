<?php

namespace xepan\production;

class page_jobcarddetail extends \Page {
	public $title='JobcardDetail';

	function init(){
		parent::init();
		$job=$this->add('xepan\production\Model_Jobcard');
		
		$crud=$this->add('xepan\base\CRUD',
						[
							'action_page'=>'xepan_production_jobcard',
							'grid_options'=>[
											'defaultTemplate'=>['grid/jobcarddetail']
											]
						]);

		$crud->setModel($job);
		$crud->grid->addQuickSearch(['name']);
	}
}