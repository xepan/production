<?php


namespace xepan\production;

class page_outsourceprofile extends \Page {
	public $title='OutsourceProfile';
	

	function init(){
		parent::init();


		$osp = $this->add('xepan\production\Model_OutsourceParty')->tryLoadBy('id',$this->api->stickyGET('contact_id'));

		$d = $this->add('xepan\base\View_Document',
				[
					'action'=>$this->api->stickyGET('action')?:'view', // add/edit
					'id_fields_in_view'=>'["all"]/["post_id","field2_id"]',
					'allow_many_on_add' => false, // Only visible if editinng,
					'view_template' => ['view/portfolio']
				],
				'portfolio'
			);
		$d->setModel($osp,null,['bank_name']);

		$ord = $this->add('xepan\base\Grid',null,'orderstatus',['grid/jobcard-grid']);
		$ord->setModel($osp->ref('xepan\production\Jobcard'));

		$act = $this->add('xepan\base\View_Document',
				[
					'action'=>$this->api->stickyGET('action')?:'view', // add/edit
					'id_fields_in_view'=>'["all"]/["post_id","field2_id"]',
					'allow_many_on_add' => false, // Only visible if editinng,
					'view_template' => ['view/Activity']
				],
				'activity'
			);
		$act->setModel($act,null,['date','day']);
		
		$contact_view = $this->add('xepan\base\View_Contact',null,'contact_view');
		$contact_view->setModel($osp);
	}

	function defaultTemplate(){
		return ['page/outsourceprofile'];
	}
}
