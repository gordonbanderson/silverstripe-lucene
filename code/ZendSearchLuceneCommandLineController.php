<?php

class ZendSearchLuceneCommandLineController extends Page_Controller {
	static $allowed_actions = array(
		'search',
		'optimize'
	);


	function search() {
		echo 'search';
	}

	function optimize() {
		$index = ZendSearchLuceneWrapper::getIndex();

	    // Retrieving index size
	    $indexSize = $index->count();
	    $documents = $index->numDocs();

	    echo "INDEX SIZE:".$indexSize."\n";
	    echo "NUMBER OF DOCUMENTS:".$documents."\n";

	    $terms = $index->terms();
	    foreach($terms as $term) {
	    	error_log("TERM:".$term->field . ' => '. $term->text);
	    }



	    // Index optimisation
	    $index->optimize();
	}
}
?>
