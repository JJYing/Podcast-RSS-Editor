<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
	<meta charset="utf-8">
	<meta content="all" name="robots" />
	<meta name="author" content="JJ Ying" />
	<meta name="description" content="Podcast RSS Editor" />
	<title>Podcast RSS Editor</title>
	<link rel="stylesheet" href="assets/styles.css"/>
	<link rel="stylesheet" href="assets/balloon.min.css"/>
	<link rel="shortcut icon" href="assets/favicon.png" />
	<link href='https://fonts.googleapis.com/css?family=Alegreya+Sans:400,100,300,700' rel='stylesheet' type='text/css'>
</head>
<body>
<?php
include("config.php");
include($language);

//~Load the XML file

if ($_POST["newXMLFile"]) {
	$oldXMLFileName = $xmlFileName;
	$xmlFileName = $_POST["newXMLFile"];
	$content = file_get_contents('config.php');
	$content = str_replace($oldXMLFileName, $_POST["newXMLFile"],$content);
	file_put_contents('config.php',$content);
}

if ($_POST["newLanguage"]) {
	$oldLanguage= $language;
	$language = $_POST["newLanguage"];
	$content = file_get_contents('config.php');
	$content = str_replace($oldLanguage, $_POST["newLanguage"],$content);
	file_put_contents('config.php',$content);
	echo "<script>location.href='?ep=0&notf=1';</script>";
}

$xmlDoc = simplexml_load_file($xmlFileName,'my_node');

//~Delete Episode
if ($_GET["del"]) {
	$toDelete = (int) substr($_GET["del"],1);
	unset($xmlDoc->channel->item[$toDelete]);
	$xmlDoc -> asXML($xmlFileName);
}

//~Notifications
if ($_GET['notf']) {
	$showNotification = "notification-show";
	switch ($_GET['notf']) {
		case '1': $notificationContent = $lang[54]; break;
		case '2': $notificationContent = $lang[55]; break;
		case '3': $notificationContent = $lang[56]; break;
		case '4': $notificationContent = $lang[62]; break;
		case '5': $notificationContent = $lang[59]; break;
		default: $notificationContent = "";
	}
}

//~Process episodes
$t = 0;
$totalShownoteLinks = 0;
$totalHostsArray = array();
foreach($xmlDoc->channel->item as $thisItem){
	$thisDuration = $thisItem->children('itunes', true)->duration;
	if (substr($thisDuration,-3,1) == ':') {
		$thisSeconds[$t] = intval(substr($thisDuration,0,2) * 3600 + substr($thisDuration,3,2) * 60 + substr($thisDuration,6,2));
	}
	else {
		$thisSeconds[$t] = intval($thisDuration);
	}
	$thisMinutes[$t] = number_format(($thisSeconds[$t] / 60), 1);
	$totalSeconds += $thisSeconds[$t];
	$thisTitle[$t] = $thisItem->title;
	$thisLink[$t] = $thisItem->link;
	$thisAuthor[$t] = $thisItem->author;
	$thisDate[$t] = $thisItem->pubDate;
	$thisDate2[$t] = date('M j, Y', strtotime($thisDate[$t]));
	$thisDateH[$t] = date('G', strtotime($thisDate[$t]));

	$thisDesc[$t] = $thisItem->description;
	$thisFile[$t] = $thisItem->enclosure['url'];
	$thisImage[$t] = $thisItem->children('itunes', true)->image->attributes()->href;
	$thisShownoteLinks[$t] = substr_count($thisItem->description,"</a>");
	$totalShownoteLinks += $thisShownoteLinks[$t];
	$thisHostsArray[$t] = explode(",", $thisAuthor[$t]);
	$thisTillNow[$t] = strtotime("now") - strtotime($thisDate[$t]);
	if ($thisTillNow[$t] > 31536000) {  // more than 1 year
		$thisRelativeTime[$t] = number_format(($thisTillNow[$t] / 31536000),0).$lang[65];
	}
	elseif ($thisTillNow[$t] > 2628000) { // 1 month - 12 months
		$thisRelativeTime[$t] = number_format(($thisTillNow[$t] / 2628000),0).$lang[66];
	}
	elseif ($thisTillNow[$t] > 1209600) { // 2 weeks - 1 month
		$thisRelativeTime[$t] = number_format(($thisTillNow[$t] / 604800),0).$lang[67];
	}
	elseif ($thisTillNow[$t] > 172800) { // 2 days to 2 weeks
		$thisRelativeTime[$t] = number_format(($thisTillNow[$t] / 86400),0).$lang[68];
	}
	elseif ($thisTillNow[$t] > 86400) { // yesterday
		$thisRelativeTime[$t] = $lang[70];
	}
	elseif ($thisTillNow[$t] > 3600) { // 1 hour to 1 day
		$thisRelativeTime[$t] = number_format(($thisTillNow[$t] / 3600),0).$lang[69];
	}
	else {
		$thisRelativeTime[$t] = $lang[70];
	}
	$totalHostsArray = array_merge($totalHostsArray, $thisHostsArray[$t]);
	if ($t >= 10) {$jumpto = "#ep-$t";}else {$jumpto = "";} //~Set jump to in item list
	if ( (int) $_GET['ep'] == $t) {$highLightItem = "highlight";}
	else {$highLightItem = "";}
	$thisContent = "
	<div class='item $highLightItem'>
		<a class='item-link' href='?ep=$t$jumpto' name='ep-$t'>
			<h1>$thisTitle[$t]</h1>
			<span data-balloon='$thisRelativeTime[$t]' data-balloon-pos='right'><span class='item-date'>$thisDate2[$t]</span></span>
		</a>
		<div class='item-btns'>
			<a class='item-duplicate' href='?dupl=d$t' data-balloon='$lang[60]' data-balloon-pos='left'>
				<svg width='16' height='16' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'>
					<use x='0' y='0' width='16' height='16' xlink:href='#icon-duplicate'/>
				</svg>
			</a>
			<a class='item-delete' href='' onclick='return deleteEpisode(\"$t\",\"$thisTitle[$t]\")' data-balloon='$lang[61]' data-balloon-pos='left'>
				<svg width='16' height='16' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'>
					<use x='0' y='0' width='16' height='16' xlink:href='#icon-delete'/>
				</svg>
			</a>
		</div>
	</div>";
	$itemList.=$thisContent;

	$t+=1;
}

//~Process summaries
$latestDate  = strtotime(date('Y-m-d', strtotime($thisDate[0]))); // 取最新这期节目发布当天的 0 点

$currentTimezone = substr($thisDate[0],-5,5);
$totalHours = number_format(($totalSeconds / 3600),1);
$averageMinutes = number_format(($totalSeconds / 60 / ($t-1)),1);
$sinceLastUpdate = ceil((strtotime(now)-strtotime($thisDate[0]))/86400);
if ($sinceLastUpdate <= 0) {$sinceLastUpdate = '0';}
$totalHostsNo = count(array_unique($totalHostsArray));
$c = array_count_values($totalHostsArray);
$hostsList = implode(',  ', array_unique($totalHostsArray));
$mostHost = array_search(max($c), $c);
$navDashboard = "
	<ul>
		<li><strong>$sinceLastUpdate</strong><br />$lang[4]</li>
		<li><strong>$t</strong><br />$lang[5]</li>
	</ul>";
$totalSunNo = $totalMonNo = $totalTueNo = $totalWedNo = $totalThuNo = $totalFriNo =$totalSatNo = 0;
$fullDuration = strtotime($thisDate[0]) - strtotime($thisDate[($t-1)]);
$totalYears = number_format(($fullDuration / 31536000),1);

