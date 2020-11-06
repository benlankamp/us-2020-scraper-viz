<html>
<head>
	<title>US Election 2020 - Battleground State Projections</title>
	
	<meta http-equiv="refresh" content="60">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<style type="text/css">
		.grid {
			display: grid;grid-template-columns: 50% 50%;
		}
		
		@media only screen and (max-width: 1000px) {
			.grid {
				display: grid;grid-template-columns: 100%;
			}
		}
		
		@media (min-width: 1900px) {
			.grid {
				grid-template-columns: 33% 33% 33%;
			}
		}
	</style>
	
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/epoch/0.8.4/css/epoch.min.css" />
	
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="https://code.highcharts.com/highcharts.js"></script>
	<script src="https://code.highcharts.com/modules/accessibility.js"></script>
</head>
<body style="background:#9EB1BE;font-family:Arial">
	<?php
	header("Cache-Control: public, max-age=30");

	$utcTZ = new DateTimeZone('UTC');
	$etTZ = new DateTimeZone('America/New_York');
	$ptTZ = new DateTimeZone('America/Los_Angeles');

	// get latest batches
	$handle = fopen("latest.txt", "r");
	if ($handle) {
		fgets($handle);
		fgets($handle);
		

		$latestBatch = fgets($handle);
		$batchGMT = new DateTime( substr( $latestBatch, 22, 18 ), $utcTZ );

		$batchET = clone $batchGMT;
		$batchET->setTimeZone($etTZ);
		
		$batchPT = clone $batchGMT;
		$batchPT->setTimeZone($ptTZ);
		
		fclose($handle);
	}

	$handle = fopen("changes.csv", "r");

	if ($handle) {
		?>
		<br />
		<div style="margin-left: auto; margin-right: auto; position: relative; width: 100%; margin-bottom: 25px">
		<h1>US Election 2020 - Battleground State Projections</h1>
		<b>Latest batch:</b><br />
		<?=$batchGMT->format("M j h:i A")?> UTC<br />
		(<small><?=$batchET->format("M j h:i A T")?>, <?=$batchPT->format("M j h:i A T")?></small>)<br />
		<br />
		The projection is crude, only taking the trend of the latest batches of reported votes and applying it to the estimated remaining votes.
		It does not take into account which precincts are remaining.<br />
		<br />
		<small>
		Prime source of data: <a href="https://www.nytimes.com">New York Times</a>, for more information go to the <a href="#data-license">bottom</a> of this page.
		Disclaimer: these are non-official projections.
		</small>
		<br />
		<br />
		
		<div class="grid">
		<?php
		// skip header
		fgets($handle);
		
		$lastState = "";
		
		while ( ($line = fgets($handle)) !== false ) {
			$splitted = explode(',',$line);
			// next line shows state name
			$stateName = explode(' (',$splitted[0])[0];

			if( $stateName != $lastState )
			{
				$lastState = $stateName;
				
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
					
					$trendOfTrailingCandidate = (100 - $trendOfTrailingCandidate);
				}
				
				$fontColor = "black";
				$finalMarginColor = "rgba(29,79,156,1)";
				if($finalMargin < 0) {
					$fontColor = "white";
					$finalMarginColor = "rgba(202,34,43,1)";
				}
				
				$trendOfTrailingCandidate = round($trendOfTrailingCandidate - 50,2);
				$chartName = '#' . strtolower($stateName) . '_graph';
				?>
				<div style="color: white; border: 1px solid black; background: <?=$finalMarginColor?>;padding:10px;margin-bottom:15px;margin-right:15px">
					<h2><?=$stateName?></h2>
					Currently <b><?=$currentLead?></b> leads. The margin of Biden is <?= number_format($currentMargin,0,".",",")?> votes. The trend towards Biden is <?=$trendOfTrailingCandidate?>% (negative = towards Trump).<br />
					<br />
					There are estimated to be <?= number_format($remaining,0,".",",") ?> votes left to count and/or report. Given the current trend, Biden will receive another <?= number_format($votesForBiden,0,".",",") ?> votes, while Trump will receive another <?= number_format($votesForTrump,0,".",",") ?> votes.<br />
					<br />
					<h3>Projected final margin for Biden: <u><?= $finalMargin < 0 ? "Lose" : "Win" ?></u> by <b><?= number_format(abs($finalMargin),0,".",",") ?></b> votes.</h3>

					<div style="background:white;width:99%;height:300px;border:2px solid transparent;border-radius:5px;padding-top:10px">
						<div id="<?=$chartName?>" style="width:100%;height:285px"></div>
						<span style="font-size:10px;color:black">&nbsp;&nbsp;Times on horizontal axis are in UTC</span>
						<script language="javascript">
							$.getJSON('get-state-graph.php?state=<?=strtolower($stateName)?>&v=' + (new Date()).getTime(), function(chartData) {
								measuredData = chartData[0];
								projectionData = chartData[1];
								
								Highcharts.chart('<?=$chartName?>', {
									credits: { enabled: false },
									legend: { enabled: true },
									chart: { backgroundColor: 'transparent', style: { fontFamily: 'Arial' } },
									title: { text: '' },
									xAxis: { 
										lineColor: 'black', tickColor: 'black', 
										tickInterval: 1000 * 60 * 60 * 6,
										minorTickInterval: 1000 * 60 * 60,
										gridLineWidth: 1, 
										gridLineColor: 'rgba(0,0,0,0.2)', 
										minorGridLineColor: 'rgba(0,0,0,0.05)', 
										gridLineWidth: 1,
										labels: { style: { color: 'black' } },
										type: 'datetime', 
										min: Date.UTC(2020,10,4,0,0,0),
										max: Date.now() + (2*60*60*1000) 
									},
									yAxis: { 
										min: -250000,
										max: 250000, 
										tickInterval: 25000, 
										lineColor: 'black', 
										tickColor: 'black', 
										gridLineColor: 'rgba(0,0,0,0.2)', 
										labels: { style: { color: 'black' } }, 
										title: { text: '' }, 
										plotLines: [{
											value: 0,
											width: 2,
											color: 'black',
											dashStyle: 'shortdash'
										}],
									},
									plotOptions: {
										line: {
											marker: {
												radius: 2
											},
											lineWidth: 3,
											threshold: null
										}
									},
									series: [
										{
											type: 'line',
											name: 'Actual margin',
											data: measuredData,
											color: 'black',
										},
										{
											type: 'line',
											name: 'Projected margin',
											data: projectionData,
											zones: [ { value: 0, color: 'rgba(202,34,43,1)' }, { value: 10000000, color: 'rgba(29,79,156,1)' } ]
										}
									]
								});
							});
						</script>
					</div>
				</div>
				<?php
			}
		}

		fclose($handle);
	} else {
		// error opening the file.
	} 
	?>
	</div>
	<br />
	<br />
	<a id="data-license"></a>
	<h4>Data source and license</h4>
	The data on this page is generated by the <a href="https://github.com/alex/nyt-2020-election-scraper">NYT 2020 Election Scraper</a>, maintained by Alex Gaynor and others.
	A local copy of the data is retrieved, stored and processed on this server, as permitted by the MIT license.<br />
	<br />
	<div style="font-family:Courier">
	MIT License<br />
	<br />
	Copyright (c) 2020 Individual Contributors<br />
	<br />
	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:<br />
	<br />
	The above copyright notice and this permission notice shall be included in all
	copies or substantial portions of the Software.<br />
	<br />
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
	SOFTWARE.
	</div>
	<br />
	<br />

</body>
</html>