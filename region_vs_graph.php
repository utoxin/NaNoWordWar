<?php
require('shared.php');

require_once('jpgraph/jpgraph.php');
require_once('jpgraph/jpgraph_line.php');
require_once('jpgraph/jpgraph_regstat.php');

$max = 0;

$res = mysql_query("SELECT * FROM wc_regions ORDER BY rname");
while ($row = mysql_fetch_assoc($res)) {
	$res2 = mysql_query("SELECT * FROM wc_region_history WHERE rid = {$row['rid']} ORDER BY wcdate");
	while ($row2 = mysql_fetch_assoc($res2)) {
		$day = date("j", strtotime($row2['wcdate']));
		$teams[$row['rname']][$day] = $row2['wc'] / $row2['count'];
		if ($max < $teams[$row['rname']][$day]) {
			$max = $teams[$row['rname']][$day];
		}
	}
}

$goal = ceil($max / 10000) * 10000;

// Create the graph. These two calls are always required
$graph = new Graph(600,300,"auto");
$graph->SetScale("linlin");
$graph->xaxis->SetLabelFormat('%02d');

// Add a drop shadow
$graph->SetShadow();

// Adjust the margin a bit to make more room for titles
$top = 25;
$bottom = 35;
$left = 50;
$right = 25;
$graph->img->SetMargin($left,$right,$top,$bottom);

function format_graph_label($number) {
	if ($number) {
		return number_format($number);
	}
	return "";
}

$group = array();

$colors = array('blue','azure3','brown','darkorchid3','limegreen','orange');

foreach ($teams AS $team => $day_counts) {
	$days = array_keys($day_counts);
	$counts = array_values($day_counts);
	$spline = new Spline($days,$counts);
	list ($x, $y) = $spline->Get(count($days)*10);

	$lplot = new LinePlot($y, $x);
	$lplot->SetLegend($team);
	$lplot->SetColor(array_pop($colors));
	$lplot->SetWeight(2);

	$graph->Add($lplot);
}

// Setup the titles
$graph->title->Set("Daily Average Wordcount");

$graph->title->SetFont(FF_FONT1,FS_BOLD);

$graph->xaxis->HideTicks();

$graph->legend->SetPos(0.1, 0.12, "left", "top");

// Display the graph
$graph->Stroke();

?>
