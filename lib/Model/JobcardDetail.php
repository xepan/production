<?php

namespace xepan\production;

class Model_JobcardDetail extends \xepan\base\Model_Table{
	
	function init(){
		parent::init();

		$this->hasOne('xepan\base\Epan');

		$this->hasOne('Outsourceparty','Outsourceparty_id');
		
		$this->addField('activity')->type('text');


		$this->addField('is_active')->type('boolean')->defaultValue(true);

		

	}
}
