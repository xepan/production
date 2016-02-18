<?php

namespace xepan\production;

class page_jobcard extends \Page {
	
	public $title='Jobcard';

	function init(){
		parent::init();

	}
	
	function defaultTemplate(){
		return['page/jobcard'];
	}

}