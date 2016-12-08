<?php

namespace xepan\production;

/**
* 
*/
class page_config extends \xepan\base\Page{
	public $title = "Jobcard Configuration";
	function init(){
		parent::init();
		$config_m = $this->add('xepan\base\Model_ConfigJsonModel',
		[
			'fields'=>[
						'subject'=>'Line',
						'body'=>'xepan\base\RichText',
						'master'=>'xepan\base\RichText',

						],
				'config_key'=>'PRODUCTION_JOBCARD_SYSTEM_CONFIG',
				'application'=>'production'
		]);
		$config_m->add('xepan\hr\Controller_ACL');
		$config_m->tryLoadAny();

		$form=$this->add('Form',null,'jobcard-received');
		$form->setModel($config_m,['subject','body','master']);
		$form->getElement('subject')->set($config_m['subject'])->setFieldHint(' ')->setCaption('Subject');
		$form->getElement('body')->set($config_m['body'])->setFieldHint(' ')->setCaption('Message');
		$form->getElement('master')->set($config_m['master'])->setFieldHint('{$status},{$next_department},{$id},{$order_no},{$created_at},{$order_created_at},{$due_date},{$order_item},{$extra_info},{$order_item_quantity},{$extra_notes}')->setCaption('Master');

		$save = $form->addSubmit('Save')->addClass('btn btn-primary');
		$reset = $form->addSubmit('Reset')->addClass('btn btn-primary');

		if($form->isSubmitted()){
			if($form->isClicked($save)){
				$form->save();
				$form->js(null,$form->js()->reload())->univ()->successMessage('Information Updated')->execute();
			}

			if($form->isClicked($reset)){
				$temp = file_get_contents(realpath("../vendor/xepan/production/templates/default/jobcard-received-subject.html"));
				$temp1 = file_get_contents(realpath("../vendor/xepan/production/templates/default/jobcard-received-body.html"));
				$temp2 = file_get_contents(realpath("../vendor/xepan/production/templates/default/jobcard-received-print.html"));
				
				$config_m['subject'] = $temp;	
				$config_m['body'] = $temp1;	
				$config_m['master'] = $temp2;	
				$config_m->save();

				$form->js(null,$form->js()->reload())->univ()->successMessage('Information Resetted')->execute();
			}	
		}
	}

	function defaultTemplate(){
		return ['page/config'];
	}
}