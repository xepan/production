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

		$job_document->template->trySet('order_created_at',$order_model['created_at']);

	}
}