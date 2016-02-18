<?php

namespace xepan\production;

class page_jobcarddetail extends \Page {
	public $title='JobcardDetail';

	function init(){
		parent::init();

		// $crud = $this->add('CRUD');
		// $crud->setModel('xepan\production\Model_JobcardDetail');
	}
	function defaultTemplate(){
		return['page/jobcarddetail'];
	}
}