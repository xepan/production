// <?php

// namespace xepan\production;

// class Model_OutsourceParty extends \xepan\base\Model_Contact{

// 	public $table="OutsourceParty";

// 	function init(){
// 		parent::init();

// 		$this->hasOne('xepan','epan_id');
// 		$this->addCondition('xepan_id');

// 		$this->addField('is_active')->type('boolean')->defaultValue(true);
// 		$this->addField('tin_no');

// 	}
// }