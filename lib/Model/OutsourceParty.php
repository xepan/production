<?php

namespace xepan\production;

class Model_OutsourceParty extends \xepan\base\Model_Contact{

	//public $table="OutsourceParty";

	function init(){
		parent::init();

		$osp_j = $this->join('outsource_party.contact_id');
		$osp_j->hasOne('xepan\hr\Department');
		
		// $osp_j->addField('is_active')->type('boolean')->defaultValue(true);
		// $this->addCondition('type','OutsourceParty');
		$osp_j->addField('tin_no');
		$osp_j->addField('maintain_stock')->type('boolean')->defaultValue(false)->group('e~4~MaintainStock')->sortable(true);
		$osp_j->addField('bank_detail')->type('text')->group('c~12~ Bank Detail');

		$osp_j->hasMany('Production/OutsourceParty','outsourceparty_id');
	}
}

