<?php

namespace xepan\production;

class page_dashboard extends \xepan\base\Page{
	public $title = "Dashboard";
	function init(){
		parent::init();

		$jobcard = $this->add('xepan\production\Model_Jobcard');
		$jobcard->addExpression('department_name')->set(function($m,$q){
			return $this->add('xepan\hr\Model_Department')
						->addCondition('id',$m->getElement('department_id'))
						->setLimit(1)
						->fieldQuery('name');	
		});

		$jobcard->_dsql()->group('department_name');
		$jobcard->addExpression('count','count(*)');

		$grid = $this->add('xepan\base\Grid',null,null,['view\grid\dashboard']);
		$grid->setModel($jobcard);
		
		$color = [
					0=>"emerald", 
					1=>"green",
					2=>"red",
					3=>"yellow",
					4=>"purple",
					5=>"gray" 
				 ];
				 
		$this->count = 0;		 
		$grid->addHook('formatRow',function($g) use($color){
			if($this->count > 5) $this->count = 0;
			$g->current_row_html['bg'] = $color[$this->count].'-bg';	
			$this->count++;			
		});
	}
} 
