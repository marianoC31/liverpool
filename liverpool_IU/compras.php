<?php
require_once 'Db.php';
 
$pdo = Database::connect();
$mensaje = '';
$tipo_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
 
    try {
        $pdo->beginTransaction();
 
        if ($accion === 'registrar') {
 
            $id_proveedor  = (int)($_POST['id_proveedor'] ?? 0);
            $fecha         = $_POST['fecha'] ?? '';
            $productos_ids = $_POST['productos_ids'] ?? [];
            $cantidades    = $_POST['cantidades']    ?? [];
            $costos        = $_POST['costos']        ?? [];
 
            // Validaciones
            if ($id_proveedor <= 0)
                throw new Exception("Selecciona un proveedor válido.");
            if (empty($fecha))
                throw new Exception("Indica la fecha de recepción.");
            if (empty($productos_ids))
                throw new Exception("Agrega al menos un producto a la compra.");
            // Calcular costo total
            $costo_total = 0;
            for ($i = 0; $i < count($productos_ids); $i++) {
                $costo_total += (float)$costos[$i] * (int)$cantidades[$i];
            }
 
            // 1. Insertar compra
            $stmt = $pdo->prepare("
                INSERT INTO compra (id_proveedor, fecha, costo_compra, estado)
                VALUES (?, ?, ?, 'RECIBIDO')
            ");
            $stmt->execute([$id_proveedor, $fecha, $costo_total]);
            $id_compra = $pdo->lastInsertId();
 
            // 2. Insertar detalle + actualizar inventario
            $stmt_det = $pdo->prepare("
                INSERT INTO compra_detalle (id_producto, id_compra, cantidad, costo_unitario)
                VALUES (?, ?, ?, ?)
            ");
             $stmt_inv = $pdo->prepare("
                UPDATE inventario
                SET stock_actual = stock_actual + ?,
                    ultima_actualizacion = CURRENT_TIMESTAMP
                WHERE id_producto = ?
            ");
 
            for ($i = 0; $i < count($productos_ids); $i++) {
                $id_prod  = (int)$productos_ids[$i];
                $cantidad = (int)$cantidades[$i];
                $costo    = (float)$costos[$i];
 
                if ($id_prod <= 0 || $cantidad <= 0 || $costo <= 0)
                    throw new Exception("Datos inválidos en la línea " . ($i + 1) . ".");
 
                $stmt_det->execute([$id_prod, $id_compra, $cantidad, $costo]);
                $stmt_inv->execute([$cantidad, $id_prod]);
            }
 
            $mensaje  = "Compra #$id_compra registrada correctamente. Inventario actualizado.";
            $tipo_msg = 'ok';
 
        } elseif ($accion === 'cambiar_estado') {
            $id_compra    = (int)$_POST['id_compra'];
            $nuevo_estado = $_POST['nuevo_estado'] ?? '';
 
            if (!in_array($nuevo_estado, ['PENDIENTE', 'RECIBIDO']))
                throw new Exception("Estado inválido.");
 
            $pdo->prepare("UPDATE compra SET estado = ? WHERE id_compra = ?")
                ->execute([$nuevo_estado, $id_compra]);
 
            $mensaje  = "Estado de la compra #$id_compra actualizado a $nuevo_estado.";
            $tipo_msg = 'ok';
        }
 
        $pdo->commit();
 
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensaje  = "Error: " . $e->getMessage();
        $tipo_msg = 'error';
    }
}
 $proveedores = $pdo->query("
    SELECT id_proveedor, nombre FROM proveedor ORDER BY nombre
")->fetchAll();
 
$productos = $pdo->query("
    SELECT p.id_producto, p.nombre, p.marca, c.nombre AS cat, i.stock_actual
    FROM producto p
    INNER JOIN categoria c  ON p.id_categoria  = c.id_categoria
    INNER JOIN inventario i ON i.id_producto   = p.id_producto
    ORDER BY c.nombre, p.nombre
")->fetchAll();
 
$historial = $pdo->query("
    SELECT c.id_compra, pr.nombre AS proveedor, c.fecha,
           c.costo_compra, c.estado,
           COUNT(cd.id_compra_detalle)      AS num_lineas,
           COALESCE(SUM(cd.cantidad), 0)    AS total_unidades
    FROM compra c
    INNER JOIN proveedor pr      ON c.id_proveedor = pr.id_proveedor
    LEFT  JOIN compra_detalle cd ON cd.id_compra   = c.id_compra
    GROUP BY c.id_compra
    ORDER BY c.id_compra DESC
")->fetchAll();
 
$detalles_raw = $pdo->query("
    SELECT cd.id_compra, p.nombre AS producto, p.marca,
           cd.cantidad, cd.costo_unitario,
           (cd.cantidad * cd.costo_unitario) AS subtotal
    FROM compra_detalle cd
    INNER JOIN producto p ON cd.id_producto = p.id_producto
    ORDER BY cd.id_compra DESC, p.nombre
")->fetchAll();
 
$detalles = [];
foreach ($detalles_raw as $d) {
    $detalles[$d['id_compra']][] = $d;
}

?>

<?php include 'components/header.php'; ?>

<?php include 'components/navbar.php'; ?>

    <div class="body-inv">
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header pink-header">
                <h1>Registrar nueva compra</h1>
            </div>
 
    <main class="container wide">
 
        <?php if ($mensaje): ?>
            <div class="alert <?= $tipo_msg ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
 
        <form method="POST" class="card" onsubmit="return validarForm()">
            <input type="hidden" name="accion" value="registrar">
 
            <div class="row">
                <div class="field">
                    <label>Proveedor</label>
                    <select name="id_proveedor" required>
                        <option value="">— Selecciona un proveedor —</option>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?= $p['id_proveedor'] ?>">
                                <?= htmlspecialchars($p['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Fecha de recepción</label>
                    <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
 
            <!-- Agregar producto -->
            <div class="row" style="align-items:flex-end; margin-top:16px;">
                <div class="field" style="flex:2;">
                    <label>Producto</label>
                    <select id="sel-prod">
                        <option value="">— Elige un producto —</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= $p['id_producto'] ?>"
                                    data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                                    data-marca="<?=  htmlspecialchars($p['marca'],  ENT_QUOTES) ?>"
                                    data-cat="<?=    htmlspecialchars($p['cat'],    ENT_QUOTES) ?>"
                                    data-stock="<?= $p['stock_actual'] ?>">
                                [<?= htmlspecialchars($p['cat']) ?>]
                                <?= htmlspecialchars($p['nombre']) ?> — <?= htmlspecialchars($p['marca']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Cantidad</label>
                    <input type="number" id="inp-cant" min="1" value="1">
                </div>
                <div class="field">
                    <label>Costo unitario ($)</label>
                    <input type="number" id="inp-costo" min="0.01" step="0.01" placeholder="0.00">
                </div>
                <div class="field" style="justify-content:flex-end;">
                    <button type="button" class="btn btn-primary" onclick="agregarLinea()">+ Agregar</button>
                </div>
            </div>
 
            <!-- Tabla -->
            <table class="data-table" id="tabla-lineas" style="margin-top:16px;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Marca</th>
                        <th>Stock actual</th>
                        <th>Cantidad</th>
                        <th>Costo unit.</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tbody-lineas">
                    <tr id="fila-vacia">
                        <td colspan="9" class="muted" style="padding:18px;">
                            Aún no hay productos. Usa el selector de arriba.
                        </td>
                    </tr>
                </tbody>
            </table>
 
            <!-- Total -->
            <div style="display:flex; justify-content:flex-end; align-items:center;
                        gap:12px; margin-top:14px; padding:12px 16px;
                        background:#fce4f3; border-radius:10px;">
                <span style="color:#555;">Total de la compra:</span>
                <strong id="total-lbl" style="font-size:1.2rem; color:var(--rosa-liverpool);">$0.00</strong>
            </div>
 
            <!-- Campos hidden enviados al servidor -->
            <div id="hidden-fields"></div>
 
            <div class="actions">
                <button type="submit" class="btn btn-primary">Registrar compra</button>
            </div>
            <hr>
        </form>
 
        <!-- HISTORIAL-->
        <h2>Historial de compras</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Proveedor</th>
                    <th>Fecha</th>
                    <th>Líneas</th>
                    <th>Unidades</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($historial as $h): ?>
                <tr>
                    <td><?= $h['id_compra'] ?></td>
                    <td><?= htmlspecialchars($h['proveedor']) ?></td>
                    <td><?= $h['fecha'] ?></td>
                    <td><?= $h['num_lineas'] ?></td>
                    <td><?= $h['total_unidades'] ?></td>
                    <td>$<?= number_format($h['costo_compra'], 2) ?></td>
                    <td>
                        <span class="  <?= $h['estado'] === 'RECIBIDO' ? 'on' : 'off' ?>">
                            <?= $h['estado'] ?>
                        </span>
                    </td>
                    <td class="acciones-celda">
                        <!-- Ver detalle -->
                        <button class="btn-secondary"
                                onclick="toggleDet(<?= $h['id_compra'] ?>)">
                            Ver detalle
                        </button>
                        <!-- Cambiar estado -->
                        <?php $otro = $h['estado'] === 'RECIBIDO' ? 'PENDIENTE' : 'RECIBIDO'; ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="accion"       value="cambiar_estado">
                            <input type="hidden" name="id_compra"    value="<?= $h['id_compra'] ?>">
                            <input type="hidden" name="nuevo_estado" value="<?= $otro ?>">
                            <button type="submit" class="btn-secondary">→ <?= $otro ?></button>
                        </form>
                    </td>
                </tr>
                <!-- Fila de detalle colapsable -->
                <tr id="det-<?= $h['id_compra'] ?>" style="display:none;">
                    <td colspan="8" style="background:#fdf3fb; padding:10px 24px;">
                        <?php $dets = $detalles[$h['id_compra']] ?? []; ?>
                        <?php if (empty($dets)): ?>
                            <span class="muted">Sin detalles registrados.</span>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Marca</th>
                                        <th>Cantidad</th>
                                        <th>Costo unit.</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dets as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['producto']) ?></td>
                                        <td><?= htmlspecialchars($d['marca']) ?></td>
                                        <td><?= $d['cantidad'] ?></td>
                                        <td>$<?= number_format($d['costo_unitario'], 2) ?></td>
                                        <td>$<?= number_format($d['subtotal'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
 
    </main>
    </div>
    </div>
</div>
 <script>
// ── Carrito de compra ─────────────────────────────────────────
let lineas = [];
 
function agregarLinea() {
    const sel   = document.getElementById('sel-prod');
    const opt   = sel.selectedOptions[0];
    const cant  = parseInt(document.getElementById('inp-cant').value);
    const costo = parseFloat(document.getElementById('inp-costo').value);
 
    if (!sel.value)            { alert('Selecciona un producto.');               return; }
    if (!cant  || cant  <= 0)  { alert('La cantidad debe ser mayor a 0.');       return; }
    if (!costo || costo <= 0)  { alert('El costo unitario debe ser mayor a 0.'); return; }
 
    const existe = lineas.find(l => l.id === sel.value);
    if (existe) {
        existe.cantidad += cant;
        existe.costo = costo;
    } else {
        lineas.push({
            id:       sel.value,
            nombre:   opt.dataset.nombre,
            cat:      opt.dataset.cat,
            marca:    opt.dataset.marca,
            stock:    parseInt(opt.dataset.stock),
            cantidad: cant,
            costo:    costo
        });
    }
 
    sel.value = '';
    document.getElementById('inp-cant').value  = 1;
    document.getElementById('inp-costo').value = '';
    renderTabla();
}
 
function eliminar(idx) {
    lineas.splice(idx, 1);
    renderTabla();
}
 
function actualizarCant(idx, val) {
    const v = parseInt(val);
    if (v > 0) { lineas[idx].cantidad = v; renderTabla(); }
}
 
function renderTabla() {
    const tbody  = document.getElementById('tbody-lineas');
    const hidden = document.getElementById('hidden-fields');
 
    tbody.innerHTML  = '';
    hidden.innerHTML = '';
 
    if (lineas.length === 0) {
        tbody.innerHTML = `<tr id="fila-vacia">
            <td colspan="9" class="muted" style="padding:18px;">
                Aún no hay productos. Usa el selector de arriba.
            </td></tr>`;
        document.getElementById('total-lbl').textContent = '$0.00';
        return;
    }
 
    let total = 0;
    lineas.forEach((l, i) => {
        const sub = l.cantidad * l.costo;
        total += sub;
 
        tbody.innerHTML += `<tr>
            <td>${i + 1}</td>
            <td>${l.nombre}</td>
            <td>${l.cat}</td>
            <td>${l.marca}</td>
            <td>${l.stock}</td>
            <td>
                <input type="number" min="1" value="${l.cantidad}"
                       style="width:68px; padding:4px 6px; border:1px solid #ddd; border-radius:6px;"
                       onchange="actualizarCant(${i}, this.value)">
            </td>
            <td>$${l.costo.toFixed(2)}</td>
            <td>$${sub.toFixed(2)}</td>
            <td>
                <button type="button" class="btn-danger" onclick="eliminar(${i})">✕</button>
            </td>
        </tr>`;
 
        hidden.innerHTML += `
            <input type="hidden" name="productos_ids[]" value="${l.id}">
            <input type="hidden" name="cantidades[]"    value="${l.cantidad}">
            <input type="hidden" name="costos[]"        value="${l.costo}">`;
    });
 
    document.getElementById('total-lbl').textContent =
        '$' + total.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
 
function validarForm() {
    if (lineas.length === 0) {
        alert('Agrega al menos un producto antes de registrar la compra.');
        return false;
    }
    return true;
}
 
// ── Acordeón historial ────────────────────────────────────────
function toggleDet(id) {
    const f = document.getElementById('det-' + id);
    f.style.display = f.style.display === 'none' ? 'table-row' : 'none';
}
</script>
<?php include 'components/footer.php'; ?>