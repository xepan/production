<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\base\Model_Document{
	
	function init(){
		parent::init();

		$job_j = $this->join('jobcard.document_id');
<<<<<<< HEAD
		$this->hasOne('xepan\base\contact','contact_id');
		$job_j->addField('name');
		
=======
		$job_j->hasOne('xepan\base\commerce\Order','order_id');
		$job_j->addField('name');
>>>>>>> b88d1718ec9b7af96e40d20967b38f7deb2ac890
	
	}
}