<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMigrationsCommand extends Command
{
    protected $signature = 'migrate:fix-legacy';
    protected $description = 'Mark legacy migrations as executed to allow new presupuestación migrations';

    public function handle()
    {
        $this->info('📋 Marcando migraciones antiguas como ejecutadas...');

        $legacy_migrations = [
            '0001_01_01_000000_create_users_table',
            '0001_01_01_000001_create_cache_table',
            '0001_01_01_000002_create_jobs_table',
            '2026_03_08_000001_create_apoyos_and_aux_tables',
            '2026_03_12_060718_add_google_auth_to_sigo_tables',
            '2026_03_12_070000_create_solicitudes_tables',
            '2026_03_12_090000_add_file_type_rules_to_cat_tipos_documento',
            '2026_03_21_000001_create_apoyo_comentarios_tables',
            '2026_03_21_000002_create_hitos_apoyo_table',
            '2026_03_21_000003_add_google_drive_fields_to_documentos_expediente',
            '2026_03_24_120000_add_workflow_closure_process_tables',
            '2026_03_25_000001_fix_google_id_unique_constraint',
            '2026_03_25_create_google_drive_files_table',
            '2026_03_26_000001_fix_documentos_expediente_columns',
            '2026_03_26_072752_add_debe_cambiar_password_to_usuarios_table',
            '2026_03_26_add_admin_verification_to_documentos',
            '2026_03_28_000000_add_google_calendar_fields',
            '2026_03_28_000001_add_carga_fria_fields_to_documentos',
            '2026_03_28_000001_create_caso_a_google_calendar_tables',
            '2026_03_28_000002_add_inventory_fields_to_apoyos',
            '2026_03_28_000003_add_new_states_to_cat_estados',
            '2026_03_28_000004_create_carga_fria_tables',
            '2026_03_28_000005_create_inventory_system_tables',
            '2026_03_28_000006_enhance_google_drive_audit_and_lgpdp',
            '2026_03_30_213927_create_oauth_states_table',
            '2026_03_31_000001_add_foto_ruta_to_usuarios',
        ];

        $batch = DB::table('migrations')->max('batch') + 1;
        $marked = 0;

        foreach ($legacy_migrations as $migration) {
            $exists = DB::table('migrations')->where('migration', $migration)->exists();
            if (!$exists) {
                DB::table('migrations')->insert(['migration' => $migration, 'batch' => $batch]);
                $marked++;
                $this->line("  ✓ {$migration}");
            }
        }

        $this->info("✅ {$marked} migraciones antiguas marcadas como ejecutadas");
        $this->info('🚀 Ahora ejecuta: php artisan migrate');
    }
}
