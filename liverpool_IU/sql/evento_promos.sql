CREATE EVENT IF NOT EXISTS control_diario_promociones
ON SCHEDULE EVERY 1 DAY
DO
BEGIN

  UPDATE promocion
  SET estado = 'inactiva'
  WHERE fecha_fin < CURRENT_DATE AND estado = 'activa';


  UPDATE promocion
  SET estado = 'activa'
  WHERE fecha_inicio <= CURRENT_DATE AND fecha_fin >= CURRENT_DATE AND estado = 'inactiva';
END;