<?php
require('shared.php');

require_once('jpgraph/jpgraph.php');
require_once('jpgraph/jpgraph_bar.php');
require_once('jpgraph/jpgraph_line.php');

function ttlcalc($seconds) {
	$d = 60*60*24;
	$h = 60*60;
	$m = 60;

	$ttl = '';

	if ($seconds > $d) {
		$ttl .= floor($seconds / $d) . ' days, ';
		$seconds -= $d * floor($seconds / $d);
	}

	$ttl .= sprintf('%02d', floor($seconds / $h)).':';
	$seconds -= $h * floor($seconds / $h);

	$ttl .= sprintf('%02d', floor($seconds / $m)).':';
	$seconds -= $m * floor($seconds / $m);

	$ttl .= sprintf('%02d', $seconds);

	return $ttl;

	if ($seconds > $d) {
		return ceil($seconds / $d)."d";
	} elseif ($seconds > $h) {
		return ceil($seconds / $h)."h";
	} elseif ($seconds > $m) {
		return ceil($seconds / $m)."m";
	} else {
		return "{$seconds}s";
	}
}

$days = array();

for ($i = 1; $i <= 30; $i++) {
	$days['2009-11-'.sprintf("%02d", $i)] = "";
}

$sql = "SELECT * FROM ml_wc_cache WHERE uid='".mysql_real_escape_string($_GET['uid'])."'";
$res = mysql_query($sql);
$user_data = mysql_fetch_assoc($res);

$user_data['ttl'] = ttlcalc(strtotime($user_data['expires']) - time());

header("Expires: ".date("r", strtotime($user_data['expires'])));

$sql = "SELECT * FROM ml_wc_history WHERE uid='".mysql_real_escape_string($_GET['uid'])."' ORDER BY date ASC";
$res = mysql_query($sql);

$data = array();
while ($row = mysql_fetch_assoc($res)) {
	$data[$row['date']] = $row['count'];
}

$last_value = 0;
$unset_keys = array();

foreach (array_keys($days) AS $key) {
	if (isset($data[$key])) {
		if ($unset_keys) {
			for ($i = 0; $i < count($unset_keys); $i++) {
				$days[$unset_keys[$i]] = $last_value;
			}

			$unset_keys = array();
		}

		$days[$key] = $data[$key];
		$last_value = $data[$key];
	} else {
		$unset_keys[] = $key;
	}
}

$average = $last_value / (int) date("d");
//$average = $last_value / 30;
$estimate = $average * 30;

$goal = 50000;

if ($estimate > 50000) {
	$estimate = ceil($estimate / 10000) * 10000;

	$goal = $estimate;
}

$goal1 = array();

for ($i = 1; $i < 30; $i++) {
	$goal1[] = $i * 1667;
}

$goal1[] = 50000;

// Create the graph. These two calls are always required
$graph = new Graph(600,350,"auto");
$graph->SetScale("textlin", 0, $goal);

// Add a drop shadow
$graph->SetShadow();

// Adjust the margin a bit to make more room for titles
$top = 25;
$bottom = 85;
$left = 50;
$right = 25;
$graph->img->SetMargin($left,$right,$top,$bottom);
$graph->img->SetImgFormat('png');
$graph->img->SetAntiAliasing();

$goal1p = new LinePlot($goal1);
$goal1p->SetFillColor("#eeffee");
$graph->Add($goal1p);

// Create a bar pot
$bplot = new BarPlot(array_values($days));
$bplot->SetFillColor("#BBBBEE");
$bplot->value->Show();
$bplot->value->SetFont(FF_ARIAL,FS_NORMAL,8);
$bplot->value->SetAngle(90);
$bplot->value->SetFormatCallback('number_format');

$graph->Add($bplot);

$txt = new Text("Average / Day: ".number_format($average));
$txt->SetColor("black");
$txt->SetFont(FF_ARIAL,FS_BOLD,10);
$txt->SetPos(30,295);
$graph->AddText($txt);

$txt = new Text("30 Day Expected: ".number_format($average*30));
$txt->SetColor("black");
$txt->SetFont(FF_ARIAL,FS_BOLD,10);
$txt->SetPos(300,295);
$graph->AddText($txt);

$txt = new Text("TTL: {$user_data['ttl']}");
$txt->SetColor("black");
$txt->SetFont(FF_ARIAL,FS_BOLD,10);
$txt->SetPos(30,315);
$graph->AddText($txt);

// Setup the titles
$graph->title->Set("Daily Wordcount");

$graph->title->SetFont(FF_FONT1,FS_BOLD);

$graph->xaxis->HideTicks();

// Display the graph
$graph->Stroke();

?>
