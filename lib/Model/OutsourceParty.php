<?php

namespace xepan\production;

class Model_OutsourceParty extends \xepan\base\Model_Contact{
	function init(){
		parent::init();

		$osp_j = $this->join('outsource_party.contact_id');
		$osp_j->hasOne('xepan\hr\Department');

		$osp_j->addField('bank_detail')->type('text');
		
		$osp_j->addField('pan_it_no')->caption('Pan / IT No.');
		$osp_j->addField('tin_no')->caption('TIN / CST No.');
		$osp_j->hasMany('xepan\production\Jobcard');

		$this->addCondition('type','OutsourceParty');
	}
}

