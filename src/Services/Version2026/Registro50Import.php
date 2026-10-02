<?php

namespace iEducar\Packages\Educacenso\Services\Version2026;

use App\Models\Educacenso\Registro50;
use App\Models\Educacenso\RegistroEducacenso;
use iEducar\Packages\Educacenso\Services\Version2025\Models\Registro50Model;
use iEducar\Packages\Educacenso\Services\Version2025\Registro50Import as Registro50Import2025;

class Registro50Import extends Registro50Import2025
{
    /**
     * @return Registro50|RegistroEducacenso
     */
    public static function getModel($arrayColumns)
    {
        $registro = new Registro50Model();
        $registro->hydrateModel($arrayColumns);

        return $registro;
    }
}
