<?php
header('Cache-Control: public, max-age=30');
header('Content-type: application/json');

$requestState = 'pennsylvania';
if( isset($_GET['state']) )$requestState = $_GET['state'];
$utcTZ = new DateTimeZone('UTC');

$handle = fopen("changes.csv", "r");

if ($handle) {
	// skip header
	fgets($handle);
	
	$lastState = "";
	$actualDataArray = [];
	$projectionDataArray = [];
	
	while ( ($line = fgets($handle)) !== false ) {
		$splitted = explode(',',$line);
		$stateName = strtolower( explode(' (',$splitted[0])[0] );

		if( $stateName == $requestState )
		{
			$lastState = $stateName;
			
			$batchGMT = new DateTime( $splitted[1], $utcTZ );
			
			// calculate final margin
			$currentLead = $splitted[2];
			$currentMargin = intval( $splitted[6] );
			$remaining = intval( $splitted[7] );
			$trendOfTrailingCandidate = floatval( $splitted[15] ) * 100.0;
			
			// calculate end margins
			if($currentLead == "Trump") {
				$currentMargin = -$currentMargin;
				$votesForBiden = round(($trendOfTrailingCandidate/100.0) * $remaining);
				$votesForTrump = round($remaining - $votesForBiden);
				$finalMargin = $currentMargin + $votesForBiden - $votesForTrump;
			} else {
				// Biden currently leads
				// calculate end margins
				$votesForTrump = round(($trendOfTrailingCandidate/100.0) * $remaining);
				$votesForBiden = round($remaining - $votesForTrump);
				$finalMargin = $currentMargin + $votesForBiden - $votesForTrump;
				
				if($trendOfTrailingCandidate > 50) $trendOfTrailingCandidate = (100 - $trendOfTrailingCandidate);
			}
			
			array_push( $actualDataArray, array( 'x' => $batchGMT->getTimestamp()*1000, 'y' => $currentMargin ) );
			array_push( $projectionDataArray, array( 'x' => $batchGMT->getTimestamp()*1000, 'y' => $finalMargin ) );
		}
	}
	
	usort($actualDataArray, function ($a, $b) {
		return $a > $b;
	});
	
	usort($projectionDataArray, function ($a, $b) {
		return $a > $b;
	});
	
	echo json_encode( [ $actualDataArray, $projectionDataArray ] );
}
?>