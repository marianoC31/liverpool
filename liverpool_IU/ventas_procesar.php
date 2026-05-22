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


$total_venta = 0;
$detalles_recibo = [];

try {
    $pdo->beginTransaction();

    $stmt_venta = $pdo->prepare("INSERT INTO venta (id_cliente,id_cajero,id_caja,total) VALUES (?,?,?,0)");
    $stmt_venta->execute([$id_cliente,$id_personal,$id_caja]);
    $id_venta = $pdo -> lastInsertId();

    $stmt_info = $pdo->prepare("SELECT p.nombre,p.precio,i.stock_actual,
                                    IFNULL(pr.porcentaje,0)AS descuento_porcentaje,
                                    IFNULL(pr.id_promo,NULL) AS id_promo
                                FROM producto p
                                INNER JOIN inventario i ON p.id_producto = i.id_producto
                                LEFT JOIN producto_promo pp ON p.id_producto = pp.id_producto
                                LEFT JOIN promocion pr ON pp.id_promo = pr.id_promo AND pr.estado = 'activa'
                                WHERE p.id_producto = ? ");

    $stmt_ins_detalle = $pdo->prepare("INSERT INTO venta_detalle (id_item, cantidad, precio_unitario, descuento, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmt_update_stock = $pdo ->prepare("UPDATE inventario SET stock_actual=stock_actual-? WHERE id_producto = ?");
    $stmt_ins_promo = $pdo -> prepare ("INSERT INTO venta_promo(id_promo,id_detalle) VALUES (?,?)");


    foreach ($productos_id as $id_prod){
        $cantidad = intval($_POST['cantidad_' . $id_prod]);    
        
        $stmt_info->execute([$id_prod]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if (!$info||$info['stock_actual']<$cantidad) {
            throw new Exception("Stock insuficiente para el producto ID $id_prod");
        }

        $precio_original = $info['precio'];
        $porcentaje_desc = $info['descuento_porcentaje'];

        $monto_descuento_unitario = $precio_original * ($porcentaje_desc/100);
        $precio_con_descuento = $precio_original - $monto_descuento_unitario;
        $subtotal_item = $precio_con_descuento * $cantidad;

        $total_venta += $subtotal_item;

        $stmt_ins_detalle ->execute([$id_prod, $cantidad, $precio_original, $monto_descuento_unitario * $cantidad, $subtotal_item]);
        $id_detalle = $pdo -> lastInsertId();
        $stmt_update_stock ->execute([$cantidad,$id_prod]);

        if($info['id_promo']){
            $stmt_ins_promo->execute([$info['id_promo'],$id_detalle]);
        }

        $detalles_recibo[] = [
            'nombre' => $info['nombre'],
            'cantidad' => $cantidad,
            'precio' => $precio_original,
            'descuento' => $monto_descuento_unitario * $cantidad,
            'subtotal' => $subtotal_item
        ];
    }

    $stmt_venta = $pdo->prepare("UPDATE venta SET total = ? WHERE id_venta = ?");
    $stmt_venta->execute([$total_venta,$id_venta]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Venta cancelada. Error: ". $e->getMessage());
}
?>

<head>
    <title>Recibo de Compra</title>
</head>
<body>
    <div class="container no-print text-center mt-3">
        <button onclick="window.print();" class="btn btn-warning fw-bold px-4"> Imprimir Ticket</button>
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
                            <?php if ($item['descuento'] > 0): ?>
                                <br><small class="text-danger">Ahorro Promo: -$<?= number_format($item['descuento'], 2) ?></small>
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
