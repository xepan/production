<?php

namespace xepan\production;

class Model_OutsourceParty extends \xepan\base\Model_Contact{
	public $status = ['Active','InActive'];
	public $actions = [
					'Active'=>['view','edit','delete','deactivate'],
					'InActive'=>['view','edit','delete','activate']
					];

	function init(){
		parent::init();

		$osp_j = $this->join('outsource_party.contact_id');
		$osp_j->hasOne('xepan\hr\Department','department_id');
		$osp_j->hasOne('xepan\accounts\Currency','currency_id');


		$osp_j->addField('bank_name');
		$osp_j->addField('pan_it_no')->caption('Pan / IT No.');
		$osp_j->addField('tin_no')->caption('TIN / CST No.');
		$osp_j->addField('account_no');
		$osp_j->addField('account_type');
		$osp_j->addField('time');
		$osp_j->addField('os_address')->type('text');
		$osp_j->addField('os_city');
		$osp_j->addField('os_state');
		$osp_j->addField('os_country');
		$osp_j->addField('os_pincode');
		
		$osp_j->hasMany('xepan\production\Jobcard');

		$this->hasMany('xepan/commerce/Model_QSP_Master',null,null,'QSPMaster');
		$this->addCondition('type','OutsourceParty');
		
		//TODO Extra Organization Specific Fields other Contacts
		$this->getElement('status')->defaultValue('Active');
		// $this->addHook('beforeSave',$this);		
		$this->addHook('afterSave',$this);
		$this->addHook('beforeDelete',[$this,'checkExistingQSPMaster']);	
		$this->addHook('beforeDelete',[$this,'checkExistingJobCard']);	
		
	}
	function checkExistingJobCard(){
		$this->ref('xepan\production\Jobcard')->each(function($m){$m->delete();}));
	}
	function checkExistingQSPMaster(){
		$outsource_party_qsp_count = $this->ref('QSPMaster')->count()->getOne();
		if($outsource_party_qsp_count){
			throw new \Exception("First delete the invoice/order/.. of this outsource party");
			
		}	
	}

	function afterSave(){
		$this->app->hook('outsource_party_update',[$this]);
	}

	//activate OutsourceParty
	function activate(){
		$this['status']='Active';
		$this->app->employee
            ->addActivity("InActive OutsourceParty", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/)
            ->notifyWhoCan('activate','InActive');
		$this->save();
	}

	//deactivate OutsourceParty
	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("Active OutsourceParty", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/)
            ->notifyWhoCan('deactivate','Active');
		$this->save();
	}

	function ledger(){
		$account = $this->add('xepan\accounts\Model_Ledger')
				->addCondition('contact_id',$this->id)
				->addCondition('group_id',$this->add('xepan\accounts\Model_Group')->loadSundryDebtor()->fieldQuery('id'));
		$account->tryLoadAny();
		if(!$account->loaded()){
			$account['name'] = $this['name'];
			$account['AccountDisplayName'] = $this['name'];
			$account->save();
		}else{
			$account['name'] = $this['name'];
			$account['updated_at'] = $this->app->now;
			$account->save();
		}

		return $account;

	}
}