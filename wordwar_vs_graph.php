<?php
require('shared.php');

require_once('jpgraph/jpgraph.php');
require_once('jpgraph/jpgraph_bar.php');

$days[1] = array();
$days[2] = array();

for ($i = 1; $i <= 30; $i++) {
	$days[1]['2009-11-'.sprintf("%02d", $i)] = 0;
	$days[2]['2009-11-'.sprintf("%02d", $i)] = 0;
}

$team_count = array();

$res = mysql_query("SELECT team_id, count(*) as total FROM ml_wc_cache GROUP BY team_id");
while ($row = mysql_fetch_assoc($res)) {
	$team_count[$row['team_id']] = $row['total'];
}

$data = array(1 => array(), 2 => array(), 3 => array());

$res = mysql_query("SELECT ml_wc_history.*, ml_wc_cache.team_id FROM ml_wc_history INNER JOIN ml_wc_cache USING (uid) ORDER BY ml_wc_history.uid ASC, date ASC");
while ($row = mysql_fetch_assoc($res)) {
	if (!isset($data[$row['team_id']][$row['date']])) {
		$data[$row['team_id']][$row['date']] = 0;
	}
	$data[$row['team_id']][$row['date']] += $row['count'];
}

for ($i = 1; $i <= 3; $i++) {
	foreach ($data[$i] AS $key => $value) {
		$days[$i][$key] = $value / $team_count[$i];
	}
}

$max_today = max($days[1]['2009-11-'.date("d")], $days[2]['2009-11-'.date("d")]);
$average = $max_today / (int) date("d");

$estimate = $average * 30;

$goal = ceil($estimate / 10000) * 10000;

// Create the graph. These two calls are always required
$graph = new Graph(900,450,"auto");
$graph->SetScale("textlin", 0, $goal);

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

// Create a bar pot
$bplot1 = new BarPlot(array_values($days[1]));
$bplot1->SetFillColor("orange");
$bplot1->SetLegend("MLs");
//$bplot1->value->Show();
//$bplot1->value->SetFont(FF_ARIAL,FS_NORMAL,5);
//$bplot1->value->SetAngle(90);
//$bplot1->value->SetFormatCallback('format_graph_label');

$bplot2 = new BarPlot(array_values($days[2]));
$bplot2->SetFillColor("blue");
$bplot2->SetLegend("Staff");
//$bplot2->value->Show();
//$bplot2->value->SetFont(FF_ARIAL,FS_NORMAL,5);
//$bplot2->value->SetAngle(90);
//$bplot2->value->SetFormatCallback('format_graph_label');

$gbplot = new GroupBarPlot(array($bplot1, $bplot2));

$graph->Add($gbplot);

// Setup the titles
$graph->title->Set("Daily Average Wordcount");

$graph->title->SetFont(FF_FONT1,FS_BOLD);

$graph->xaxis->HideTicks();

$graph->legend->SetPos(0.1, 0.12, "left", "top");

// Display the graph
$graph->Stroke();

?>
