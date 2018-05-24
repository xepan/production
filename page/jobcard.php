<?php

namespace xepan\production;

class page_jobcard extends \xepan\base\Page {
	
	public $title='Jobcard';
	public $department_id;
	function init(){
		parent::init();

		$this->department_id = $this->api->stickyGET('department_id');
		$this->customer_id = $this->api->stickyGET('customer_id');
		$this->outsource_party_id = $this->api->stickyGET('outsource_party_id');


		$jobcard_model = $this->add('xepan\production\Model_Jobcard')->setOrder('id','desc');
		$jobcard_model->add('xepan\base\Controller_TopBarStatusFilter');
		$jobcard_model->addExpression('department_name')->set($jobcard_model->refSQL('department_id')->fieldQuery('name'));

		if($this->department_id){
			$jobcard_model->addCondition('department_id',$this->department_id);
			$jobcard_model->tryLoadAny();

			$this->title = "Jobcard / Department :: ".$jobcard_model['department_name'];
		}

		if($this->customer_id){
			$jobcard_model->addCondition('customer_id',$this->customer_id);
		}

		if($this->outsource_party_id){			
			$jobcard_model->addCondition('outsourceparty_id',$this->outsource_party_id);
		}

		$crud=$this->add('xepan\hr\CRUD',null,null,['view/grid/jobcard']);
		$crud->grid->addColumn('departmental_status');


		$crud->setModel($jobcard_model);
		$crud->grid->addQuickSearch(['customer_name','order_no','order_item_name']);


		$crud->grid->addMethod('format_department123',function($grid,$field){
				$m = $grid->add('xepan\production\Model_Jobcard')->load($grid->model->id);
				$m = $m->orderItem()->deptartmentalStatus();				
				$v = $grid->add('xepan\production\View_Department',null,'department123');		
				$v->setModel($m);
				$grid->current_row_html[$field] = $v->getHtml();
			});
		$crud->grid->addPaginator($ipp=30);
		//$crud->grid->addFormatter('departmental_status','departmental_status');


		//  MATERIAL REQUEST MANAGEMENT
		$this->app->side_menu->addItem(
										[
											'Material Request',
											'icon'=>"fa fa-user",
											'badge'=>[0,'swatch'=>' label label-primary label-circle pull-right']
										],
										$this->api->url("xepan_production_departmentstock",['department_id'=>$this->department_id])
									)->setAttr(['title'=>"Material Request"]);
	}
	
}