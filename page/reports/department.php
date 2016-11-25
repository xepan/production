<?php

namespace xepan\production;

class page_reports_department extends \xepan\production\page_reports_reportsidebar{
	public $title = 'Department Report';

	function init(){
		parent::init();
		
		$toggle_button = $this->add('Button',null,'toggle')->set('Show/Hide form')->addClass('btn btn-primary btn-sm');
		$form = $this->add('xepan\production\Reports_FilterForm',null,'filterform');
		$this->js(true,$form->js()->hide());
		$toggle_button->js('click',$form->js()->toggle());
		
		$this->add('View',null,'view',null)->set('To BE IMPLEMENTED');		

	}

	function defaultTemplate(){
		return ['reports\productionspagetemplate'];
	}
}