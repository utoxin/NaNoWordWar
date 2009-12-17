<?php
require('shared.php');

if (!isset($_GET['sort'])) {
	$_GET['sort'] = 'count';
}

function fetch_user_history($user_id) {
	$ch = curl_init("http://www.nanowrimo.org/wordcount_api/wchistory/".$user_id);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);

	$data = array();

	if (($input = curl_exec($ch)) !== FALSE) {
		if (($xml = simplexml_load_string($input)) !== FALSE) {
			if (isset($xml->uname)) {
				$data['current_count'] = (int) $xml->user_wordcount;
				$data['user_name'] = (string) $xml->uname;
				$data['history'] = array();

				$wordcounts = (array) $xml->wordcounts->children();

				if (isset($wordcounts['wcentry'])) {
					$wordcounts = (array) $wordcounts['wcentry'];
					
					if (isset($wordcounts['wc'])) {
						$data['history'][(string) $wordcounts['wcdate']] = (int) $wordcounts['wc'];
					} else {
						foreach ($wordcounts AS $wordcount) {
							$data['history'][(string) $wordcount->wcdate] = (int) $wordcount->wc;
						}
					}
				}

				return $data;
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function cmp($a, $b) {
	if (isset($_GET['sort']) && $_GET['sort'] == 'count') {
		$b2 = (int) str_replace(',','',$b['count']);
		$a2 = (int) str_replace(',','',$a['count']);

		if ($a2 == $b2) {
			return strcasecmp($a['name'], $b['name']);
		}

		return $b2 - $a2;
	} else {
		return strcasecmp($a['name'], $b['name']);
	}
}

$res = mysql_query("SELECT ml_wc_cache.*, ml_wc_adjustments.adjustment FROM `ml_wc_cache` LEFT JOIN ml_wc_adjustments ON (ml_wc_cache.uid = ml_wc_adjustments.uid) ORDER BY user_name ASC");

$data = array(1 => array(), 2 => array());
$sum = array(1 => 0, 2 => 0);

while ($row = mysql_fetch_assoc($res)) {
	if ((!isset($_GET['use_cache']) || !$_GET['use_cache']) && strtotime($row['expires']) < time()) {
		$cache = fetch_user_history($row['uid']);
		if ($cache) {
			$row['user_name'] = $cache['user_name'];
			if (($cache['current_count'] + $row['adjustment'] + ($row['adjustment'] / 2)) > 0) {
				$row['count'] = $cache['current_count'] + $row['adjustment'];
			} else {
				$row['count'] = $cache['current_count'];
			}
			foreach ($cache['history'] AS $key => $value) {
				if (($value + $row['adjustment'] + ($row['adjustment'] / 2)) > 0) {
					$cache['history'][$key] = $value + $row['adjustment'];
				} else {
					$cache['history'][$key] = $value;
				}
				mysql_query($query = "INSERT INTO ml_wc_history SET uid='{$row['uid']}', date='".mysql_real_escape_string($key)."', count='".mysql_real_escape_string($cache['history'][$key])."' ON DUPLICATE KEY UPDATE uid='{$row['uid']}', date='".mysql_real_escape_string($key)."', count='".mysql_real_escape_string($cache['history'][$key])."'");
			}
			if (time() < strtotime('2009-11-01')) {
				$row['expires'] = strtotime('2009-11-01');
			} else {
				$row['expires'] = mt_rand(3600, 10800) + time();
			}
		} else {
			if (time() < strtotime('2009-11-01')) {
				$row['expires'] = strtotime('2009-11-01');
			} else {
				$row['expires'] = mt_rand(1800, 3600) + time();
			}
		}

		mysql_query("UPDATE ml_wc_cache SET user_name='".mysql_real_escape_string($row['user_name'])."', count='".mysql_real_escape_string($row['count'])."', expires=FROM_UNIXTIME({$row['expires']}) WHERE uid={$row['uid']}");
	}

	$data[$row['team_id']][] = array('name' => $row['user_name'], 'count' => $row['count'], 'id' => $row['uid'], 'expires' => $row['expires']);
	$sum[$row['team_id']] += $row['count'];
}

usort($data[1], "cmp");
usort($data[2], "cmp");

$pace = 50000 * ((time() - mktime(0,0,0,11,1,2009))/(30*24*60*60));
//$pace = 50000;

if (isset($_GET['goal2'])) {
	$pace2 = $_GET['goal2'] * ((time() - mktime(0,0,0,11,1,2009))/(30*24*60*60));
}

$average1 = $sum[1]/count($data[1]);
$average2 = $sum[2]/count($data[2]);

$high_average = max($average1, $average2);

?>

<html>
<head>
<title>Utah NaNoWriMo Word War - Beta Version</title>
<SCRIPT SRC="javascript/boxover.js"></SCRIPT>

<style type="text/css">
body {
	font-family: arial;
}

table {
	border-spacing: 0;
	margin-left: 1em;
}

a {
	text-decoration: none;
	font-size: 0.9em;
	font-weight: bold;
}

.data1 {
	background: #ffffdd;
	font-size: 0.8em;
}

.data2 {
	background: #ddddff;
	font-size: 0.8em;
}

.name {
	padding-right: 1em;
}

.count {
	text-align: right;
}

.ttl {
	padding-left: 1em;
	text-align: right;
	font-size: 0.6em;
}

.as_of {
	font-size: 0.7em;
}

.subliminal {
	font-size: 0.4em;
}

.definition {
	font-size: 0.7em;
	width: 290px;
	margin-left: 20px;
	margin-top: 20px;
}

.pace_bar {
	border-style: solid;
	border-top-width: 6px;
	border-bottom-width: 0px;
	border-left-width: 0px;
	border-right-width: 0px;
	border-color: red;
}

.pace_bar2 {
	border-style: solid;
	border-top-width: 6px;
	border-bottom-width: 0px;
	border-left-width: 0px;
	border-right-width: 0px;
	border-color: gold;
}

.goal_bar {
	border-style: solid;
	border-top-width: 6px;
	border-bottom-width: 0px;
	border-left-width: 0px;
	border-right-width: 0px;
	border-color: green;
}

.goal_bar2 {
	border-style: solid;
	border-top-width: 6px;
	border-bottom-width: 0px;
	border-left-width: 0px;
	border-right-width: 0px;
	border-color: blue;
}

.statsblock {
	font-size: x-small;
}

</style>
</head>
<body>

<span title="offsetx=[10] offsety=[10] header=[] body=[<img src=http://www.utahwrimos.net/mlwordwar/wordwar_vs_graph.php width=900 height=450 style='opacity: 0.95; filter: alpha(opacity=95)'>] cssbody=[width: 900px; height: 450px;]"><strong><font color="green">Hover mouse here for overall vs graph</font></strong></span><br />

<table width=810>
	<tr>
		<td valign="top" width=260>
			<strong>ML Team Stats:</strong><br />
			<table width=260 class="statsblock">
				<tr>
					<td>Total Members:</td>
					<td align=right><?php echo count($data[1]); ?></td>
				</tr>
				<tr>
					<td>Total Words:</td>
					<td align=right><?php echo number_format($sum[1]); ?></td>
				</tr>
				<tr>
					<td>Average Words:</td>
					<td align=right><?php echo number_format($sum[1]/count($data[1])); ?></td>
				</tr>
				<tr>
					<td>Margin (Average):</td>
					<td align=right><?php 
						if ($average1 == $high_average) {
							echo '--'; 
						} else { 
							echo number_format($high_average - $average1); 
						}
					?></td>
				</tr>
				<tr>
					<td>Margin (Adjusted):</td>
					<td align=right><?php
						if ($average1 == $high_average) {
							echo '--';
						} else {
							echo number_format(($high_average - $average1) * count($data[1]));
						}
					?></td>
				</tr>
			</table>
			<br />
			<strong>Member List:</strong><br />
			<div style="width: 250; height: 400; overflow: auto;">
			<table style="width: 230px; margin: 0px; padding: 0px;">
				<tr>
					<td class="name">Name</td>
					<td class="count">Count</td>
				</tr>
<?php
$trclass = "data1";
$pace_line = false;
$goal_line = false;
$pace_line2 = false;
$goal_line2 = false;
for ($i = 0; $i < count($data[1]); $i++) {
	$tdclass = "";

	if (isset($_GET['goal2'])) {
		if (isset($_GET['sort']) && $_GET['sort'] == 'count' && !$goal_line2 && (str_replace(',','',$data[1][$i]['count']) < $_GET['goal2'] )) {
			$goal_line2 = true;
			$tdclass = " goal_bar2";
		}

		if (isset($_GET['sort']) && $_GET['sort'] == 'count' && !$pace_line2 && (str_replace(',','',$data[1][$i]['count']) < $pace2 )) {
			$pace_line2 = true;
			$tdclass = " pace_bar2";
		}
	}

	if (isset($_GET['sort']) && $_GET['sort'] == 'count' && !$goal_line && (str_replace(',','',$data[1][$i]['count']) < 50000 )) {
		$goal_line = true;
		$tdclass = " goal_bar";
	}

	if (isset($_GET['sort']) && $_GET['sort'] == 'count' && !$pace_line && (str_replace(',','',$data[1][$i]['count']) < $pace )) {
		$pace_line = true;
		$tdclass = " pace_bar";
	}

	$data[1][$i]['count'] = number_format($data[1][$i]['count']);

	echo <<<EOF
				<tr class="{$trclass}">
					<td class="name{$tdclass}"><div class="name" TITLE="offsetx=[10] offsety=[10] header=[] body=[<img src=http://www.utahwrimos.net/mlwordwar/wordwar_uid_graph.php?uid={$data[1][$i]['id']} width=600 height=350 style='opacity: 0.95; filter: alpha(opacity=95)'>] cssbody=[width: 600px; height: 300px;]"><a href="http://www.nanowrimo.org/user/{$data[1][$i]['id']}" target="_blank">{$data[1][$i]['name']}</a></div></td>
					<td class="count{$tdclass}">{$data[1][$i]['count']}</td>
				</tr>

EOF;
	if ($trclass == 'data1') {
		$trclass = 'data2';
	} else {
		$trclass = 'data1';
	}
}
?>
			</table>
			</div>
		</td>
		<td valign="top" width=260>
			<strong>Staff Team Stats:</strong><br />
			<table width=260 class="statsblock">
				<tr>
					<td>Total Members:</td>
					<td align=right><?php echo count($data[2]); ?></td>
				</tr>
				<tr>
					<td>Total Words:</td>
					<td align=right><?php echo number_format($sum[2]); ?></td>
				</tr>
				<tr>
					<td>Average Words:</td>
					<td align=right><?php echo number_format($sum[2]/count($data[2])); ?></td>
				</tr>
				<tr>
					<td>Margin (Average):</td>
					<td align=right><?php 
						if ($average2 == $high_average) {
							echo '--'; 
						} else { 
							echo number_format($high_average - $average2); 
						}
					?></td>
				</tr>
				<tr>
					<td>Margin (Adjusted):</td>
					<td align=right><?php
						if ($average2 == $high_average) {
							echo '--';
						} else {
							echo number_format(($high_average - $average2) * count($data[2]));
						}
					?></td>
				</tr>
			</table>
			<br />
			<strong>Member List:</strong><br />
			<div style="width: 250; height: 400; overflow: auto;">
			<table style="width: 230px; margin: 0px; padding: 0px;">
				<tr>
					<td class="name">Name</td>
					<td class="count">Count</td>
				</tr>
<?php
$trclass = "data1";
$pace_line = false;
$goal_line = false;
$pace_line2 = false;
$goal_line2 = false;
for ($i = 0; $i < count($data[2]); $i++) {
	$tdclass = "";

	if (isset($_GET['goal2'])) {
		if (isset($_GET['sort']) && $_GET['sort'] == 'count' && !$goal_line2 && (str_replace(',','',$data[2][$i]['count']) < $_GET['goal2'] )) {
			$goal_line2 = true;
			$tdclass = " goal_bar2";
		}

		if (isset($_GET['sort']) && $_GET['sort'] == 'count' && !$pace_line2 && (str_replace(',','',$data[2][$i]['count']) < $pace2 )) {
			$pace_line2 = true;
			$tdclass = " pace_bar2";
		}
	}

	if (isset($_GET['sort']) && $_GET['sort'] == 'count' && !$goal_line && (str_replace(',','',$data[2][$i]['count']) < 50000 )) {
		$goal_line = true;
		$tdclass = " goal_bar";
	}

	if (isset($_GET['sort']) && $_GET['sort'] == 'count' && !$pace_line && (str_replace(',','',$data[2][$i]['count']) < $pace )) {
		$pace_line = true;
		$tdclass = " pace_bar";
	}

	$data[2][$i]['count'] = number_format($data[2][$i]['count']);

	echo <<<EOF
				<tr class="{$trclass}">
					<td class="name{$tdclass}"><div class="name" TITLE="offsetx=[10] offsety=[10] header=[] body=[<img src=http://www.utahwrimos.net/mlwordwar/wordwar_uid_graph.php?uid={$data[2][$i]['id']} width=600 height=350 style='opacity: 0.95; filter: alpha(opacity=95)'>] cssbody=[width: 600px; height: 300px;]"><a href="http://www.nanowrimo.org/user/{$data[2][$i]['id']}" target="_blank">{$data[2][$i]['name']}</a></div></td>
					<td class="count{$tdclass}">{$data[2][$i]['count']}</td>
				</tr>

EOF;
	if ($trclass == 'data1') {
		$trclass = 'data2';
	} else {
		$trclass = 'data1';
	}
}
?>
			</table>
			</div>
		</td>
		<td valign=top width=290>
			<strong>Overall Stats:</strong><br />
			<table width=290>
				<tr>
					<td>On Pace Goal:</td>
					<td align=right><?php echo number_format($pace); ?></td>
				</tr>
				<tr>
					<td>Total Members:</td>
					<td align=right><?php echo count($data[1])+count($data[2]); ?></td>
				</tr>
				<tr>
					<td>Total Words:</td>
					<td align=right><?php echo number_format($sum[1]+$sum[2]); ?></td>
				</tr>
				<tr>
					<td>Average Words:</td>
					<td align=right><?php echo number_format(($sum[1]+$sum[2])/(count($data[1])+count($data[2]))); ?></td>
				</tr>
			</table>

			<div class="definition">
				TTL: How long until the page fetches new data for someone.<br /><br />
				Previous Average: Average of wordcounts from previous wordwars. 0 if no previous count.<br /><br />
				On Pace Goal: How many words you should have as of Right Now to finish in time. Is calculated to the second. This number is used to find the location for the Bar-O-Doom when you sort by count.<br /><br />
				Margin (Average): This is the difference between the averages for the two teams.<br /><br />
				Margin (Adjusted): This is the difference in wordcounts, taking the average for each team, and multiplying by the number of members in the largest team. Basically, this is how many words the team that is behind needs to write to catch up.
			</div>
			<br />
			<br />
<?php
if (isset($_GET['sort']) && $_GET['sort'] == 'count') {
	?>
			<center><a href="?sort=name">Sort By Name</a></center>
	<?php
} else {
	?>
			<center><a href="?sort=count">Sort By Count</a></center>
	<?php
}
?>
		</td>
	</tr>
</table>
</body>
</html>
