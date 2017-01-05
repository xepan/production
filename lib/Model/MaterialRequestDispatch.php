<?php

namespace xepan\production;

class Model_MaterialRequestDispatch extends \xepan\commerce\Model_Store_TransactionAbstract{
	public $status = ['Received','WaitingReceive'];
	public $actions = [
					'WaitingReceive'=>['view','edit','delete','receive'],
					'Received'=>['view','edit','delete']
					];

	function init(){
		parent::init();

		$this->addCondition('type','MaterialRequestDispatch');

	}


	function page_receive($page){

		$page->add('View')->setElement('h3')->set("Item Receive From warehouse ".$this['to_warehouse']);

		$form = $page->add('Form_Stacked',null,null,array('form/minimal'));

	    $th = $form->add('Columns')->addClass('row');
	    
	    $th_name =$th->addColumn(5)->addClass('col-md-5');
	    $th_name->add('H4')->set('Items');

	    $th_unit =$th->addColumn(1)->addClass('col-md-1');
	    $th_unit->add('H4')->set('Unit');

	    $th_qty =$th->addColumn(2)->addClass('col-md-2');
	    $th_qty->add('H4')->set('Request Qty');
	    
	    $th_pre_received_qty = $th->addColumn(2)->addClass('col-md-2');
	    $th_pre_received_qty->add('H4')->set('Pre Received Qty');

	    $th_dispatched_qty = $th->addColumn(2)->addClass('col-md-2');
	    $th_dispatched_qty->add('H4')->set('Received Qty');
   		
   		$received_items = $this->receivedItem();

	    foreach ($received_items as $ri) {

	      	$c = $form->add('Columns')->addClass('row');
			$c1 = $c->addColumn(5)->addClass('col-md-5');
			$c1->addField('line','item_name_'.$ri->id)->set($ri['item'])->setAttr('disabled','disabled');

			$c2 = $c->addColumn(1)->addClass('col-md-1');
			$c2->addField('line','item_unit_'.$ri->id)->set($ri['unit'])->setAttr('disabled','disabled');

			$c3 = $c->addColumn(2)->addClass('col-md-2');
			$c3->addField('line','item_qty_'.$ri->id)->set($ri['request_qty'])->setAttr('disabled','disabled');
			$c3->addField('hidden','item_qty_hidden'.$ri->id)->set($ri['request_qty']);
			
			$c4 = $c->addColumn(2)->addClass('col-md-2');
			$c4->addField('line','item_pre_dispatched_qty_'.$ri->id)->set($ri['pre_received']?:0)->setAttr('disabled','disabled');
			// $c4->addField('hidden','item_pre_dispatched_qty_hidden_'.$ri->id)->set($ri['pre_received']?:0);

			$c5 = $c->addColumn(2)->addClass('col-md-2');
			$c5->addField('line','item_received_qty_'.$ri->id)->set($ri['quantity'])->setAttr('disabled','disabled');

			$form->add('View')->setStyle('height','10px');
	    }
	    
	    $form->addSubmit('Receive')->addClass('btn btn-primary');
	    
     //   	$mr = $this->add('xepan\production\Model_MaterialRequest')->load($this['related_transaction_id']);    	
    	// $items = $mr->requestItem();
    	// $page->add('Grid')->setModel($items);

	    if($form->isSubmitted()){

	    	$items = $this->receivedItem();
			
			foreach ($items as $item) {
				$item['status'] = "Received";
				$item->save();

				// stock entry
		    	$item_model = $this->add('xepan\commerce\Model_Item')->load($item['item_id']);
		    	$cf = [];
		    	$custom_field_combination = $item_model->convertCustomFieldToKey(json_decode($item['extra_info'],true));
		    	if($custom_field_combination)
					$cf = $this->convertCFKeyToArray($custom_field_combination);

		    	foreach ($cf as $custom_field_id => $cf_array) {
					if(!is_array($cf_array)) continue;
					
				 	$m = $this->add('xepan\commerce\Model_Store_TransactionRowCustomFieldValue');
					$m['customfield_generic_id'] = $custom_field_id; 
					$m['customfield_value_id']= $cf_array['custom_field_value_id']; 
					$m['custom_name'] = $cf_array['custom_field_name'];
					$m['custom_value'] = $cf_array['custom_field_value_name'];
					$m['store_transaction_row_id'] = $item->id;
					$m->save();
				}
			}
	    	$this['status'] = "Received";
	    	$this->save();

	    	$mr = $this->add('xepan\production\Model_MaterialRequest')->load($this['related_transaction_id']);
	    	$mr['status'] = "Complete";
	    	
	    	$items = $mr->requestItem();
	    	foreach ($items as $item) {
	    		if($item['qty_to_receive'] > 0){
	    			$mr['status'] = "PartialComplete";
					break;
	    		}
	    	}
	    	$mr->save();

	    	$this->app->page_action_result = $form->js(null,$form->js()->closest('.dialog')->dialog('close'))->univ()->successMessage('Item Received from warehouse '.$this['to_warehouse']);
	    }


	}

	function receivedItem(){
		$row = $this->add('xepan\commerce\Model_Store_TransactionRow');
		$row->addCondition('store_transaction_id',$this->id);
		$row->addExpression('unit')->set($row->refSQL('item_id')->fieldQuery('qty_unit'));

		$row->addExpression('pre_received')->set(function($m,$q){
			$mrd = $m->add('xepan\commerce\Model_Store_TransactionRow',['table_alias'=>'mrdp_received'])
					->addCondition('related_transaction_row_id',$m->getElement('id'))
					->addCondition('status',"Received")
					->sum('quantity');
			return $q->expr("IFNULL([0], 0)",[$mrd]);
		});

		$row->addExpression('request_qty')->set(function($m,$q){
			$rqr = $m->add('xepan\commerce\Model_Store_TransactionRow',['table_alias'=>'rqr_m'])
					->addCondition('id',$m->getElement('related_transaction_row_id'))
					->sum('quantity');
			return $q->expr("IFNULL([0], 0)",[$rqr]);
		});

		return $row;
	}
}