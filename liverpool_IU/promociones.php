<?php
require_once 'db.php';

$pdo = Database::connect();
$mensaje = '';
$tipo_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? ''; // crear, toggle, eliminar

    try {
        $pdo->beginTransaction();
        if ($accion === 'crear') { 
            $id_promo = (int)$_POST['id_promo'];
            $nombre = trim($_POST['nombre']);
            $tipo = trim($_POST['tipo']);
            $porcentaje = (int)$_POST['porcentaje'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin = $_POST['fecha_fin'];
            $productos = $_POST['productos'] ?? [];

            if ($nombre == '' || $porcentaje <= 0 || $porcentaje > 100) {
                throw new Exception("Datos inválidos. Recuerda incluir nombre y que el porcentaje debe estar entre 1 y 100");
            }

            if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
                throw new Exception("La fecha final no puede ser menor a la fehc ade inicio.");
            }

            $stmt = $pdo->prepare("
                INSERT INTO promocion
                (id_promo, porcentaje, fecha_inicio, fecha_fin, nombre, tipo, estado)
                VALUES (?, ?, ?, ?, ?, ?, 'activa')
            ");

            $stmt->execute([
                $id_promo,
                $porcentaje,
                $fecha_inicio,
                $fecha_fin,
                $nombre,
                $tipo
            ]);

            if (!empty($productos)) {
                $stmtProductos = $pdo->prepare("
                    INSERT INTO producto_promo (id_producto, id_promo)
                    VALUES (?, ?)
                ");
                foreach ($productos as $id_producto) { //asocioando producto con su promo
                    $stmtProductos->execute([
                        (int)$id_producto,
                        $id_promo
                    ]);
                }
            }

            $mensaje = "Promoción creada correctamente.";
            $tipo_msg = 'ok';
        }

        elseif ($accion === 'toggle') { //activar o descativar promo
            $id_promo = (int)$_POST['id_promo'];
            $stmt = $pdo->prepare("
                UPDATE promocion
                SET estado =
                    CASE
                        WHEN estado = 'activa' THEN 'inactiva'
                        ELSE 'activa'
                    END
                WHERE id_promo = ?
            ");
            $stmt->execute([$id_promo]);
            $mensaje = "Estado actualizado.";
            $tipo_msg = 'ok';
        }

        elseif ($accion === 'eliminar') { //bye promo
            $id_promo = (int)$_POST['id_promo'];
            $pdo->prepare("
                DELETE FROM venta_promo
                WHERE id_promo = ?
            ")->execute([$id_promo]);
            $pdo->prepare("
                DELETE FROM producto_promo
                WHERE id_promo = ?
            ")->execute([$id_promo]);
            $pdo->prepare("
                DELETE FROM promocion
                WHERE id_promo = ?
            ")->execute([$id_promo]);
            $mensaje = "Promoción eliminada.";
            $tipo_msg = 'ok';
        }
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $mensaje = "Error: " . $e->getMessage();
        $tipo_msg = 'error';
    }
}
$max_id = $pdo->query(" 
    SELECT COALESCE(MAX(id_promo), 0) + 1
    FROM promocion
")->fetchColumn();

$promos = $pdo->query("
    SELECT *
    FROM promocion
    ORDER BY id_promo DESC
")->fetchAll();

$productos = $pdo->query("
    SELECT id_producto, nombre
    FROM producto
    ORDER BY nombre
")->fetchAll();

// id promo con producto
$relaciones = $pdo->query(" 
    SELECT pp.id_promo, p.nombre
    FROM producto_promo pp
    JOIN producto p
    ON pp.id_producto = p.id_producto
")->fetchAll();
$prods_por_promo = [];

foreach ($relaciones as $r) {
    $prods_por_promo[$r['id_promo']][] = $r['nombre'];
}
?>

<?php include 'components/header.php'; ?>
<?php include 'components/navbar.php'; ?>

<div class="body-inv">
    <h1>Descuentos y Promociones</h1>
    <?php if ($mensaje): ?>
        <p><?= $mensaje ?></p>
    <?php endif; ?>

    <div class="card">
        <h2>Crear promoción</h2>
        <form method="POST">
            <input type="hidden" name="accion" value="crear">
            <p>ID promoción
                <input type="number" name="id_promo" value="<?= $max_id ?>" required>
            </p>
            <p>Nombre
                <input type="text" name="nombre" required>
            </p>
            <p>Tipo
                <input type="text" name="tipo" placeholder="DESCUENTO" required>
            </p>
            <p>Porcentaje
                <input type="number" name="porcentaje" min="1" max="100" required>
            </p>
            <p>Fecha inicio
                <input type="date" name="fecha_inicio" value="<?= date('Y-m-d') ?>" required>
            </p>

            <p>Fecha fin
                <input type="date" name="fecha_fin" required>
            </p>

            <p>Productos asociados(opcional):</p>
            <?php foreach ($productos as $p): ?>
                <label>
                    <input
                        type="checkbox"
                        name="productos[]"
                        value="<?= $p['id_producto'] ?>"
                    >
                    <?= htmlspecialchars($p['nombre']) ?>
                </label>
                <br>
            <?php endforeach; ?>
            <br>
            <button type="submit">
                Crear promoción
            </button>
        </form>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Porcentaje</th>
                <th>Fechas</th>
                <th>Estado</th>
                <th>Productos</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($promos as $p): ?>
                <tr>
                    <td><?= $p['id_promo'] ?></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['tipo']) ?></td>
                    <td><?= $p['porcentaje'] ?>%</td>
                    <td><?= $p['fecha_inicio'] ?> a <?= $p['fecha_fin'] ?></td>
                    <td><?= $p['estado'] ?></td>
                    <td>
                        <?php
                        if (isset($prods_por_promo[$p['id_promo']])) {
                            echo implode(', ', $prods_por_promo[$p['id_promo']]);
                        } else {
                            echo 'Sin productos';
                        }
                        ?>
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="accion" value="toggle">
                            <input
                                type="hidden"
                                name="id_promo"
                                value="<?= $p['id_promo'] ?>"
                            >
                            <button type="submit">
                                <?php
                                if ($p['estado'] == 'activa') {
                                    echo 'Desactivar';
                                } else {
                                    echo 'Activar';
                                }
                                ?>
                            </button>
                        </form>
                        <br>
                        <form method="POST">
                            <input type="hidden" name="accion" value="eliminar">
                            <input
                                type="hidden"
                                name="id_promo"
                                value="<?= $p['id_promo'] ?>"
                            >
                            <button type="submit">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'components/footer.php'; ?>