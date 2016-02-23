<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\base\Model_Document{
	
	function init(){
		parent::init();

		$job_j = $this->join('jobcard.document_id');
		$job_j->hasOne('xepan\base\contact','contact_id');
		$job_j->addField('name');
		
	
	}
}
