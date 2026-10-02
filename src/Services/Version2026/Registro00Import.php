<?php

namespace iEducar\Packages\Educacenso\Services\Version2026;

use App\Models\Educacenso\Registro00;
use App\Models\Educacenso\RegistroEducacenso;
use iEducar\Packages\Educacenso\Services\Version2024\Registro00Import as Registro00Import2024;
use iEducar\Packages\Educacenso\Services\Version2026\Models\Registro00Model;

class Registro00Import extends Registro00Import2024
{
    /**
     * @return Registro00|RegistroEducacenso
     */
    public static function getModel($arrayColumns)
    {
        $registro = new Registro00Model();
        $registro->hydrateModel($arrayColumns);

        return $registro;
    }
}
