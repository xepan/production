<?php

namespace xepan\production;

/**
* 
*/
class page_report extends \xepan\base\Page{
	public $title="Production Report";
	function init(){
		parent::init();	

		$form = $this->add('Form',null,null,['form/empty']);
		$form->addField('Dropdown','outsource_party')->setEmptyText('Please Select')->setModel('xepan\production\OutsourceParty');

		$jobcard_m=$this->add('xepan\production\Model_Jobcard');
		$grid = $this->add('xepan\hr\Grid',null,null,['view/grid/jobcard']);

		$form->addSubmit('Get Report');

		if($_GET['filter']){
			$this->app->stickyGET('outsource_party_id');
			$jobcard_m->addCondition('outsourceparty_id',$_GET['outsource_party_id']);
			// throw new \Exception($jobcard_m->count()->getOne(), 1);
			
		}else{
			$jobcard_m->addCondition('id',-1);
		}
		$grid->setModel($jobcard_m);//,['department','outsourceparty','order_item','order_no','customer_name','order_item_quantity']);
		$grid->addQuickSearch(['department']);
		if($form->isSubmitted()){
			$grid->js()->reload(
									[
										'outsource_party_id'=>$form['outsource_party'],
										'filter'=>1
									]
									)->execute();
		}
	}
}