<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // 1. Apuntamos a tu tabla de SQL Server
    protected $table = 'Beneficiarios';

    // 2. Definimos tu llave primaria personalizada
    //protected $primaryKey = 'id_empleado';
    protected $primaryKey = 'curp'; // Indica que no es 'id'
    public $incrementing = false;   // Indica que no es un número auto-incremental
    protected $keyType = 'string';  // Indica que es texto
    // 3. Campos que se pueden llenar
    protected $fillable = [
        'nombre',
        'correo_inst',
        'pass_hash',
        'fk_rol',
        'activo',
    ];

    // 4. Laravel busca 'password' por defecto, le decimos que use 'pass_hash'
    public function getAuthPassword()
    {
        return $this->pass_hash;
    }

    // 5. Desactivamos timestamps si no los agregaste en el script SQL
    public $timestamps = false;

   
    public function getEmailAttribute()
    {
        return $this->correo_inst;
    }
    // Dentro de tu clase User, añade esto:
    public function getAuthIdentifierName()
    {
        return 'id_empleado';
    }
}
?>