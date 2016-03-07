<?php


namespace xepan\production;

class page_profile extends \Page {
	public $title='OutsourcePartyProfile';
	

	function init(){
		parent::init();

		$action = $this->api->stickyGET('action')?:'view';
		$osp = $this->add('xepan\production\Model_OutsourceParty')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		$contact_view = $this->add('xepan\base\View_Contact',null,'contact_view');
		$contact_view->setModel($osp);

		if($osp->loaded()){
			$portfolio_view = $this->add('xepan\hr\View_Document',['action'=> $action],'portfolio',['view/outsourceparty/portfolio']);
			
			$portfolio_view->setIdField('contact_id');
			
			$portfolio_view->setModel($osp,
				['department','bank_name','account_type','account_no','os_country','tin_no','pan_it_no','os_address'],
				['department_id','bank_name','account_type','account_no','os_country','tin_no','pan_it_no','os_address']
				);
		}
	}
	
	function defaultTemplate(){
		return ['view/outsourceparty/profile'];
	}
}
