<?php

namespace xepan\production;

class page_jobcardorderdetail extends \xepan\base\Page{
	public $title="Jobcard Orders Detail";
	function init(){
		parent::init();

		$doc_id=$this->api->stickyGET('document_id');

		$action = $this->api->stickyGET('action')?'view':'view';

		$job_crd_dtl_mdl = $this->add('xepan\production\Model_Jobcard')->tryLoadBy('order_no',$doc_id);
		$customer_model = $this->add('xepan\base\Model_Contact')->tryLoadBy('id',$job_crd_dtl_mdl['customer_id']);
	
		$dtl_view = $this->add('xepan\hr\View_Document',null,null,['view/jobcard/orderdetail']);
		$dtl_view->setIdField('order_no');
		$dtl_view->setModel($job_crd_dtl_mdl);

		$dtl_view->template->trySet('address',$customer_model['address']);
		$dtl_view->template->trySet('city',$customer_model['city']);
		$dtl_view->template->trySet('state',$customer_model['state']);
		$dtl_view->template->trySet('country',$customer_model['country']);

		$order_item_detail=$this->add('xepan\commerce\Model_QSP_Detail');
		$c = $order_item_detail->tryLoadBy('id',$job_crd_dtl_mdl['order_item_id']);
		$dtl_view->template->trySet('job_crd_cnt',$c->count()->getOne());

		$dtl_view->template->trySet('job_id',$job_crd_dtl_mdl['order_no']);

		// $array = json_decode($order_item_detail['extra_info']?:"[]",true);
		// $cf_html = "";

		// foreach ($array as $department_id => &$details) {
		// 	$department_name = $details['department_name'];
		// 	$cf_list = $dtl_view->add('CompleteLister',null,'extra_info',['view\qsp\extrainfo']);
		// 	$cf_list->template->trySet('department_name',$department_name);
		// 	unset($details['department_name']);
			
		// 	$cf_list->setSource($details);

		// 	$cf_html  .= $cf_list->getHtml();	
		// }		

		// $dtl_view->template->trySetHtml('extra_info',$cf_html);
		// $dtl_view->template->trySet('order_created_at',$order_model['created_at']);
		// if($job_crd_dtl_mdl['parent_jobcard_id']){
		// 	$dtl_view->template->trySet('form_department',$job_crd_dtl_mdl->parentJobcard()->get("department"));
		// }else{
		// 	$dtl_view->template->tryDel('from_dept_row');
		// }

		
		
	}
}