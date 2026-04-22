-- Insert test folio 1035 if not exists
IF NOT EXISTS (SELECT 1 FROM Solicitudes WHERE folio = 1035)
BEGIN
    INSERT INTO Solicitudes (folio, fk_curp, beneficiario_id, origen_solicitud, estado_solicitud, fecha_creacion, hora_creacion)
    VALUES (1035, 'TEST1234567890TEST', NULL, 'admin_caso_a', 'DOCUMENTOS_PENDIENTE_VERIFICACIÓN', CAST(GETDATE() AS DATE), CAST(GETDATE() AS TIME));
    PRINT 'Folio 1035 creado';
END
ELSE
BEGIN
    PRINT 'Folio 1035 ya existe';
END

-- Insert test clave if not exists
IF NOT EXISTS (SELECT 1 FROM claves_seguimiento_privadas WHERE folio = 1035)
BEGIN
    INSERT INTO claves_seguimiento_privadas (folio, clave_alfanumerica, beneficiario_id, fecha_creacion, activa, bloqueada)
    VALUES (1035, 'TEST-TEST-TEST-TEST', NULL, GETDATE(), 1, 0);
    PRINT 'Clave para folio 1035 creada';
END
ELSE
BEGIN
    PRINT 'Clave para folio 1035 ya existe';
END

-- Show results
SELECT 'Solicitud 1035' as [Info], folio, fk_curp, origen_solicitud, estado_solicitud FROM Solicitudes WHERE folio = 1035;
SELECT 'Clave 1035' as [Info], folio, clave_alfanumerica, activa, bloqueada FROM claves_seguimiento_privadas WHERE folio = 1035;
