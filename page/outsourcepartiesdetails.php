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

			$contact_view = $this->add('xepan\base\View_Contact',['acl'=>'xepan\production\Model_OutsourceParty','view_document_class'=>'xepan\hr\View_Document','page_reload'=>($action=='add')],'contact_view_full_width');
			$contact_view->document_view->effective_template->del('im_and_events_andrelation');
			$contact_view->document_view->effective_template->del('email_and_phone');
			$contact_view->document_view->effective_template->tryDel('online_status_wrapper');
			$contact_view->document_view->effective_template->del('avatar_wrapper');
			$contact_view->document_view->effective_template->tryDel('contact_since_wrapper');
			$contact_view->document_view->effective_template->tryDel('contact_type_wrapper');
			$contact_view->document_view->effective_template->tryDel('send_email_sms_wrapper');
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
			$orderstatus_view->js('click')->_selector('.do-view-outsourceparties-jobcard')->univ()->frameURL('Jobcard Details',[$this->api->url('xepan_production_jobcarddetail'),'document_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
			$orderstatus_view->js('click')->_selector('.do-view-outsourceparties-order')->univ()->frameURL('Order Details',[$this->api->url('xepan_commerce_salesorderdetail'),'document_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
			$orderstatus_view->js('click')->_selector('.do-view-outsourceparties-customer-details')->univ()->frameURL('Customer Details',[$this->api->url('xepan_commerce_customerdetail'),'contact_id'=>$this->js()->_selectorThis()->closest('[data-id]')->data('id')]);
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
