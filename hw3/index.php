<?php 
header('X-Frame-Options: DENY');

require_once('./webroot.conf.php');
$page=process_script();

?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<title>CS4411: Assignment3</title>
		<link rel="stylesheet" href="./css/project.css" type="text/css" media="screen" />

		<style>
			body { display: none ; }
		</style>
	</head>

<body>
<div id="mainBody">



<?php echo $page; ?>

 
</div>

<script>
if (self == top) {document.getElementsByTagName('body')[0].style.display='block';}
else {top.location=self.location;}
</script>
</body>
</html>
