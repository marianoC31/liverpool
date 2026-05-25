<?php include 'components/header.php'; ?>

<?php

require 'Db.php';
$pdo = Database::connect();

if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty ($_POST['productos_seleccionados'])) {
    die("Error: peticion invalida o formato incompleto");
}

$id_caja = $_POST['id_caja'];
$id_cliente = $_POST['id_cliente'];
$id_personal = $_POST['id_personal'];
$productos_id = $_POST['productos_seleccionados'];
$id_metodo = $_POST['id_metodo'];

$total_venta = 0;
$detalles_recibo = [];

try {
    $pdo->beginTransaction();

    $stmt_venta = $pdo->prepare("INSERT INTO venta (id_cliente,id_cajero,id_caja,total) VALUES (?,?,?,0)");
    $stmt_venta->execute([$id_cliente,$id_personal,$id_caja]);
    $id_venta = $pdo -> lastInsertId();

    $stmt_info = $pdo->prepare("SELECT p.nombre,p.precio,i.stock_actual, 
                                GROUP_CONCAT(DISTINCT pr.tipo) AS tipos_promos,
                                IFNULL(MAX(pr.porcentaje), 0) AS descuento_porcentaje,
                                GROUP_CONCAT(DISTINCT pr.id_promo) AS ids_promos
                                FROM producto p
                                INNER JOIN inventario i ON p.id_producto = i.id_producto
                                LEFT JOIN producto_promo pp ON p.id_producto = pp.id_producto
                                LEFT JOIN promocion pr ON pp.id_promo = pr.id_promo AND pr.estado = 'activa'
                                WHERE p.id_producto = ? 
                                GROUP BY p.id_producto");

    $stmt_ins_detalle = $pdo->prepare("INSERT INTO venta_detalle (id_venta,id_item, cantidad, precio_unitario, descuento, subtotal) VALUES (?,?, ?, ?, ?, ?)");
    $stmt_update_stock = $pdo ->prepare("UPDATE inventario SET stock_actual=stock_actual-? WHERE id_producto = ?");
    $stmt_ins_promo = $pdo -> prepare ("INSERT INTO venta_promo(id_promo,id_detalle) VALUES (?,?)");

    $stmt_ins_pago = $pdo->prepare("INSERT INTO pago (id_venta,id_metodo,monto) VALUES (?,?,?)");

    foreach ($productos_id as $id_prod){
        $cantidad = intval($_POST['cantidad_' . $id_prod]);    
        
        $stmt_info->execute([$id_prod]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$info||$info['stock_actual']<$cantidad) {
            throw new Exception("Stock insuficiente para el producto ID $id_prod");
        }

        $precio_original = $info['precio'];
        $porcentaje_desc = $info['descuento_porcentaje'];

        $tipos_promos = explode(',', $info['tipos_promos'] ?? '');


        $prod_efectivos = $cantidad;
        $piezas_gratis = 0;

        if (in_array('2X1', $tipos_promos)) {
            $prod_efectivos = floor($cantidad / 2) + ($cantidad % 2);
            $piezas_gratis = $cantidad - $prod_efectivos;
        }

        $monto_descuento_unitario = $precio_original * ($porcentaje_desc/100);
        $precio_con_descuento = $precio_original - $monto_descuento_unitario;
        $subtotal_item = $precio_con_descuento * $prod_efectivos;

        $total_venta += $subtotal_item;
        
        $ahorro_total = ($monto_descuento_unitario * $cantidad) + ($piezas_gratis * $precio_original);
        $stmt_ins_detalle ->execute([$id_venta,$id_prod, $cantidad, $precio_original, $ahorro_total, $subtotal_item]);
        $id_detalle = $pdo -> lastInsertId();
        $stmt_update_stock ->execute([$cantidad,$id_prod]);
        
        if(!empty($info['ids_promo'])){
           $arr_ids_promos = explode(',', $info['ids_promos']);
            foreach ($arr_ids_promos as $id_promo_individual) {
                $stmt_ins_promo->execute([$id_promo_individual, $id_detalle]);
            }
        }

        $detalles_recibo[] = [
            'nombre' => $info['nombre'],
            'cantidad' => $cantidad,
            'precio' => $precio_original,
            'piezas_gratis' => $piezas_gratis,
            'ahorro' => $ahorro_total,
            'subtotal' => $subtotal_item,
            'tiene_2x1' => in_array('2X1', $tipos_promos)
        ];
    }

    $stmt_ins_pago ->execute([$id_venta,$id_metodo,$total_venta]);
    $stmt_venta = $pdo->prepare("UPDATE venta SET total = ? WHERE id_venta = ?");
    $stmt_venta->execute([$total_venta,$id_venta]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Venta cancelada. Error: ". $e->getMessage());
}
?>

<body>
    <div class="container no-print text-center mt-3">
        <button onclick="window.print();" class="btn btn-warning fw-bold px-4">Imprimir Ticket</button>
        <a href="ventas.php" class="btn btn-secondary px-4">Nueva Venta</a>
    </div>

    <div class="ticket shadow">
        <div class="text-center mb-3">
            <div class="logo-ticket">LIVERPOOL</div>
            <small>LIVERPOOL S.A. DE C.V.</small><br>
            <small>RFC: LIV640911-28A</small><br>
            <small>SUCURSAL Ciudad Dan Perez Plaza MarianoPro</small>
        </div>
        
        <hr style="border-top: 1px dashed #000;">
        
        <p class="my-1"><strong>TICKET NO:</strong> LV-00<?= $id_venta ?></p>
        <p class="my-1"><strong>FECHA:</strong> <?= date('d-m-Y H:i:s') ?></p>
        <p class="my-1"><strong>CAJA:</strong> 00<?= htmlspecialchars($id_caja) ?> &nbsp;&nbsp;&nbsp; <strong>CAJERO:</strong> #<?= htmlspecialchars($id_personal) ?></p>
        <p class="my-1"><strong>CLIENTE:</strong> #<?= htmlspecialchars($id_cliente) ?></p>
        
        
        <hr style="border-top: 1px dashed #000;">
        
        <table class="w-100" style="font-size: 14px;">
            <thead>
                <tr>
                    <th class="text-start">ARTÍCULO</th>
                    <th class="text-center">CANT.</th>
                    <th class="text-end">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles_recibo as $item): ?>
                    <tr>
                        <td class="text-start">
                            <?= htmlspecialchars($item['nombre']) ?><br>
                            <small class="text-muted">Precio base: $<?= number_format($item['precio'], 2) ?></small>
                            
                            <?php if ($item['tiene_2x1'] && $item['piezas_gratis'] > 0): ?>
                                <br><small class="text-success">★ Promo 2x1: ¡<?= $item['piezas_gratis'] ?> pz gratis!</small>
                            <?php endif; ?>

                            <?php if ($item['ahorro'] > 0): ?>
                                <br><small class="text-danger">Ahorro total: -$<?= number_format($item['ahorro'], 2) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center align-top"><?= $item['cantidad'] ?></td>
                        <td class="text-end align-top">$<?= number_format($item['subtotal'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <hr style="border-top: 1px dashed #000;">
        
        <div class="d-flex justify-content-between font-weight-bold" style="font-size: 18px;">
            <span><strong>TOTAL:</strong></span>
            <span><strong>$<?= number_format($total_venta, 2) ?> MXN</strong></span>
        </div>
        
        <hr style="border-top: 1px dashed #000;">
        
        <div class="text-center mt-4">
            <p class="mb-1">¡GRACIAS POR TU COMPRA!</p>
            <small>Liverpool es parte de mi vida.</small><br>
            <small>Para facturar cuentas con 30 días naturales.</small>
        </div>
    </div>
</body>

<?php include 'components/footer.php'; ?>