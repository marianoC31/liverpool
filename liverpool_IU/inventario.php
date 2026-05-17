
<!DOCTYPE html>
<html lang="esp"> 
<head>
    <meta charset="UTF-8">
    <title>Inventario</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL79Yl9cLTuSTNVDAiKDtw1" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>
<body class="body-inv">
    <div>
        <h1>Inventario</h1>
    </div>
    <div class ="container">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>stock_actual</th>
                    <th>stock_minimo</th>
                    <th>categoria</th>
                    <th>marca</th>
                    <th>promos_activas</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    include 'Db.php';
                    $pdo = Database::connect();
                    $sql = 'SELECT 
                            p.id_producto,
                            p.nombre AS producto_nombre,
                            p.precio,
                            p.marca,
                            c.nombre AS categoria_nombre,
                            i.stock_actual,
                            i.stock_minimo,
                            IFNULL(GROUP_CONCAT(DISTINCT pr.nombre SEPARATOR \',\'),\'SIN_PROMOCION\') AS promos_activas
                        FROM producto p
                        INNER JOIN inventario i ON p.id_producto = i.id_producto
                        INNER JOIN categoria c ON p.id_categoria = c.id_categoria
                        LEFT JOIN producto_promo pp ON p.id_producto = pp.id_producto
                        LEFT JOIN promocion pr ON pp.id_promo = pr.id_promo AND pr.estado = \'activa\'
                        GROUP BY p.id_producto
                        ORDER BY p.id_producto';
                    foreach ($pdo->query($sql) as $row) {
                        echo '<tr>';
                        echo '<td>'. $row['id_producto'] . '</td>';
                        echo '<td>'. $row['producto_nombre'] . '</td>';
                        echo '<td>'. $row['precio'] . '</td>';
                        echo '<td>'. $row['stock_actual'] . '</td>';
                        echo '<td>'. $row['stock_minimo'] . '</td>';
                        echo '<td>'. $row['categoria_nombre'] . '</td>';
                        echo '<td>'. $row['marca'] . '</td>';
                        echo '<td>'. $row['promos_activas'] . '</td>';
                        echo '</tr>';
                    }
                    Database::disconnect();
                ?>
            </tbody>
        </table>
    </div>
</body>



</html>