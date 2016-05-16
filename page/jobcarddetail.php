<?php

namespace xepan\production;

class page_jobcarddetail extends \xepan\base\Page {
	public $title='JobcardDetail';
	public $breadcrumb=['Home'=>'index','Jobcard Detail'=>'xepan_production_jobcard','Detail'=>'#'];

	function init(){
		parent::init();

		$doc_id=$this->api->stickyGET('document_id');

		$action = $this->api->stickyGET('action')?'view':'view';

		$job_model = $this->add('xepan\production\Model_Jobcard')->tryLoadBy('id',$doc_id);
		$customer_model = $this->add('xepan\base\Model_Contact')->tryLoadBy('id',$job_model['customer_id']);
		$order_model = $this->add('xepan\commerce\Model_QSP_Master')->tryLoadBy('id',$job_model['order_no']);
		

		$job_document = $this->add('xepan\hr\View_Document',['action'=> $action],null,['view/jobcard/detail']);

		$job_document->setIdField('document_id');		
		$job_document->setModel($job_model);

		$job_document->template->trySet('address',$customer_model['address']);
		$job_document->template->trySet('city',$customer_model['city']);
		$job_document->template->trySet('state',$customer_model['state']);
		$job_document->template->trySet('country',$customer_model['country']);
		// throw new \Exception($job_model['department'], 1);
		
		$order_item_detail=$this->add('xepan\commerce\Model_QSP_Detail');
		$order_item_detail->tryLoadBy('id',$job_model['order_item_id']);

		$array = json_decode($order_item_detail['extra_info']?:"[]",true);
		$cf_html = "";

		foreach ($array as $department_id => &$details) {
			$department_name = $details['department_name'];
			$cf_list = $job_document->add('CompleteLister',null,'extra_info',['view\qsp\extrainfo']);
			$cf_list->template->trySet('department_name',$department_name);
			unset($details['department_name']);
			
			$cf_list->setSource($details);

			$cf_html  .= $cf_list->getHtml();	
		}		

		$job_document->template->trySetHtml('extra_info',$cf_html);
		$job_document->template->trySet('order_created_at',$order_model['created_at']);
		if($job_model['parent_jobcard_id']){
			$job_document->template->trySet('form_department',$job_model->parentJobcard()->get("department"));
		}else{
			$job_document->template->tryDel('from_dept_row');
		}
		
		$job_document->template->trySet('current_department',$job_model['department']);
		
		if($job_model->nextProductionDepartment()){
			$job_document->template->trySet('next_department',$job_model->nextProductionDepartment()->get('name'));
		}else{
			$job_document->template->tryDel('next_dept_row');
		}




	}
}