for ($i = 0; $i < $t; $i++) {
	$thisDateD[$i] = date('D', strtotime($thisDate2[$i]));
	switch ($thisDateD[$i]){
		case 'Sun': $totalSunNo += 1;
		break;
		case 'Mon': $totalMonNo += 1; break;
		case 'Tue': $totalTueNo += 1; break;
		case 'Wed': $totalWedNo += 1; break;
		case 'Thu': $totalThuNo += 1; break;
		case 'Fri': $totalFriNo += 1; break;
		case 'Sat': $totalSatNo += 1; break;
		default:
		  $totalSunNo += 1;
	}
	$N = $thisDateD[$i];

}

//~Duplicate Episode
if ($_GET["dupl"]) {
	$dupl = (int) substr($_GET['dupl'],1);
	$newDuplicatedItem = $xmlDoc->channel->addNewItem();
	$NS = array(
    'itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd'
	);
	$xmlDoc->registerXPathNamespace('itunes', $NS['itunes']);
	$newDuplicatedItem->addChild('title', $thisTitle[$dupl]);
	$newDuplicatedItem->addChild('description','');
	$newDuplicatedItem->description->addCData($thisDesc[$dupl]);
	$newDuplicatedItem->addChild('link', $thisLink[$dupl]);
	$newDuplicatedItem->addChild('explicit', 'no',$NS['itunes']);
	$newDuplicatedItem->addChild('guid', $thisLink[$dupl]);
	$newDuplicatedItem->guid->addAttribute('isPermaLink', 'true');
	$newDuplicatedItem->addChild('author', $thisAuthor[$dupl]);
	$newDuplicatedItem->addChild('image', "",$NS['itunes']);
	$newDuplicatedItem->children('itunes', true)->image->addAttribute('href', $thisImage[$dupl]);
	$newDuplicatedItem->addChild('pubDate', $thisDate2[$dupl]);
	$newDuplicatedItem->addChild('enclosure');
	$newDuplicatedItem->enclosure->addAttribute('type', 'audio/mpeg');
	$newDuplicatedItem->enclosure->addAttribute('url', $thisFile[$dulp]);
	$newDuplicatedItem->addChild('duration',  $thisSeconds[$dupl], $NS['itunes']);

	$xmlDoc -> asXML($xmlFileName);
	echo "<script>location.href='?ep=0&notf=4';</script>";
}

//~Initiate
$ep = (int) $_GET['ep'];
if ($ep >= 0) {
	$ep2 = $t  - $ep;
	$panelTitle = "$lang[43]$ep2$lang[44]";
	$currentTitle = "$thisTitle[$ep]";
	$currentDuration = "$thisSeconds[$ep]";
	$currentHH = floor($thisSeconds[$ep] / 3600);
	$currentMM = floor($thisSeconds[$ep] / 60 - $currentHH * 60);
	$currentSS = $thisSeconds[$ep] - $currentHH * 3600 - $currentMM * 60;
	$currentMonth = date('m',  strtotime($thisDate[$ep]));
	$currentDay = date('d',  strtotime($thisDate[$ep]));
	$currentYear = date('Y',  strtotime($thisDate[$ep]));
	$currentHour = date('H',  strtotime($thisDate[$ep]));
	$currentMinute = date('i',  strtotime($thisDate[$ep]));
	$currentSecond = date('s',  strtotime($thisDate[$ep]));
	$currentLink = "$thisLink[$ep]";
	$currentAuthor = "$thisAuthor[$ep]";
	$currentImage = "$thisImage[$ep]";
	$currentFile = "$thisFile[$ep]";
	$currentDesc = "$thisDesc[$ep]";
	$currentDuration = "$thisSeconds[$ep]";
	$currentLink = "$thisLink[$ep]";
	$currentDate = "$thisDate[$ep]";
	$currentDate2 = "$thisDate2[$ep]";
	$currentAuthor = "$thisAuthor[$ep]";
	$navHighlight1 = "highlight";

	if ($_POST["yy"] =="yes") {

		//~Edit existing episodes
		$navHighlight1 = "highlight";
		$NS = array(
	    'itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd'
		);
		$xmlDoc->registerXPathNamespace('itunes', $NS['itunes']);
		$thisEdit = $xmlDoc->channel->item[$ep];
		$thisEdit->title = $_POST["newTitle"];
		$thisEdit->pubDate = date(DATE_RFC2822, mktime($_POST["newHour"], $_POST["newMinute"], $_POST["newSecond"], $_POST["newMonth"], $_POST["newDay"], $_POST["newYear"]));
		$thisEdit->children('itunes', true)->duration = $_POST["newHH"] * 3600 + $_POST["newMM"] * 60 + $_POST["newSS"];
		$thisEdit->enclosure->attributes()->url=$_POST["newFile"];
		$thisEdit->link =  $_POST["newLink"];
		$thisEdit->guid = $_POST["newLink"];
		$thisEdit->author =  $_POST["newAuthor"];
		$thisEdit->children('itunes', true)->image->attributes()->href = $_POST["newImage"];
		$thisEdit->description = '';
		$thisEdit->description->addCData($_POST["newDesc"]);
		$xmlDoc->asXML($xmlFileName);
		echo "<script>
			location.href='".$_SERVER["HTTP_REFERER"]."&notf=2#ep-$ep"."';
		</script>";
	}
}
elseif ($ep == -2) {

	//~Show Dashboard
	$fileSize = number_format((filesize($xmlFileName) / 1024),1);
	$showDashboard = "panel-show";

	$averageCycle = number_format(($fullDuration / $t / 86400),1);

	$totalWeekdayNo = array($totalSunNo, $totalMonNo, $totalTueNo, $totalWedNo, $totalThuNo, $totalFriNo, $totalSatNo);
	$maxWeekdayNo = max($totalWeekdayNo);
	$barHeight = 140;
	$w1 = 45;
	$j = 0;
	foreach ($totalWeekdayNo as $value) {
		$weekNoHeight[$j] = $value / $maxWeekdayNo * $barHeight;
		$weekBarY[$j] = $barHeight - $weekNoHeight[$j];
		if ($value == $maxWeekdayNo) {
			$highestBar[$j] = "highest";
		}
		if ($value == 0) {
			$highestBar[$j] .= "empty";
		}
		$j ++;
	}
	$weekDay = "
		<ul>
			<li class='$highestBar[0]'><div  data-balloon='$totalSunNo $lang[63]' data-balloon-pos='down'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar bar-in-1'>
			<rect  class='chart-bar-1' x='0' y='$weekBarY[0]' width='$w1' height='$weekNoHeight[0]'/></svg><label>$lang[30]</label></div></li>

			<li class='$highestBar[1]'><div  data-balloon='$totalMonNo $lang[63]' data-balloon-pos='down'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-2'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[1]' width='$w1' height='$weekNoHeight[1]'/></svg><label>$lang[31]</label></div></li>

			<li class='$highestBar[2]'><div  data-balloon='$totalTueNo $lang[63]' data-balloon-pos='down'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-3'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[2]' width='$w1' height='$weekNoHeight[2]'/></svg><label>$lang[32]</label></div></li>

			<li class='$highestBar[3]'><div  data-balloon='$totalWedNo $lang[63]' data-balloon-pos='down'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-4'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[3]' width='$w1' height='$weekNoHeight[3]'/></svg><label>$lang[33]</label></div></li>

			<li class='$highestBar[4]'><div  data-balloon='$totalThuNo $lang[63]' data-balloon-pos='down'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-5'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[4]' width='$w1' height='$weekNoHeight[4]'/></svg><label>$lang[34]</label></div></li>

			<li class='$highestBar[5]'><div  data-balloon='$totalFriNo $lang[63]' data-balloon-pos='down'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-6'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[5]' width='$w1' height='$weekNoHeight[5]'/></svg><label>$lang[35]</label></div></li>

			<li class='$highestBar[6]'><div  data-balloon='$totalSatNo $lang[63]' data-balloon-pos='down'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-7'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[6]' width='$w1' height='$weekNoHeight[6]'/></svg><label>$lang[36]</label></div></li>
		</ul>
	";
	$recentDurations = array_slice($thisSeconds, 0, 10);
	$recentLongest = max($recentDurations);
	$averageLineH = $barHeight -10 - ( $barHeight - 16 ) * $averageMinutes / $recentLongest * 60;
	for ($z = 0; $z < 10; $z++) {
		$durationChartH[$z] = $barHeight -10 - ( $barHeight - 16 ) * $recentDurations[$z] / $recentLongest;
		if ($recentDurations[$z] == $recentLongest) {
			$highestPoint[$z] = "highest-point";
		}
	}
	$navHighlight2 = "highlight";
}
elseif ($ep == -3) {
	//~Show Settings
	$showSettings = "panel-show";
	$navHighlight3 = "highlight";
	$settingsContent = "
		<form method='post' autocomplete='on' action='index.php?ep=-1&notf=1'>
			<section>
				<label for='newXMLFile'>$lang[39]</label>
				<select id='content' name='newXMLFile' class='settings-select right-in-3'>
	";
	foreach (glob('*.{rss,xml}', GLOB_BRACE) as $filename) {
		if ($xmlFileName == $filename) {
			$settingsContent .= "<option selected='selected' value='$filename'>$filename</option>";
		}
		else {
			$settingsContent .= "<option value='$filename'>$filename</option>";
		}
	}
	$settingsContent .= "
				</select>
			</section>
			<br />
			<section>
				<label for='newLanguage'>$lang[40]</label>
				<select id='language' name='newLanguage' class='settings-select right-in-6'>

	";
	foreach (glob('language/*.php', GLOB_BRACE) as $languageFile) {
		$pathParts = pathinfo($languageFile);
		$languageName = $pathParts['filename'];
		if ($language == $languageFile) {
			$settingsContent .= "<option selected='selected' value='$languageFile'>$languageName</option>";
		}
		else {
			$settingsContent .= "<option value='$languageFile'>$languageName</option>";
		}
	}
	$settingsContent .= "
				</select>
			</section>
			<br />
			<input type='submit' value=$lang[41] class='right-in-10'>
		</form>
	";
}
else {
	$panelTitle = "$lang[42]";
	$currentAuthor = "$thisAuthor[0]";
	$currentDay = date('d',time());
	$currentMonth = date('m',time());
	$currentYear = date('Y',time());
	$currentHour = 0;
	$currentMinute = 0;
	$currentSecond = 0;
	$navHighlight1 = "highlight";
	if ($_POST["yy"] =="yes") {

		//~Add new episode

		$newDuration = $_POST["newHH"] * 3600 + $_POST["newMM"] * 60 + $_POST["newSS"];
		$newDate = date(DATE_RFC2822, mktime($_POST["newHour"], $_POST["newMinute"], $_POST["newSecond"], $_POST["newMonth"], $_POST["newDay"], $_POST["newYear"]));
		$NS = array(
	    'itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd'
		);
		$xmlDoc->registerXPathNamespace('itunes', $NS['itunes']);
		$newItem = $xmlDoc->channel->addNewItem();
		$newItem->addChild('title', $_POST["newTitle"]);
		$newItem->addChild('description','');
		$newItem->description->addCData($_POST["newDesc"]);
		$newItem->addChild('link', $_POST["newLink"]);
		$newItem->addChild('explicit', 'no',$NS['itunes']);
		$newItem->addChild('guid', $_POST["newLink"]);
		$newItem->guid->addAttribute('isPermaLink', 'true');
		$newItem->addChild('author', $_POST["newAuthor"]);
		$newItem->addChild('image', "",$NS['itunes']);
		$newItem->children('itunes', true)->image->addAttribute('href', $_POST["newImage"]);
		$newItem->addChild('pubDate', $newDate);
		$newItem->addChild('enclosure');
		$newItem->enclosure->addAttribute('type', 'audio/mpeg');
		$newItem->enclosure->addAttribute('url', $_POST["newFile"]);
		$newItem->addChild('duration',  $newDuration, $NS['itunes']);
		$xmlDoc->asXML($xmlFileName);

		echo "<script>location.href='".$_SERVER["HTTP_REFERER"]."&notf=3';</script>";
	}
}

