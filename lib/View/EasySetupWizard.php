<?php


namespace xepan\production;

class View_EasySetupWizard extends \View{
	public $vp;
	function init(){
		parent::init();

		/**************************************************************************
			SETUP PDF AND MAILING TEMPLATE WIZARD
		**************************************************************************/	
		if($_GET[$this->name.'_jobcard_recieved_mail_layouts']){
			
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
			$config_m->tryLoadAny();

			$subject = file_get_contents(realpath("../vendor/xepan/production/templates/default/jobcard-received-subject.html"));
			$body = file_get_contents(realpath("../vendor/xepan/production/templates/default/jobcard-received-body.html"));
			$master = file_get_contents(realpath("../vendor/xepan/production/templates/default/jobcard-received-print.html"));
				
			if(!$config_m['subject']){
				$config_m['subject'] = $subject;
			}
			
			if(!$config_m['body']){
				$config_m['body'] = $body;
			}
			
			if(!$config_m['master']){
				$config_m['master'] = $master;
			}

			$config_m->save();

			$this->js(true)->univ()->frameURL("Jobcard Received Mail Content Layouts",$this->app->url('xepan_production_config'));
		}

		$isDone = false;
		$action = $this->js()->reload([$this->name.'_jobcard_recieved_mail_layouts'=>1]);

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
		$config_m->tryLoadAny();
		
		if(!$config_m['subject'] || !$config_m['body'] || !$config_m['master']){
			$isDone = false;
		}else{
			$isDone = true;
			$action = $this->js()->univ()->dialogOK("Already have Templates",' You have already updated documents layouts for sending or receiving mail, visit page ? <a href="'. $this->app->url('xepan_production_config')->getURL().'"> click here to go </a>');
		}	
		
		$jobcard_layouts_view = $this->add('xepan\base\View_Wizard_Step')
			->setAddOn('Application - Production')
			->setTitle('Set Jobcard Documents Layouts')
			->setMessage('Please set documents layouts for sending jobcard through mail.')
			->setHelpMessage('Need help ! click on the help icon')
			->setHelpURL('#')
			->setAction('Click Here',$action,$isDone);
	}
}