<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Companies or networks that supply the internet service.
 */
enum ProveedorInternetEnum: string
{
    use ProvidesOptions;

    case Cantv = 'cantv';
    case Movilnet = 'movilnet';
    case EmpresaPrivada = 'empresa_privada';
    case RedComunitaria = 'red_comunitaria';
    case SinProveedor = 'sin_proveedor';

    public function label(): string
    {
        return match ($this) {
            self::Cantv => 'CANTV',
            self::Movilnet => 'Movilnet',
            self::EmpresaPrivada => 'Empresa privada',
            self::RedComunitaria => 'Red comunitaria / cooperativa',
            self::SinProveedor => 'No tiene proveedor',
        };
    }

    /**
     * Determine whether the provider needs the "¿cuál?" field.
     */
    public function requiereNombre(): bool
    {
        return match ($this) {
            self::EmpresaPrivada, self::RedComunitaria => true,
            default => false,
        };
    }
}
