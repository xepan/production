<?php

namespace xepan\production;

class Model_Jobcard extends \xepan\hr\Model_Document{

	public $status=[
	'Draft',
	'Submitted',
	'Approved',
	'Reject'
	];

	public $action=[

				// 'Draft'=>['view','edit','delete','submit'],
				// 'Submitted'=>['view','edit','delete','reject'],
				// 'Approved'=>['view','edit','delete','reject'],
				// 'Reject'=>['view','edit','delete','submit']

					'*'=>['view','edit','delete']
	];
	
	function init(){
		parent::init();

		$job_j = $this->join('jobcard.document_id');
		//$job_j->hasOne('xepan\base\contact','contact_id');
		$job_j->hasOne('xepan\hr\Department','department_id');
		$job_j->hasOne('xepan\production\OutsourceParty','outsourceparty_id');

		$job_j->addField('name');
		$job_j->addField('jobcard_no');
		$job_j->addField('order_name');
		$job_j->addField('day');
		$job_j->addField('date');
		

		$this->addCondition('type','JobCard');		
		
	
	}
}
