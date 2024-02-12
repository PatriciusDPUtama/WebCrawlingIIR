<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
		td,th,tr,table{
			border: 1px solid black;
			border-collapse: collapse;
			padding: 5px 5px 5px 5px;

		}
		td{
			font-size: 12px;
		}
		table{
			margin-left: auto; 
  margin-right: auto;
  max-width: 90%;
		}

		body {
			margin: 0px 0px 0px 0px;
			background-color: #B7C4CF;
			font-family: Lucida Sans Unicode;
		}

		.topnav {
			overflow: hidden;
			background-color: #967E76;
			width: 100%;
		}

		.topnav a {
			float: right;
			color: #f2f2f2;
			text-align: center;
			padding: 14px 16px;
			text-decoration: none;
			font-size: 14px;

		}

		.topnav a:hover {
			background-color: #D7C0AE;
			color: black;
		}

		.topnav a.active {
			background-color: #EEE3CB;
			color: black;
		}

		.center {
			position: absolute;
			margin: auto;
			top: 10%;
			width: 98%;
			padding: 10px;
			text-align: center;
		}
		.container {
			height: 100%;
		}

		.full-height {
			height: 100%;
		}
		p{
			font-size: 20px;
		}
		h1{
			font-size: 40px;
		}
		img{
			height: 250px;
		}
		.crawl{
			width: 400px;
			border-radius: 15px;
			height: 20px;
			padding: 10px;
		}
		.button{
			font-family: Lucida Sans Unicode;
			border-radius: 5px;
			width: 120px;
			padding: 8px;
			margin: 10px;
		}
	</style>
</head>
<body>
	<?php
	$searchword = "";

	if(isset($_POST['crawls'])){
		$searchword = $_POST["keyword"];
	}

	echo '	<div class="topnav">
	<a href="project_eval.php">Evaluation</a>
	<a href="project_classify.php">Classification</a>
	<a href="project_crawl.php">Crawling</a>
	<a class="active" href="home.php">Home</a>
	</div>

	<div class="container">
	<div style="padding-left:16px" class="center">
	<h1><img src="https://cdn-icons-png.flaticon.com/512/3003/3003388.png"></h1>
	<form action="" method="POST">
	<p>Input Keyword &nbsp;&nbsp;<input type="text" name="keyword" class="crawl" placeholder="&#128269;&nbsp;Search" value="'.$searchword.'"> 
	<input type="submit" name="crawls" value="Find" class="button">
	</p>
	</form>';


	require_once __DIR__ . '/vendor/autoload.php';
	include_once('simple_html_dom.php');
	use Phpml\Classification\KNearestNeighbors;
	use Phpml\FeatureExtraction\TokenCountVectorizer;
	use Phpml\Tokenization\WhitespaceTokenizer;
	use Phpml\FeatureExtraction\TfIdfTransformer;
	$stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();
	$stemmer  = $stemmerFactory->createStemmer();

	$stopwordFactory = new \Sastrawi\StopWordRemover\StopWordRemoverFactory();
	$stopword = $stopwordFactory->createStopWordRemover();

	$mysqli = new mysqli("localhost","root","","uas_iir");
	if($mysqli->connect_errno)
	{
		echo "Failed to connect to MySQL";
	}

	if(isset($_POST["crawls"]))
	{
		$sampledata=array();
		$titlearray=array();
		$datearray=array();
		$categoryarray=array();
		$preprocess=array();
		$resultsim1 = array();
		$resultsim2 = array();

		$searchword = $_POST["keyword"];
		$keyword = $_POST["keyword"];
		$keywordStem = $stemmer->stem($keyword);
		$keywordStop = $stopword->remove($keywordStem);

		$sql = "SELECT * FROM news WHERE title LIKE '%".$keyword."%' OR title_preprocess LIKE '%".$keyword."%'";
		$res = $mysqli->query($sql);
		$countEx=$res->num_rows;
		if($countEx < 1){
			echo "There is no news with the keyword <b>".$keyword."</b>.Please try another keyword.";
			die();
		}
		while ($row=$res->fetch_assoc()) {
			$title = $row['title'];
			$category = $row['category'];
			$date = $row['date'];

			$titleStem = $stemmer->stem($title);
			$titleStop = $stopword->remove($titleStem);

			array_push($sampledata, $titleStop);
			array_push($titlearray,$title);
			array_push($preprocess,$titleStop);
			array_push($categoryarray,$category);
			array_push($datearray,$date);
		}
		echo "<table>
		<tr>
		<th>No</th>
		<th>Title</th>
		<th>Preprocessed Title</th>
		<th>Category</th>
		<th>Date</th>
		<th>Dice Method</th>
		<th>Jaccard Method</th>
		</tr>";

		array_push($sampledata,$keywordStop);

		$tf = new TokenCountVectorizer(new WhitespaceTokenizer());
		$tf->fit($sampledata);
		$tf->transform($sampledata);
		$vocabulary = $tf->getVocabulary();

		$tfidf = new TfIdfTransformer($sampledata);
		$tfidf ->transform($sampledata);
		$query_idx = count($sampledata)-1;

		for ($a=0; $a <$query_idx; $a++) { 
			$numerator = 0.0;
			$denom_wkq = 0.0;
			$denom_wkj = 0.0;
			$similarity1 = 0.0;
			$similarity2 = 0.0;

			for ($b=0; $b < count($sampledata[$a]) ; $b++) { 
				$numerator += $sampledata[$query_idx][$b] * $sampledata[$a][$b];
				$denom_wkq += pow($sampledata[$query_idx][$b],2);
				$denom_wkj += pow($sampledata[$a][$b],2);
			}

			if((0.5*$denom_wkq + 0.5*$denom_wkj)!=0){
				$similarity1 = round($numerator/(0.5*$denom_wkq + 0.5*$denom_wkj),5);
			}
			else{
				$similarity1 = 0.0;
			}	

			if(($denom_wkq + $denom_wkj -$numerator)!=0){
				$similarity2= round($numerator/($denom_wkq+$denom_wkj - $numerator),5);
			}
			else{
				$similarity2 = 0.0;
			}		
			array_push($resultsim1,$similarity1);
			array_push($resultsim2,$similarity2);
		}

		$maxnum = max($resultsim1);
		$maxidx = array_search($maxnum, $resultsim1);

		array_multisort($resultsim1,SORT_DESC,$titlearray,$preprocess,$datearray,$categoryarray,$resultsim2);

		foreach ($titlearray as $key => $tt) {
			echo "<tr>";
			echo "<td>".($key+1)."</td>";
			echo "<td>".$titlearray[$key]."</td>";
			echo "<td>".$preprocess[$key]."</td>";
			echo "<td>".$categoryarray[$key]."</td>";
			echo "<td>".$datearray[$key]."</td>";
			echo "<td>".$resultsim1[$key]."</td>";
			echo "<td>".$resultsim2[$key]."</td>";
			echo "</tr>";
		}

		$getweight = array();
		foreach ($sampledata[$maxidx] as $key => $items) {
			array_push($getweight,$items);
		}

		$topknum = max($getweight);
		$topkidx = array_search($topknum, $getweight);
		echo "<br>";
		echo "Try searching : <b> ".$searchword." ".$vocabulary[$topkidx]."<b><br><br>";
	}
	?>
</div>
</div>
</body>
</html>
