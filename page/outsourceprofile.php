<?php

namespace xepan\production;

class page_outsourceprofile extends \Page {
	public $title='OutsourceProfile';

	function init(){
		parent::init();

		$crud=$this->add('CRUD');
		$crud->setModel('xepan\production\Model_OutsourceProfile');
	}
}