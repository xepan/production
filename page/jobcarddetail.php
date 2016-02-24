<?php

namespace xepan\production;

class page_jobcarddetail extends \Page {
	public $title='JobcardDetail';

	function init(){
		parent::init();
		 $job = $this->add('xepan\production\Model_Jobcard');

	}
	function defaultTemplate(){
		return ['page/jobcarddetail'];
	}
}