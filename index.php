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
	<link href='https://fonts.googleapis.com/css?family=Alegreya+Sans:400,100,300,700' rel='stylesheet' type='text/css'>
</head>
<body>
<?php

//Load the XML file
$xmlDoc = simplexml_load_file('rss.xml','my_node');
//Process episodes
$t = 0;
$totalShownoteLinks = 0;
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
	if ($t >= 10) {$jumpto = "#ep-$t";}else {$jumpto = "";} //Set jump to in item list
	if ( (int) $_GET['ep'] == $t) {$highLightItem = "highlight";}
	else {$highLightItem = "";}
	$thisContent = "<a href='?ep=$t$jumpto' class='item $highLightItem' name='ep-$t'><h1>$thisTitle[$t]</h1><span class='item-date'>$thisDate2[$t]</span></a>";
	$itemList.=$thisContent;
	$t+=1;
}

//Process summaries
$currentTimezone =  substr($thisDate[0],-5,5);
$totalHours = number_format(($totalSeconds / 3600),1);
$averageMinutes = number_format(($totalSeconds / 60 / $t),1);
$sinceLastUpdate = ceil((strtotime(now)-strtotime($thisDate[0]))/86400);
$navDashboard = "
	<ul>
		<li><strong>$sinceLastUpdate</strong><br />Days Since Last Ep.</li>
		<li><strong>$t</strong><br />Total Episodes</li>
	</ul>";


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
	if ($_POST["yy"] =="yes") {	
	
		//Edit existing episodes
	
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
		$xmlDoc->asXML('rss.xml');
		echo "<script>
			location.href='".$_SERVER["HTTP_REFERER"]."#ep-$ep"."';
		</script>";
	}
}
elseif ($ep == -2) {

	//Show Dashboard
	$fileSize = 	number_format((filesize('rss.xml') / 1024),1);
	$showDashboard = "dashboard-show";
	$fullDuration = strtotime($thisDate[0]) - strtotime($thisDate[($t-1)]);
	$averageCycle = number_format(($fullDuration / $t / 86400),1);
	$totalYears = number_format(($fullDuration / 31536000),1);
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
	if ($_POST["yy"] =="yes") { 	
		
		//Add new episode
		
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
		$xmlDoc->asXML('rss.xml');			
		echo "<script>location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
	}	
}

//Extras
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
<nav>
	<a href="?ep=-1" class="logo"><strong>Podcast</strong> RSS Editor</a>
		<ul>
			<li><a href="?ep=-1">Episodes</a></li>
			<li><a href="?ep=-2">Dashboard</a></li>
			<li><a href="?ep=-1">Channel Settings</a></li>
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
<aside>
	<a href='?ep=-1' class="item add-new-item"><h1>Add New Episode</h1></a>
	<?php echo($itemList);?>
</aside>
<article class="dashboard panel-in <?php echo($showDashboard);?>">
	<section class="last-udpate"><strong class="scale-in-1"><?php echo($sinceLastUpdate);?></strong><br />Days Since Last Ep.</section>
	<section><strong class="scale-in-2"><?php echo($t);?></strong><br />Total Episodes</section>
	<section class="total-hours"><strong class="scale-in-3"><?php echo($totalHours);?></strong><br />Total Hours</section>
	<section class="d-total-links"><strong class="scale-in-4"><?php echo($totalShownoteLinks);?></strong><br />Links in Shownotes</section>
	<section class="d-file-size"><strong class="scale-in-5"><?php echo($fileSize);?></strong><br />KB for RSS file</section>
	
	<section class="d-average-cycle"><strong class="scale-in-2"><?php echo($averageCycle);?></strong><br />Average Update Cycle</section>
	<section><strong class="scale-in-3"><?php echo($totalYears);?></strong><br />Years after first Episode</section>
	<section class="total-hours"><strong class="scale-in-4"><?php echo($totalHours);?></strong><br />Total Hours</section>
	<section class="d-total-links"><strong class="scale-in-5"><?php echo($totalShownoteLinks);?></strong><br />Links in Shownotes</section>
	<section class="d-file-size"><strong class="scale-in-5"><?php echo($fileSize);?></strong><br />KB for RSS file</section>
</article>
<script type="text/javascript">
	
</script>

</body>
</html>