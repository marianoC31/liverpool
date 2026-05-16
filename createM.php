<?php


	require 'database.php';

		$marcError = null;

	if ( !empty($_POST)) {

		$marc = $_POST['marc'];

		// validate input
		$valid = true;

		if (empty($marc)) {
			$marcError = 'Porfavor escribe la marca quieres agregar';
			$valid = false;
		}

		// insert data
		if ($valid) {
			$pdo = Database::connect();
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$sql = "INSERT INTO marca(idmarca,nombrem) values(null,?)";
			$q = $pdo->prepare($sql);
		
			$q->execute(array($marc));
			Database::disconnect();
			header("Location: create.php");
		}
	}
?>


<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta 	charset="utf-8">
	    <link   href=	"css/bootstrap.min.css" rel="stylesheet">
	    <script src=	"js/bootstrap.min.js"></script>
	</head>

	<body>
	    <div class="container">
	    	<div class="span10 offset1">
	    		<div class="row">
		   			<h3>Agregar una marca nueva</h3>
		   		</div>

				<form class="form-horizontal" action="createM.php" method="post">


					<div class="control-group <?php echo !empty($marcError)?'error':'';?>">
				
					
							<label class="control-label">marca</label>
							<div class="controls">
								<input name="marc" type="text"  placeholder="marca" value="<?php echo !empty($marc)?$marc:'';?>">
								<?php if (($marcError != null)) ?>
									<span class="help-inline"><?php echo $marcError;?></span>
							</div>

					
					      	<?php if (($marcError) != null) ?>
					      		<span class="help-inline"><?php echo $marcError;?></span>
						
					</div>


					<div class="form-actions">
						<button type="submit" class="btn btn-success">Agregar</button>
						<a class="btn" href="create.php">Regresar</a>
					</div>

				</form>
			</div>
	    </div> <!-- /container -->
	</body>
</html>
