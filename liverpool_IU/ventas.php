<?php include 'components/header.php'; ?>

<?php include 'components/navbar.php'; ?>
<?php
    require 'Db.php';
    $pdo = Database::connect();

    $cajas_stmt = $pdo->query("SELECT id_caja FROM caja_cobro WHERE estado = 'abierta'");
    $cajas = $cajas_stmt->fetchAll(PDO::FETCH_ASSOC);

    $clientes_stmt = $pdo->query("SELECT id_cliente, nombre FROM cliente_liverpool");
    $clientes = $clientes_stmt->fetchAll(PDO::FETCH_ASSOC);

    $personal_stmt = $pdo->query("SELECT id_personal, nombre FROM personal");
    $personal = $personal_stmt->fetchAll(PDO::FETCH_ASSOC);

    $query_productos = "SELECT p.id_producto, p.nombre, p.precio, p.marca, i.stock_actual, c.nombre AS cat,
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
    $productos_all = $prod_stmt->fetchALL(PDO::FETCH_ASSOC);

    Database::disconnect();

    $productos_selec = [];
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
                <form action="ventas_procesar.php" method="POST" onsubmit="return validarForm()">
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
                            <select name="id_cliente" class="form-select" required>
                                <option value="">Seleccionar Cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?= $cliente['id_cliente'] ?>"><?= htmlspecialchars($cliente['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">ID Personal (Cajero):</label>
                            <select name="id_personal" class="form-select" required>
                                <option value="">Seleccionar Trabajador</option>
                                <?php foreach ($personal as $p): ?>
                                    <option value="<?= $p['id_personal'] ?>"><?= $p['id_personal'] . " " . htmlspecialchars($p['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr>
                    <h2>Seleccionar Producto</h2>
                        <input type="hidden" name="accion" value="registrar">
                        <div class="row" style="align-items:flex-end; margin-top:16px;">
                            <div class="field" style="flex:2;">
                                <label>Producto</label>
                                <select id="sel-prod">
                                    <option value="">— Elige un producto —</option>
                                    <?php foreach ($productos_all as $p): ?>
                                        <option value="<?= $p['id_producto'] ?>"
                                                data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                                                data-marca="<?=  htmlspecialchars($p['marca'],  ENT_QUOTES) ?>"
                                                data-cat="<?=    htmlspecialchars($p['cat'],    ENT_QUOTES) ?>"
                                                data-promos="<?=  htmlspecialchars($p['promos_activas'],  ENT_QUOTES) ?>"
                                                data-precio = "<?= $p['precio'] ?>"
                                                data-descuento = "<?=  $p['descuento_porcentaje'] ?>"
                                                data-stock="<?= $p['stock_actual'] ?>">
                                            [<?= htmlspecialchars($p['cat']) ?>]
                                            <?= htmlspecialchars($p['nombre']) ?> — <?= htmlspecialchars($p['marca']) ?>  <? htmlspecialchars($p['precio'])?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label>Cantidad</label>
                                <input type="number" id="inp-cant" min="1" value="1">
                            </div>
                            <div>
                                <button type="button" class="btn btn-primary" onclick="agregarLinea()">+ Agregar</button>
                            </div>
                        </div>
                        <hr>
                        <h5 class="mb-3 fw-bold text-secondary">Artículos Seleccionados</h5>
                        <!-- Carrito -->
                         <div class="table-responsive">
                            <table class="data-table table table-hover align-middle" id="tabla-lineas" style="margin-top:16px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th>Marca</th>
                                        <th>Categoria</th>
                                        <th>Precio Base</th>
                                        <th>Promociones Activas</th>
                                        <th>Stock Disp.</th>
                                        <th style="width: 120px;">Cantidad</th>
                                        <th>Subtotal</th>
                                        <th>id</th>
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
                         </div>
                        <!-- Total -->
                        <div style="display:flex; justify-content:flex-end; align-items:center;
                                    gap:12px; margin-top:14px; padding:12px 16px;
                                    background:#fce4f3; border-radius:10px;">
                            <span style="color:#555;">Total de la compra:</span>
                            <strong id="total-lbl" style="font-size:1.2rem; color:var(--rosa-liverpool);">$0.00</strong>
                        </div>
            
                        <!-- Campos hidden enviados al servidor -->
                        <div id="hidden-fields"></div>
            
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-secondary btn-lgpx-5">Procesar Venta y Generar Recibo</button>
                    </div>
                    </form>
            </div>
        </div>
    </div>
    </body>
    <script>
        let lineas = [];
 
        function agregarLinea() {
            const sel   = document.getElementById('sel-prod');
            const opt   = sel.selectedOptions[0];
            const cant  = parseInt(document.getElementById('inp-cant').value);
        
            if (!sel.value)            { alert('Selecciona un producto.');               return; }
            if (!cant  || cant  <= 0)  { alert('La cantidad debe ser mayor a 0.');       return; }
        
            const existe = lineas.find(l => l.id === sel.value);
            if (existe) {
                existe.cantidad += cant;
            } else {
                lineas.push({
                    id:       sel.value,
                    nombre:   opt.dataset.nombre,
                    marca:    opt.dataset.marca,
                    cat: opt.dataset.cat,
                    precio:      opt.dataset.precio,
                    descuento: opt.dataset.descuento,
                    promo: opt.dataset.promos,
                    stock:    parseInt(opt.dataset.stock),
                    cantidad: cant,
                });
            }
        
            sel.value = '';
            document.getElementById('inp-cant').value  = 1;
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
                const sub = (l.precio*(1-(l.descuento/100)))*l.cantidad;
                total += sub;
        
                tbody.innerHTML += `<tr>
                    <td>${i + 1}</td>
                    <td><strong>${l.nombre}</strong></td>
                    <td><span class="badge bg-secondary">${l.marca}</span></td>
                    <td>${l.cat}</td>
                    <td>${l.precio}</td>
                    <td>
                    ${l.descuento > 0 ? `<span class="badge bg-success"> ${l.promo} (-${l.descuento}%)</span>`: `<span class="text-muted"> Sin descuento</span>`}
                    </td>
                    </td>
                    <td>${l.stock} pzs</td>
                    <td>
                        <input type="number" value="${l.cantidad}" min="1" max="${l.stock}"
                            class="form-control form-control-sm"
                            onchange="actualizarCant(${i}, this.value)">
                    </td>
                    <td>$${sub.toFixed(2)}</td>
                    <td>${l.id}</td>
                    <td>
                        <button type="button" class="btn btn-danger" onclick="eliminar(${i})">✕</button>
                    </td>
                </tr>`;
        
                hidden.innerHTML += `
                    <input type="hidden" name="productos_seleccionados[]" value="${l.id}">
                    <input type="hidden" name="cantidad_${l.id}"  value="${l.cantidad}" min="1" max="${l.stock}">
                    `;
                    
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
    </script>
    


    
<?php include 'components/footer.php'; ?>