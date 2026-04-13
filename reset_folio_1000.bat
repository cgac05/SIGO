@echo off
REM Script para resetear folio 1000

sqlcmd -S "JDEV\PARTIDA" -U sa -P "LocalSummer470" -d BD_SIGO -Q ^
"UPDATE Solicitudes SET cuv = NULL, directivo_autorizo = NULL, fecha_actualizacion = GETDATE() WHERE folio = 1000; ^
UPDATE Documentos_Expediente SET estado_validacion = 'Correcto' WHERE fk_folio = 1000; ^
SELECT folio, cuv, fk_id_hito_actual, directivo_autorizo FROM Solicitudes WHERE folio = 1000;"

pause
