<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" lang="zh-cmn-Hans">
<head>
	<meta charset="utf-8">
	<meta content="all" name="robots" />
	<meta name="author" content="JJ Ying" />
	<meta name="description" content="Podcast RSS Editor" />
	<title>Podcast RSS Editor</title>
	<link rel="stylesheet" rev="stylesheet" href="styles.css" type="text/css" media="all" />
	<link rel="shortcut icon" href="assets/favicon.png" />
	<link href='https://fonts.googleapis.com/css?family=Tulpen+One|Alegreya+Sans:400,100,300,700' rel='stylesheet' type='text/css'>
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
}

$xmlDoc = simplexml_load_file($xmlFileName,'my_node');

//~Notifications
if ($_GET['notf']) {
	$showNotification = "notification-show";
	switch ($_GET['notf']) {
		case '1': $notificationContent = $lang[51]; break;
		case '2': $notificationContent = $lang[52]; break;
		case '3': $notificationContent = $lang[53]; break;
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
	$thisDesc[$t] = $thisItem->description;
	$thisFile[$t] = $thisItem->enclosure['url'];
	$thisImage[$t] = $thisItem->children('itunes', true)->image->attributes()->href;
	$thisShownoteLinks[$t] = substr_count($thisItem->description,"</a>");
	$totalShownoteLinks += $thisShownoteLinks[$t];
	$thisHostsArray[$t] = explode(",", $thisAuthor[$t]);
	$totalHostsArray = array_merge($totalHostsArray, $thisHostsArray[$t]);
	if ($t >= 10) {$jumpto = "#ep-$t";}else {$jumpto = "";} //~Set jump to in item list
	if ( (int) $_GET['ep'] == $t) {$highLightItem = "highlight";}
	else {$highLightItem = "";}
	$thisContent = "<a href='?ep=$t$jumpto' class='item $highLightItem' name='ep-$t'><h1>$thisTitle[$t]</h1><span class='item-date'>$thisDate2[$t]</span></a>";
	$itemList.=$thisContent;
	$t+=1;
}

//~Process summaries
$currentTimezone =  substr($thisDate[0],-5,5);
$totalHours = number_format(($totalSeconds / 3600),1);
$averageMinutes = number_format(($totalSeconds / 60 / ($t-1)),1);
$sinceLastUpdate = ceil((strtotime(now)-strtotime($thisDate[0]))/86400);
$totalHostsNo = count(array_unique($totalHostsArray));
$navDashboard = "
	<ul>
		<li><strong>$sinceLastUpdate</strong><br />$lang[4]</li>
		<li><strong>$t</strong><br />$lang[5]</li>
	</ul>";


//~Initiate
$ep = (int) $_GET['ep']; 
if ($ep >= 0) {
	$ep2 = $t  -$ep;
	$panelTitle = "$lang[43]$ep2$lang[44]";
	$currentTitle = "$thisTitle[$ep]";
	$currentDuration = "$thisSeconds[$ep]";
	$currentHH = floor($thisSeconds[$ep] / 3600);
	$currentMM = floor($thisSeconds[$ep] / 60 - $currentHH * 60);
	$currentSS = $thisSeconds[$ep] - $currentHH * 3600 - $currentMM * 60;
	$currentMonth = gmdate('m',  strtotime($thisDate[$ep]));
	$currentDay = gmdate('d',  strtotime($thisDate[$ep]));
	$currentYear = gmdate('Y',  strtotime($thisDate[$ep]));
	$currentHour = gmdate('H',  strtotime($thisDate[$ep]));
	$currentMinute = gmdate('i',  strtotime($thisDate[$ep]));
	$currentSecond = gmdate('s',  strtotime($thisDate[$ep]));
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
		$thisEdit->pubDate = gmdate(DATE_RFC2822, gmmktime($_POST["newHour"], $_POST["newMinute"], $_POST["newSecond"], $_POST["newMonth"], $_POST["newDay"], $_POST["newYear"]));
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
	
	$fileSize = 	number_format((filesize($xmlFileName) / 1024),1);
	$showDashboard = "panel-show";
	$fullDuration = strtotime($thisDate[0]) - strtotime($thisDate[($t-1)]);
	$averageCycle = number_format(($fullDuration / $t / 86400),1);
	$totalYears = number_format(($fullDuration / 31536000),1);
	$totalSunNo = $totalMonNo = $totalTueNo = $totalWedNo = $totalThuNo = $totalFriNo =$totalSatNo = 0;
	for ($i = 0; $i < $t; $i++) {
		$thisDateD[$i] = date('D', strtotime($thisDate2[$i]));
		switch ($thisDateD[$i])
		{
		case 'Sun': $totalSunNo += 1; break;
		case 'Mon': $totalMonNo += 1; break;
		case 'Tue': $totalTueNo += 1; break;
		case 'Wed': $totalWedNo += 1; break;
		case 'Thu': $totalThuNo += 1; break;
		case 'Fri': $totalFriNo += 1; break;
		case 'Sat': $totalSatNo += 1; break;
		default:
		  $totalSunNo += 1;
		}
	}
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
			<li class='$highestBar[0]'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-1'>
			<rect  class='chart-bar-1' x='0' y='$weekBarY[0]' width='$w1' height='$weekNoHeight[0]'/></svg><label>$lang[30]</label></li>
			
			<li class='$highestBar[1]'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-2'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[1]' width='$w1' height='$weekNoHeight[1]'/></svg><label>$lang[31]</label></li>
			
			<li class='$highestBar[2]'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-3'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[2]' width='$w1' height='$weekNoHeight[2]'/></svg><label>$lang[32]</label></li>
			
			<li class='$highestBar[3]'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-4'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[3]' width='$w1' height='$weekNoHeight[3]'/></svg><label>$lang[33]</label></li>
			
			<li class='$highestBar[4]'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-5'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[4]' width='$w1' height='$weekNoHeight[4]'/></svg><label>$lang[34]</label></li>
			
			<li class='$highestBar[5]'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-6'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[5]' width='$w1' height='$weekNoHeight[5]'/></svg><label>$lang[35]</label></li>
			
			<li class='$highestBar[6]'><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='bar-in-7'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[6]' width='$w1' height='$weekNoHeight[6]'/></svg><label>$lang[36]</label></li>
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
		<form method='post' action='index.php?ep=-1&notf=1'>
			<label for='newXMLFile'>$lang[39]</label>
			<select id='content' name='newXMLFile' class='right-in-3'>
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
			<br />
			<label for='newXMLFile'>$lang[40]</label>
			<select id='language' name='newLanguage' class='right-in-6'>

	";
	foreach (glob('language/*.php', GLOB_BRACE) as $languageFile) {
		ereg("([^\/]*)$", $languageFile, $languageName);
		if ($language == $languageFile) {
			$settingsContent .= "<option selected='selected' value='$languageFile'>$languageName[0]</option>";
		}
		else {
			$settingsContent .= "<option value='$languageFile'>$languageName[0]</option>";	
		}
	}
	$settingsContent .= "
			</select>
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
		$newDate = gmdate(DATE_RFC2822, gmmktime($_POST["newHour"], $_POST["newMinute"], $_POST["newSecond"], $_POST["newMonth"], $_POST["newDay"], $_POST["newYear"]));
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
<section class="notification <?php echo($showNotification);?>"><?php echo($notificationContent);?></section>
<nav>
	<a href="?ep=-1" class="logo"><?php echo($lang[0]);?></a>
		<ul>
			<li><a href="?ep=-1" class="<?php echo($navHighlight1);?>"><svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
			    <path id="Path" class="cls-1" d="M30,43A11.013,11.013,0,0,1,19,32V17a11,11,0,0,1,22,0V32A11.012,11.012,0,0,1,30,43ZM30,8a9.011,9.011,0,0,0-9,9V32a9,9,0,0,0,18,0V17A9.011,9.011,0,0,0,30,8Zm0,39A15.017,15.017,0,0,1,15,32V25a1,1,0,0,1,2,0v7a13,13,0,0,0,26,0V25a1,1,0,0,1,2,0v7A15.017,15.017,0,0,1,30,47Zm3,8H27a1,1,0,0,1,0-2h6A1,1,0,0,1,33,55Zm-3,0a1,1,0,0,1-1-1V46a1,1,0,0,1,2,0v8A1,1,0,0,1,30,55Zm6-31a1,1,0,1,1,1,1A1,1,0,0,1,36,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,33,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,30,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,27,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,24,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,21,24Zm14-3a1,1,0,1,1,1,1A1,1,0,0,1,35,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,32,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,29,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,26,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,23,21Zm13-3a1,1,0,1,1,1,1A1,1,0,0,1,36,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,33,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,30,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,27,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,24,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,21,18Zm14-3a1,1,0,1,1,1,1A1,1,0,0,1,35,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,32,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,29,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,26,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,23,15Zm13-3a1,1,0,1,1,1,1A1,1,0,0,1,36,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,33,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,30,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,27,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,24,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,21,12ZM32,9a1,1,0,1,1,1,1A1,1,0,0,1,32,9ZM29,9a1,1,0,1,1,1,1A1,1,0,0,1,29,9ZM26,9a1,1,0,1,1,1,1A1,1,0,0,1,26,9ZM40,28H20a1,1,0,1,1,0-2H40A1,1,0,0,1,40,28Z"/>
			</svg><?php echo($lang[1]);?></a></li>
			<li><a href="?ep=-2" class="<?php echo($navHighlight2);?>"><svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
			    <path id="Dashboard-2" data-name="Dashboard" class="cls-1" d="M190,54a1,1,0,0,1-1-1V44a1,1,0,0,1,2,0v9A1,1,0,0,1,190,54Zm-8,0a1,1,0,0,1-.914-1.406l4-9a1,1,0,0,1,1.828.813l-4,9A1,1,0,0,1,182,54Zm16,0a1,1,0,0,1-.915-0.594l-4-9a1,1,0,0,1,1.828-.812l4,9A1,1,0,0,1,198,54Zm14-12H168a1,1,0,0,1-1-1V11a1,1,0,0,1,1-1h17a1,1,0,0,1,0,2H169V40h42V12H195a1,1,0,0,1,0-2h17a1,1,0,0,1,1,1V41A1,1,0,0,1,212,42ZM195,16H185a1,1,0,0,1-1-1V7a1,1,0,0,1,1-1h10a1,1,0,0,1,1,1v8A1,1,0,0,1,195,16Zm-9-2h8V8h-8v6Z" transform="translate(-160)"/>
			</svg><?php echo($lang[2]);?></a></li>
			<li><a href="?ep=-3" class="<?php echo($navHighlight3);?>"><svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
			    <path id="settings-2" data-name="settings" class="cls-1" d="M351.331,53h-2.662a3,3,0,0,1-2.952-2.463l-0.385-2.123a18.784,18.784,0,0,1-5.044-2.1l-1.781,1.233a3.06,3.06,0,0,1-3.829-.346L332.8,45.322a3,3,0,0,1-.345-3.829l1.233-1.78a18.808,18.808,0,0,1-2.1-5.045l-2.123-.385A3,3,0,0,1,327,31.331V28.669a3,3,0,0,1,2.463-2.951l2.123-.386a18.808,18.808,0,0,1,2.1-5.045l-1.233-1.78a3,3,0,0,1,.345-3.829l1.883-1.882a3.059,3.059,0,0,1,3.828-.346l1.782,1.233a18.794,18.794,0,0,1,5.044-2.1l0.385-2.122A3,3,0,0,1,348.669,7h2.662a3,3,0,0,1,2.952,2.463l0.385,2.123a18.794,18.794,0,0,1,5.044,2.1l1.781-1.233a3.06,3.06,0,0,1,3.829.346l1.883,1.882a3,3,0,0,1,.345,3.829l-1.233,1.78a18.808,18.808,0,0,1,2.1,5.045l2.123,0.385A3,3,0,0,1,373,28.669v2.662a3,3,0,0,1-2.463,2.951l-2.123.386a18.808,18.808,0,0,1-2.1,5.045l1.233,1.78a3,3,0,0,1-.345,3.829L365.322,47.2a2.981,2.981,0,0,1-2.122.879h0a2.986,2.986,0,0,1-1.706-.533l-1.782-1.233a18.784,18.784,0,0,1-5.044,2.1l-0.385,2.122A3,3,0,0,1,351.331,53Zm-11.073-8.879a0.993,0.993,0,0,1,.542.16,16.856,16.856,0,0,0,5.609,2.331,1,1,0,0,1,.773.8l0.5,2.767a1,1,0,0,0,.984.821h2.662a1,1,0,0,0,.984-0.822l0.5-2.767a1,1,0,0,1,.773-0.8,16.856,16.856,0,0,0,5.609-2.331,1,1,0,0,1,1.112.017l2.32,1.606a1.02,1.02,0,0,0,1.276-.115l1.883-1.882a1,1,0,0,0,.114-1.276l-1.606-2.32a1,1,0,0,1-.018-1.111,16.859,16.859,0,0,0,2.331-5.608,1,1,0,0,1,.8-0.774l2.768-.5A1,1,0,0,0,371,31.331V28.669a1,1,0,0,0-.821-0.984l-2.768-.5a1,1,0,0,1-.8-0.774,16.858,16.858,0,0,0-2.331-5.608,1,1,0,0,1,.018-1.111l1.606-2.32a1,1,0,0,0-.114-1.276l-1.883-1.882a1.02,1.02,0,0,0-1.277-.115L360.312,15.7a1,1,0,0,1-1.112.018,16.862,16.862,0,0,0-5.609-2.331,1,1,0,0,1-.773-0.8l-0.5-2.768A1,1,0,0,0,351.331,9h-2.662a1,1,0,0,0-.984.822l-0.5,2.767a1,1,0,0,1-.773.8,16.862,16.862,0,0,0-5.609,2.331,1,1,0,0,1-1.112-.018l-2.32-1.606a1.02,1.02,0,0,0-1.276.115l-1.883,1.882a1,1,0,0,0-.114,1.276l1.606,2.32a1,1,0,0,1,.018,1.111,16.858,16.858,0,0,0-2.331,5.608,1,1,0,0,1-.8.774l-2.768.5a1,1,0,0,0-.821.983v2.662a1,1,0,0,0,.821.984l2.768,0.5a1,1,0,0,1,.8.774,16.859,16.859,0,0,0,2.331,5.608,1,1,0,0,1-.018,1.111l-1.606,2.32a1,1,0,0,0,.114,1.276l1.883,1.882a1.023,1.023,0,0,0,1.277.115l2.319-1.606A1,1,0,0,1,340.258,44.121ZM350,39a9,9,0,1,1,9-9A9.01,9.01,0,0,1,350,39Zm0-16a7,7,0,1,0,7,7A7.008,7.008,0,0,0,350,23Z" transform="translate(-320)"/>
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
		<section class="edit-link right-in-5"><h3><?php echo $lang[10]?> </h3><span><input type="text" name="newLink" value="<?php echo $currentLink ?>" /></span></section>
		<section class="edit-author right-in-6"><h3><?php echo $lang[11]?> </h3><span><input type="text" name="newAuthor" value="<?php echo $currentAuthor ?>" /></span></section>
		<section class="edit-image right-in-7"><h3><?php echo $lang[12]?> </h3><span><input type="text" name="newImage" value="<?php echo $currentImage ?>" /></span></section>
		<section class="edit-audio right-in-8"><h3><?php echo $lang[13]?> </h3><span><input type="text" name="newFile" value="<?php echo $currentFile ?>" /></span></section>
		<section class="edit-desc right-in-9"><h3><?php echo $lang[14]?> </h3><span><textarea name="newDesc"  /><?php echo $currentDesc ?></textarea></span></section>
		<footer>
			<input type="submit" value="<?php echo($lang[41]);?>" class="right-in-10">
			<input type="checkbox" checked name="yy" value="yes" class="hide"/>	
		</footer>
	</form>
</article>



<article class="dashboard panel-in <?php echo($showDashboard);?>">
	<section class="last-udpate">
		<svg class="dashboard-icons" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 60 60">
		    <path id="Path" class="cls-1" d="M990.5,51a20,20,0,1,1,20-20A20.022,20.022,0,0,1,990.5,51Zm0-38a18,18,0,1,0,18,18A18.021,18.021,0,0,0,990.5,13Zm8,27a1,1,0,0,1-.707-0.293l-8-8A1,1,0,0,1,989.5,31V21a1,1,0,0,1,2,0v9.586l7.707,7.707A1,1,0,0,1,998.5,40Zm10.72-13.282a1,1,0,0,1-.71-1.707,8.5,8.5,0,1,0-12.021-12.021,1,1,0,0,1-1.414-1.414,10.5,10.5,0,1,1,14.845,14.85A0.982,0.982,0,0,1,1009.22,26.718Zm-37.438,0a1,1,0,0,1-.707-0.293,10.5,10.5,0,0,1,14.85-14.85,1,1,0,0,1-1.414,1.414,8.5,8.5,0,0,0-12.022,12.022A1,1,0,0,1,971.782,26.718ZM970.5,52a1,1,0,0,1-.707-1.707l6-6a1,1,0,1,1,1.414,1.414l-6,6A1,1,0,0,1,970.5,52Zm40,0a1,1,0,0,1-.71-0.293l-6-6a1,1,0,1,1,1.42-1.414l6,6A1,1,0,0,1,1010.5,52Zm-20-35a1,1,0,0,1-.71-1.71,1.047,1.047,0,0,1,1.42,0A1,1,0,0,1,990.5,17Zm0,30a0.99,0.99,0,1,1,.71-0.29A1.051,1.051,0,0,1,990.5,47Zm15-15a0.99,0.99,0,0,1-1-1,1.031,1.031,0,0,1,.29-0.71,1.047,1.047,0,0,1,1.42,0,1.031,1.031,0,0,1,.29.71A0.99,0.99,0,0,1,1005.5,32Zm-30,0a1.03,1.03,0,0,1-.71-0.29A1.048,1.048,0,0,1,974.5,31a1.026,1.026,0,0,1,.29-0.71,1.047,1.047,0,0,1,1.42,0,1.031,1.031,0,0,1,.29.71A0.99,0.99,0,0,1,975.5,32Z" transform="translate(-960)"/>
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
	
	<section class="total-hours">
		<svg class="dashboard-icons" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 60 60">
		   <path id="Path" class="cls-1" d="M830.5,15a1,1,0,0,1-1-1V9a1,1,0,0,1,2,0v5A1,1,0,0,1,830.5,15Zm0,37a1,1,0,0,1-1-1V46a1,1,0,1,1,2,0v5A1,1,0,0,1,830.5,52Zm0-19.586-6.707-6.707a1,1,0,1,1,1.414-1.414l5.293,5.293,9.293-9.293a1,1,0,1,1,1.414,1.414Zm0,20.086A22.5,22.5,0,1,1,853,30,22.525,22.525,0,0,1,830.5,52.5Zm0-43A20.5,20.5,0,1,0,851,30,20.523,20.523,0,0,0,830.5,9.5Zm21,21.5h-5a1,1,0,0,1,0-2h5A1,1,0,0,1,851.5,31Zm-37,0h-5a1,1,0,0,1,0-2h5A1,1,0,0,1,814.5,31Z" transform="translate(-800)"/>
		</svg>
		<strong class="scale-in-3"><?php echo($totalHours);?></strong><?php echo($lang[21]);?><br />
		<i><?php echo($lang[22]);?><span><?php echo(number_format(($totalHours / 7.88),1));?></span><?php echo($lang[23]);?></i>
	</section>
	
	<section class="d-file-size">
		<svg class="dashboard-icons" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 60 60">
		   <path id="Path" class="cls-1" d="M670,20.389c-8.945,0-18-2.128-18-6.194S661.055,8,670,8s18,2.128,18,6.194S678.945,20.389,670,20.389ZM670,10c-9.767,0-16,2.484-16,4.194s6.233,4.194,16,4.194,16-2.484,16-4.194S679.767,10,670,10Zm0,18.389c-8.945,0-18-2.128-18-6.194a1,1,0,0,1,2,0c0,1.71,6.233,4.194,16,4.194s16-2.484,16-4.194a1,1,0,0,1,2,0C688,26.261,678.945,28.389,670,28.389Zm0,8c-8.945,0-18-2.128-18-6.194a1,1,0,0,1,2,0c0,1.71,6.233,4.194,16,4.194s16-2.484,16-4.194a1,1,0,0,1,2,0C688,34.261,678.945,36.389,670,36.389Zm0,8c-8.945,0-18-2.128-18-6.194a1,1,0,0,1,2,0c0,1.71,6.233,4.194,16,4.194s16-2.484,16-4.194a1,1,0,0,1,2,0C688,42.261,678.945,44.389,670,44.389Zm0,8c-8.945,0-18-2.128-18-6.194a1,1,0,0,1,2,0c0,1.71,6.233,4.194,16,4.194s16-2.484,16-4.194a1,1,0,0,1,2,0C688,50.261,678.945,52.389,670,52.389ZM653,48a1,1,0,0,1-1-1V14a1,1,0,0,1,2,0V47A1,1,0,0,1,653,48Zm34,0a1,1,0,0,1-1-1V14a1,1,0,0,1,2,0V47A1,1,0,0,1,687,48Z" transform="translate(-640)"/>
		</svg>
		<strong class="scale-in-4"><?php echo($fileSize);?></strong><?php echo($lang[24]);?><br />
		<i><?php echo($lang[25]);?><span><?php echo($totalShownoteLinks);?></span><?php echo($lang[26]);?></i>
	</section>
	
	<section class="d-file-size">
		<svg class="dashboard-icons" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 60 60">
		   <path class="cls-1" d="M509.7,35.262c-4,0-6.4-4.775-6.92-6.209a1.9,1.9,0,0,1-.66-0.7,4.14,4.14,0,0,1-.544-3.366,2.493,2.493,0,0,1,.676-1.015,12.96,12.96,0,0,1,.377-3.1,7.624,7.624,0,0,1,14.141,0,12.916,12.916,0,0,1,.378,3.063,2.264,2.264,0,0,1,.621.852,4.234,4.234,0,0,1-.5,3.563,1.947,1.947,0,0,1-.662.743C516.045,30.609,513.657,35.262,509.7,35.262Zm-6.061-9.856a0.571,0.571,0,0,0-.19.284,2.356,2.356,0,0,0,.43,1.709,1.519,1.519,0,0,1,.713.772c0.014,0.034.026,0.069,0.037,0.106,0.3,1.056,2.344,4.985,5.071,4.985s4.768-3.93,5.072-4.985a1.363,1.363,0,0,1,.655-0.81c0.687-1.259.557-1.772,0.5-1.9a0.95,0.95,0,0,0-.077-0.129,1,1,0,0,1-.712-1.012,11.409,11.409,0,0,0-.3-3.044c-0.036-.123-0.985-3.286-5.138-3.286-4.2,0-5.13,3.258-5.139,3.291a11.39,11.39,0,0,0-.3,3.039,1,1,0,0,1-.622.98h0Zm19.676,22.926H496.71a1,1,0,0,1-1-.989c-0.055-4.976.7-10.368,6.706-12.642a10.331,10.331,0,0,0,3.362-1.985l1.419,1.409a12.1,12.1,0,0,1-4.073,2.447c-3.7,1.4-5.3,4.257-5.41,9.76h24.6c-0.1-5.619-1.7-8.626-5.263-9.974a14.6,14.6,0,0,1-4.107-2.288l1.31-1.512-0.655.756,0.652-.758a12.915,12.915,0,0,0,3.508,1.931c5.99,2.266,6.61,8.423,6.557,12.856A1,1,0,0,1,523.319,48.332Zm-26.319-6h-7a1,1,0,0,1-1-1.011l0-.517c0.034-4.006.072-8.546,5.436-10.575a22.557,22.557,0,0,0,3.2-1.546,13.99,13.99,0,0,1-2.83-4.029,2.005,2.005,0,0,1-.659-0.71,4.154,4.154,0,0,1-.546-3.367,2.521,2.521,0,0,1,.677-1.024,12.963,12.963,0,0,1,.377-3.107,7.027,7.027,0,0,1,7.071-4.777,6.844,6.844,0,0,1,6.937,5.165l-1.955.42a4.86,4.86,0,0,0-4.982-3.585c-4.152,0-5.1,3.165-5.14,3.3a11.318,11.318,0,0,0-.3,3.037,1,1,0,0,1-.616.978,0.563,0.563,0,0,0-.194.293,2.377,2.377,0,0,0,.43,1.714,1.672,1.672,0,0,1,.713.792,0.956,0.956,0,0,1,.037.107,10.973,10.973,0,0,0,3.231,4.051,1,1,0,0,1,.039,1.68,25.843,25.843,0,0,1-4.779,2.48c-3.909,1.479-4.1,4.59-4.139,8.232H497v2Zm33,0h-7v-2h5.984c-0.1-4.038-.662-6.815-4.409-8.232a23.028,23.028,0,0,1-4.81-2.611,1,1,0,0,1,.176-1.718c1.469-.668,2.951-3.275,3.125-3.882a0.956,0.956,0,0,1,.037-0.107,1.665,1.665,0,0,1,.662-0.766,2.34,2.34,0,0,0,.481-1.74,0.522,0.522,0,0,0-.23-0.309,1.082,1.082,0,0,1-.58-0.962,11.461,11.461,0,0,0-.3-3.049c-0.035-.124-0.985-3.289-5.138-3.289a4.641,4.641,0,0,0-4.839,3.562l-1.966-.367a6.615,6.615,0,0,1,6.805-5.194,7.027,7.027,0,0,1,7.071,4.773,12.961,12.961,0,0,1,.378,3.11,2.521,2.521,0,0,1,.677,1.024,4.152,4.152,0,0,1-.545,3.365,1.97,1.97,0,0,1-.659.711,12.066,12.066,0,0,1-2.774,4.005,19.538,19.538,0,0,0,3.141,1.571c5.633,2.132,5.68,7.1,5.717,11.093A1,1,0,0,1,530,42.332Z" transform="translate(-480)"/>
		</svg>
		
		<strong class="scale-in-5"><?php echo($totalHostsNo);?></strong><?php echo($lang[27]);?><br />
		<i><?php echo($lang[28]);?></i>
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
				  <path class="average-line" d="M 0 <?php echo($averageLineH);?> l 500 0" stroke-width="1" fill="none" />
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
		<svg class="gear-icon-1"  width="120px" height="120px" viewBox="0 0 120 120" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
	        <path fill="#778899" d="M32.4232007,99.9724884 C32.9676882,99.9716769 33.5006916,100.129021 33.9574468,100.425402 C38.8067116,103.568164 44.1865291,105.80392 49.8349121,107.023793 C50.9555982,107.265858 51.8195913,108.160029 52.0230527,109.288363 L53.4384089,117.120944 C53.6832953,118.466929 54.8557489,119.445165 56.2238298,119.444958 L63.7591859,119.444958 C65.1281582,119.444917 66.3009298,118.465223 66.5446068,117.118113 L67.959963,109.285532 C68.1634245,108.157199 69.0274175,107.263027 70.1481036,107.020962 C75.7964867,105.801089 81.1763041,103.565333 86.0255689,100.422572 C86.9878036,99.7998778 88.2305707,99.8188769 89.173321,100.470694 L95.7405735,105.016818 C96.8710345,105.772302 98.3753975,105.63672 99.3525624,104.691286 L104.682794,99.3638853 C105.648687,98.395884 105.784494,96.8757894 105.005495,95.7518964 L100.459371,89.1846438 C99.8079449,88.2431065 99.7878349,87.0018684 100.408418,86.0397225 C103.550698,81.1912302 105.78643,75.8124304 107.006809,70.1650879 C107.247862,69.0433018 108.142243,68.1779882 109.271378,67.9741166 L117.10679,66.5587604 C118.460506,66.3195661 119.446683,65.1423631 119.444958,63.7676781 L119.444958,56.2323219 C119.445165,54.8642411 118.466929,53.6917875 117.120944,53.446901 L109.285532,52.0315449 C108.156396,51.8276732 107.262015,50.9623596 107.020962,49.8405735 C105.800643,44.1932122 103.564908,38.8144019 100.422572,33.9659389 C99.8019884,33.003793 99.8220985,31.7625549 100.473525,30.8210176 L105.019648,24.253765 C105.798648,23.1298721 105.66284,21.6097775 104.696947,20.6417761 L99.366716,15.3143756 C98.3891023,14.3674511 96.8829592,14.2318157 95.7518964,14.9888437 L89.1903053,19.5208141 C88.2481315,20.1734983 87.0053578,20.1936151 86.0425532,19.5717669 C81.1931123,16.4293398 75.813358,14.1936102 70.1650879,12.9733765 C69.0444018,12.7313114 68.1804087,11.8371401 67.9769473,10.7088067 L66.5615911,2.873395 C66.3137184,1.52650419 65.1371789,0.550226753 63.7676781,0.555041628 L56.2323219,0.555041628 C54.8633497,0.555083365 53.6905781,1.53477674 53.446901,2.88188714 L52.0315449,10.7144681 C51.8280834,11.8428015 50.9640904,12.7369728 49.8434043,12.9790379 C44.1951341,14.1992716 38.8153799,16.4350012 33.9659389,19.5774283 C33.0031343,20.1992766 31.7603606,20.1791597 30.8181869,19.5264755 L24.2509343,14.9803515 C23.1204734,14.2248677 21.6161104,14.360449 20.6389454,15.3058834 L15.3087142,20.633284 C14.3428213,21.6012853 14.2070134,23.1213799 14.986013,24.2452729 L19.5321369,30.8125254 C20.1835629,31.7540627 20.203673,32.9953009 19.5830897,33.9574468 C16.4407539,38.8059098 14.205018,44.18472 12.9846994,49.8320814 C12.743646,50.9538675 11.8492651,51.8191811 10.7201295,52.0230527 L2.88471785,53.4384089 C1.53978258,53.6831038 0.561863838,54.8539858 0.560703053,56.2209991 L0.560703053,63.7563552 C0.560496576,65.1244361 1.53873276,66.2968897 2.88471785,66.5417761 L10.7201295,67.9571323 C11.8492651,68.161004 12.743646,69.0263175 12.9846994,70.1481036 C14.2050781,75.7954461 16.4408096,81.1742459 19.5830897,86.0227382 C20.203673,86.9848841 20.1835629,88.2261223 19.5321369,89.1676596 L14.986013,95.7349121 C14.2070134,96.8588051 14.3428213,98.3788997 15.3087142,99.346901 L20.6389454,104.674302 C21.61798,105.61812 23.1218591,105.753552 24.253765,104.999833 L30.8181869,100.45371 C31.2923689,100.135005 31.8518922,99.967247 32.4232007,99.9724884 L32.4232007,99.9724884 L32.4232007,99.9724884 Z M60,85.4764107 C45.9297669,85.4764107 34.5235893,74.0702331 34.5235893,60 C34.5235893,45.9297669 45.9297669,34.5235893 60,34.5235893 C74.0702331,34.5235893 85.4764107,45.9297669 85.4764107,60 C85.4608103,74.0637662 74.0637662,85.4608103 60,85.4764107 L60,85.4764107 L60,85.4764107 Z"></path>
		</svg>
		<svg class="gear-icon-2" width="188px" height="188px" viewBox="0 0 188 188" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
			<path fill="#FF005A" d="M50.7963478,156.623565 C51.6493782,156.622294 52.4844168,156.8688 53.2,157.33313 C60.7971815,162.256791 69.2255622,165.759475 78.0746957,167.670609 C79.8304372,168.049844 81.1840263,169.450712 81.5027826,171.218435 L83.7201739,183.489478 C84.1038294,185.598188 85.9406733,187.130758 88.084,187.130435 L99.8893913,187.130435 C102.034114,187.130369 103.871457,185.595516 104.253217,183.485043 L106.470609,171.214 C106.789365,169.446278 108.142954,168.045409 109.898696,167.666174 C118.747829,165.75504 127.17621,162.252356 134.773391,157.328696 C136.280892,156.353142 138.227894,156.382907 139.70487,157.404087 L149.993565,164.526348 C151.764621,165.709939 154.121456,165.497528 155.652348,164.016348 L164.003043,155.670087 C165.516276,154.153552 165.729041,151.77207 164.508609,150.011304 L157.386348,139.722609 C156.36578,138.247534 156.334275,136.302927 157.306522,134.795565 C162.229427,127.199594 165.732073,118.772808 167.644,109.925304 C168.02165,108.167839 169.422847,106.812182 171.191826,106.492783 L183.467304,104.275391 C185.588127,103.900654 187.133136,102.056369 187.130435,99.9026957 L187.130435,88.0973043 C187.130758,85.9539777 185.598188,84.1171337 183.489478,83.7334783 L171.214,81.516087 C169.445021,81.196688 168.043824,79.8410301 167.666174,78.0835652 C165.754341,69.2360324 162.251689,60.8092297 157.328696,53.2133043 C156.356449,51.7059424 156.387954,49.761336 157.408522,48.2862609 L164.530783,37.9975652 C165.751215,36.2367996 165.53845,33.855318 164.025217,32.3387826 L155.674522,23.9925217 C154.142927,22.5090068 151.783303,22.2965112 150.011304,23.4825217 L139.731478,30.5826087 C138.255406,31.6051473 136.308394,31.6366637 134.8,30.6624348 C127.202543,25.739299 118.774261,22.236656 109.925304,20.3249565 C108.169563,19.9457212 106.815974,18.5448528 106.497217,16.7771304 L104.279826,4.50165217 C103.891492,2.39152322 102.048247,0.862021913 99.9026957,0.869565217 L88.0973043,0.869565217 C85.9525812,0.869630604 84.115239,2.40448356 83.7334783,4.51495652 L81.516087,16.786 C81.1973307,18.5537223 79.8437416,19.9545907 78.088,20.3338261 C69.2390435,22.2455255 60.8107618,25.7481685 53.2133043,30.6713043 C51.7049104,31.6455333 49.7578983,31.6140169 48.2818261,30.5914783 L37.9931304,23.4692174 C36.222075,22.285626 33.8652396,22.4980367 32.3343478,23.9792174 L23.9836522,32.3254783 C22.47042,33.8420137 22.2576544,36.2234952 23.478087,37.9842609 L30.6003478,48.2729565 C31.6209153,49.7480316 31.652421,51.6926381 30.6801739,53.2 C25.7571811,60.7959253 22.2545282,69.2227281 20.3426957,78.0702609 C19.9650454,79.8277257 18.5638486,81.1833837 16.7948696,81.5027826 L4.5193913,83.7201739 C2.41232604,84.1035294 0.880253346,85.9379112 0.878434783,88.0795652 L0.878434783,99.8849565 C0.878111303,102.028283 2.41068133,103.865127 4.5193913,104.248783 L16.7948696,106.466174 C18.5638486,106.785573 19.9650454,108.141231 20.3426957,109.898696 C22.2546224,118.746199 25.7572684,127.172985 30.6801739,134.768957 C31.652421,136.276318 31.6209153,138.220925 30.6003478,139.696 L23.478087,149.984696 C22.2576544,151.745461 22.47042,154.126943 23.9836522,155.643478 L32.3343478,163.989739 C33.8681686,165.468388 36.224246,165.680564 37.9975652,164.499739 L48.2818261,157.377478 C49.0247113,156.878175 49.9012977,156.615354 50.7963478,156.623565 L50.7963478,156.623565 L50.7963478,156.623565 Z M94,133.913043 C71.9566348,133.913043 54.0869565,116.043365 54.0869565,94 C54.0869565,71.9566348 71.9566348,54.0869565 94,54.0869565 C116.043365,54.0869565 133.913043,71.9566348 133.913043,94 C133.888603,116.033234 116.033234,133.888603 94,133.913043 L94,133.913043 L94,133.913043 Z" ></path>
		</svg>
	</section>
	<span><?php echo($settingsContent);?></span>
</article>

<aside>
	<a href='?ep=-1' class="item add-new-item"><h1><?php echo($lang[6]);?></h1></a>
	<?php echo($itemList);?>
</aside>

</body>
</html>