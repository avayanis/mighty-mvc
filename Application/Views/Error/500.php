<!DOCTYPE html> 
<html lang="en"> 
	<head>
		<title>Server Error</title>
	</head>
	<body>
		<h1>Application Error</h1>
		<h2>Details:</h2>
		<p><?php echo $this->error->getMessage();?></p>
		<p>In file: <?php echo $this->error->getFile();?> on line <?php echo $this->error->getLine();?></p>
		<pre><?php echo $this->error->getTraceAsString();?></pre>
	</body>
</html>