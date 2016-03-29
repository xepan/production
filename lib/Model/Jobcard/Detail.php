<?php

namespace xepan\production;

class Model_Jobcard_Detail extends \xepan\base\Model_Table{
	public $table = "jobcard_detail";
	public $status = ['ToReceived','Received','Forwarded','Completed'];

	function init(){
		parent::init();

		$this->hasOne('xepan\production\Model_Jobcard','jobcard_id');
		$this->addField('quantity'); 
		$this->addField('parent_detail_id')->defaultValue(0); //parent jobcard detail id
		$this->addField('status');

	}
}
