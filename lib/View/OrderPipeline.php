<?php

namespace xepan\production;

class View_OrderPipeline extends \xepan\hr\Grid{

	function formatRow(){
		// $view = $this->add('View','order_detail')->set($this->model->id);
		
		$order_items = $this->add('xepan\commerce\Model_QSP_Detail')
						->addCondition('qsp_master_id',$this->model->id);
		$grid_item = $this->add('xepan\hr\Grid',null,'order_detail',['view\orderpipeline','order_detail']);
		$grid_item->setModel($order_items,['item','quantity','qty_unit_id','qty_unit']);
		$grid_item->addColumn('expander','Timeline');

		$grid_item->addHook('formatRow',function($g){
			$order_detail = $g->add('xepan\commerce\Model_QSP_Detail')->load($g->model->id);
			$array = json_decode($order_detail['extra_info']?:"[]",true);
			unset($array[0]);
			ksort($array); 
			$jobcard_pipeline = $g->add('xepan\production\View_JobcardPipeline',['order_detail_id'=>$g->model->id],'production_step',['view\orderpipeline','production_step']);
			$jobcard_pipeline->setSource($array);

			$g->current_row_html['production_step'] = $jobcard_pipeline->getHtml();
		});


		$this->current_row_html['order_detail'] = $grid_item->getHtml();
		parent::formatRow();
	}

	function defaultTemplate(){
		return['view\orderpipeline'];
	}
}