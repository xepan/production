<?php

namespace xepan\production;

class page_outsourceprofile extends \Page {
	public $title='OutsourceProfile';


	function init(){
		parent::init();

		$this->add('xepan/production/View_Activity',null,'activity');
		$this->add('xepan/production/View_OutsourceStatus',null,'outsourcestatus');
	}
	function defaultTemplate(){
		return['page/outsourceprofile'];
	}
}