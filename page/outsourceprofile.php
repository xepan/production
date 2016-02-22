<?php


namespace xepan\production;

class page_outsourceprofile extends \Page {
	public $title='OutsourceProfile';
	

	function init(){
		parent::init();

		$osp= $this->add('xepan\production\Model_OutsourceParty')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		
		$contact_view = $this->add('xepan\base\View_Contact',null,'contact_view');
		$contact_view->setModel($osp);
	}

	function defaultTemplate(){
		return ['page/outsourceprofile'];
	}
}
