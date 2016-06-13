<?php


namespace xepan\production;

class page_outsourcepartiesdetails extends \xepan\base\Page{
	public $title='Outsource Party Details';
	public $breadcrumb=['Home'=>'index','Outsource Party'=>'xepan_production_outsourceparties','Details'=>'#'];

	function init(){
		parent::init();

		$action = $this->api->stickyGET('action')?:'view';
		$osp = $this->add('xepan\production\Model_OutsourceParty')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		
		if($action=="add"){

			$contact_view = $this->add('xepan\base\View_Contact',['acl'=>'xepan\production\Model_OutsourceParty','view_document_class'=>'xepan\hr\View_Document'],'contact_view_full_width');
			$contact_view->document_view->effective_template->del('im_and_events_andrelation');
			$contact_view->document_view->effective_template->del('email_and_phone');
			$this->template->del('details');
			$contact_view->setStyle(['width'=>'50%','margin'=>'auto']);
		}else{
			$contact_view = $this->add('xepan\base\View_Contact',['acl'=>'xepan\production\Model_OutsourceParty','view_document_class'=>'xepan\hr\View_Document'],'contact_view');
		}	

		$contact_view->setModel($osp);

		if($osp->loaded()){
			$portfolio_view = $this->add('xepan\hr\View_Document',['action'=> $action],'portfolio',['view/outsourceparty/portfolio']);
			
			$portfolio_view->setIdField('contact_id');
			
			$portfolio_view->setModel($osp,
				['department','bank_name','account_type','account_no','os_country','tin_no','pan_it_no','os_address','remark'],
				['bank_name','account_type','account_no','os_country','tin_no','pan_it_no','os_address','remark']
				);
		}
		if($osp->loaded()){
				$orderstatus_view = $this->add('xepan\hr\View_Document',['action'=> $action],'orderstatus',['view/outsourceparty/orderstatus']);
				
				$orderstatus_view->setIdField('contact_id');
				
			}
		if($osp->loaded()){
			$activity_view = $this->add('xepan\base\Grid',null,'activity',['view/activity/activity-grid']);

			$activity=$this->add('xepan\base\Model_Activity');
			$activity->addCondition('contact_id',$_GET['contact_id']);
			$activity->tryLoadAny();
			$activity_view->setModel($activity);
		}
		
	}
	
	function defaultTemplate(){
		return ['view/outsourceparty/profile'];
	}
}
