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
//~Load the XML file

if ($_POST["newXMLFile"]) {
	$oldXMLFileName = $xmlFileName;
	$xmlFileName = $_POST["newXMLFile"];
	$content = file_get_contents('config.php');
	$content = str_replace($oldXMLFileName, $_POST["newXMLFile"],$content);
	file_put_contents('config.php',$content);
}

$xmlDoc = simplexml_load_file($xmlFileName,'my_node');

//~Notifications
if ($_GET['notf']) {
	$showNotification = "notification-show";
	switch ($_GET['notf']) {
		case '1': $notificationContent = 'New XML file loaded'; break;
		case '2': $notificationContent = 'Episode updated'; break;
		case '3': $notificationContent = 'New episode created'; break;
	}
}
//~Process episodes
$t = 0;
$totalShownoteLinks = 0;
$totalHostsArray = array();
foreach($xmlDoc->channel->item as $thisItem){
	$thisDuration = $thisItem->children('itunes', true)->duration;
	if (substr($thisDuration,-3,1) == ':') {
		$thisSeconds[$t] = substr($thisDuration,0,2) * 3600 + substr($thisDuration,3,2) * 60 + substr($thisDuration,6,2);
	}
	else {
		$thisSeconds[$t] = $thisDuration;
	}
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
$averageMinutes = number_format(($totalSeconds / 60 / $t),1);
$sinceLastUpdate = ceil((strtotime(now)-strtotime($thisDate[0]))/86400);
$totalHostsNo = count(array_unique($totalHostsArray));
$navDashboard = "
	<ul>
		<li><strong>$sinceLastUpdate</strong><br />Days Since Last Ep.</li>
		<li><strong>$t</strong><br />Total Episodes</li>
	</ul>";


//~Initiate
$ep = (int) $_GET['ep']; 
if ($ep >= 0) {
	$ep2 = $t  -$ep;
	$panelTitle = "Edit Episode #$ep2";
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
		$j ++;
	}
	$weekDay = "
		<ul>
			<li><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='$highestBar[0] bar-in-1'>
		
			
			<rect  class='chart-bar-1' x='0' y='$weekBarY[0]' width='$w1' height='$weekNoHeight[0]'/></svg><label>Sun</label></li>
			
			<li><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='$highestBar[1] bar-in-2'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[1]' width='$w1' height='$weekNoHeight[1]'/></svg><label>Mon</label></li>
			
			<li><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='$highestBar[2] bar-in-3'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[2]' width='$w1' height='$weekNoHeight[2]'/></svg><label>Tue</label></li>
			
			<li><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='$highestBar[3] bar-in-4'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[3]' width='$w1' height='$weekNoHeight[3]'/></svg><label>Wed</label></li>
			
			<li><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='$highestBar[4] bar-in-5'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[4]' width='$w1' height='$weekNoHeight[4]'/></svg><label>Thu</label></li>
			
			<li><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='$highestBar[5] bar-in-6'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[5]' width='$w1' height='$weekNoHeight[5]'/></svg><label>Fri</label></li>
			
			<li><svg xmlns='http://www.w3.org/2000/svg' width='$w1' height='$barHeight' viewBox='0 0 $w1 $barHeight' class='$highestBar[6] bar-in-7'>
			<rect class='chart-bar-1' x='0' y='$weekBarY[6]' width='$w1' height='$weekNoHeight[6]'/></svg><label>Sat</label></li>
		</ul>
	";
	$navHighlight2 = "highlight";
}
elseif ($ep == -3) {
	//~Show Settings
	$showSettings = "panel-show";
	$navHighlight3 = "highlight";
	$settingsContent = "
		<form method='post' action='index.php?ep=-1&notf=1'>
			<label for='newXMLFile'>Select the file to edit</label>
			<select id='content' name='newXMLFile' class='right-in-8'>
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
			<label><br />
			And...there is nothing else to set....<br />for now...</label>
			<input type='submit' value='Save' class='right-in-10'>
		</form>
	";
}
else {
	$panelTitle = "Add New Episode";
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
	<a href="?ep=-1" class="logo"><strong>Podcast</strong> RSS Editor</a>
		<ul>
			<li><a href="?ep=-1" class="<?php echo($navHighlight1);?>"><svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
			    <path id="Path" class="cls-1" d="M30,43A11.013,11.013,0,0,1,19,32V17a11,11,0,0,1,22,0V32A11.012,11.012,0,0,1,30,43ZM30,8a9.011,9.011,0,0,0-9,9V32a9,9,0,0,0,18,0V17A9.011,9.011,0,0,0,30,8Zm0,39A15.017,15.017,0,0,1,15,32V25a1,1,0,0,1,2,0v7a13,13,0,0,0,26,0V25a1,1,0,0,1,2,0v7A15.017,15.017,0,0,1,30,47Zm3,8H27a1,1,0,0,1,0-2h6A1,1,0,0,1,33,55Zm-3,0a1,1,0,0,1-1-1V46a1,1,0,0,1,2,0v8A1,1,0,0,1,30,55Zm6-31a1,1,0,1,1,1,1A1,1,0,0,1,36,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,33,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,30,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,27,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,24,24Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,21,24Zm14-3a1,1,0,1,1,1,1A1,1,0,0,1,35,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,32,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,29,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,26,21Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,23,21Zm13-3a1,1,0,1,1,1,1A1,1,0,0,1,36,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,33,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,30,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,27,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,24,18Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,21,18Zm14-3a1,1,0,1,1,1,1A1,1,0,0,1,35,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,32,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,29,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,26,15Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,23,15Zm13-3a1,1,0,1,1,1,1A1,1,0,0,1,36,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,33,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,30,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,27,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,24,12Zm-3,0a1,1,0,1,1,1,1A1,1,0,0,1,21,12ZM32,9a1,1,0,1,1,1,1A1,1,0,0,1,32,9ZM29,9a1,1,0,1,1,1,1A1,1,0,0,1,29,9ZM26,9a1,1,0,1,1,1,1A1,1,0,0,1,26,9ZM40,28H20a1,1,0,1,1,0-2H40A1,1,0,0,1,40,28Z"/>
			</svg>Episodes</a></li>
			<li><a href="?ep=-2" class="<?php echo($navHighlight2);?>"><svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
			    <path id="Dashboard-2" data-name="Dashboard" class="cls-1" d="M190,54a1,1,0,0,1-1-1V44a1,1,0,0,1,2,0v9A1,1,0,0,1,190,54Zm-8,0a1,1,0,0,1-.914-1.406l4-9a1,1,0,0,1,1.828.813l-4,9A1,1,0,0,1,182,54Zm16,0a1,1,0,0,1-.915-0.594l-4-9a1,1,0,0,1,1.828-.812l4,9A1,1,0,0,1,198,54Zm14-12H168a1,1,0,0,1-1-1V11a1,1,0,0,1,1-1h17a1,1,0,0,1,0,2H169V40h42V12H195a1,1,0,0,1,0-2h17a1,1,0,0,1,1,1V41A1,1,0,0,1,212,42ZM195,16H185a1,1,0,0,1-1-1V7a1,1,0,0,1,1-1h10a1,1,0,0,1,1,1v8A1,1,0,0,1,195,16Zm-9-2h8V8h-8v6Z" transform="translate(-160)"/>
			</svg>Dashboard</a></li>
			<li><a href="?ep=-3" class="<?php echo($navHighlight3);?>"><svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60">
			    <path id="settings-2" data-name="settings" class="cls-1" d="M351.331,53h-2.662a3,3,0,0,1-2.952-2.463l-0.385-2.123a18.784,18.784,0,0,1-5.044-2.1l-1.781,1.233a3.06,3.06,0,0,1-3.829-.346L332.8,45.322a3,3,0,0,1-.345-3.829l1.233-1.78a18.808,18.808,0,0,1-2.1-5.045l-2.123-.385A3,3,0,0,1,327,31.331V28.669a3,3,0,0,1,2.463-2.951l2.123-.386a18.808,18.808,0,0,1,2.1-5.045l-1.233-1.78a3,3,0,0,1,.345-3.829l1.883-1.882a3.059,3.059,0,0,1,3.828-.346l1.782,1.233a18.794,18.794,0,0,1,5.044-2.1l0.385-2.122A3,3,0,0,1,348.669,7h2.662a3,3,0,0,1,2.952,2.463l0.385,2.123a18.794,18.794,0,0,1,5.044,2.1l1.781-1.233a3.06,3.06,0,0,1,3.829.346l1.883,1.882a3,3,0,0,1,.345,3.829l-1.233,1.78a18.808,18.808,0,0,1,2.1,5.045l2.123,0.385A3,3,0,0,1,373,28.669v2.662a3,3,0,0,1-2.463,2.951l-2.123.386a18.808,18.808,0,0,1-2.1,5.045l1.233,1.78a3,3,0,0,1-.345,3.829L365.322,47.2a2.981,2.981,0,0,1-2.122.879h0a2.986,2.986,0,0,1-1.706-.533l-1.782-1.233a18.784,18.784,0,0,1-5.044,2.1l-0.385,2.122A3,3,0,0,1,351.331,53Zm-11.073-8.879a0.993,0.993,0,0,1,.542.16,16.856,16.856,0,0,0,5.609,2.331,1,1,0,0,1,.773.8l0.5,2.767a1,1,0,0,0,.984.821h2.662a1,1,0,0,0,.984-0.822l0.5-2.767a1,1,0,0,1,.773-0.8,16.856,16.856,0,0,0,5.609-2.331,1,1,0,0,1,1.112.017l2.32,1.606a1.02,1.02,0,0,0,1.276-.115l1.883-1.882a1,1,0,0,0,.114-1.276l-1.606-2.32a1,1,0,0,1-.018-1.111,16.859,16.859,0,0,0,2.331-5.608,1,1,0,0,1,.8-0.774l2.768-.5A1,1,0,0,0,371,31.331V28.669a1,1,0,0,0-.821-0.984l-2.768-.5a1,1,0,0,1-.8-0.774,16.858,16.858,0,0,0-2.331-5.608,1,1,0,0,1,.018-1.111l1.606-2.32a1,1,0,0,0-.114-1.276l-1.883-1.882a1.02,1.02,0,0,0-1.277-.115L360.312,15.7a1,1,0,0,1-1.112.018,16.862,16.862,0,0,0-5.609-2.331,1,1,0,0,1-.773-0.8l-0.5-2.768A1,1,0,0,0,351.331,9h-2.662a1,1,0,0,0-.984.822l-0.5,2.767a1,1,0,0,1-.773.8,16.862,16.862,0,0,0-5.609,2.331,1,1,0,0,1-1.112-.018l-2.32-1.606a1.02,1.02,0,0,0-1.276.115l-1.883,1.882a1,1,0,0,0-.114,1.276l1.606,2.32a1,1,0,0,1,.018,1.111,16.858,16.858,0,0,0-2.331,5.608,1,1,0,0,1-.8.774l-2.768.5a1,1,0,0,0-.821.983v2.662a1,1,0,0,0,.821.984l2.768,0.5a1,1,0,0,1,.8.774,16.859,16.859,0,0,0,2.331,5.608,1,1,0,0,1-.018,1.111l-1.606,2.32a1,1,0,0,0,.114,1.276l1.883,1.882a1.023,1.023,0,0,0,1.277.115l2.319-1.606A1,1,0,0,1,340.258,44.121ZM350,39a9,9,0,1,1,9-9A9.01,9.01,0,0,1,350,39Zm0-16a7,7,0,1,0,7,7A7.008,7.008,0,0,0,350,23Z" transform="translate(-320)"/>
			</svg>Settings</a></li>
		</ul>
	<section class="nav-dashboard"><?php echo($navDashboard);?></section>
</nav>

<article class="panel edit-panel">	
	<h2 class="panel-title right-in-1"><?php echo $panelTitle ?></h2>
	<form action="index.php?ep=<?php echo $ep?>" method="post">
		<section class="edit-title right-in-2"><h3>Title: </h3><span><input type="text" name="newTitle" value="<?php echo $currentTitle ?>" /></span></section>
		<section class="edit-date right-in-3">
			<h3>Publish Time: </h3>
			<span>
				<input type="text" name="newMonth" value="<?php echo $currentMonth ?>" /><label for="newMonth">Month</label>
				<input type="text" name="newDay" value="<?php echo $currentDay ?>" /><label for="newDay">Day</label>
				<input type="text" name="newYear" value="<?php echo $currentYear ?>" /><label for="newYear">Year</label>
				<input type="text" name="newHour" value="<?php echo $currentHour ?>" /><label for="newHour">Hour</label>
				<input type="text" name="newMinute" value="<?php echo $currentMinute ?>" /><label for="newMinute">Minute</label>
				<input type="text" name="newSecond" value="<?php echo $currentSecond ?>" /><label for="newSecond">Second</label>
			</span></section>
		<section class="edit-duration right-in-4">
			<h3>Duration: </h3>
			<span>
				<input type="text" name="newHH" value="<?php echo $currentHH ?>" /><label for="newHH">HH</label>
				<input type="text" name="newMM" value="<?php echo $currentMM ?>" /><label for="newMM">MM</label>
				<input type="text" name="newSS" value="<?php echo $currentSS ?>" /><label for="newSS">SS</label>
			</span>
		</section>
		<section class="edit-link right-in-5"><h3>Link: </h3><span><input type="text" name="newLink" value="<?php echo $currentLink ?>" /></span></section>
		<section class="edit-author right-in-6"><h3>Authors: </h3><span><input type="text" name="newAuthor" value="<?php echo $currentAuthor ?>" /></span></section>
		<section class="edit-image right-in-7"><h3>Cover Image: </h3><span><input type="text" name="newImage" value="<?php echo $currentImage ?>" /></span></section>
		<section class="edit-audio right-in-8"><h3>Audio File: </h3><span><input type="text" name="newFile" value="<?php echo $currentFile ?>" /></span></section>
		<section class="edit-desc right-in-9"><h3>Description: </h3><span><textarea name="newDesc"  /><?php echo $currentDesc ?></textarea></span></section>
		<footer>
			<input type="submit" value="Save" class="right-in-10">
			<input type="checkbox" checked name="yy" value="yes" class="hide"/>	
		</footer>
	</form>
</article>



<article class="dashboard panel-in <?php echo($showDashboard);?>">
	<section class="last-udpate"><strong class="scale-in-1"><?php echo($sinceLastUpdate);?></strong>Days<br /><i>since last episode, your average update cycle is <span><?php echo($averageCycle);?></span> days.</i></section>
	<section><strong class="scale-in-2"><?php echo($t);?></strong>Episodes<br /><i>in total after your first show <br /><span><?php echo($totalYears);?></span> years ago.</i></section>
	<section class="total-hours"><strong class="scale-in-3"><?php echo($totalHours);?></strong>Hours<br /><i>podcasted, which is <span><?php echo(number_format(($totalHours / 7.88),1));?></span> 'The Hobbit' trilogy combined.</i></section>
	<section class="d-file-size"><strong class="scale-in-4"><?php echo($fileSize);?></strong>KB<br /><i>for RSS file size, including <span><?php echo($totalShownoteLinks);?></span> links <br />in shownotes.</i></section>
	<section class="d-file-size"><strong class="scale-in-5"><?php echo($totalHostsNo);?></strong>People<br /><i>showed in the aurthor list.</i></section>

	<section class="d-weekday"><div class="scale-in-4"><?php echo($weekDay);?></div></section>

</article>

<article class="settings panel-in <?php echo($showSettings);?>">
	<span><?php echo($settingsContent);?></span>
</article>

<aside>
	<a href='?ep=-1' class="item add-new-item"><h1>Add New Episode</h1></a>
	<?php echo($itemList);?>
</aside>

</body>
</html>