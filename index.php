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
	$thisDate2[$t] = gmdate('M j, Y', strtotime($thisDate[$t]));
	$thisDesc[$t] = $thisItem->description;
	$thisFile[$t] = $thisItem->enclosure['url'];
	$thisImage[$t] = $thisItem->children('itunes', true)->image->attributes()->href;
	$thisShownoteLinks[$t] = substr_count($thisItem->description,"</a>");
	$totalShownoteLinks += $thisShownoteLinks[$t];
	if ( (int) $_GET['ep'] == $t) {$highLightItem = "highlight";}
	else {$highLightItem = "";}
	$thisContent = "<a href='?ep=$t' class='item $highLightItem'><h1>$thisTitle[$t]</h1><span class='item-date'>$thisDate2[$t]</span></a>";
	$itemList.=$thisContent;
	$t+=1;
}

//Process summaries
$totalHours = number_format(($totalSeconds / 3600),1);
$averageMinutes = number_format(($totalSeconds / 60 / $t),1);
$sinceLastUpdate = ceil((strtotime(now)-strtotime($thisDate[0]))/86400);
$dashboardContent = "
	<ul>
		<li><strong>$sinceLastUpdate</strong><br />Days Since Last Ep.</li>
		<li><strong>$t</strong><br />Total Episodes</li>
		<li><strong>$totalHours</strong><br />Total Hours</li>
		<li><strong>$averageMinutes</strong><br />Ave. Minutes per Ep.</li>
		<li><strong>$totalShownoteLinks</strong><br />Links in Shownotes</li>
	</ul>";


$ep = (int) $_GET['ep']; 
if ($ep >= 0) {
	$ep2 = $ep + 1;
	$panelTitle = "Edit Episode #$ep2";
	$currentTitle = "$thisTitle[$ep]";
	$currentDuration = "$thisSeconds[$ep]";
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
	if ($_POST["yy"] =="yes") {	//Edit existing episodes
		$xmlDoc->channel->item[$ep]->title = $_POST["newTitle"];
		$xmlDoc->channel->item[$ep]->link =  $_POST["newLink"];
		$xmlDoc->asXML('rss.xml');
		echo "<script>location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
	}
}
else {
	$panelTitle = "Add New Item";
	$currentAuthor = "$thisAuthor[0]";
	if ($_POST["yy"] =="yes") { 	//Add new episode
		$NS = array( 
		    'itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd' 
		);
		$xmlDoc->registerXPathNamespace('itunes', $NS['itunes']); 
		$newItem = $xmlDoc->channel->addNewItem();
		$newItem->addChild('title', $_POST["newTitle"]);
		$newItem->addChild('description', $_POST["newTitle"]);
		$newItem->addChild('link', $_POST["newTitle"]);
		$newItem->addChild('explicit', $_POST["newTitle"],$NS['itunes']);
		$newItem->addChild('guid', $_POST["newTitle"]);
		$newItem->addChild('author', $_POST["newTitle"]);
		$newItem->addChild('image', $_POST["newTitle"],$NS['itunes']);
		$newItem->addChild('pubDate', $_POST["newTitle"]);
		$newItem->addChild('enclosure', $_POST["newTitle"]);
		$newItem->addChild('duration', '★★★★★★★',$NS['itunes']);
		$xmlDoc->asXML('rss.xml');			
		echo "<script>location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
	}	
}

//Extras
class my_node extends SimpleXMLElement
{
    public function prependChild($name)
    {
        $dom = dom_import_simplexml($this);

        $new = $dom->insertBefore(
            $dom->ownerDocument->createElement($name),
            $dom->firstChild
        );
        return simplexml_import_dom($new, get_class($this));
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
	<a href="#" class="logo"><strong>Podcast</strong> RSS Editor</a>
	<section class="dashboard"><?php echo($dashboardContent);?></section>
</nav>
<article class="panel edit-panel">	
	<h2 class="panel-title right-in-1"><?php echo $panelTitle ?></h2>
	<form action="index.php?ep=<?php echo $ep?>" method="post">
		<section class="edit-title right-in-2"><h3>Title: </h3><input type="text" name="newTitle" value="<?php echo $currentTitle ?>" /></section>
		<section class="edit-date right-in-3"><h3>Publish Time: </h3><input type="text" name="newDate" value="<?php echo $currentDate2 ?>" /></section>
		<section class="edit-duration right-in-4"><h3>Duration: </h3><input type="text" name="newDuration" value="<?php echo $currentDuration ?>" /></section>
		<section class="edit-link right-in-5"><h3>Link: </h3><input type="text" name="newLink" value="<?php echo $currentLink ?>" /></section>
		<section class="edit-author right-in-6"><h3>Authors: </h3><input type="text" name="newAuthor" value="<?php echo $currentAuthor ?>" /></section>
		<section class="edit-image right-in-7"><h3>Cover Image: </h3><input type="text" name="newDuration" value="<?php echo $currentImage ?>" /></section>
		<section class="edit-audio right-in-8"><h3>Audio File: </h3><input type="text" name="newAudio" value="<?php echo $currentFile ?>" /></section>
		
		<section class="edit-desc right-in-9"><h3>Description: </h3><textarea name="newDesc"  /><?php echo $currentDesc ?></textarea></section>
	<input type="submit" value="Save" class="right-in-10">
	<input type="checkbox" checked name="yy" value="yes" class="hide"/>	
	</form>
</article>
<aside>
	<a href='?ep=-1' class="item add-new-item"><h1>Add New Episode</h1></a>
	<?php echo($itemList);?>
</aside>

<script type="text/javascript">
	
</script>

</body>
</html>