//~Extras
class my_node extends SimpleXMLElement
{
	public function addCData($cdata_text) {
		$node = dom_import_simplexml($this);
		$no   = $node->ownerDocument;
		$node->appendChild($no->createCDATASection($cdata_text));
	}
    public function addNewItem()
    {
    	$dom = dom_import_simplexml($this);
    	$new = $dom->insertBefore(
    	            $dom->ownerDocument->createElement('item'),
    	            $dom->getElementsByTagName('item')->item(0)
        );
        return simplexml_import_dom($new, get_class($this));
    }
}

?>

<!--页面正文-->
<section class="notification <?php echo($showNotification);?>"><?php echo($notificationContent);?></section>
<nav>
	<a href="?ep=-1" class="logo"><?php echo($lang[0]);?></a>
		<ul>
			<li><a href="?ep=-1" class="<?php echo($navHighlight1);?>"><svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
		    <path d="M30,43A11.013,11.013,0,0,1,19,32V17a11,11,0,0,1,22,0V32A11.012,11.012,0,0,1,30,43ZM30,8a9.011,9.011,0,0,0-9,9V32a9,9,0,0,0,18,0V17A9.011,9.011,0,0,0,30,8Zm0,39A15.017,15.017,0,0,1,15,32V25a1,1,0,0,1,2,0v7a13,13,0,0,0,26,0V25a1,1,0,0,1,2,0v7A15.017,15.017,0,0,1,30,47Zm3,8H27a1,1,0,0,1,0-2h6A1,1,0,0,1,33,55Zm-3,0a1,1,0,0,1-1-1V46a1,1,0,0,1,2,0v8A1,1,0,0,1,30,55Zm6-31a1,1,0,1,1,1,1A1,1,0,0,1,36,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,33,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,30,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,27,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,24,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,21,24Zm14-3a1,1,0,1,1,1,1A1,1,0,0,1,35,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,32,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,29,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,26,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,23,21Zm13-3a1,1,0,1,1,1,1A1,1,0,0,1,36,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,33,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,30,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,27,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,24,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,21,18Zm14-3a1,1,0,1,1,1,1A1,1,0,0,1,35,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,32,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,29,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,26,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,23,15Zm13-3a1,1,0,1,1,1,1A1,1,0,0,1,36,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,33,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,30,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,27,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,24,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,21,12ZM32,9a1,1,0,1,1,1,1A1,1,0,0,1,32,9ZM29,9a1,1,0,1,1,1,1A1,1,0,0,1,29,9ZM26,9a1,1,0,1,1,1,1A1,1,0,0,1,26,9ZM40,28H20a1,1,0,1,1,0-2H40A1,1,0,0,1,40,28Z"/>
			</svg><?php echo($lang[1]);?></a></li>
			<li><a href="?ep=-2" class="<?php echo($navHighlight2);?>"><svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
		    <path id="Dashboard-2" data-name="Dashboard"  d="M190,54a1,1,0,0,1-1-1V44a1,1,0,0,1,2,0v9A1,1,0,0,1,190,54Zm-8,0a1,1,0,0,1-.914-1.406l4-9a1,1,0,0,1,1.828.813l-4,9A1,1,0,0,1,182,54Zm16,0a1,1,0,0,1-.915-0.594l-4-9a1,1,0,0,1,1.828-.812l4,9A1,1,0,0,1,198,54Zm14-12H168a1,1,0,0,1-1-1V11a1,1,0,0,1,1-1h17a1,1,0,0,1,0,2H169V40h42V12H195a1,1,0,0,1,0-2h17a1,1,0,0,1,1,1V41A1,1,0,0,1,212,42ZM195,16H185a1,1,0,0,1-1-1V7a1,1,0,0,1,1-1h10a1,1,0,0,1,1,1v8A1,1,0,0,1,195,16Zm-9-2h8V8h-8v6Z" transform="translate(-160)"/>
			</svg><?php echo($lang[2]);?></a></li>
			<li><a href="?ep=-3" class="<?php echo($navHighlight3);?>"><svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
		    <path id="settings-2" data-name="settings" d="M351.331,53h-2.662a3,3,0,0,1-2.952-2.463l-0.385-2.123a18.784,18.784,0,0,1-5.044-2.1l-1.781,1.233a3.06,3.06,0,0,1-3.829-.346L332.8,45.322a3,3,0,0,1-.345-3.829l1.233-1.78a18.808,18.808,0,0,1-2.1-5.045l-2.123-.385A3,3,0,0,1,327,31.331V28.669a3,3,0,0,1,2.463-2.951l2.123-.386a18.808,18.808,0,0,1,2.1-5.045l-1.233-1.78a3,3,0,0,1,.345-3.829l1.883-1.882a3.059,3.059,0,0,1,3.828-.346l1.782,1.233a18.794,18.794,0,0,1,5.044-2.1l0.385-2.122A3,3,0,0,1,348.669,7h2.662a3,3,0,0,1,2.952,2.463l0.385,2.123a18.794,18.794,0,0,1,5.044,2.1l1.781-1.233a3.06,3.06,0,0,1,3.829.346l1.883,1.882a3,3,0,0,1,.345,3.829l-1.233,1.78a18.808,18.808,0,0,1,2.1,5.045l2.123,0.385A3,3,0,0,1,373,28.669v2.662a3,3,0,0,1-2.463,2.951l-2.123.386a18.808,18.808,0,0,1-2.1,5.045l1.233,1.78a3,3,0,0,1-.345,3.829L365.322,47.2a2.981,2.981,0,0,1-2.122.879h0a2.986,2.986,0,0,1-1.706-.533l-1.782-1.233a18.784,18.784,0,0,1-5.044,2.1l-0.385,2.122A3,3,0,0,1,351.331,53Zm-11.073-8.879a0.993,0.993,0,0,1,.542.16,16.856,16.856,0,0,0,5.609,2.331,1,1,0,0,1,.773.8l0.5,2.767a1,1,0,0,0,.984.821h2.662a1,1,0,0,0,.984-0.822l0.5-2.767a1,1,0,0,1,.773-0.8,16.856,16.856,0,0,0,5.609-2.331,1,1,0,0,1,1.112.017l2.32,1.606a1.02,1.02,0,0,0,1.276-.115l1.883-1.882a1,1,0,0,0,.114-1.276l-1.606-2.32a1,1,0,0,1-.018-1.111,16.859,16.859,0,0,0,2.331-5.608,1,1,0,0,1,.8-0.774l2.768-.5A1,1,0,0,0,371,31.331V28.669a1,1,0,0,0-.821-0.984l-2.768-.5a1,1,0,0,1-.8-0.774,16.858,16.858,0,0,0-2.331-5.608,1,1,0,0,1,.018-1.111l1.606-2.32a1,1,0,0,0-.114-1.276l-1.883-1.882a1.02,1.02,0,0,0-1.277-.115L360.312,15.7a1,1,0,0,1-1.112.018,16.862,16.862,0,0,0-5.609-2.331,1,1,0,0,1-.773-0.8l-0.5-2.768A1,1,0,0,0,351.331,9h-2.662a1,1,0,0,0-.984.822l-0.5,2.767a1,1,0,0,1-.773.8,16.862,16.862,0,0,0-5.609,2.331,1,1,0,0,1-1.112-.018l-2.32-1.606a1.02,1.02,0,0,0-1.276.115l-1.883,1.882a1,1,0,0,0-.114,1.276l1.606,2.32a1,1,0,0,1,.018,1.111,16.858,16.858,0,0,0-2.331,5.608,1,1,0,0,1-.8.774l-2.768.5a1,1,0,0,0-.821.983v2.662a1,1,0,0,0,.821.984l2.768,0.5a1,1,0,0,1,.8.774,16.859,16.859,0,0,0,2.331,5.608,1,1,0,0,1-.018,1.111l-1.606,2.32a1,1,0,0,0,.114,1.276l1.883,1.882a1.023,1.023,0,0,0,1.277.115l2.319-1.606A1,1,0,0,1,340.258,44.121ZM350,39a9,9,0,1,1,9-9A9.01,9.01,0,0,1,350,39Zm0-16a7,7,0,1,0,7,7A7.008,7.008,0,0,0,350,23Z" transform="translate(-320)"/>
			</svg><?php echo($lang[3]);?></a></li>
		</ul>
	<section class="nav-dashboard"><?php echo($navDashboard);?></section>
