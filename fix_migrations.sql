-- Script para registrar migraciones ya ejecutadas en la BD SIGO
-- Esto permite que Laravel no intente ejecutarlas de nuevo

USE BD_SIGO;

-- Obtener el batch más reciente
DECLARE @batch INT = ISNULL((SELECT MAX(batch) FROM migrations), 0) + 1;

-- Insertar migraciones antiguas que ya están en la BD como ejecutadas
INSERT INTO migrations (migration, batch)
SELECT '0001_01_01_000000_create_users_table', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '0001_01_01_000000_create_users_table')

INSERT INTO migrations (migration, batch)
SELECT '0001_01_01_000001_create_cache_table', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '0001_01_01_000001_create_cache_table')

INSERT INTO migrations (migration, batch)
SELECT '0001_01_01_000002_create_jobs_table', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '0001_01_01_000002_create_jobs_table')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_08_000001_create_apoyos_and_aux_tables', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_08_000001_create_apoyos_and_aux_tables')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_12_060718_add_google_auth_to_sigo_tables', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_12_060718_add_google_auth_to_sigo_tables')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_12_070000_create_solicitudes_tables', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_12_070000_create_solicitudes_tables')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_12_090000_add_file_type_rules_to_cat_tipos_documento', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_12_090000_add_file_type_rules_to_cat_tipos_documento')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_21_000001_create_apoyo_comentarios_tables', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_21_000001_create_apoyo_comentarios_tables')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_21_000002_create_hitos_apoyo_table', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_21_000002_create_hitos_apoyo_table')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_21_000003_add_google_drive_fields_to_documentos_expediente', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_21_000003_add_google_drive_fields_to_documentos_expediente')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_24_120000_add_workflow_closure_process_tables', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_24_120000_add_workflow_closure_process_tables')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_25_000001_fix_google_id_unique_constraint', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_25_000001_fix_google_id_unique_constraint')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_25_create_google_drive_files_table', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_25_create_google_drive_files_table')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_26_000001_fix_documentos_expediente_columns', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_26_000001_fix_documentos_expediente_columns')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_26_072752_add_debe_cambiar_password_to_usuarios_table', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_26_072752_add_debe_cambiar_password_to_usuarios_table')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_26_add_admin_verification_to_documentos', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_26_add_admin_verification_to_documentos')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_28_000000_add_google_calendar_fields', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_28_000000_add_google_calendar_fields')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_28_000001_add_carga_fria_fields_to_documentos', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_28_000001_add_carga_fria_fields_to_documentos')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_28_000002_add_inventory_fields_to_apoyos', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_28_000002_add_inventory_fields_to_apoyos')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_28_000003_add_new_states_to_cat_estados', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_28_000003_add_new_states_to_cat_estados')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_28_000004_create_carga_fria_tables', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_28_000004_create_carga_fria_tables')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_28_000005_create_inventory_system_tables', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_28_000005_create_inventory_system_tables')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_28_000006_enhance_google_drive_audit_and_lgpdp', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_28_000006_enhance_google_drive_audit_and_lgpdp')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_30_213927_create_oauth_states_table', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_30_213927_create_oauth_states_table')

INSERT INTO migrations (migration, batch)
SELECT '2026_03_31_000001_add_foto_ruta_to_usuarios', @batch
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_03_31_000001_add_foto_ruta_to_usuarios')

-- Ahora ejecutar SOLO las nuevas migraciones de presupuestación
