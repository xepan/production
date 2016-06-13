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
		$osp_j->addField('remark')->type('text');
		
		$osp_j->hasMany('xepan\production\Jobcard','outsourceparty_id');

		$this->hasMany('xepan/commerce/Model_QSP_Master',null,null,'QSPMaster');
		$this->addCondition('type','OutsourceParty');
		//TODO Extra Organization Specific Fields other Contacts
		$this->getElement('status')->defaultValue('Active');
		// $this->addHook('beforeSave',$this);		
		$this->addHook('afterSave',$this);
		$this->addHook('beforeDelete',[$this,'checkExistingQSPMaster']);	
		$this->addHook('beforeDelete',[$this,'checkExistingJobCard']);
		$this->addHook('beforeSave',[$this,'updateSearchString']);	
		
	}
	function checkExistingJobCard(){
		$this->ref('xepan\production\Jobcard')->each(function($m){$m->delete();});
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
            ->addActivity("OutsourceParty '".$this['contact']."' now active", null/* Related Document ID*/, $this->id /*Related Contact ID*/)
            ->notifyWhoCan('activate','InActive',$this);
		$this->save();
	}

	//deactivate OutsourceParty
	function deactivate(){
		$this['status']='InActive';
		$this->app->employee
            ->addActivity("OutsourceParty '".$this['contact']."' has deactivated", null/* Related Document ID*/, $this->id /*Related Contact ID*/)
            ->notifyWhoCan('deactivate','Active',$this);
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

	function updateSearchString($m){

		$search_string = ' ';
		$search_string .=" ". $this['outsource_party_id'];
		$search_string .=" ". $this['bank_name'];
		$search_string .=" ". $this['pan_it_no'];
		$search_string .=" ". $this['tin_no'];
		$search_string .=" ". $this['account_no'];
		$search_string .=" ". $this['os_address'];
		$search_string .=" ". $this['os_city'];
		$search_string .=" ". $this['os_state'];
		$search_string .=" ". $this['os_country'];
		$search_string .=" ". $this['os_pincode'];
		$search_string .=" ". $this['pan_no'];
		$search_string .=" ". $this['tin_no'];
		$search_string .=" ". $this['name'];
		$search_string .=" ". str_replace("<br/>", " ", $this['contacts_str']);
		$search_string .=" ". str_replace("<br/>", " ", $this['emails_str']);
		$search_string .=" ". $this['source'];
		$search_string .=" ". $this['type'];
		$search_string .=" ". $this['city'];
		$search_string .=" ". $this['state'];
		$search_string .=" ". $this['pin_code'];
		$search_string .=" ". $this['organization'];
		$search_string .=" ". $this['post'];
		$search_string .=" ". $this['website'];

		if($this->loaded()){
			$qsp_master = $this->ref('QSPMaster');
			foreach ($qsp_master as $all_qsp_detail) {
				$search_string .=" ". $all_qsp_detail['qsp_master_id'];
				$search_string .=" ". $all_qsp_detail['document_no'];
				$search_string .=" ". $all_qsp_detail['from'];
				$search_string .=" ". $all_qsp_detail['total_amount'];
				$search_string .=" ". $all_qsp_detail['gross_amount'];
				$search_string .=" ". $all_qsp_detail['net_amount'];
				$search_string .=" ". $all_qsp_detail['narration'];
				$search_string .=" ". $all_qsp_detail['exchange_rate'];
				$search_string .=" ". $all_qsp_detail['tnc_text'];
			}

			$jobcard = $this->ref('xepan\production\Jobcard');
			foreach ($jobcard as $jobcard_detail) {
				$search_string .=" ". $jobcard_detail['order_no'];
				$search_string .=" ". $jobcard_detail['customer_id'];
				$search_string .=" ". $jobcard_detail['customer_name'];
				$search_string .=" ". $jobcard_detail['order_item_name'];
				$search_string .=" ". $jobcard_detail['order_item_quantity'];
				$search_string .=" ". $jobcard_detail['days_elapsed'];
				$search_string .=" ". $jobcard_detail['forwarded'];
			}
		}

		$this['search_string'] = $search_string;
	}

	function quickSearch($app,$search_string,$view){		
		$this->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
		$this->addCondition('Relevance','>',0);
 		$this->setOrder('Relevance','Desc');
 		if($this->count()->getOne()){
 			$oc = $view->add('Completelister',null,null,['view/grid/quicksearch-production-grid']);
 			$oc->setModel($this);
    		$oc->addHook('formatRow',function($g){
    			$g->current_row_html['url'] = $this->app->url('xepan_production_outsourcepartiesdetails',['contact_id'=>$g->model->id]);	
     		});	
		}

		$jobcard = $this->add('xepan\production\Model_Jobcard');
		$jobcard->addExpression('Relevance')->set('MATCH(search_string) AGAINST ("'.$search_string.'" IN NATURAL LANGUAGE MODE)');
		$jobcard->addCondition('Relevance','>',0);
 		$jobcard->setOrder('Relevance','Desc');
 		if($jobcard->count()->getOne()){
 			$jc = $view->add('Completelister',null,null,['view/grid/quicksearch-production-grid']);
 			$jc->setModel($jobcard);
    		$jc->addHook('formatRow',function($g){
    			$g->current_row_html['url'] = $this->app->url('xepan_production_jobcardorder');	
     		});	
		}
	}
}