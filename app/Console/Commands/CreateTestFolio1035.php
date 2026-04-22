<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Solicitud;
use App\Models\Apoyo;
use Illuminate\Support\Facades\DB;

class CreateTestFolio1035 extends Command
{
    protected $signature = 'test:folio-1035';
    protected $description = 'Create test folio 1035 with real apoyo';

    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('CREATE TEST FOLIO 1035 WITH REAL APOYO');
        $this->info('═══════════════════════════════════════════════════════');

        try {
            // 1. Get first active apoyo
            $this->line("\n✓ Getting active apoyos...");
            $apoyo = Apoyo::where('activo', 1)->first();
            
            if (!$apoyo) {
                $this->error('   ✗ No active apoyos found!');
                return 1;
            }
            
            $this->line("   Apoyo: {$apoyo->nombre_apoyo} (ID: {$apoyo->id_apoyo})");

            // 1b. Get any existing CURP to use
            $this->line("\n✓ Getting valid CURP...");
            $beneficiario = DB::table('Beneficiarios')->first();
            
            if (!$beneficiario) {
                $this->error('   ✗ No beneficiarios found!');
                return 1;
            }
            
            $curp = $beneficiario->curp;
            $this->line("   Using CURP: {$curp}");

            // 2. Delete existing folio 1035
            $this->line("\n✓ Cleaning up...");
            Solicitud::where('folio', 1035)->delete();
            DB::table('claves_seguimiento_privadas')->where('folio', 1035)->delete();
            $this->line("   ✓ Old records deleted");

            // 3. Create solicitud (sin IDENTITY_INSERT, dejar que se autogenere)
            $this->line("\n✓ Creating solicitud...");
            
            // Crear con método normal (sin IDENTITY_INSERT)
            $solicitud = Solicitud::create([
                'fk_curp' => $curp,
                'fk_id_apoyo' => $apoyo->id_apoyo,
                'beneficiario_id' => null,
                'origen_solicitud' => 'admin_caso_a',
                'estado_solicitud' => 'DOCUMENTOS_PENDIENTE_VERIFICACIÓN',
            ]);
            
            // Obtener folio desde BD (ya que el modelo puede no capturarlo bien)
            $folio = DB::table('Solicitudes')
                ->where('fk_curp', $curp)
                ->where('fk_id_apoyo', $apoyo->id_apoyo)
                ->orderByDesc('folio')
                ->value('folio');
            
            $this->line("   ✓ Solicitud created: Folio {$folio}");

            // 4. Create clave
            $this->line("\n✓ Creating clave privada...");
            $clave_text = 'TEST-TEST-TEST-TEST';
            $hash_clave = hash('sha256', $clave_text);
            
            DB::table('claves_seguimiento_privadas')->insert([
                'folio' => $folio,  // Usar el folio generado
                'clave_alfanumerica' => $clave_text,
                'hash_clave' => $hash_clave,
                'beneficiario_id' => null,
                'fecha_creacion' => DB::raw('GETDATE()'),
                'bloqueada' => 0,
            ]);
            $this->line("   ✓ Clave created");

            // 5. Verify
            $this->line("\n✓ Verifying...");
            $verif = DB::table('Solicitudes')->where('folio', $folio)->first();
            $apoyo_verif = DB::table('apoyos')->where('id_apoyo', $verif->fk_id_apoyo)->first();
            $clave_verif = DB::table('claves_seguimiento_privadas')->where('folio', $folio)->first();
            
            if ($verif) {
                $this->line("   Folio: {$verif->folio}");
                $this->line("   Apoyo: {$apoyo_verif->nombre_apoyo}");
                $this->line("   Estado: {$verif->estado_solicitud}");
                $this->line("   Clave: {$clave_verif->clave_alfanumerica}");
            }

            // 6. Show URLs
            $this->line("\n" . str_repeat("═", 60));
            $this->line("READY FOR TESTING");
            $this->line(str_repeat("═", 60));
            
            $this->line("\nURLs:\n");
            $this->line("1. Admin Resumen:");
            $this->line("   http://localhost:8000/admin/caso-a/resumen/{$folio}\n");
            
            $this->line("2. QR Direct:");
            $this->line("   http://localhost:8000/consulta-privada/acceso-qr?folio={$folio}&clave=TEST-TEST-TEST-TEST\n");
            
            $this->line("3. Private Query:");
            $this->line("   http://localhost:8000/consulta-privada/resumen");

            return 0;

        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
            return 1;
        }
    }
}
