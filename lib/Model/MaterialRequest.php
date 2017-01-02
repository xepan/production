<?php

namespace xepan\production;

class Model_MaterialRequest extends \xepan\commerce\Model_Store_TransactionAbstract{

	public $status = ['Draft','Submit','Received','Reject','PartialComplete','WaitingReceive','Complete'];
	public $actions = [
					'Draft'=>['view','edit','delete','submit'],
					'Submit'=>['view','edit','delete','receive','reject'],
					'Received'=>['view','edit','delete','dispatch','complete'],
					'Reject'=>['view','edit','delete','redraft'],
					'PartialComplete'=>['view','edit','delete','dispatch']
					];
	function init(){
		parent::init();

		$this->addCondition('type','MaterialRequest');
	}

	function page_submit($page){

	}

	function submit(){

	}

	function page_receive($page){

	}

	function receive(){

	}

	function page_reject($page){

	}

	function reject(){

	}

	function page_dispatch($page){

	}

	function dispatch(){

	}

	function page_redraft($page){

	}

	function redraft(){

	}
}