</nav>

<article class="panel edit-panel">
	<h2 class="panel-title right-in-1"><?php echo $panelTitle ?></h2>
	<form action="index.php?ep=<?php echo $ep?>" method="post">
		<section class="edit-title right-in-2"><h3><?php echo $lang[7]?> </h3><span><input type="text" name="newTitle" value="<?php echo $currentTitle ?>" /></span></section>
		<section class="edit-date right-in-3">
			<h3><?php echo $lang[8]?> </h3>
			<span>
				<input type="text" name="newMonth" value="<?php echo $currentMonth ?>" /><label for="newMonth"><?php echo $lang[45]?></label>
				<input type="text" name="newDay" value="<?php echo $currentDay ?>" /><label for="newDay"><?php echo $lang[46]?></label>
				<input type="text" name="newYear" value="<?php echo $currentYear ?>" /><label for="newYear"><?php echo $lang[47]?></label>
				<input type="text" name="newHour" value="<?php echo $currentHour ?>" /><label for="newHour"><?php echo $lang[48]?></label>
				<input type="text" name="newMinute" value="<?php echo $currentMinute ?>" /><label for="newMinute"><?php echo $lang[49]?></label>
				<input type="text" name="newSecond" value="<?php echo $currentSecond ?>" /><label for="newSecond"><?php echo $lang[50]?></label>
			</span></section>
		<section class="edit-duration right-in-4">
			<h3><?php echo $lang[9]?> </h3>
			<span>
				<input type="text" name="newHH" value="<?php echo $currentHH ?>" /><label for="newHH"><?php echo $lang[51]?></label>
				<input type="text" name="newMM" value="<?php echo $currentMM ?>" /><label for="newMM"><?php echo $lang[52]?></label>
				<input type="text" name="newSS" value="<?php echo $currentSS ?>" /><label for="newSS"><?php echo $lang[53]?></label>
			</span>
		</section>
		<section class="edit-link right-in-5">
			<h3>
				<?php echo $lang[10]?>
				<?php if ($currentLink) {echo("<em><a target=\"_blank\" href=\"$currentLink\">$lang[72]</a> ⎋</em>");}?>
			</h3>
			<span><input type="text" name="newLink" value="<?php echo $currentLink ?>" /></span>
		</section>
		<section class="edit-author right-in-6"><h3><?php echo $lang[11]?> </h3><span><input type="text" name="newAuthor" value="<?php echo $currentAuthor ?>" /></span></section>
		<section class="edit-image right-in-7">
			<h3>
				<?php echo $lang[12]?>
				<?php if ($currentImage) {echo("<em><a target=\"_blank\" href=\"$currentImage\">$lang[72]</a> ⎋</em>");}?>
			</h3>
			<span><input type="text" name="newImage" value="<?php echo $currentImage ?>" /></span>
		</section>
		<section class="edit-audio right-in-8">
			<h3><?php echo $lang[13]?> <?php if ($currentFile) {echo("<em><a target=\"_blank\" href=\"$currentFile\">$lang[72]</a> ⎋</em>");}?></h3>
			<span><input type="text" name="newFile" value="<?php echo $currentFile ?>" /></span>
		</section>
		<section class="edit-desc right-in-9"><h3><?php echo $lang[14]?> </h3><span><textarea name="newDesc"  /><?php echo $currentDesc ?></textarea></span></section>



		<footer>
			<input type="submit" value="<?php echo($lang[41]);?>" class="right-in-10">
			<input type="checkbox" checked name="yy" value="yes" class="hide"/>
		</footer>
	</form>
