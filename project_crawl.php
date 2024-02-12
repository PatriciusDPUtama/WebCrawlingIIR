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
			max-width: 80%;
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

	</style>
</head>
<body>
	<div class="topnav">
		<a href="project_eval.php">Evaluation</a>
		<a href="project_classify.php">Classification</a>
		<a class="active" href="project_crawl.php">Crawling</a>
		<a href="home.php">Home</a>
	</div>
	<div class="container">
		<div class="center">
			<?php
			$searchword = "";

			if(isset($_POST['crawls'])){
				$searchword = $_POST["keyword"];
			}
			echo "<h1>Crawling Data from News CNN and Kompas</h1>";
			echo '<form method="POST" action="">';
			echo '<p>Input Keyword&nbsp;&nbsp;<input type="text" class="crawl" name="keyword" value="'.$searchword.'" placeholder="&#128269;&nbsp;Search"> <input type="submit" name="crawls" value="Crawl" class="button">
			</p>
			</form>';

			require_once __DIR__ . '/vendor/autoload.php';
			include_once('simple_html_dom.php');
			use Phpml\FeatureExtraction\TokenCountVectorizer;
			use Phpml\Tokenization\WhitespaceTokenizer;
			use Phpml\FeatureExtraction\TfIdfTransformer;

			$stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();
			$stemmer  = $stemmerFactory->createStemmer();

			$stopwordFactory = new \Sastrawi\StopWordRemover\StopWordRemoverFactory();
			$stopword = $stopwordFactory->createStopWordRemover();

			if(isset($_POST["crawls"]))
			{   
				error_reporting(E_ALL ^ E_WARNING);

				$mysqli = new mysqli("localhost","root","","uas_iir");
				if($mysqli->connect_errno)
				{
					echo "Failed to connect to MySQL";
				}

				$searchword = $_POST["keyword"];
				$keyword = "+".str_replace(" ","+",$_POST["keyword"]);
				$keywordStem = $stemmer->stem($keyword);
				$keywordStop = $stopword->remove($keywordStem);

				echo "<h3>Search Results for ".$_POST["keyword"]."</h3>";

				echo "<table>
				<tr>
				<th>No</th>
				<th>Title</th>
				<th>Preprocessed Title</th>
				<th>Category</th>
				<th>Date</th>
				</tr>";

				$titlearray=array();
				$datearray=array();
				$categoryarray=array();
				$preprocess=array();

				$websites = array("cnnindonesia.com","www.kompas.com");

				$domResults = new simple_html_dom();
				$domResults2 = new simple_html_dom();
				$domResults3 = new simple_html_dom();


				foreach ($websites as $key => $website) {
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
								array_push($categoryarray,$category);
								array_push($datearray,$date);
								array_push($preprocess,$titleStop);

								$i++;
							}
						}
					}
				}	

				$stmt = $mysqli->prepare("INSERT INTO news (title,title_preprocess,date,category) VALUES(?,?,?,?)");

				foreach ($titlearray as $key => $tt) {
					echo "<tr>";
					echo "<td>".($key+1)."</td>";
					echo "<td>".$titlearray[$key]."</td>";
					echo "<td>".$preprocess[$key]."</td>";
					echo "<td>".$categoryarray[$key]."</td>";
					echo "<td>".$datearray[$key]."</td>";
					echo "</tr>";

					$stmt->bind_param('ssss',$titlearray[$key],$preprocess[$key],$datearray[$key],$categoryarray[$key]);
					$stmt->execute();
				}
				$stmt->close();

				echo "</table></div>";

				$mysqli->close();
			}

			?>
		</div></div>
	</body>
	</html>


