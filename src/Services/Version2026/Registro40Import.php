<?php

namespace iEducar\Packages\Educacenso\Services\Version2026;

use App\Models\Educacenso\RegistroEducacenso;
use Illuminate\Support\Facades\DB;
use iEducar\Packages\Educacenso\Services\Version2020\Registro40Import as Registro40Import2020;

class Registro40Import extends Registro40Import2020
{
    public function import(RegistroEducacenso $model, $year, $user): void
    {
        parent::import($model, $year, $user);

        $school = $this->getSchool();
        $employee = $this->getEmployee();

        if ($school && $employee) {
            $schoolId = $school->getKey();
            $institutionId = $school->ref_cod_instituicao ?: 1;
            $userId = $user->id ?? 1;

            $alocacaoExistente = DB::table('pmieducar.servidor_alocacao')
                ->where('ref_cod_servidor', $employee->getKey())
                ->where('ref_cod_escola', $schoolId)
                ->where('ano', $year)
                ->where('ativo', 1)
                ->exists();

            if (! $alocacaoExistente) {
                DB::table('pmieducar.servidor_alocacao')->insert([
                    'ref_ref_cod_instituicao' => $institutionId,
                    'ref_usuario_cad' => $userId,
                    'ref_cod_escola' => $schoolId,
                    'ref_cod_servidor' => $employee->getKey(),
                    'data_cadastro' => now(),
                    'ativo' => 1,
                    'carga_horaria' => '40:00:00',
                    'periodo' => 1,
                    'ano' => $year,
                    'data_admissao' => now()->toDateString(),
                ]);
            }
        }
    }
}
