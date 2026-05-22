CREATE EVENT IF NOT EXISTS control_diario_promociones
ON SCHEDULE EVERY 1 DAY DO
BEGIN
  -- 1. Desactivar las que ya caducaron
  UPDATE promocion
  SET estado = 'inactiva'
  WHERE fecha_fin < CURRENT_DATE() AND estado = 'activa';

  -- 2. Activar las que arrancan hoy
  UPDATE promocion
  SET estado = 'activa'
  WHERE fecha_inicio <= CURRENT_DATE() AND fecha_fin >= CURRENT_DATE() AND estado = 'inactiva';
END;