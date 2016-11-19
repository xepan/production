<?php

namespace xepan\production;

class View_JobcardPipeline extends \CompleteLister{
	public  $order_detail_id;
		
	function formatRow(){

		$jobcard = $this->add('xepan\production\Model_Jobcard');
		$jobcard->addCondition('order_item_id',$this->order_detail_id);
		$jobcard->addCondition('department_id',$this->model->id);
		$jobcard->tryLoadAny();

		$this->current_row_html['jobcard_no'] = $jobcard['id'];
		$this->current_row_html['status'] = $jobcard['status'];
		$this->current_row_html['created_at'] = $jobcard['created_at'];
		
		if($jobcard->loaded())
			$this->current_row_html['active'] = "active";
		else
			$this->current_row_html['active'] = "deactive";
		parent::formatRow();
	}

	function defaultTemplate(){
		return['view\jobcardpipeline'];
	}
}