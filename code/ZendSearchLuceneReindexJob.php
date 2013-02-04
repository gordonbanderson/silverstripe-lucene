<?php

/**
 * The job description class for reindexing the search index via the Queued Jobs 
 * SilverStripe module.
 * 
 * @package lucene-silverstripe-module
 * @author Darren Inwood <darren.inwood@chrometoaster.com>
 */
class ZendSearchLuceneReindexJob extends AbstractQueuedJob implements QueuedJob {

    public function getTitle() {
        return _t('ZendSearchLucene.ReindexJobTitle', 'Rebuild the Lucene search engine index');
    }

    public function getSignature() {
        return 'ZendSearchLuceneReindexJob';
    }

    public function setup() {
        // Wipe current index
        ZendSearchLuceneWrapper::getIndex(true);
        $indexed = ZendSearchLuceneWrapper::getAllIndexableObjects();
        error_log("INDEXABLE OBJECTS:".count($indexed));
        $this->remainingDocuments = $indexed;
        $this->totalSteps = count($indexed);
    }

    public function process() {
    	error_log("++++ REINDEX JOB ++++");
    	$batchSize = 100;
		$remainingDocuments = $this->remainingDocuments;

		error_log("REMAINING DOCUMENTS - TO GO:".count($remainingDocuments));

		// if there's no more, we're done!
		if (!count($remainingDocuments)) {
			$this->isComplete = true;
			error_log("RETURN FROM REINDEX AS DOCS ALL DONE - T1");
			return;
		}
		
		$items = array();
		for ($i=0; $i < $batchSize; $i++) {

        	
			$item = array_shift($remainingDocuments);
			error_log('SIFT ITEM '.$i.": ".$item[0]. ' --> ID '.$item[1]);
			

			$className = $item[0];
			if(!isset($items[$className])) {
				$items[$className] = array();
			}

			$classNameItems = $items[$className];

			array_push($classNameItems, $item);
			$items[$className] = $classNameItems;

			if (count($remainingDocuments) == 0) {
				error_log("NO REMAINING DOCS");
				break;
			}
		}

		error_log("****** ITEMS *****");
		print_r($items);


		foreach ($items as $className => $classNameItems) {
			$ids = array();
			foreach ($classNameItems as $item) {
				// push the id
				array_push($ids, $item[1]);
			}

			$idsCSV = implode(',', $ids);

			error_log("$className => IDS ".$idsCSV);

			$startTime = time();

			//$idsCSV = '28516,28669,29073,29098,29514,29692,29777,30006,30029,30380,30401,30645,30675,30725,30776,30815,30909,30918,31460,31612,31630,31685,31874,32359,34593,34827,34664,34696,34911,35534,35682,35033,35809,36024,36155,35853,35923,36129,36198,36237,35882,35965,36396,36507,36603,36622,36702,36854';

			$objects = DataList::create($className)->where("ID in (".$idsCSV.")");
			error_log("N OBJECTS FOUND FOR $className: ".$objects->count());
			foreach ($objects as $obj) {
				//error_log("INDEXING $className:".$obj->ID);
        		ZendSearchLuceneWrapper::index($obj,false);
        		$this->currentStep++;

			}

			// commit after the batch
			ZendSearchLuceneWrapper::getIndex()->commit();

			$elapsedTime = time()-$startTime;
        	error_log("INDEX TIME FOR $batchSize objects:".$elapsedTime);

		}


		
	
		

		// and now we store the new list of remaining children
		$this->remainingDocuments = $remainingDocuments;

		$nRemaining = count($remainingDocuments);
			echo 'Documents remaining to index:'.$nRemaining."\n";
		
		if (!count($remainingDocuments)) {
			$this->isComplete = true;
			return;
		}    
    }

}


