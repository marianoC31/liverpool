<?php include 'components/header.php'; ?>

<?php include 'components/navbar.php'; ?>
<?php
    require 'Db.php';
    $pdo = Database::connect();

    $cajas_stmt = $pdo->query("SELECT id_caja FROM caja_cobro WHERE estado = 'abierta'");
    $cajas = $cajas_stmt->fetchAll(PDO::FETCH_ASSOC);

    $query_productos = "SELECT p.id_producto, p.nombre, p.precio, p.marca, i.stock_actual,
        IFNULL(GROUP_CONCAT(DISTINCT pr.porcentaje SEPARATOR ','), 0) AS descuento_porcentaje,
        IFNULL(GROUP_CONCAT(DISTINCT pr.nombre SEPARATOR ','), 'Sin promo') AS promos_activas
        FROM producto p
        INNER JOIN inventario i ON p.id_producto = i.id_producto
        INNER JOIN categoria c ON p.id_categoria = c.id_categoria
        LEFT JOIN producto_promo pp ON p.id_producto = pp.id_producto
        LEFT JOIN promocion pr ON pp.id_promo = pr.id_promo AND pr.estado = 'activa'
        WHERE i.stock_actual > 0
        GROUP BY p.id_producto";

    $prod_stmt = $pdo->query($query_productos);
    $productos = $prod_stmt->fetchALL(PDO::FETCH_ASSOC);
    Database::disconnect();
?>

<!DOCTYPE html>
<head>
    <title>Liverpool - Caja</title>
</head>
    <body>
        <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header pink-header">
                <h3 class="mb-0">Sistema de Ventas</h3>
            </div>
            <div class="card-body">
                <form action="ventas_procesar.php" method="POST">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Seleccionar Caja de Cobro:</label>
                            <select name="id_caja" class="form-select" required>
                                <option value="">Cajas</option>
                                <?php foreach ($cajas as $caja): ?>
                                    <option value="<?= $caja['id_caja'] ?>">Caja #<?= $caja['id_caja'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ID Cliente (Frecuente):</label>
                            <input type="number" name="id_cliente" class="form-select" value="0"  required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ID Personal (Cajero):</label>
                            <input type="number" name="id_personal" class="form-select" value="1" required>
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3 fw-bold text-secondary">Artículos en Inventario</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Seleccionar</th>
                                    <th>Producto</th>
                                    <th>Marca</th>
                                    <th>Precio Base</th>
                                    <th>Promoción Activa</th>
                                    <th>Stock Disp.</th>
                                    <th style="width: 120px;">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $p): ?>
                                <tr>
                                    <td>
                                        <input class="form-check-input" type="checkbox" name="productos_seleccionados[]" value="<?= $p['id_producto'] ?>">
                                    </td>
                                    <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($p['marca']) ?></span></td>
                                    <td>$<?= number_format($p['precio'], 2) ?></td>
                                    <td>
                                        <?php if ($p['descuento_porcentaje'] > 0): ?>
                                            <span class="badge bg-success"><?= $p['promos_activas'] ?> (-<?= $p['descuento_porcentaje'] ?>%)</span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin descuento</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $p['stock_actual'] ?> pzs</td>
                                    <td>
                                        <input type="number" name="cantidad_<?= $p['id_producto'] ?>" class="form-control form-control-sm" value="1" min="1" max="<?= $p['stock_actual'] ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-liverpool btn-lgpx-5">Procesar Venta y Generar Recibo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </body>
    


    
<?php include 'components/footer.php'; ?>