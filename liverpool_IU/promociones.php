<?php
require_once 'Db.php';

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mensaje  = '';
$tipo_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? '';

    try {

        $pdo->beginTransaction();

        if ($accion === 'crear') {

            $id_promo     = (int)($_POST['id_promo'] ?? 0);
            $nombre       = trim($_POST['nombre'] ?? '');
            $tipo         = $_POST['tipo'] ?? '';
            $porcentaje   = (int)($_POST['porcentaje'] ?? 0);
            $fecha_inicio = $_POST['fecha_inicio'] ?? '';
            $fecha_fin    = $_POST['fecha_fin'] ?? '';
            $productos    = $_POST['productos'] ?? [];

          

            if ($id_promo <= 0)
                throw new Exception("ID inválido.");

            if ($nombre === '')
                throw new Exception("El nombre es obligatorio.");

            if (!in_array($tipo, ['PORCENTAJE', '2X1']))
                throw new Exception("Tipo de promoción inválido.");

            if ($fecha_inicio === '' || $fecha_fin === '')
                throw new Exception("Debes indicar las fechas.");

            if (strtotime($fecha_fin) < strtotime($fecha_inicio))
                throw new Exception("La fecha fin no puede ser menor a la fecha inicio.");

            // Si es porcentaje, validar %
            if ($tipo === 'PORCENTAJE') {

                if ($porcentaje < 1 || $porcentaje > 100)
                    throw new Exception("El porcentaje debe estar entre 1 y 100.");

            } else {

                // 2X1  no usan porcentaje
                $porcentaje = 0;
            }

            //no es necesario
            /*
            if (empty($productos))
                throw new Exception("Selecciona al menos un producto.");*/


            $stmt = $pdo->prepare("
                INSERT INTO promocion
                (
                    id_promo,
                    porcentaje,
                    fecha_inicio,
                    fecha_fin,
                    nombre,
                    tipo,
                    estado
                )
                VALUES
                (
                    ?, ?, ?, ?, ?, ?, 'activa'
                )
            ");

            $stmt->execute([
                $id_promo,
                $porcentaje,
                $fecha_inicio,
                $fecha_fin,
                $nombre,
                $tipo
            ]);

            $stmtProd = $pdo->prepare("
                INSERT INTO producto_promo
                (
                    id_producto,
                    id_promo
                )
                VALUES
                (
                    ?, ?
                )
            ");

            foreach ($productos as $id_producto) {

                $stmtProd->execute([
                    (int)$id_producto,
                    $id_promo
                ]);
            }

            $mensaje  = "Promoción creada correctamente.";
            $tipo_msg = 'ok';
        }

        elseif ($accion === 'toggle') {

            $id_promo = (int)($_POST['id_promo'] ?? 0);

            $stmt = $pdo->prepare("
                UPDATE promocion
                SET estado =
                    CASE
                        WHEN estado = 'activa'
                        THEN 'inactiva'
                        ELSE 'activa'
                    END
                WHERE id_promo = ?
            ");

            $stmt->execute([$id_promo]);

            $mensaje  = "Estado de la promoción actualizado.";
            $tipo_msg = 'ok';
        }

        elseif ($accion === 'eliminar') {

            $id_promo = (int)($_POST['id_promo'] ?? 0);

            // Primero relaciones
            $pdo->prepare("
                DELETE FROM venta_promo
                WHERE id_promo = ?
            ")->execute([$id_promo]);

            $pdo->prepare("
                DELETE FROM producto_promo
                WHERE id_promo = ?
            ")->execute([$id_promo]);

            // Luego promo
            $pdo->prepare("
                DELETE FROM promocion
                WHERE id_promo = ?
            ")->execute([$id_promo]);

            $mensaje  = "Promoción eliminada.";
            $tipo_msg = 'ok';
        }

        $pdo->commit();

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $mensaje  = "Error: " . $e->getMessage();
        $tipo_msg = 'error';
    }
}


$max_id = $pdo->query("
    SELECT COALESCE(MAX(id_promo), 0) + 1
    FROM promocion
")->fetchColumn();

$promos = $pdo->query("
    SELECT
        id_promo,
        nombre,
        tipo,
        porcentaje,
        fecha_inicio,
        fecha_fin,
        estado
    FROM promocion
    ORDER BY id_promo DESC
")->fetchAll();

$productos = $pdo->query("
    SELECT
        id_producto,
        nombre
    FROM producto
    ORDER BY nombre
")->fetchAll();

$relaciones = $pdo->query("
    SELECT
        pp.id_promo,
        p.nombre
    FROM producto_promo pp
    INNER JOIN producto p
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

    <main class="container wide">

        <div class="card shadow-sm">

            <div class="card-header pink-header">
                <h1>Descuentos y promociones</h1>
            </div>

            <div class="card-body">

                <?php if ($mensaje): ?>
                    <div class="alert <?= $tipo_msg ?>">
                        <?= htmlspecialchars($mensaje) ?>
                    </div>
                <?php endif; ?>


                <h2>Nueva promoción</h2>

                <form method="POST" class="card p-4" onsubmit="return validarForm()">

                    <input type="hidden" name="accion" value="crear">

                    <div class="row">

                        <div class="field">
                            <label>ID promoción</label>

                            <input
                                type="number"
                                name="id_promo"
                                value="<?= $max_id ?>"
                                required
                            >
                        </div>

                        <div class="field">
                            <label>Nombre</label>

                            <input
                                type="text"
                                name="nombre"
                                placeholder="Ej. Hot Sale"
                                required
                            >
                        </div>

                    </div>

                    <div class="row">

                        <div class="field">

                            <label>Tipo de promoción</label>

                            <select
                                name="tipo"
                                id="sel-tipo"
                                onchange="cambiarTipoPromo()"
                                required
                            >
                                <option value="PORCENTAJE">
                                    Porcentaje
                                </option>

                                <option value="2X1">
                                    2x1
                                </option>
                            </select>
                        </div>

                        <div class="field" id="campo-porcentaje">

                            <label>Porcentaje (%)</label>

                            <input
                                type="number"
                                name="porcentaje"
                                id="inp-porcentaje"
                                min="1"
                                max="100"
                                placeholder="1 - 100"
                            >
                        </div>

                    </div>

                    <div class="row">

                        <div class="field">

                            <label>Fecha inicio</label>

                            <input
                                type="date"
                                name="fecha_inicio"
                                value="<?= date('Y-m-d') ?>"
                                required
                            >
                        </div>

                        <div class="field">

                            <label>Fecha fin</label>

                            <input
                                type="date"
                                name="fecha_fin"
                                required
                            >
                        </div>

                    </div>

                    <div class="field">

                        <label>Productos asociados</label>

                        <div class="checkbox-grid">

                            <?php foreach ($productos as $p): ?>

                                <label class="check">

                                    <input
                                        type="checkbox"
                                        name="productos[]"
                                        value="<?= $p['id_producto'] ?>"
                                    >

                                    <?= htmlspecialchars($p['nombre']) ?>

                                </label>

                            <?php endforeach; ?>

                        </div>

                    </div>

                    <div class="actions">

                        <button type="submit" class="btn btn-primary">
                            Crear promoción
                        </button>

                    </div>

                </form>

                <hr>


                <h2>Promociones registradas</h2>

                <div class="table-responsive">
                    <table class="data-table table table-hover align-middle" id="tabla-lineas" style="margin-top:16px;">
                        <thead class="table-light">

                        <tr>

                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Descuento</th>
                            <th>Vigencia</th>
                            <th>Estado</th>
                            <th>Productos</th>
                            <th>Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($promos as $p): ?>

                        <tr>

                            <td><?= $p['id_promo'] ?></td>

                            <td>
                                <?= htmlspecialchars($p['nombre']) ?>
                            </td>

                            <td>

                                <?php if ($p['tipo'] === 'PORCENTAJE'): ?>

                                    <span class="mb-3 fw-bold text-secondary off">
                                        %
                                    </span>

                                <?php elseif ($p['tipo'] === '2X1'): ?>

                                    <span class="mb-3 fw-bold text-secondary on">
                                        2x1
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($p['tipo'] === 'PORCENTAJE'): ?>

                                    <?= $p['porcentaje'] ?>%

                                <?php elseif ($p['tipo'] === '2X1'): ?>

                                    Lleva 2 paga 1

                                <?php endif; ?>

                            </td>

                            <td>

                                <?= $p['fecha_inicio'] ?>

                                →

                                <?= $p['fecha_fin'] ?>

                            </td>

                            <td>

                                <span class="mb-3 fw-bold text-secondary <?= $p['estado'] === 'activa' ? 'on' : 'off' ?>">

                                    <?= $p['estado'] ?>

                                </span>

                            </td>

                            <td>

                                <?php

                                $lista = $prods_por_promo[$p['id_promo']] ?? [];

                                if (!empty($lista)) {

                                    echo htmlspecialchars(
                                        implode(', ', $lista)
                                    );

                                } else {

                                    echo '<span class="muted">—</span>';
                                }

                                ?>

                            </td>

                            <td class="acciones-celda">

                                <!-- ACTIVAR / DESACTIVAR -->

                                <form method="POST" style="display:inline;">

                                    <input
                                        type="hidden"
                                        name="accion"
                                        value="toggle"
                                    >

                                    <input
                                        type="hidden"
                                        name="id_promo"
                                        value="<?= $p['id_promo'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn-secondary"
                                    >

                                        <?= $p['estado'] === 'activa'
                                            ? 'Desactivar'
                                            : 'Activar'
                                        ?>

                                    </button>

                                </form>

                                <!-- ELIMINAR -->

                                <form
                                    method="POST"
                                    style="display:inline;"
                                    onsubmit="return confirm('¿Eliminar esta promoción?')"
                                >

                                    <input
                                        type="hidden"
                                        name="accion"
                                        value="eliminar"
                                    >

                                    <input
                                        type="hidden"
                                        name="id_promo"
                                        value="<?= $p['id_promo'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn-danger"
                                    >
                                        Eliminar
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>
                </div>

            </div>

        </div>

    </main>

</div>

<script>

function cambiarTipoPromo() {

    const tipo = document.getElementById('sel-tipo').value;

    const campo = document.getElementById('campo-porcentaje');

    const input = document.getElementById('inp-porcentaje');


    if (tipo === 'PORCENTAJE') {

        campo.style.display = '';

        input.required = true;
    }

    else {

        campo.style.display = 'none';

        input.required = false;

        input.value = '';
    }
}

function validarForm() {

    const tipo = document.getElementById('sel-tipo').value;

    if (tipo === 'PORCENTAJE') {

        const pct = parseInt(
            document.getElementById('inp-porcentaje').value
        );

        if (!pct || pct < 1 || pct > 100) {

            alert('El porcentaje debe estar entre 1 y 100.');

            return false;
        }
    }

    return true;
}

// Ejecutar al cargar
cambiarTipoPromo();

</script>

<?php include 'components/footer.php'; ?>