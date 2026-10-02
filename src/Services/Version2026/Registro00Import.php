<?php

namespace iEducar\Packages\Educacenso\Services\Version2026;

use App\Models\Educacenso\Registro00;
use App\Models\Educacenso\RegistroEducacenso;
use App\Models\LegacyInstitution;
use App\Models\LegacySchool;
use iEducar\Packages\Educacenso\Services\Version2024\Registro00Import as Registro00Import2024;
use iEducar\Packages\Educacenso\Services\Version2026\Models\Registro00Model;

class Registro00Import extends Registro00Import2024
{
    protected function getOrCreateSchool(): void
    {
        parent::getOrCreateSchool();

        $schoolInep = parent::getSchool();
        if ($schoolInep && $schoolInep->school) {
            /** @var LegacySchool $school */
            $school = $schoolInep->school;
            if (empty($school->ref_cod_instituicao)) {
                $institution = LegacyInstitution::first();
                if ($institution) {
                    $school->ref_cod_instituicao = $institution->getKey() ?? $institution->id;
                    $school->save();
                }
            }
        }
    }

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
