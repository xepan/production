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
		$form->getElement('master')->set($config_m['master'])->setFieldHint(' ')->setCaption('Master');

		$form->addSubmit('Update')->addClass('btn btn-primary');

		if($form->isSubmitted()){
			$form->save();
			$form->js(null,$form->js()->reload())->univ()->successMessage('Update Information')->execute();
		}

	}

	function defaultTemplate(){
		return ['page/config'];
	}
}