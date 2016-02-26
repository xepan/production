<?php

namespace xepan\production;

class Model_OutsourceParty extends \xepan\base\Model_Contact{
	function init(){
		parent::init();

		$osp_j = $this->join('outsource_party.contact_id');
		$osp_j->hasOne('xepan\hr\Department','department_id');

		$osp_j->addField('bank_name');
		
		//$osp_j->addField('outsourceparty');
		$osp_j->addField('pan_it_no')->caption('Pan / IT No.');
		$osp_j->addField('tin_no')->caption('TIN / CST No.');
		$osp_j->addField('account_no');
		$osp_j->addField('account_type');
		$osp_j->addField('time');
		$osp_j->addField('os_address');
		$osp_j->addField('os_country');
		
		$osp_j->hasMany('xepan\production\Jobcard');

		$this->addCondition('type','OutsourceParty');
		
	}
}

