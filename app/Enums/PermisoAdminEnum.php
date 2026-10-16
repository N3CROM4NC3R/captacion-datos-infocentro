<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Lo que un administrador general puede hacer en el panel.
 *
 * Los permisos son de un solo administrador, no del rol: dos administradores
 * generales pueden tenerlos distintos. El técnico los tiene todos y no es
 * editable desde la interfaz.
 */
enum PermisoAdminEnum: string
{
    use ProvidesOptions;

    case Ver = 'infocentros.ver';
    case Crear = 'infocentros.crear';
    case Editar = 'infocentros.editar';
    case Eliminar = 'infocentros.eliminar';

    /**
     * Get the human readable label for the permission.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ver => 'Ver infocentros y sus fichas',
            self::Crear => 'Crear infocentros',
            self::Editar => 'Editar infocentros y sus fichas',
            self::Eliminar => 'Eliminar infocentros y vaciar fichas',
        };
    }

    /**
     * Whether a new general administrator starts with this permission.
     *
     * Solo ver: una cuenta creada por descuido y abandonada no ha roto nada. Es
     * lo contrario de lo que podía el administrador general antes de que
     * existieran estos permisos, que podía de todo —pero esa era una decisión
     * única y general, no una que deba repetirse cada vez que se da de alta una
     * cuenta.
     */
    public function porDefecto(): bool
    {
        return $this === self::Ver;
    }

    /**
     * The permissions that let someone into the panel at all.
     *
     * @return list<string>
     */
    public static function deAcceso(): array
    {
        return [self::Ver->value];
    }
}