</article>



<article class="dashboard panel-in <?php echo($showDashboard);?>">
	<section class="d-last-udpate">
		<svg class="dashboard-icons" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 60 60">
	    <path d="M990.5,51a20,20,0,1,1,20-20A20.022,20.022,0,0,1,990.5,51Zm0-38a18,18,0,1,0,18,18A18.021,18.021,0,0,0,990.5,13Zm8,27a1,1,0,0,1-.707-0.293l-8-8A1,1,0,0,1,989.5,31V21a1,1,0,0,1,2,0v9.586l7.707,7.707A1,1,0,0,1,998.5,40Zm10.72-13.282a1,1,0,0,1-.71-1.707,8.5,8.5,0,1,0-12.021-12.021,1,1,0,0,1-1.414-1.414,10.5,10.5,0,1,1,14.845,14.85A0.982,0.982,0,0,1,1009.22,26.718Zm-37.438,0a1,1,0,0,1-.707-0.293,10.5,10.5,0,0,1,14.85-14.85,1,1,0,0,1-1.414,1.414,8.5,8.5,0,0,0-12.022,12.022A1,1,0,0,1,971.782,26.718ZM970.5,52a1,1,0,0,1-.707-1.707l6-6a1,1,0,1,1,1.414,1.414l-6,6A1,1,0,0,1,970.5,52Zm40,0a1,1,0,0,1-.71-0.293l-6-6a1,1,0,1,1,1.42-1.414l6,6A1,1,0,0,1,1010.5,52Zm-20-35a1,1,0,0,1-.71-1.71,1.047,1.047,0,0,1,1.42,0A1,1,0,0,1,990.5,17Zm0,30a0.99,0.99,0,1,1,.71-0.29A1.051,1.051,0,0,1,990.5,47Zm15-15a0.99,0.99,0,0,1-1-1,1.031,1.031,0,0,1,.29-0.71,1.047,1.047,0,0,1,1.42,0,1.031,1.031,0,0,1,.29.71A0.99,0.99,0,0,1,1005.5,32Zm-30,0a1.03,1.03,0,0,1-.71-0.29A1.048,1.048,0,0,1,974.5,31a1.026,1.026,0,0,1,.29-0.71,1.047,1.047,0,0,1,1.42,0,1.031,1.031,0,0,1,.29.71A0.99,0.99,0,0,1,975.5,32Z" transform="translate(-960)"/>
		</svg>
		<strong class="scale-in-1"><?php echo($sinceLastUpdate);?></strong><?php echo($lang[15]);?><br />
		<i><?php echo($lang[16]);?><span><?php echo($averageCycle);?></span><?php echo($lang[17]);?></i>
	</section>

	<section class="d-total-episodes">
		<svg class="dashboard-icons" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 60 60">
		   <path d="M1168.5,32.8V15a3.006,3.006,0,0,0-3-3h-7V11a3,3,0,1,0-6,0v1h-10V11a3,3,0,1,0-6,0v1h-5a3.006,3.006,0,0,0-3,3V50a3.006,3.006,0,0,0,3,3h28a0.972,0.972,0,0,0,.31-0.062A11.487,11.487,0,0,0,1168.5,32.8Zm-14-21.8a1,1,0,1,1,2,0v4.9a1,1,0,1,1-2,0V11Zm-16,0a1,1,0,1,1,2,0v4.9a1,1,0,1,1-2,0V11Zm-7,40a1,1,0,0,1-1-1V15a1,1,0,0,1,1-1h5v1.9a3,3,0,1,0,6,0V14h10v1.9a3,3,0,1,0,6,0V14h7a1,1,0,0,1,1,1V31.4a11.483,11.483,0,0,0-16.89,8.6h-3.11a1,1,0,0,0-1,1v4a1,1,0,0,0,2,0V42h2.03a11.493,11.493,0,0,0,5,9H1131.5Zm29.5,0a9.5,9.5,0,1,1,9.5-9.5A9.51,9.51,0,0,1,1161,51Zm0.5-9.48V37a1,1,0,0,0-2,0v5a1,1,0,0,0,.38.781l5,4a0.974,0.974,0,0,0,.62.219,1.006,1.006,0,0,0,.78-0.375,0.989,0.989,0,0,0-.16-1.406ZM1141.5,21h-4a1,1,0,0,0-1,1v4a1,1,0,0,0,2,0V23h3A1,1,0,0,0,1141.5,21Zm5,6a1,1,0,0,0,1-1V23h3a1,1,0,0,0,0-2h-4a1,1,0,0,0-1,1v4A1,1,0,0,0,1146.5,27Zm9,0a1,1,0,0,0,1-1V23h3a1,1,0,0,0,0-2h-4a1,1,0,0,0-1,1v4A1,1,0,0,0,1155.5,27Zm-14,3h-4a1,1,0,0,0-1,1v4a1,1,0,0,0,2,0V32h3A1,1,0,0,0,1141.5,30Zm5,6a1,1,0,0,0,1-1V32h3a1,1,0,0,0,0-2h-4a1,1,0,0,0-1,1v4A1,1,0,0,0,1146.5,36Zm-5,4h-4a1,1,0,0,0-1,1v4a1,1,0,0,0,2,0V42h3A1,1,0,0,0,1141.5,40Z" transform="translate(-1120)"/>
		</svg>
		<strong class="scale-in-2"><?php echo($t);?></strong><?php echo($lang[18]);?><br />
		<i><?php echo($lang[19]);?><span><?php echo($totalYears);?></span><?php echo($lang[20]);?></i>
	</section>

	<section class="d-total-hours">
		<svg class="dashboard-icons" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 60 60">
		   <path d="M830.5,15a1,1,0,0,1-1-1V9a1,1,0,0,1,2,0v5A1,1,0,0,1,830.5,15Zm0,37a1,1,0,0,1-1-1V46a1,1,0,1,1,2,0v5A1,1,0,0,1,830.5,52Zm0-19.586-6.707-6.707a1,1,0,1,1,1.414-1.414l5.293,5.293,9.293-9.293a1,1,0,1,1,1.414,1.414Zm0,20.086A22.5,22.5,0,1,1,853,30,22.525,22.525,0,0,1,830.5,52.5Zm0-43A20.5,20.5,0,1,0,851,30,20.523,20.523,0,0,0,830.5,9.5Zm21,21.5h-5a1,1,0,0,1,0-2h5A1,1,0,0,1,851.5,31Zm-37,0h-5a1,1,0,0,1,0-2h5A1,1,0,0,1,814.5,31Z" transform="translate(-800)"/>
		</svg>
		<strong class="scale-in-3"><?php echo($totalHours);?></strong><?php echo($lang[21]);?><br />
		<i><?php echo($lang[22]);?><span><?php echo(number_format(($totalHours / 7.88),1));?></span><?php echo($lang[23]);?></i>
	</section>

	<section class="d-file-size">
		<svg class="dashboard-icons" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 60 60">
		   <path d="M670,20.389c-8.945,0-18-2.128-18-6.194S661.055,8,670,8s18,2.128,18,6.194S678.945,20.389,670,20.389ZM670,10c-9.767,0-16,2.484-16,4.194s6.233,4.194,16,4.194,16-2.484,16-4.194S679.767,10,670,10Zm0,18.389c-8.945,0-18-2.128-18-6.194a1,1,0,0,1,2,0c0,1.71,6.233,4.194,16,4.194s16-2.484,16-4.194a1,1,0,0,1,2,0C688,26.261,678.945,28.389,670,28.389Zm0,8c-8.945,0-18-2.128-18-6.194a1,1,0,0,1,2,0c0,1.71,6.233,4.194,16,4.194s16-2.484,16-4.194a1,1,0,0,1,2,0C688,34.261,678.945,36.389,670,36.389Zm0,8c-8.945,0-18-2.128-18-6.194a1,1,0,0,1,2,0c0,1.71,6.233,4.194,16,4.194s16-2.484,16-4.194a1,1,0,0,1,2,0C688,42.261,678.945,44.389,670,44.389Zm0,8c-8.945,0-18-2.128-18-6.194a1,1,0,0,1,2,0c0,1.71,6.233,4.194,16,4.194s16-2.484,16-4.194a1,1,0,0,1,2,0C688,50.261,678.945,52.389,670,52.389ZM653,48a1,1,0,0,1-1-1V14a1,1,0,0,1,2,0V47A1,1,0,0,1,653,48Zm34,0a1,1,0,0,1-1-1V14a1,1,0,0,1,2,0V47A1,1,0,0,1,687,48Z" transform="translate(-640)"/>
		</svg>
		<strong class="scale-in-4"><?php echo($fileSize);?></strong><?php echo($lang[24]);?><br />
		<i><?php echo($lang[25]);?><span><?php echo($totalShownoteLinks);?></span><?php echo($lang[26]);?></i>
	</section>

	<section class="d-host-num">
		<svg class="dashboard-icons" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 60 60">
	   <path d="M509.7,35.262c-4,0-6.4-4.775-6.92-6.209a1.9,1.9,0,0,1-.66-0.7,4.14,4.14,0,0,1-.544-3.366,2.493,2.493,0,0,1,.676-1.015,12.96,12.96,0,0,1,.377-3.1,7.624,7.624,0,0,1,14.141,0,12.916,12.916,0,0,1,.378,3.063,2.264,2.264,0,0,1,.621.852,4.234,4.234,0,0,1-.5,3.563,1.947,1.947,0,0,1-.662.743C516.045,30.609,513.657,35.262,509.7,35.262Zm-6.061-9.856a0.571,0.571,0,0,0-.19.284,2.356,2.356,0,0,0,.43,1.709,1.519,1.519,0,0,1,.713.772c0.014,0.034.026,0.069,0.037,0.106,0.3,1.056,2.344,4.985,5.071,4.985s4.768-3.93,5.072-4.985a1.363,1.363,0,0,1,.655-0.81c0.687-1.259.557-1.772,0.5-1.9a0.95,0.95,0,0,0-.077-0.129,1,1,0,0,1-.712-1.012,11.409,11.409,0,0,0-.3-3.044c-0.036-.123-0.985-3.286-5.138-3.286-4.2,0-5.13,3.258-5.139,3.291a11.39,11.39,0,0,0-.3,3.039,1,1,0,0,1-.622.98h0Zm19.676,22.926H496.71a1,1,0,0,1-1-.989c-0.055-4.976.7-10.368,6.706-12.642a10.331,10.331,0,0,0,3.362-1.985l1.419,1.409a12.1,12.1,0,0,1-4.073,2.447c-3.7,1.4-5.3,4.257-5.41,9.76h24.6c-0.1-5.619-1.7-8.626-5.263-9.974a14.6,14.6,0,0,1-4.107-2.288l1.31-1.512-0.655.756,0.652-.758a12.915,12.915,0,0,0,3.508,1.931c5.99,2.266,6.61,8.423,6.557,12.856A1,1,0,0,1,523.319,48.332Zm-26.319-6h-7a1,1,0,0,1-1-1.011l0-.517c0.034-4.006.072-8.546,5.436-10.575a22.557,22.557,0,0,0,3.2-1.546,13.99,13.99,0,0,1-2.83-4.029,2.005,2.005,0,0,1-.659-0.71,4.154,4.154,0,0,1-.546-3.367,2.521,2.521,0,0,1,.677-1.024,12.963,12.963,0,0,1,.377-3.107,7.027,7.027,0,0,1,7.071-4.777,6.844,6.844,0,0,1,6.937,5.165l-1.955.42a4.86,4.86,0,0,0-4.982-3.585c-4.152,0-5.1,3.165-5.14,3.3a11.318,11.318,0,0,0-.3,3.037,1,1,0,0,1-.616.978,0.563,0.563,0,0,0-.194.293,2.377,2.377,0,0,0,.43,1.714,1.672,1.672,0,0,1,.713.792,0.956,0.956,0,0,1,.037.107,10.973,10.973,0,0,0,3.231,4.051,1,1,0,0,1,.039,1.68,25.843,25.843,0,0,1-4.779,2.48c-3.909,1.479-4.1,4.59-4.139,8.232H497v2Zm33,0h-7v-2h5.984c-0.1-4.038-.662-6.815-4.409-8.232a23.028,23.028,0,0,1-4.81-2.611,1,1,0,0,1,.176-1.718c1.469-.668,2.951-3.275,3.125-3.882a0.956,0.956,0,0,1,.037-0.107,1.665,1.665,0,0,1,.662-0.766,2.34,2.34,0,0,0,.481-1.74,0.522,0.522,0,0,0-.23-0.309,1.082,1.082,0,0,1-.58-0.962,11.461,11.461,0,0,0-.3-3.049c-0.035-.124-0.985-3.289-5.138-3.289a4.641,4.641,0,0,0-4.839,3.562l-1.966-.367a6.615,6.615,0,0,1,6.805-5.194,7.027,7.027,0,0,1,7.071,4.773,12.961,12.961,0,0,1,.378,3.11,2.521,2.521,0,0,1,.677,1.024,4.152,4.152,0,0,1-.545,3.365,1.97,1.97,0,0,1-.659.711,12.066,12.066,0,0,1-2.774,4.005,19.538,19.538,0,0,0,3.141,1.571c5.633,2.132,5.68,7.1,5.717,11.093A1,1,0,0,1,530,42.332Z" transform="translate(-480)"/>
		</svg>

		<strong class="scale-in-5"><?php echo($totalHostsNo);?></strong><?php echo($lang[27]);?><br />
		<i><?php echo($lang[28]);?><span  data-balloon="<?php echo($lang[64]);?> <?php echo($hostsList);?>" data-balloon-pos="left" data-balloon-length="medium"><?php echo($mostHost);?></span><?php echo($lang[29]);?></i>
	</section>

	<section class="d-weekday">
		<div class="scale-in-1"><?php echo($weekDay);?></div>
	</section>

	<section class="d-duration">
		<div class="scale-in-6">
			<svg xmlns="http://www.w3.org/2000/svg" width="500" height="150" viewBox="-10 -10 490 140" class="">
				<defs>
			    <filter id="f3" x="-30%" y="-30%" width="300%" height="300%">
			      <feOffset result="offOut" in="SourceAlpha" dx="0" dy="20" />
	            <feGaussianBlur result="blurOut" in="offOut" stdDeviation="9" />
	            <feBlend in="SourceGraphic" in2="blurOut" mode="normal" />
			    </filter>
				  </defs>
				  <path class="average-line" d="M 0 <?php echo($averageLineH);?>l 500 0" stroke-width="1" fill="none" />
					<path filter="url(#f3)" class="duration-chart" stroke="#ff2d77" stroke-width="3" fill="none" d="
						M10 <?php echo($durationChartH[0]);?>
						C30 <?php echo($durationChartH[0]);?> 40 <?php echo($durationChartH[1]);?> 60 <?php echo($durationChartH[1]);?>
						C80 <?php echo($durationChartH[1]);?> 90 <?php echo($durationChartH[2]);?> 110 <?php echo($durationChartH[2]);?>
						C130 <?php echo($durationChartH[2]);?> 140 <?php echo($durationChartH[3]);?> 160 <?php echo($durationChartH[3]);?>
						C180 <?php echo($durationChartH[3]);?> 190 <?php echo($durationChartH[4]);?> 210 <?php echo($durationChartH[4]);?>
						C230 <?php echo($durationChartH[4]);?> 240 <?php echo($durationChartH[5]);?> 260 <?php echo($durationChartH[5]);?>
						C280 <?php echo($durationChartH[5]);?> 290 <?php echo($durationChartH[6]);?> 310 <?php echo($durationChartH[6]);?>
						C330 <?php echo($durationChartH[6]);?> 340 <?php echo($durationChartH[7]);?> 360 <?php echo($durationChartH[7]);?>
						C380 <?php echo($durationChartH[7]);?> 390 <?php echo($durationChartH[8]);?> 410 <?php echo($durationChartH[8]);?>
						C430 <?php echo($durationChartH[8]);?> 440 <?php echo($durationChartH[9]);?> 460 <?php echo($durationChartH[9]);?>
					" />
					<g stroke="#ff2d77" stroke-width="3" fill="#232733">
				    <circle class="point-1 <?php echo($highestPoint[0]);?>" cx="10" cy="<?php echo($durationChartH[0]);?>" r="4" />
				    <circle class="point-2 <?php echo($highestPoint[1]);?>" cx="60" cy="<?php echo($durationChartH[1]);?>" r="4" />
				    <circle class="point-3 <?php echo($highestPoint[2]);?>" cx="110" cy="<?php echo($durationChartH[2]);?>" r="4" />
				    <circle class="point-4 <?php echo($highestPoint[3]);?>" cx="160" cy="<?php echo($durationChartH[3]);?>" r="4" />
				    <circle class="point-5 <?php echo($highestPoint[4]);?>" cx="210" cy="<?php echo($durationChartH[4]);?>" r="4" />
				    <circle class="point-6 <?php echo($highestPoint[5]);?>" cx="260" cy="<?php echo($durationChartH[5]);?>" r="4" />
				    <circle class="point-7 <?php echo($highestPoint[6]);?>" cx="310" cy="<?php echo($durationChartH[6]);?>" r="4" />
				    <circle class="point-8 <?php echo($highestPoint[7]);?>" cx="360" cy="<?php echo($durationChartH[7]);?>" r="4" />
				    <circle class="point-9 <?php echo($highestPoint[8]);?>" cx="410" cy="<?php echo($durationChartH[8]);?>" r="4" />
				    <circle class="point-10 <?php echo($highestPoint[9]);?>" cx="460" cy="<?php echo($durationChartH[9]);?>" r="4" />
				  </g>
				  <g>
				  	<text x="0" y="<?php echo($durationChartH[0] + 20);?>" class="duration-chart-text"><?php echo($thisMinutes[0]);?></text>
				  	<text x="50" y="<?php echo($durationChartH[1] + 20);?>" class="duration-chart-text"><?php echo($thisMinutes[1]);?></text>
				  	<text x="100" y="<?php echo($durationChartH[2] + 20);?>" class="duration-chart-text"><?php echo($thisMinutes[2]);?></text>
				  	<text x="150" y="<?php echo($durationChartH[3] + 20);?>" class="duration-chart-text"><?php echo($thisMinutes[3]);?></text>
				  	<text x="200" y="<?php echo($durationChartH[4] + 20);?>" class="duration-chart-text"><?php echo($thisMinutes[4]);?></text>
				  	<text x="250" y="<?php echo($durationChartH[5] + 20);?>" class="duration-chart-text"><?php echo($thisMinutes[5]);?></text>
				  	<text x="300" y="<?php echo($durationChartH[6] + 20);?>" class="duration-chart-text"><?php echo($thisMinutes[6]);?></text>
						<text x="350" y="<?php echo($durationChartH[7] + 20);?>" class="duration-chart-text"><?php echo($thisMinutes[7]);?></text>
				  	<text x="400" y="<?php echo($durationChartH[8] + 20);?>" class="duration-chart-text"><?php echo($thisMinutes[8]);?></text>
				  	<text x="450" y="<?php echo($durationChartH[9] + 20);?>" class="duration-chart-text"><?php echo($thisMinutes[9]);?></text>
				  </g>
			</svg>
			<br />
			<label><?php echo($lang[37]);?><span><?php echo($averageMinutes);?> </span><?php echo($lang[38]);?></label>

		</div>
	</section>

