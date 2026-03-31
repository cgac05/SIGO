<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sistema de Gestión de Inventario para Apoyos en Especie
 * 
 * Tablas creadas:
 * 1. inventario_material - Stock de artículos
 * 2. componentes_apoyo - Definición de kits/combos
 * 3. ordenes_compra_interno - Solicitudes de compra
 * 4. recepciones_material - Recepción de mercancía
 * 5. facturas_compra - Facturas de compra
 * 6. movimientos_inventario - Auditoría de movimientos
 * 7. salidas_beneficiarios - Entregas a beneficiarios
 * 8. detalle_salida_beneficiarios - Desglose item-por-item
 * 9. auditorias_salida_material - Firma digital y compliance
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla: Inventario de Material
        Schema::create('inventario_material', function (Blueprint $table) {
            $table->id('id_inventario');
            $table->string('codigo_material', 50)->unique();
            $table->string('nombre_material', 255);
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('fk_id_apoyo');
            $table->string('unidad_medida', 30)->default('pieza')
                ->comment("'pieza', 'kg', 'paquete', 'caja', 'metro', etc.");
            $table->decimal('cantidad_actual', 19, 4)->default(0);
            $table->decimal('cantidad_minima', 19, 4)->default(0);
            $table->decimal('costo_unitario', 19, 4)->default(0);
            $table->string('proveedor_principal', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTime('ultima_actualizacion')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('fk_id_apoyo')
                ->references('id_apoyo')
                ->on('Apoyos')
                ->onDelete('restrict');

            $table->index('fk_id_apoyo');
            $table->index('codigo_material');
            $table->index('activo');
        });

        // 2. Tabla: Componentes de Apoyo (Kit/Combo definition)
        Schema::create('componentes_apoyo', function (Blueprint $table) {
            $table->id('id_componente');
            $table->unsignedInteger('fk_id_apoyo')->comment("Apoyo padre (kit)");
            $table->unsignedBigInteger('fk_id_inventario')->comment("Material componente");
            $table->decimal('cantidad_requerida', 19, 4);
            $table->decimal('costo_componente', 19, 4)->default(0);
            $table->integer('orden_presentacion')->default(0);
            $table->text('especificaciones')->nullable()
                ->comment("Tallas, colores, especificaciones técnicas");
            $table->boolean('es_opcional')->default(false);

            $table->foreign('fk_id_apoyo')
                ->references('id_apoyo')
                ->on('Apoyos')
                ->onDelete('cascade');

            $table->foreign('fk_id_inventario')
                ->references('id_inventario')
                ->on('inventario_material')
                ->onDelete('restrict');

            $table->unique(['fk_id_apoyo', 'fk_id_inventario']);
            $table->index('fk_id_apoyo');
            $table->index('fk_id_inventario');
        });

        // 3. Tabla: Órdenes de Compra Interno
        Schema::create('ordenes_compra_interno', function (Blueprint $table) {
            $table->id('id_orden_compra');
            $table->string('numero_orden', 50)->unique();
            $table->unsignedInteger('fk_id_solicitante');
            $table->unsignedInteger('fk_id_autorizante')->nullable();
            $table->unsignedInteger('fk_id_almacenista')->nullable();
            $table->enum('estado', ['Solicitada', 'Autorizada', 'En Compra', 'Recibida', 'Cancelada'])
                ->default('Solicitada');
            $table->decimal('monto_presupuestado', 19, 4);
            $table->string('justificacion', 510)->nullable();
            $table->dateTime('fecha_solicitud')->useCurrent();
            $table->dateTime('fecha_autorizacion')->nullable();
            $table->dateTime('fecha_recepcion')->nullable();
            $table->text('observaciones')->nullable();

            $table->foreign('fk_id_solicitante')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('restrict');

            $table->foreign('fk_id_autorizante')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('set null');

            $table->foreign('fk_id_almacenista')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('set null');

            $table->index('fk_id_solicitante');
            $table->index('estado');
            $table->index('fecha_solicitud');
        });

        // 4. Tabla: Recepciones de Material
        Schema::create('recepciones_material', function (Blueprint $table) {
            $table->id('id_recepcion');
            $table->string('numero_recepcion', 50)->unique();
            $table->unsignedBigInteger('fk_id_orden_compra')->nullable();
            $table->unsignedBigInteger('fk_id_factura_compra')->nullable();
            $table->unsignedInteger('fk_id_almacenista');
            $table->unsignedInteger('fk_id_supervisor')->nullable();
            $table->dateTime('fecha_recepcion')->useCurrent();
            $table->text('condicion_recepcion')->nullable()
                ->comment("'conforme', 'parcial', 'defectuosa'");
            $table->text('observaciones')->nullable();
            $table->boolean('requiere_verificacion')->default(false);

            $table->foreign('fk_id_orden_compra')
                ->references('id_orden_compra')
                ->on('ordenes_compra_interno')
                ->onDelete('set null');

            $table->foreign('fk_id_factura_compra')
                ->references('id_factura')
                ->on('facturas_compra')
                ->onDelete('set null');

            $table->foreign('fk_id_almacenista')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('restrict');

            $table->foreign('fk_id_supervisor')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('set null');

            $table->index('fk_id_orden_compra');
            $table->index('fk_id_almacenista');
            $table->index('fecha_recepcion');
        });

        // 5. Tabla: Facturas de Compra
        Schema::create('facturas_compra', function (Blueprint $table) {
            $table->id('id_factura');
            $table->string('numero_factura', 50)->unique();
            $table->string('rfc_proveedor', 20)->nullable();
            $table->string('razon_social_proveedor', 255);
            $table->unsignedBigInteger('fk_id_orden_compra')->nullable();
            $table->dateTime('fecha_factura');
            $table->dateTime('fecha_vencimiento')->nullable();
            $table->decimal('subtotal', 19, 4);
            $table->decimal('impuestos', 19, 4)->default(0);
            $table->decimal('descuentos', 19, 4)->default(0);
            $table->decimal('total', 19, 4);
            $table->enum('estado_pago', ['Pendiente', 'Parcial', 'Pagada', 'Cancelada'])
                ->default('Pendiente');
            $table->string('folio_cfdi', 50)->nullable();
            $table->text('observaciones')->nullable();

            $table->foreign('fk_id_orden_compra')
                ->references('id_orden_compra')
                ->on('ordenes_compra_interno')
                ->onDelete('set null');

            $table->index('fk_id_orden_compra');
            $table->index('estado_pago');
            $table->index('fecha_factura');
        });

        // 6. Tabla: Movimientos de Inventario (Auditoría)
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id('id_movimiento');
            $table->unsignedBigInteger('fk_id_inventario');
            $table->enum('tipo_movimiento', [
                'Entrada Compra',
                'Salida Beneficiario',
                'Ajuste Administrativo',
                'Devolución',
                'Pérdida/Robo',
                'Caducidad'
            ]);
            $table->decimal('cantidad', 19, 4);
            $table->decimal('costo_unitario', 19, 4);
            $table->unsignedInteger('fk_id_usuario');
            $table->dateTime('fecha_movimiento')->useCurrent();
            $table->string('referencia', 100)->nullable()
                ->comment("FK a salidas_beneficiarios o recepciones_material");
            $table->text('observaciones')->nullable();

            $table->foreign('fk_id_inventario')
                ->references('id_inventario')
                ->on('inventario_material')
                ->onDelete('restrict');

            $table->foreign('fk_id_usuario')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('restrict');

            $table->index('fk_id_inventario');
            $table->index('tipo_movimiento');
            $table->index('fecha_movimiento');
        });

        // 7. Tabla: Salidas a Beneficiarios
        Schema::create('salidas_beneficiarios', function (Blueprint $table) {
            $table->id('id_salida');
            $table->string('numero_salida', 50)->unique();
            $table->unsignedInteger('fk_id_solicitud');
            $table->unsignedInteger('fk_id_beneficiario');
            $table->unsignedInteger('fk_id_almacenista');
            $table->unsignedInteger('fk_id_supervisor')->nullable();
            $table->enum('tipo_entrega', ['Kit Completo', 'Kit Parcial', 'Artículos Variados'])
                ->default('Kit Completo');
            $table->dateTime('fecha_salida')->useCurrent();
            $table->dateTime('fecha_entrega_beneficiario')->nullable();
            $table->text('firma_beneficiario_base64')->nullable()
                ->comment("Recibido conforme - Firma Digital SEL 2012");
            $table->text('firma_almacenista_base64')->nullable();
            $table->decimal('monto_total_entregado', 19, 4);
            $table->enum('estado', ['Generada', 'Entregada', 'Rechazada', 'Devuelta'])
                ->default('Generada');
            $table->text('observaciones')->nullable();

            $table->foreign('fk_id_solicitud')
                ->references('folio')
                ->on('Solicitudes')
                ->onDelete('restrict');

            $table->foreign('fk_id_beneficiario')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('restrict');

            $table->foreign('fk_id_almacenista')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('restrict');

            $table->foreign('fk_id_supervisor')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('set null');

            $table->index('fk_id_solicitud');
            $table->index('fk_id_beneficiario');
            $table->index('fk_id_almacenista');
            $table->index('estado');
            $table->index('fecha_salida');
        });

        // 8. Tabla: Detalle de Salida a Beneficiarios
        Schema::create('detalle_salida_beneficiarios', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->unsignedBigInteger('fk_id_salida');
            $table->unsignedBigInteger('fk_id_inventario');
            $table->decimal('cantidad_solicitada', 19, 4);
            $table->decimal('cantidad_entregada', 19, 4);
            $table->decimal('costo_unitario', 19, 4);
            $table->text('especificaciones_entregadas')->nullable()
                ->comment("Talla, color, especificaciones técnicas exactas");
            $table->text('observaciones')->nullable();

            $table->foreign('fk_id_salida')
                ->references('id_salida')
                ->on('salidas_beneficiarios')
                ->onDelete('cascade');

            $table->foreign('fk_id_inventario')
                ->references('id_inventario')
                ->on('inventario_material')
                ->onDelete('restrict');

            $table->index('fk_id_salida');
            $table->index('fk_id_inventario');
        });

        // 9. Tabla: Auditoría de Salida de Material
        Schema::create('auditorias_salida_material', function (Blueprint $table) {
            $table->id('id_auditoria');
            $table->unsignedBigInteger('fk_id_salida');
            $table->string('evento_tipo', 50)->comment("'generado', 'modificado', 'entregado', 'rechazado'");
            $table->unsignedInteger('fk_id_usuario');
            $table->dateTime('fecha_evento')->useCurrent();
            $table->ipAddress('ip_origen')->nullable();
            $table->text('navegador_agente')->nullable();
            $table->text('cambios_realizados')->nullable()
                ->comment("JSON con antes/después si fue modificada");
            $table->text('razon_auditoria')->nullable();
            $table->enum('estado_cumplimiento', ['Conforme', 'Parcial', 'No Conforme'])
                ->default('Conforme');

            $table->foreign('fk_id_salida')
                ->references('id_salida')
                ->on('salidas_beneficiarios')
                ->onDelete('cascade');

            $table->foreign('fk_id_usuario')
                ->references('id_usuario')
                ->on('Usuarios')
                ->onDelete('restrict');

            $table->index('fk_id_salida');
            $table->index('fk_id_usuario');
            $table->index('fecha_evento');
            $table->index('evento_tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias_salida_material');
        Schema::dropIfExists('detalle_salida_beneficiarios');
        Schema::dropIfExists('salidas_beneficiarios');
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('facturas_compra');
        Schema::dropIfExists('recepciones_material');
        Schema::dropIfExists('ordenes_compra_interno');
        Schema::dropIfExists('componentes_apoyo');
        Schema::dropIfExists('inventario_material');
    }
};
