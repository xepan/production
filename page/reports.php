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
		$grid = $view->add('xepan\base\Grid',null,null,['view\report']);
		$grid->setModel($jobcard,['name','total_jobcards','ToReceived','Received','Processing','Forwarded','Completed','Cancelled','Rejected']);

		$vp = $this->add('VirtualPage');
		$vp->set(function($p){
			$department_id = $p->app->stickyGET('department_id');
			$jobcard_status = $p->app->stickyGET('jobcard_status');
			$from_date = $this->app->stickyGET('from_date');
			$to_date = $this->app->stickyGET('to_date');
			
			$jobcard_model = $p->add('xepan\production\Model_Jobcard')->setOrder('id','desc');
			$jobcard_model->addCondition('department_id',$department_id);
			
			if($jobcard_status !='all')
				$jobcard_model->addCondition('status',$jobcard_status);
	
			$grid = $p->add('xepan\hr\Grid');
			$grid->setModel($jobcard_model,['department', 'order_item', 'order_no', 'customer_name','order_item_quantity','order_document_id','customer_id']);
			
			$grid->addMethod('init_order_no',function($g,$f){
				$g->js('click')->_selector('.order_no')->univ()->frameURL('ORDER',[$this->app->url('xepan_commerce_salesorderdetail'),'document_id'=>$g->js()->_selectorThis()->data('order_document_id')]);
			});

			$grid->addMethod('format_order_no',function($g,$f){
				$g->current_row_html[$f]='<div class="order_no" style="cursor:pointer" data-order_document_id="'.$g->model['order_document_id'].'">'.$g->model['order_no'].'</div>';
			});

			$grid->addFormatter('order_no','order_no');

			$grid->addMethod('init_customer_name',function($g,$f){
				$g->js('click')->_selector('.customer_detail')->univ()->frameURL('CUSTOMER',[$this->app->url('xepan_commerce_customerdetail'),'contact_id'=>$g->js()->_selectorThis()->data('contact_id')]);
			});

			$grid->addMethod('format_customer_name',function($g,$f){												
				$g->current_row_html[$f]='<div class="customer_detail" style="cursor:pointer" data-contact_id="'.$g->model['customer_id'].'">'.$g->model['customer_name'].'</div>';
			});

			$grid->addFormatter('customer_name','customer_name');

			$grid->removeColumn('customer_id');
			$grid->removeColumn('order_document_id');

		});
			
		$grid->on('click','.xepan-production-report',function($js,$data)use($vp, $from_date, $to_date){
			return $js->univ()->frameURL("JOB CARDS",$this->api->url($vp->getURL(),['department_id'=>$data['id'], 'jobcard_status'=>$data['status'], 'from_date'=>$from_date, 'to_date'=>$to_date]));
		});

		if($f->isSubmitted()){
			$js=[
				$view->js()->reload(['from_date'=>$f['from_date'],'to_date'=>$f['to_date']])
			];
			$f->js(null,$js)->execute();
		}	
	}
}