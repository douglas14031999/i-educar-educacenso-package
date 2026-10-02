<?php

namespace iEducar\Packages\Educacenso\Services\Version2025;

use App\Models\Educacenso\Registro50;
use App\Models\Educacenso\RegistroEducacenso;
use App\Models\LegacySchoolClassTeacher;
use Illuminate\Support\Facades\DB;
use iEducar\Packages\Educacenso\Services\Version2023\Registro50Import as Registro50Import2023;
use iEducar\Packages\Educacenso\Services\Version2025\Models\Registro50Model;

class Registro50Import extends Registro50Import2023
{
    public function import(RegistroEducacenso $model, $year, $user): void
    {
        $this->user = $user;
        $this->model = $model;
        $this->year = $year;

        parent::import($model, $year, $user);

        $employee = parent::getEmployee();
        $schoolClass = $this->getSchoolClass();

        if ($employee && $schoolClass) {
            $schoolClassTeacher = LegacySchoolClassTeacher::where('turma_id', $schoolClass->getKey())
                ->where('servidor_id', $employee->getKey())
                ->first();

            if ($schoolClassTeacher) {
                if (is_array($model->areaItinerario) && count($model->areaItinerario) > 0) {
                    $schoolClassTeacher->area_itinerario = $this->getPostgresIntegerArray($model->areaItinerario);
                }
                $schoolClassTeacher->leciona_itinerario_tecnico_profissional = $model->lecionaItinerarioTecnicoProfissional ?: null;

                $schoolClassTeacher->save();
            }

            $schoolId = $schoolClass->ref_ref_cod_escola;
            $institutionId = $schoolClass->school?->ref_cod_instituicao ?: 1;
            $periodo = $schoolClass->turma_turno_id ?: 1;
            $userId = $user->id ?? 1;

            $alocacaoExistente = DB::table('pmieducar.servidor_alocacao')
                ->where('ref_cod_servidor', $employee->getKey())
                ->where('ref_cod_escola', $schoolId)
                ->where('ano', $year)
                ->where('periodo', $periodo)
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
                    'carga_horaria' => '20:00:00',
                    'periodo' => $periodo,
                    'ano' => $year,
                    'data_admissao' => now()->toDateString(),
                ]);
            }
        }
    }

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
