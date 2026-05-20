

<?php
require_once 'db.php';
$pdo = Database::connect();
$mensaje = '';
$tipo_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        // ============== INICIA TRANSACCIÓN ==============
        $pdo->beginTransaction();

        if ($accion === 'crear') {
            // ALTA de promoción + asociación con productos
            $id_promo     = (int)$_POST['id_promo'];
            $nombre       = trim($_POST['nombre']);
            $tipo         = trim($_POST['tipo']);
            $porcentaje   = (int)$_POST['porcentaje'];
            $fecha_inicio = $_POST['fecha_inicio'];
            $fecha_fin    = $_POST['fecha_fin'];
            $productos    = $_POST['productos'] ?? [];

            if ($nombre === '' || $porcentaje <= 0 || $porcentaje > 100) {
                throw new Exception("Datos inválidos para la promoción.");
            }
            if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
                throw new Exception("La fecha fin debe ser posterior a la fecha inicio.");
            }

            // 1. Insertar la promoción
            $stmt = $pdo->prepare("
                INSERT INTO promocion (id_promo, porcentaje, fecha_inicio, fecha_fin, nombre, tipo, estado)
                VALUES (?, ?, ?, ?, ?, ?, 'activa')
            ");
            $stmt->execute([$id_promo, $porcentaje, $fecha_inicio, $fecha_fin, $nombre, $tipo]);

            // 2. Asociar productos (si se seleccionaron)
            if (!empty($productos)) {
                $stmt_pp = $pdo->prepare("INSERT INTO producto_promo (id_producto, id_promo) VALUES (?, ?)");
                foreach ($productos as $id_prod) {
                    $stmt_pp->execute([(int)$id_prod, $id_promo]);
                }
            }

            $mensaje = "Promoción '$nombre' creada correctamente.";
            $tipo_msg = 'ok';

        } elseif ($accion === 'toggle') {
            // Cambiar estado activa <-> inactiva
            $id_promo = (int)$_POST['id_promo'];
            $stmt = $pdo->prepare("
                UPDATE promocion
                SET estado = CASE WHEN estado = 'activa' THEN 'inactiva' ELSE 'activa' END
                WHERE id_promo = ?
            ");
            $stmt->execute([$id_promo]);
            $mensaje = "Estado de la promoción actualizado.";
            $tipo_msg = 'ok';

        } elseif ($accion === 'eliminar') {
            // BAJA: borramos asociaciones y promoción
            $id_promo = (int)$_POST['id_promo'];

            // 1. Borrar relaciones venta_promo (si las hay)
            $pdo->prepare("DELETE FROM venta_promo WHERE id_promo = ?")->execute([$id_promo]);
            // 2. Borrar asociación con productos
            $pdo->prepare("DELETE FROM producto_promo WHERE id_promo = ?")->execute([$id_promo]);
            // 3. Borrar promoción
            $pdo->prepare("DELETE FROM promocion WHERE id_promo = ?")->execute([$id_promo]);

            $mensaje = "Promoción eliminada.";
            $tipo_msg = 'ok';
        }

        // ============== COMMIT ==============
        $pdo->commit();

    } catch (Exception $e) {
        // ============== ROLLBACK ==============
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensaje = "Error: " . $e->getMessage();
        $tipo_msg = 'error';
    }
}

// ============================================================
// DATOS PARA MOSTRAR
// ============================================================
// Próximo ID sugerido
$max_id = (int)$pdo->query("SELECT COALESCE(MAX(id_promo),0)+1 FROM promocion")->fetchColumn();

// Todas las promociones
$promos = $pdo->query("
    SELECT id_promo, nombre, tipo, porcentaje, fecha_inicio, fecha_fin, estado
    FROM promocion
    ORDER BY id_promo DESC
")->fetchAll();

// Productos disponibles para asociar
$productos = $pdo->query("SELECT id_producto, nombre FROM producto ORDER BY nombre")->fetchAll();

// Productos por promoción
$relaciones = $pdo->query("
    SELECT pp.id_promo, p.nombre
    FROM producto_promo pp
    JOIN producto p ON pp.id_producto = p.id_producto
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

    <main class="container wide">

        <?php if ($mensaje): ?>
            <div class="alert <?= $tipo_msg ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <h2>Crear nueva promoción</h2>
        <form method="POST" class="card">
            <input type="hidden" name="accion" value="crear">

            <div class="row">
                <div class="field">
                    <label>ID promoción</label>
                    <input type="number" name="id_promo" value="<?= $max_id ?>" required>
                </div>
                <div class="field">
                    <label>Nombre</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="field">
                    <label>Tipo</label>
                    <input type="text" name="tipo" value="DESCUENTO" required>
                </div>
            </div>

            <div class="row">
                <div class="field">
                    <label>Porcentaje (%)</label>
                    <input type="number" name="porcentaje" min="1" max="100" required>
                </div>
                <div class="field">
                    <label>Fecha inicio</label>
                    <input type="date" name="fecha_inicio" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="field">
                    <label>Fecha fin</label>
                    <input type="date" name="fecha_fin" required>
                </div>
            </div>

            <div class="field">
                <label>Productos asociados (opcional)</label>
                <div class="checkbox-grid">
                    <?php foreach ($productos as $p): ?>
                        <label class="check">
                            <input type="checkbox" name="productos[]" value="<?= $p['id_producto'] ?>">
                            <?= htmlspecialchars($p['nombre']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn-primary">Crear promoción</button>
            </div>
        </form>

        <h2>Promociones existentes</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Nombre</th><th>Tipo</th><th>%</th>
                    <th>Vigencia</th><th>Estado</th><th>Productos</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($promos as $p): ?>
                <tr>
                    <td><?= $p['id_promo'] ?></td>
                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                    <td><?= htmlspecialchars($p['tipo']) ?></td>
                    <td><?= $p['porcentaje'] ?>%</td>
                    <td><?= $p['fecha_inicio'] ?> → <?= $p['fecha_fin'] ?></td>
                    <td>
                        <span class="badge <?= $p['estado'] === 'activa' ? 'on' : 'off' ?>">
                            <?= $p['estado'] ?>
                        </span>
                    </td>
                    <td>
                        <?php
                            $lista = $prods_por_promo[$p['id_promo']] ?? [];
                            echo $lista ? htmlspecialchars(implode(', ', $lista)) : '<span class="muted">—</span>';
                        ?>
                    </td>
                    <td class="acciones-celda">
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="accion" value="toggle">
                            <input type="hidden" name="id_promo" value="<?= $p['id_promo'] ?>">
                            <button class="btn-secondary" type="submit">
                                <?= $p['estado'] === 'activa' ? 'Desactivar' : 'Activar' ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline"
                              onsubmit="return confirm('¿Eliminar esta promoción?')">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_promo" value="<?= $p['id_promo'] ?>">
                            <button class="btn-danger" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>
    
<?php include 'components/footer.php'; ?>