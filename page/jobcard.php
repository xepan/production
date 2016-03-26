<?php

namespace xepan\production;

class page_jobcard extends \Page {
	
	public $title='Jobcard';
	public $department_id;
	function init(){
		parent::init();

		$this->department_id = $this->api->stickyGET('department_id');

		$jobcard_model =$this->add('xepan\production\Model_Jobcard');

		if($this->department_id){
			$jobcard_model->addCondition('department_id',$this->department_id);
		}
		

		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_production_jobcarddetail'],null,['view/jobcard/grid']);

		$crud->setModel($jobcard_model);
		$crud->grid->addQuickSearch(['name']);

		//$crud->gird->addPaginator(10);
		
		// $crud->add('xepan\base\Controller_Avatar',['name_field'=>'name']);
	}
	
}