<?php

namespace xepan\production;

class page_jobcarddetail extends \Page {
	public $title='JobcardDetail';

	function init(){
		parent::init();

		$action = $this->api->stickyGET('action')?:'view';
		$job_model = $this->add('xepan\production\Model_Jobcard')->tryLoadBy('id',$this->api->stickyGET('qsp_master_id'));
		
		$job_document = $this->add('xepan\hr\View_Document',['action'=> $action],null,['view/jobcard/detail']);
		$job_document->setIdField('qsp_master_id');
		$job_document->setModel($job_model,
			['jobcard_no','type','document_no','create_date','due_date','date','order_name','current_department','day'],
			['jobcard_no','document_no_id','create_date','due_date','date','order_name','current_department','day']
			);

	}
}