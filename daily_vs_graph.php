<?php
require('shared.php');

require_once('jpgraph/jpgraph.php');
require_once('jpgraph/jpgraph_bar.php');

$days[1] = array();
$days[2] = array();

for ($i = 1; $i <= 30; $i++) {
	$days[1]['2008-11-'.sprintf("%02d", $i)] = 0;
	$days[2]['2008-11-'.sprintf("%02d", $i)] = 0;
}

$team_count = array();

$res = mysql_query("SELECT team_id, count(*) as total FROM wc_cache GROUP BY team_id");
while ($row = mysql_fetch_assoc($res)) {
	$team_count[$row['team_id']] = $row['total'];
}

$last = array(1 => 0, 2 => 0);
$max = 0;

$res = mysql_query("SELECT wc_cache.team_id, wc_history.date, AVG(wc_history.count) AS average FROM wc_history INNER JOIN wc_cache USING (uid) GROUP BY wc_cache.team_id, date ORDER BY team_id ASC, date ASC");
while ($row = mysql_fetch_assoc($res)) {
	$days[$row['team_id']][$row['date']] = $row['average'] - $last[$row['team_id']];
	$last[$row['team_id']] = $row['average'];
	if ($days[$row['team_id']][$row['date']] > $max) {
		$max = $days[$row['team_id']][$row['date']];
	}
}

$max_today = max($days[1]['2008-11-'.date("d")], $days[2]['2008-11-'.date("d")]);
$average = $max_today / (int) date("d");

$estimate = $average * 30;

$goal = ceil($max / 1000) * 1000 + 1000;

// Create the graph. These two calls are always required
$graph = new Graph(600,300,"auto");
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
$bplot1->SetLegend("Elsewhere");
$bplot1->value->Show();
$bplot1->value->SetFont(FF_VERA,FS_NORMAL,5);
$bplot1->value->SetAngle(90);
$bplot1->value->SetFormatCallback('format_graph_label');

$bplot2 = new BarPlot(array_values($days[2]));
$bplot2->SetFillColor("blue");
$bplot2->SetLegend("SLC");
$bplot2->value->Show();
$bplot2->value->SetFont(FF_VERA,FS_NORMAL,5);
$bplot2->value->SetAngle(90);
$bplot2->value->SetFormatCallback('format_graph_label');

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
