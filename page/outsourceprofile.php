<?php


namespace xepan\production;

class page_outsourceprofile extends \Page {
	public $title='OutsourceProfile';
	

	function init(){
		parent::init();


		$osp = $this->add('xepan\production\Model_OutsourceParty')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		

		if($osp->loaded()){
			$portfolio_view = $this->add('xepan\hr\View_Document',
				[
					'action'=>$this->api->stickyGET('action')?:'view', // add/edit
						'id_fields_in_view'=>[],
						'allow_many_on_add' => false, // Only visible if editinng,
						'view_template' => ['view/portfolio'],
						'submit_button'=>'Update',
						'id_field_on_reload'=>'contact_id'
				],
				'portfolio'
			);
		
		
		$portfolio_view->setModel($osp,['department','post'],['department_id','post_id']);

		$ord = $this->add('xepan\hr\Grid',['defaultTemplate'=>['grid/jobcard-grid']],'orderstatus');
		$ord->setModel($osp->ref('xepan\production\Jobcard'));

	
		
		$contact_view = $this->add('xepan\hr\View_Contact',null,'contact_view');
		$contact_view->setModel($osp);

		
	}
}
	function defaultTemplate(){
		return ['page/outsourceprofile'];
	}
}
