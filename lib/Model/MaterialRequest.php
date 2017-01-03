<?php

namespace xepan\production;

class Model_MaterialRequest extends \xepan\commerce\Model_Store_TransactionAbstract{
	public $status = ['Draft','Submitted','Received','Rejected','PartialComplete','WaitingReceive','Complete'];
	public $actions = [
					'Draft'=>['view','edit','delete','submit'],
					'Submitted'=>['view','edit','delete','receive','reject'],
					'Received'=>['view','edit','delete','dispatch','complete'],
					'Rejected'=>['view','edit','delete','redraft'],
					'PartialComplete'=>['view','edit','delete','dispatch'],
					'WaitingReceive'=>['view','edit','delete','dispatch'],
					'Complete'=>['view','edit','delete']
					];

	function init(){
		parent::init();

		$this->addCondition('type','MaterialRequestSend');
		$this->getElement('status')->defaultValue('Draft');

		$this->addExpression('item_count')->set($this->refSql('StoreTransactionRows')->count());
	}

	function submit(){

		if(!$this['item_count']) throw new \Exception("please add request item, to submit");

		$this['status'] = "Submitted";
		$this->save();
		$this->app->employee
			->addActivity(
							$this['from_warehouse']." Warehouse Submitted New Material Request to Warehouse ".$this['to_warehouse'],
						 	$this->id/* Related Document ID*/, 
						 	$this->app->auth->model->id /*Related Contact ID*/,
						 	null,
						 	null,
						 	"xepan_production_materialrequest&department_id=".$this['department_id'].""
						)
			->notifyWhoCan('receive,reject','Submitted',$this);
	}


	function receive(){

		$items = $this->requestItem();
		foreach ($items as $item) {
			$item['status'] = "Received";
			$item->save();
		}

		$this['status'] = "Received";
		$this->save();
	}

	function reject(){
		$this['status'] = "Rejected";
		$this->save();
	}

	function page_dispatch($page){

		$page->add('View')->setElement('h3')->set("Transfer Stock To warehouse ".$this['from_warehouse']);

		$form = $page->add('Form_Stacked',null,null,array('form/minimal'));

	    $th = $form->add('Columns')->addClass('row');
	    
	    $th_name =$th->addColumn(5)->addClass('col-md-5');
	    $th_name->add('H4')->set('Items');

	    $th_unit =$th->addColumn(1)->addClass('col-md-1');
	    $th_unit->add('H4')->set('Unit');

	    $th_qty =$th->addColumn(2)->addClass('col-md-2');
	    $th_qty->add('H4')->set('Request Qty');
	    
	    $th_pre_dispatched_qty = $th->addColumn(2)->addClass('col-md-2');
	    $th_pre_dispatched_qty->add('H4')->set('Pre Dispatched Qty');
	    
	    $th_dispatched_qty = $th->addColumn(2)->addClass('col-md-2');
	    $th_dispatched_qty->add('H4')->set('Dispatched Qty');
   		
   		$request_items = $this->requestItem();

   		// $page->add('Grid')->setModel($request_items);

	    foreach ($request_items as $ri) {

	      	$c = $form->add('Columns')->addClass('row');
			$c1 = $c->addColumn(5)->addClass('col-md-5');
			$c1->addField('line','item_name_'.$ri->id)->set($ri['item'])->setAttr('disabled','disabled');

			$c2 = $c->addColumn(1)->addClass('col-md-1');
			$c2->addField('line','item_unit_'.$ri->id)->set($ri['unit'])->setAttr('disabled','disabled');

			$c3 = $c->addColumn(2)->addClass('col-md-2');
			$c3->addField('line','item_qty_'.$ri->id)->set($ri['quantity'])->setAttr('disabled','disabled');
			$c3->addField('hidden','item_qty_hidden'.$ri->id)->set($ri['quantity']);
			
			$c4 = $c->addColumn(2)->addClass('col-md-2');
			$c4->addField('line','item_pre_dispatched_qty_'.$ri->id)->set($ri['pre_dispatched']?:0)->setAttr('disabled','disabled');
			$c4->addField('hidden','item_pre_dispatched_qty_hidden_'.$ri->id)->set($ri['pre_dispatched']?:0);

			$c5 = $c->addColumn(2)->addClass('col-md-2');
			$c5->addField('Number','item_dispatch_qty_'.$ri->id)->set(0);

			$form->add('View')->setStyle('height','10px');
	    }
	    
	    $form->addSubmit('send to warehouse '.$this['from_warehouse'])->addClass('btn btn-primary');

	    if($form->isSubmitted()){
	     	$check = 1;
	      	
	      	foreach ($request_items as $ri) {
	        	$qty_to_receive = $ri['quantity'] - $ri['received_qty'];
	        	if($form['item_dispatch_qty_'.$ri->id] > $qty_to_receive){
	            	$form->displayError('item_dispatch_qty_'.$ri->id,'dispatch qty must be smaller then order qty');
	    	    }
	      	}
				      	
	      	// create new stock movement transaction
	      	$transaction = $this->add('xepan\production\Model_MaterialRequestDispatch');
	  		$transaction['from_warehouse_id'] = $this['to_warehouse_id'];
	  		$transaction['to_warehouse_id'] = $this['from_warehouse_id'];
	  		$transaction['department_id'] = $this['department_id'];
	  		$transaction['type'] = "MaterialRequestDispatch";
	  		$transaction['related_transaction_id'] = $this->id;
	  		$transaction['status'] = "WaitingReceive";
	  		$transaction->save();
	  		
	      	foreach ($request_items as $ri) {
				$new_item = $this->add('xepan\commerce\Model_Store_TransactionRow');
				$new_item['store_transaction_id'] = $transaction->id;
				$new_item['item_id'] = $ri['item_id'];
				$new_item['quantity'] = $form['item_dispatch_qty_'.$ri->id];
				$new_item['extra_info'] = $ri['extra_info'];
				$new_item['status'] = 'ToReceived';
				$new_item['related_transaction_row_id'] = $ri->id;
				$new_item->save();
	    	}

	    	$this['status'] = "WaitingReceive";
	    	$this->save();
	  	  	$this->app->page_action_result = $form->js(null,$form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Request Items Send To Warehouse');
	    }

	}

	function page_redraft($page){

	}

	function redraft(){

	}

	function requestItem(){		
		$row = $this->add('xepan\commerce\Model_Store_TransactionRow');
		$row->addCondition('store_transaction_id',$this->id);
		$row->addExpression('unit')->set($row->refSQL('item_id')->fieldQuery('qty_unit'));

		$row->addExpression('pre_dispatched')->set(function($m,$q){
			$mrd = $m->add('xepan\commerce\Model_Store_TransactionRow',['table_alias'=>'str_dispatched'])
					->addCondition('related_transaction_row_id',$q->getField('id'))
					->sum('quantity');
			return $q->expr("IFNULL([0], 0)",[$mrd]);
		});

		$row->addExpression('dispatched_received')->set(function($m,$q){
			$mrd = $m->add('xepan\commerce\Model_Store_TransactionRow',['table_alias'=>'strd_received'])
					->addCondition('related_transaction_row_id',$m->getElement('id'))
					->addCondition('status','Received')
					->sum('quantity');
			return $q->expr("IFNULL([0], 0)",[$mrd]);
		});

		$row->addExpression('qty_to_receive')->set(function($m,$q){
			return $q->expr('[0]-[1]',[$m->getElement('quantity'),$m->getElement('dispatched_received')]);
		})->type('Number');

		return $row;
	}

}