<?php


namespace xepan\production;

class View_EasySetupWizard extends \View{
	public $vp;
	function init(){
		parent::init();

		/**************************************************************************
			SETUP PDF AND MAILING TEMPLATE WIZARD
		**************************************************************************/	

		if($_GET[$this->name.'_add_template'])
			$this->js(true)->univ()->frameURL("Production Configuration => Templates",$this->app->url('xepan_production_config'));

		$isDone = false;
		$action = $this->js()->reload([$this->name.'_add_template'=>1]);

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

		if($config_m['subject'] || $config_m['body'] || $config_m['master']){
			$isDone = true;
			$action = $this->js()->univ()->dialogOK("Already have Data",' You have already added working week days, visit page ? <a href="'. $this->app->url('xepan_production_config')->getURL().'"> click here to go </a>');
		}

		$template_view = $this->add('xepan\base\View_Wizard_Step')
			->setAddOn('Application - Production')
			->setTitle('Add Templates')
			->setMessage('Add templates for email content and PDF')
			->setHelpMessage('Need help ! click on the help icon')
			->setHelpURL('#')
			->setAction('Click Here',$action,$isDone);		
	}
}