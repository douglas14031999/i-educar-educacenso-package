<?php

namespace iEducar\Packages\Educacenso\Services\Version2026;

use App\Models\Educacenso\Registro60;
use App\Models\Educacenso\RegistroEducacenso;
use iEducar\Packages\Educacenso\Services\Version2025\Registro60Import as Registro60Import2025;
use iEducar\Packages\Educacenso\Services\Version2026\Models\Registro60Model;

class Registro60Import extends Registro60Import2025
{
    /**
     * @return Registro60|RegistroEducacenso
     */
    public static function getModel($arrayColumns)
    {
        $registro = new Registro60Model();
        $registro->hydrateModel($arrayColumns);

        return $registro;
    }
}
