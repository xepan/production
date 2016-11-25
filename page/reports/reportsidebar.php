<?php

namespace xepan\production;

class page_reports_reportsidebar extends \xepan\base\Page{
	function init(){
		parent::init();

		$this->app->side_menu->addItem(['Customer','icon'=>'fa fa-user'],'xepan_production_reports_customer')->setAttr(['title'=>'Customer Report']);
		$this->app->side_menu->addItem(['Outsource Party','icon'=>'fa fa-user'],'xepan_production_reports_outsourceparty')->setAttr(['title'=>'Outsource Party Report']);
		$this->app->side_menu->addItem(['Department','icon'=>'fa fa-sliders'],'xepan_production_reports_department')->setAttr(['title'=>'Department Report']);
	}
}