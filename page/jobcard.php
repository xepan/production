<?php

namespace xepan\production;

class page_jobcard extends \xepan\base\Page {
	
	public $title='Jobcard';
	public $department_id;
	function init(){
		parent::init();

		$this->department_id = $this->api->stickyGET('department_id');

		$jobcard_model = $this->add('xepan\production\Model_Jobcard');
		$jobcard_model->addExpression('department_name')->set($jobcard_model->refSQL('department_id')->fieldQuery('name'));

		if($this->department_id){
			$jobcard_model->addCondition('department_id',$this->department_id);
			$jobcard_model->tryLoadAny();

			$this->title = "Jobcard / Department :: ".$jobcard_model['department_name'];
		}

		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_production_jobcarddetail'],null,['view/grid/jobcard']);
		$crud->grid->addColumn('departmental_status');

		$crud->setModel($jobcard_model);
		$crud->grid->addQuickSearch(['name']);


		$crud->grid->addMethod('format_department123',function($grid,$field){
				$m = $grid->add('xepan\production\Model_Jobcard')->load($grid->model->id);
				$m = $m->orderItem()->deptartmentalStatus();				
				$v = $grid->add('xepan\production\View_Department',null,'department123');		
				$v->setModel($m);
				$grid->current_row_html[$field] = $v->getHtml();
			});
		//$crud->grid->addFormatter('departmental_status','departmental_status');

	}
	
}