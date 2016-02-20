<?php

namespace xepan\production;

class page_jobcard extends \Page {
	
	public $title='Jobcard';

	function init(){
		parent::init();

		$jobcard=$this->add('xepan\production\Model_Jobcard');
		$crud=$this->add('xepan\base\CRUD',
						array(
							'grid_class'=>'xepan\base\Grid',
							'grid_options'=>array(
											'defaultTemplate'=>['grid/jobcard']
											)
						));
		$crud->setModel($jobcard);
		$crud->grid->addQuickSearch(['name']);

	
	}
	
}