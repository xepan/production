<?php

namespace xepan\production;

class page_outsourceparty extends \Page {
	public $title='OutsourceParty';

	function init(){
		parent::init();

		// $crud=$this->add('CRUD');
		// $crud->setModel('xepan\production\Model_OutsourceParty');
	}
	function defaultTemplate(){
		return['page/outsourceparty'];
	}
}
