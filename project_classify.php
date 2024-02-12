<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title></title>
	<style type="text/css">
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
  max-width: 60%;
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
			top: 30%;
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
		.table{
			margin: auto;
		}
	</style>
</head>
<body>
	<div class="topnav">
		<a href="project_eval.php">Evaluation</a>
		<a class="active" href="project_classify.php">Classification</a>
		<a href="project_crawl.php">Crawling</a>
		<a href="home.php">Home</a>
	</div>
	<div class="container">
		<div class="center">
			<?php
			$searchword = "";

			if(isset($_POST['crawls'])){
				$searchword = $_POST["keyword"];
			}
			echo "<h1>Classification Data</h1>";
			echo '<form method="POST" action="">';
			echo '<p>Input Keyword &nbsp;&nbsp; <input type="text" name="keyword" class="crawl" value="'.$searchword.'" placeholder="&#128269;&nbsp;Search"><input type="submit" name="crawls" class="button" value="Enter"><br>
			<input type ="radio" id="cnnweb" checked name="website" value="cnnindonesia.com">CNN</input>
			<input type ="radio" id="kompasweb"name="website" value="www.kompas.com">Kompas</input>
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

			if(isset($_POST["crawls"]) && isset($_POST['website']))
			{   
				error_reporting(E_ALL ^ E_WARNING);

				$mysqli = new mysqli("localhost","root","","uas_iir");
				if($mysqli->connect_errno)
				{
					echo "Failed to connect to MySQL";
				}

				$sampleDB = array();
				$categoryDB = array();
				$res = $mysqli->query("SELECT * FROM news");
				while ($row=$res->fetch_assoc()) {
					$titDB = $row['title'];
					$catDB = $row['category'];

					$stemDB = $stemmer->stem($titDB);
					$stopDB = $stopword->remove($stemDB);

					array_push($sampleDB, $stopDB);
					array_push($categoryDB, $catDB);
				}


				$searchword = $_POST["keyword"];
				$website = $_POST['website'];
				$keyword = "+".str_replace(" ","+",$_POST["keyword"]);
				$lcase = strtolower($_POST['keyword']);

				echo "<h3>Classification Results for ".$lcase."</h3>";

				echo "<table>
				<tr>
				<th>No</th>
				<th>Title</th>
				<th>Date</th>
				<th>Original Category</th>
				<th>New Category</th>
				</tr>";

				$sampledata=array();
				$titlearray=array();
				$datearray=array();
				$categoryarray=array();
				$preprocess=array();
				$resultsim = array();

				$domResults = new simple_html_dom();
				$domResults2 = new simple_html_dom();
				$domResults3 = new simple_html_dom();

				$start=0;
				$end =10;

				while ($start <= $end) {
					$result = file_get_html('https://www.google.com/search?q='.$website.$keyword.'&source=lnms&tbm=nws&start='.$start);
					$domResults->load($result);
					$start = $start + 10;
					$i = 0;
					foreach ($domResults->find('a[href^=/url?]') as $link) {
						if(!empty($link->plaintext) && $i<10){
							$linkWeb = "https://www.google.com/".$link->href;

							$html = file_get_html($linkWeb);
							$domResults2->load($html);
							$linkReal = $domResults2->find('a',0)->href;

							$news = file_get_html($linkReal);
							$domResults3->load($news);
							if($website=="cnnindonesia.com")
							{
								if($domResults3->find('h1[class="title"]',0)->innertext)
								{
									$title = $domResults3->find('h1[class="title"]',0)->innertext;
								}
								else{
									continue;
								}

								if($domResults3->find('div[class="date"]',0)->innertext)
								{
									$date = $domResults3->find('div[class="date"]',0)->innertext;
								}
								else{
									continue;
								}

								if($domResults3->find('a[class="gtm_breadcrumb_subkanal"]',0)->innertext)
								{
									$category = $domResults3->find('a[class="gtm_breadcrumb_subkanal"]',0)->innertext;
								}
								else{
									continue;
								}

							}
							else{
								if($domResults3->find('h1[class="read__title"]',0)->innertext)
								{
									$title = $domResults3->find('h1[class="read__title"]',0)->innertext;
								}
								else{
									continue;
								}

								if($domResults3->find('div[class="read__time"]',0)->innertext)
								{
									$dateBefore = strip_tags($domResults3->find('div[class="read__time"]',0)->innertext);
									$date = str_replace('Kompas.com - ', '', $dateBefore);
								}
								else{
									continue;						
								}

								if($domResults3->find('span[itemprop="name"]',1)->innertext)
								{
									$category =$domResults3->find('span[itemprop="name"]',1)->innertext;
								}
								else{
									continue;
								}

							}

							$titleStem = $stemmer->stem($title);
							$titleStop = $stopword->remove($titleStem);

							array_push($titlearray,$title);
							array_push($sampledata,$titleStop);
							array_push($categoryarray,$category);
							array_push($datearray,$date);

							$i++;
						}
					}
				}

				$stmt = $mysqli->prepare("INSERT INTO classifications (title,original_category,new_category) VALUES(?,?,?)");
				$newCategory = array();
				foreach ($sampledata as $key => $words) {
					$testing = $sampleDB;
					array_push($testing,$words);

					$tf = new TokenCountVectorizer(new WhitespaceTokenizer());
					$tf->fit($testing);
					$tf->transform($testing);
					$tfidf = new TfIdfTransformer($testing);
					$tfidf ->transform($testing);

					$count = count($testing);
					$newComment = $testing[$count-1];
					array_pop($testing);
					$k = 10;

					$classifier = new KNearestNeighbors($k);
					$classifier->train($testing,$categoryDB);
					$resultCat = $classifier->predict($newComment);

					echo "<tr>";
					echo "<td>".($key+1)."</td>";
					echo "<td>".$titlearray[$key]."</td>";
					echo "<td>".$datearray[$key]."</td>";
					echo "<td>".$categoryarray[$key]."</td>";
					echo "<td>".$resultCat."</td>";
					echo "</tr>";

					$stmt->bind_param('sss',$titlearray[$key],$categoryarray[$key],$resultCat);
					$stmt->execute();
				}

			}
			echo "</table>";
			?>
		</div></div>
	</body>
	</html>


