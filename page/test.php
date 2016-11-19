<?php

namespace xepan\production;


/**
* 
*/
class page_test extends \xepan\base\Page{
	
	function init(){
		parent::init();
		if(!$document_id = $_GET['jobcard_id'])
				throw $this->exception('Document Id not found in Query String');

		$document= $this->add('xepan\production\Model_Jobcard')->load($document_id);

		// $document= $this->add('xepan\commerce\Model_'.$document['type']);
		// $document->load($document_id);
		
		$document->generatePDF('dump');
	}
}