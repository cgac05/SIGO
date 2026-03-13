    <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Usuarios')) {
            Schema::table('Usuarios', function (Blueprint $table) {
                if (! Schema::hasColumn('Usuarios', 'google_id')) {
                    $table->string('google_id')->nullable()->unique();
                }

                if (! Schema::hasColumn('Usuarios', 'google_token')) {
                    $table->text('google_token')->nullable();
                }

                if (! Schema::hasColumn('Usuarios', 'google_refresh_token')) {
                    $table->text('google_refresh_token')->nullable();
                }

                if (! Schema::hasColumn('Usuarios', 'google_avatar')) {
                    $table->text('google_avatar')->nullable();
                }

                if (! Schema::hasColumn('Usuarios', 'remember_token')) {
                    $table->rememberToken();
                }

                if (! Schema::hasColumn('Usuarios', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('Apoyos')) {
            Schema::table('Apoyos', function (Blueprint $table) {
                if (! Schema::hasColumn('Apoyos', 'anio_fiscal')) {
                    $table->integer('anio_fiscal')->default((int) now()->format('Y'));
                }

                if (! Schema::hasColumn('Apoyos', 'cupo_limite')) {
                    $table->integer('cupo_limite')->nullable();
                }

                if (! Schema::hasColumn('Apoyos', 'fecha_inicio')) {
                    $table->dateTime('fecha_inicio')->nullable();
                }

                if (! Schema::hasColumn('Apoyos', 'fecha_fin')) {
                    $table->dateTime('fecha_fin')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('Usuarios')) {
            Schema::table('Usuarios', function (Blueprint $table) {
                if (Schema::hasColumn('Usuarios', 'google_avatar')) {
                    $table->dropColumn('google_avatar');
                }

                if (Schema::hasColumn('Usuarios', 'google_refresh_token')) {
                    $table->dropColumn('google_refresh_token');
                }

                if (Schema::hasColumn('Usuarios', 'google_token')) {
                    $table->dropColumn('google_token');
                }

                if (Schema::hasColumn('Usuarios', 'google_id')) {
                    $table->dropUnique(['google_id']);
                    $table->dropColumn('google_id');
                }

                if (Schema::hasColumn('Usuarios', 'remember_token')) {
                    $table->dropRememberToken();
                }

                if (Schema::hasColumn('Usuarios', 'email_verified_at')) {
                    $table->dropColumn('email_verified_at');
                }
            });
        }

        if (Schema::hasTable('Apoyos')) {
            Schema::table('Apoyos', function (Blueprint $table) {
                foreach (['fecha_fin', 'fecha_inicio', 'cupo_limite', 'anio_fiscal'] as $column) {
                    if (Schema::hasColumn('Apoyos', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