</article>

<article class="settings panel-in <?php echo($showSettings);?>">
	<section class="settings-icon">
		<svg xmlns="http://www.w3.org/2000/svg" class="gear-icon-1" width="120" height="120" viewBox="0 0 120 120"><path d="M32.4 100C33 100 33.5 100.1 34 100.4 38.8 103.6 44.2 105.8 49.8 107 51 107.3 51.8 108.2 52 109.3L53.4 117.1C53.7 118.5 54.9 119.4 56.2 119.4L63.8 119.4C65.1 119.4 66.3 118.5 66.5 117.1L68 109.3C68.2 108.2 69 107.3 70.1 107 75.8 105.8 81.2 103.6 86 100.4 87 99.8 88.2 99.8 89.2 100.5L95.7 105C96.9 105.8 98.4 105.6 99.4 104.7L104.7 99.4C105.6 98.4 105.8 96.9 105 95.8L100.5 89.2C99.8 88.2 99.8 87 100.4 86 103.6 81.2 105.8 75.8 107 70.2 107.2 69 108.1 68.2 109.3 68L117.1 66.6C118.5 66.3 119.4 65.1 119.4 63.8L119.4 56.2C119.4 54.9 118.5 53.7 117.1 53.4L109.3 52C108.2 51.8 107.3 51 107 49.8 105.8 44.2 103.6 38.8 100.4 34 99.8 33 99.8 31.8 100.5 30.8L105 24.3C105.8 23.1 105.7 21.6 104.7 20.6L99.4 15.3C98.4 14.4 96.9 14.2 95.8 15L89.2 19.5C88.2 20.2 87 20.2 86 19.6 81.2 16.4 75.8 14.2 70.2 13 69 12.7 68.2 11.8 68 10.7L66.6 2.9C66.3 1.5 65.1 0.6 63.8 0.6L56.2 0.6C54.9 0.6 53.7 1.5 53.4 2.9L52 10.7C51.8 11.8 51 12.7 49.8 13 44.2 14.2 38.8 16.4 34 19.6 33 20.2 31.8 20.2 30.8 19.5L24.3 15C23.1 14.2 21.6 14.4 20.6 15.3L15.3 20.6C14.3 21.6 14.2 23.1 15 24.2L19.5 30.8C20.2 31.8 20.2 33 19.6 34 16.4 38.8 14.2 44.2 13 49.8 12.7 51 11.8 51.8 10.7 52L2.9 53.4C1.5 53.7 0.6 54.9 0.6 56.2L0.6 63.8C0.6 65.1 1.5 66.3 2.9 66.5L10.7 68C11.8 68.2 12.7 69 13 70.1 14.2 75.8 16.4 81.2 19.6 86 20.2 87 20.2 88.2 19.5 89.2L15 95.7C14.2 96.9 14.3 98.4 15.3 99.3L20.6 104.7C21.6 105.6 23.1 105.8 24.3 105L30.8 100.5C31.3 100.1 31.9 100 32.4 100L32.4 100 32.4 100ZM60 85.5C45.9 85.5 34.5 74.1 34.5 60 34.5 45.9 45.9 34.5 60 34.5 74.1 34.5 85.5 45.9 85.5 60 85.5 74.1 74.1 85.5 60 85.5L60 85.5 60 85.5Z" fill="#789"/></svg>
		<svg xmlns="http://www.w3.org/2000/svg" class="gear-icon-2" width="188" height="188" viewBox="0 0 188 188"><path d="M50.8 156.6C51.6 156.6 52.5 156.9 53.2 157.3 60.8 162.3 69.2 165.8 78.1 167.7 79.8 168 81.2 169.5 81.5 171.2L83.7 183.5C84.1 185.6 85.9 187.1 88.1 187.1L99.9 187.1C102 187.1 103.9 185.6 104.3 183.5L106.5 171.2C106.8 169.4 108.1 168 109.9 167.7 118.7 165.8 127.2 162.3 134.8 157.3 136.3 156.4 138.2 156.4 139.7 157.4L150 164.5C151.8 165.7 154.1 165.5 155.7 164L164 155.7C165.5 154.2 165.7 151.8 164.5 150L157.4 139.7C156.4 138.2 156.3 136.3 157.3 134.8 162.2 127.2 165.7 118.8 167.6 109.9 168 108.2 169.4 106.8 171.2 106.5L183.5 104.3C185.6 103.9 187.1 102.1 187.1 99.9L187.1 88.1C187.1 86 185.6 84.1 183.5 83.7L171.2 81.5C169.4 81.2 168 79.8 167.7 78.1 165.8 69.2 162.3 60.8 157.3 53.2 156.4 51.7 156.4 49.8 157.4 48.3L164.5 38C165.8 36.2 165.5 33.9 164 32.3L155.7 24C154.1 22.5 151.8 22.3 150 23.5L139.7 30.6C138.3 31.6 136.3 31.6 134.8 30.7 127.2 25.7 118.8 22.2 109.9 20.3 108.2 19.9 106.8 18.5 106.5 16.8L104.3 4.5C103.9 2.4 102 0.9 99.9 0.9L88.1 0.9C86 0.9 84.1 2.4 83.7 4.5L81.5 16.8C81.2 18.6 79.8 20 78.1 20.3 69.2 22.2 60.8 25.7 53.2 30.7 51.7 31.6 49.8 31.6 48.3 30.6L38 23.5C36.2 22.3 33.9 22.5 32.3 24L24 32.3C22.5 33.8 22.3 36.2 23.5 38L30.6 48.3C31.6 49.7 31.7 51.7 30.7 53.2 25.8 60.8 22.3 69.2 20.3 78.1 20 79.8 18.6 81.2 16.8 81.5L4.5 83.7C2.4 84.1 0.9 85.9 0.9 88.1L0.9 99.9C0.9 102 2.4 103.9 4.5 104.2L16.8 106.5C18.6 106.8 20 108.1 20.3 109.9 22.3 118.7 25.8 127.2 30.7 134.8 31.7 136.3 31.6 138.2 30.6 139.7L23.5 150C22.3 151.7 22.5 154.1 24 155.6L32.3 164C33.9 165.5 36.2 165.7 38 164.5L48.3 157.4C49 156.9 49.9 156.6 50.8 156.6L50.8 156.6 50.8 156.6ZM94 133.9C72 133.9 54.1 116 54.1 94 54.1 72 72 54.1 94 54.1 116 54.1 133.9 72 133.9 94 133.9 116 116 133.9 94 133.9L94 133.9 94 133.9Z" fill="#FF005A"/></svg>
	</section>
	<span><?php echo($settingsContent);?></span>
