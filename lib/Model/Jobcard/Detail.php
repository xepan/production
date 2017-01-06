<?php

namespace xepan\production;

class Model_Jobcard_Detail extends \xepan\base\Model_Table{
	public $table = "jobcard_detail";
	public $status = ['ToReceived','Received','Forwarded','Completed','Dispatched','ReceivedByDispatch','ReceivedByNext'];

	function init(){
		parent::init();

		$this->hasOne('xepan\production\Jobcard','jobcard_id');
		$this->addField('quantity')->hint('converted quantity');
		$this->addField('parent_detail_id')->defaultValue(0); //parent jobcard detail id
		$this->addField('status');
		$this->hasMany('xepan\commerce\Store_TransactionRow','jobcard_detail_id');
		
		$this->addHook('beforeDelete',[$this,'checkExistingRelatedTransactionRow']);

		$this->addExpression('item_name')->set($this->refSQL('jobcard_id')->fieldQuery('order_item'));
	}

	function checkExistingRelatedTransactionRow(){
		$this->ref('xepan\commerce\Store_TransactionRow')->each(function($m){$m->delete();});
	}


	function received(){
		if(!$this->loaded())
			throw $this->exception("model must be loaded ")
				->addMoreInfo('jobcard Detail model for Completed');
		
		$this['status'] = "Received";
		$this->save();

		if($this['parent_detail_id'])
			$this->add('xepan\production\Model_Jobcard_Detail')->load($this['parent_detail_id'])->receivedByNext();
	}

	function jobcard(){
		$this->ref('jobcard_id');
	}

	function complete(){
		if(!$this->loaded())
			throw $this->exception();
			
		$this['status'] = "Completed";
		$this->save();
		$jobcard=$this->add('xepan\production\Model_Jobcard')
				->load($this['jobcard_id']);
		
		if($jobcard->checkAllDetailComplete()){
			$jobcard->complete();
		}
		
	}

	function receivedByNext(){
		if(!$this->loaded())
			throw $this->exception();

		$new_jd = $this->add('xepan\production\Model_Jobcard_Detail');
		$new_jd['quantity'] = $this['quantity']; 
		$new_jd['parent_detail_id'] = $this['parent_detail_id']; 
		$new_jd['jobcard_id'] = $this['jobcard_id'];
		$new_jd['status'] = "ReceivedByNext";
		$new_jd->save();
		return $new_jd;
	}

}
