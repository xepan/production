<?php

namespace xepan\production;

class View_Department extends \CompleteLister{
	function init(){
		parent::init();

		// $m = $this->add('xepan\production\Model_Jobcard')->load(1903);
		// $m = $m->orderItem()->deptartmentalStatus();		
		// $this->setModel($m);
	}

	function setModel($m){
		parent::setModel($m);
	}

	function defaultTemplate(){
		return['view\department'];
	}
}