</article>

<aside>
	<a href='?ep=-1' class="item add-new-item"><h1><?php echo($lang[6]);?></h1></a>
	<?php echo($itemList);?>
</aside>

<section class="hidden-stuff">
	<svg id="icon-delete" xmlns="http://www.w3.org/2000/svg" width="46" height="46" viewBox="0 0 46 46">
	  <path d="M744.5,754h-2V728h2v26Z" transform="translate(-722 -716)"/>
	  <path d="M736.5,754.077l-2-26,1.994-.154,2,26Z" transform="translate(-722 -716)"/>
	  <path d="M750.5,754.077l-1.994-.154,2-26,1.994,0.154Z" transform="translate(-722 -716)"/>
	  <path d="M752.8,761H734.2a3.99,3.99,0,0,1-3.978-3.58L726.506,722.1l1.988-.209,3.717,35.315A2,2,0,0,0,734.2,759h18.6a2,2,0,0,0,1.99-1.791l3.717-35.314,1.988,0.209-3.717,35.315A3.991,3.991,0,0,1,752.8,761Z" transform="translate(-722 -716)"/>
	  <path d="M761.5,723h-36v-2h36v2Z" transform="translate(-722 -716)"/>
	  <path d="M752.5,721h-2v-2a1,1,0,0,0-1-1h-12a1,1,0,0,0-1,1v2h-2v-2a3,3,0,0,1,3-3h12a3,3,0,0,1,3,3v2Z" transform="translate(-722 -716)"/>
	</svg>

	<svg id="icon-duplicate" xmlns="http://www.w3.org/2000/svg" width="46" height="46" viewBox="0 0 38 46">
	  <path d="M280.5,477h-27a3,3,0,0,1-3-3V439a3,3,0,0,1,3-3h17a1,1,0,0,1,.707.293l12,12a1,1,0,0,1,.293.707v25A3,3,0,0,1,280.5,477Zm-27-39a1,1,0,0,0-1,1v35a1,1,0,0,0,1,1h27a1,1,0,0,0,1-1V449.414L270.086,438H253.5Z" transform="translate(-245.5 -436)"/>
	  <path d="M282.5,450h-12a1,1,0,0,1-1-1V438h2v10h11v2Z" transform="translate(-245.5 -436)"/>
	  <path d="M275.5,482h-27a3,3,0,0,1-3-3V444a3,3,0,0,1,3-3h3v2h-3a1,1,0,0,0-1,1v35a1,1,0,0,0,1,1h27a1,1,0,0,0,1-1v-3h2v3A3,3,0,0,1,275.5,482Z" transform="translate(-245.5 -436)"/>
	</svg>
</section>

<!-- ~Process Episode Deleting -->
<script type='text/javascript'>
	function deleteEpisode(epNumber,epTitle){
		if(confirm('<?php echo($lang[57]);?>'+epTitle+'<?php echo($lang[58]);?>')){
			window.location = '?notf=5&del=d'+epNumber;
			return false;
		}
		else {
			history.back();
		}
	}
</script>
</body>
</html>
