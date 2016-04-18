<?php

namespace xepan\production;

class Model_Jobcard_Detail extends \xepan\base\Model_Table{
	public $table = "jobcard_detail";
	public $status = ['ToReceived','Received','Forwarded','Completed'];

	function init(){
		parent::init();

		$this->hasOne('xepan\production\Model_Jobcard','jobcard_id');
		$this->addField('quantity'); 
		$this->addField('parent_detail_id')->defaultValue(0); //parent jobcard detail id
		$this->addField('status');
		$this->hasMany('xepan\commerce\Store_TransactionRow','jobcard_detail_id');

		$this->addHook('beforeDelete',[$this,'checkExistingRelatedTransactionRow']);

	}

	function checkExistingRelatedTransactionRow($m){
		$m->ref('xepan\commerce\Store_TransactionRow')->deleteAll();
	}


	function received(){
		if(!$this->loaded())
			throw $this->exception("model must be loaded ")
				->addMoreInfo('jobcard Detail model for Completed');
		
		$this['status'] = "Received";
		$this->save();

		if($this['parent_detail_id'])
			$this->add('xepan\production\Model_Jobcard_Detail')->load($this['parent_detail_id'])->complete();
	}

	function jobcard(){
		$this->ref('jobcard_id');
	}

	function complete(){
		if(!$this->loaded())
			throw $this->exception();
			
		$this['status'] = "Completed";
		$this->save();

		if($this['jobcard_id'] and $this->jobcard()->checkAllDetailComplete()){
			$this->jobcard()->complete();
		}



	}

}
