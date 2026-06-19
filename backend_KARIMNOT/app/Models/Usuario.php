<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Laravel\Sanctum\HasApiTokens;


class Usuario extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'users'; // Nombre de la tabla en la base de datos

    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'password',
        'phoneNumber',
        'role',
        'status',
        'address',
        'profilePicture',
    ];

    // Campos ocultos al convertir a JSON
    protected $hidden = [
        'password',
        'remember_token'
    ];

    // Casts para atributos específicos
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'address' => 'array',
        ];
    }

    // JWT: Devuelve el id del usuario para identificarlo en el token
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // JWT: Información extra que se puede agregar al token
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'status' => $this->status,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
        ];
    }

    public function getAuthPassword()
    {
        return $this->password;
    }
}
