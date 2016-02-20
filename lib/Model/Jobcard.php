<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\base\Model_Document{
	
	function init(){
		parent::init();

		$job_j = $this->join('jobcard.document_id');
		$job_j->addField('name');
		$job_j->addField('order_id');
		$job_j->addField('order_name');
		$job_j->addField('date');
		$job_j->addField('day');
		
		//$job_j->addField('status')->enum(['Active','DeActive']);
	
	}
}