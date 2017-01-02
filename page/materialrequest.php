<?php

namespace xepan\production;

class page_materialrequest extends \xepan\base\Page {
	
	public $title='Material Request';
	public $department_id;
	function init(){
		parent::init();

		$this->department_id = $this->api->stickyGET('department_id');

		
	}
}