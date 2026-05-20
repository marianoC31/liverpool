<?php include 'components/header.php'; ?>

<?php include 'components/navbar.php'; ?>

    <div class="body-inv">
        <h1>Inventario</h1>
    
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
    </div>
<?php include 'components/footer.php'; ?>