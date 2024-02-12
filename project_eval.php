<!DOCTYPE html>
<html>
<head>
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
			text-align: center;
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
	<?php
	echo '<div class="topnav">
	<a class="active" href="project_eval.php">Evaluation</a>
	<a href="project_classify.php">Classification</a>
	<a href="project_crawl.php">Crawling</a>
	<a href="home.php">Home</a>
	</div>
	<div class="container">
	<div class="center">';
	echo "<table>
	<caption><h2>Evaluation Result</h2></caption>
	<tr>
	<th>Title</th>
	<th>Original Category</th>
	<th>System Classification</th>
	<th>Result</th>";

	$titlearray=array();
	$oldCategories=array();
	$newCategories=array();

	$mysqli = new mysqli("localhost","root","","uas_iir");
	if($mysqli->connect_errno)
	{
		echo "Failed to connect to MySQL";
	}
	$res = $mysqli->query("SELECT * FROM classifications");
	while ($row=$res->fetch_assoc()) {
		$titDB = $row['title'];
		$oriCat = $row['original_category'];
		$newCat = $row['new_category'];

		array_push($titlearray, $titDB);
		array_push($oldCategories, $oriCat);
		array_push($newCategories, $newCat);
	}
	$total = count($titlearray);
	$count =0;
	foreach ($titlearray as $key => $value) {
		echo "<tr>";
		echo "<td>".$value."</td>";
		echo "<td>".$oldCategories[$key]."</td>";
		echo "<td>".$newCategories[$key]."</td>";
		if($oldCategories[$key]==$newCategories[$key]){
			$count++;
			echo "<td>&#9745;</td>";
		}

		else{
			echo "<td>&#9746;</td>";
		}
		echo "</tr>";
	}

	echo "</table><br>";
	$percentSame = round($count/$total,2)*100;
	$percentNotSame = 100-$percentSame;

	$dataPoints = array( 
		array("label"=>"Correct Classification", "y"=>$percentSame),
		array("label"=>"Wrong Classification", "y"=>$percentNotSame)
	);


	?>

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>
		
	</title>
	<script>
		window.onload = function() {
			var chart = new CanvasJS.Chart("chartContainer", {
				animationEnabled: true,
				title: {
					text: "Accuracy"
				},
				data: [{
					type: "pie",
					yValueFormatString: "#,##0.00\"%\"",
					indexLabel: "{label} ({y})",
					dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
				}]
			});
			chart.render();

		}
	</script>
</head>
<body>

	<div id="chartContainer" style="height: 370px; width: 100%;"></div>
	<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
</div></div>
</body>
</html>