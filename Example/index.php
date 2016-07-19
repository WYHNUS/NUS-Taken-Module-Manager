<?php
	require_once '../NUSModuleCrawler.php';

	// check if the form has been submitted
	 if (isset($_POST["send"])) {
	 	$crawler = new NUSModuleCrawler($_POST["matric_num"], $_POST["pwd"]);
	 	$takenModules = $crawler->run();
	 	$semNum = count($takenModules);
	 }
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Retrieve NUS Taken Modules</title>
</head>

<body>
	<form method="post" action="">
		<label for="nus-matric-num">NUS User ID: </label>
		<input id="nus-matric-num" name="matric_num" id="matric_num" type="text" required/>
		<label for="nus-password">Password: </label>
		<input id="nus-password" name="pwd" id="pwd" type="password" required/>
		<input name="send" id="send" type="submit" value="Retrieve" />
	</form>

	<div>
    <?php for ($i=0; $i<$semNum; $i++) {?>
		<div>
			<h3><?php echo $takenModules[$i]["semester"] ?></h3>
			<table>
				<thead>
	                <tr>
	                    <td>#</td>
	                    <td class="course-header course-id">Course ID</td>
	                    <td class="course-header course-title">Course Title</td>
	                    <td class="course-header course-mc">MCs</td>
	                    <td class="course-header course-grade">Grade</td>
	                </tr>
	            </thead>
	            <?php 
	            	$moduleNum = count($takenModules[$i]["module_info"]);
	            	for ($j=0; $j<$moduleNum; $j++) {
	            ?>
	            <tr>
	                <td><?php echo ($j+1) ?></td>
	                <td><?php echo $takenModules[$i]["module_info"][$j]["code"] ?></td>
	                <td><?php echo $takenModules[$i]["module_info"][$j]["name"] ?></td>
	                <td><?php echo $takenModules[$i]["module_info"][$j]["MC"] ?></td>
	                <td><?php echo $takenModules[$i]["module_info"][$j]["grade"] ?></td>
	            </tr>
	            <?php } ?>
	            </table>
			</table>
			</div>
	<?php } ?>
	</div>
</body>