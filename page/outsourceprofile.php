<?php

namespace xepan\production;

class page_outsourceprofile extends \Page {
	public $title='OutsourceProfile';


	function init(){
		parent::init();

		$this->add('xepan/production/View_Activity',null,'activity');
		$this->add('xepan/production/View_OrderStatus',null,'orderstatus');
		$this->add('xepan/production/View_Portfolio',null,'portfolio');
	}
	function defaultTemplate(){
		return['page/outsourceprofile'];
	}
}