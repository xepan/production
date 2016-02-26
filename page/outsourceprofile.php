<?php


namespace xepan\production;

class page_outsourceprofile extends \Page {
	public $title='OutsourceProfile';
	

	function init(){
		parent::init();

		$action = $this->api->stickyGET('action')?:'view';
		$osp = $this->add('xepan\production\Model_OutsourceParty')->tryLoadBy('id',$this->api->stickyGET('contact_id'));
		
		$contact_view = $this->add('xepan\base\View_Contact',null,'contact_view');
		$contact_view->setModel($osp);

		// if($osp->loaded()){			
		// 	$portfolio_view = $this->add('xepan\hr\View_Document',
		// 		[
		// 			'action'=>$this->api->stickyGET('action')?:'view', // add/edit
		// 				'id_fields_in_view'=>[],
		// 				'allow_many_on_add' => false, // Only visible if editinng,
		// 				'view_template' => ['view/portfolio'],
		// 				'submit_button'=>'Update',
		// 				'id_field_on_reload'=>'contact_id'
		// 		],
		// 		'portfolio'
		// 	);
				

		// 	$portfolio_view->setModel($osp,['department'],['department_id']);




		// 	// $ord = $this->add('xepan\hr\Grid',['defaultTemplate'=>['view/orderstatus']],'orderstatus');
		// 	// $ord->setModel($osp->ref('xepan\production\Jobcard'));

		
			
		// }


		if($osp->loaded()){
			$portfolio_view = $this->add('xepan\hr\View_Document',['action'=> $action],'portfolio',['view/portfolio']);
			$portfolio_view->setModel($osp,['department'],['department_id']);
		}
	}
	
	function defaultTemplate(){
		return ['page/outsourceprofile'];
	}
}
