<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta 	charset="utf-8">
	    <link   href="css/bootstrap.min.css" rel="stylesheet">
	    <script src="js/bootstrap.min.js"></script>
	</head>

	<body>
	    <div class="container">

    		<div class="row">
    			<h3>Ejemplo de operaciones básicas a una tabla de autos</h3>
    		</div>

				<div class="row">
					<p>
						<a href="create.php" class="btn btn-success">Agregar un Auto</a>
					</p>

					<table class="table table-striped table-bordered">
	            <thead>
	                <tr>
	                	<th>submarcamarca	</th>
	                	<th>marca 				</th>
	                  <th>A/C 					</th>
	                </tr>
	            </thead>
	            <tbody>
	              	<?php
								   	include 'database.php';
								   	$pdo = Database::connect();
								   	$sql = 'SELECT * FROM auto natural join marca ORDER BY idauto';
				 				   	foreach ($pdo->query($sql) as $row) {
											echo '<tr>';
			    					   	echo '<td>'. $row['nombrec'] . '</td>';
			    					  	echo '<td>'. $row['nombrem'] . '</td>';
			                            echo '<td>';    echo ($row['ac'])?"SI":"NO"; echo'</td>';
			                            echo '<td width=250>';
			    					   	echo '<a class="btn" href="read.php?id='.$row['idauto'].'">Detalles</a>';
			    					   	echo '&nbsp;';
			    					  	echo '<a class="btn btn-success" href="update.php?id='.$row['idauto'].'">Actualizar</a>';
			    					   	echo '&nbsp;';
			    					   	echo '<a class="btn btn-danger" href="delete.php?id='.$row['idauto'].'">Eliminar</a>';
			    					   	echo '</td>';
										  echo '</tr>';
								    }
								   	Database::disconnect();
				  				?>
			    		</tbody>
		      </table>

		    </div>

	    </div> <!-- /container -->
	</body>
</html>
