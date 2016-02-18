<?php

namespace xepan\production;

class Model_Jobcard extends \Model_Table{
	public $table="jobcard";
	
	function init(){
		parent::init();


		$this->hasOne('xepan\base\Epan');
		$this->addField('outsourceparty');
		$this->addfield('duration');
		//$this->addField('type')->defaultValue('JobCard');
		$this->addField('order_no');
	
		$this->addField('status')->enum(['Active','DeActive']);
		//$this->hasMany('xepan\base\Epan\production\outsourceparty',null,null,'outsourceparty');

	}
}
