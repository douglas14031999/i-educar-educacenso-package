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
            $userId = $user->id ?? 1;

            $periodo = $this->getPeriodoAlocacao($schoolClass->turma_turno_id);
            $vinculoId = $this->getFuncionarioVinculoId($model->tipoVinculo);

            $funcaoId = DB::table('pmieducar.funcao')
                ->where('ref_cod_instituicao', $institutionId)
                ->where('professor', 1)
                ->where('ativo', 1)
                ->value('cod_funcao');

            $servidorFuncaoId = null;
            if ($funcaoId) {
                $servidorFuncao = DB::table('pmieducar.servidor_funcao')
                    ->where('ref_cod_servidor', $employee->getKey())
                    ->where('ref_cod_funcao', $funcaoId)
                    ->first();

                if (! $servidorFuncao) {
                    $servidorFuncaoId = DB::table('pmieducar.servidor_funcao')->insertGetId([
                        'ref_ref_cod_instituicao' => $institutionId,
                        'ref_cod_servidor' => $employee->getKey(),
                        'ref_cod_funcao' => $funcaoId,
                    ], 'cod_servidor_funcao');
                } else {
                    $servidorFuncaoId = $servidorFuncao->cod_servidor_funcao;
                }
            }

            $alocacao = DB::table('pmieducar.servidor_alocacao')
                ->where('ref_cod_servidor', $employee->getKey())
                ->where('ref_cod_escola', $schoolId)
                ->where('ano', $year)
                ->where('ativo', 1)
                ->where(function ($query) use ($periodo) {
                    $query->where('periodo', $periodo)
                        ->orWhere('periodo', 4);
                })
                ->first();

            if (! $alocacao) {
                DB::table('pmieducar.servidor_alocacao')->insert([
                    'ref_ref_cod_instituicao' => $institutionId,
                    'ref_usuario_cad' => $userId,
                    'ref_cod_escola' => $schoolId,
                    'ref_cod_servidor' => $employee->getKey(),
                    'ref_cod_servidor_funcao' => $servidorFuncaoId,
                    'ref_cod_funcionario_vinculo' => $vinculoId,
                    'data_cadastro' => now(),
                    'ativo' => 1,
                    'carga_horaria' => '20:00:00',
                    'periodo' => $periodo,
                    'ano' => $year,
                    'data_admissao' => now()->toDateString(),
                ]);
            } else {
                $updateData = [];
                if ($alocacao->periodo == 4) {
                    $updateData['periodo'] = $periodo;
                }
                if (empty($alocacao->ref_cod_funcionario_vinculo) && $vinculoId) {
                    $updateData['ref_cod_funcionario_vinculo'] = $vinculoId;
                }
                if (empty($alocacao->ref_cod_servidor_funcao) && $servidorFuncaoId) {
                    $updateData['ref_cod_servidor_funcao'] = $servidorFuncaoId;
                }
                if (! empty($updateData)) {
                    DB::table('pmieducar.servidor_alocacao')
                        ->where('ref_cod_servidor', $employee->getKey())
                        ->where('ref_cod_escola', $schoolId)
                        ->where('ano', $year)
                        ->where('ativo', 1)
                        ->where('periodo', $alocacao->periodo)
                        ->update($updateData);
                }
            }

            if ($vinculoId) {
                DB::table('portal.funcionario')
                    ->where('ref_cod_pessoa_fj', $employee->getKey())
                    ->whereNull('ref_cod_funcionario_vinculo')
                    ->update(['ref_cod_funcionario_vinculo' => $vinculoId]);
            }
        }
    }

    protected function getPeriodoAlocacao($turmaTurnoId): int
    {
        return match ((int) $turmaTurnoId) {
            2 => 2,
            3 => 3,
            default => 1,
        };
    }

    protected function getFuncionarioVinculoId($tipoVinculo): ?int
    {
        if (empty($tipoVinculo)) {
            return null;
        }

        $tipoVinculo = (int) $tipoVinculo;

        if ($tipoVinculo === 1) {
            $id = DB::table('portal.funcionario_vinculo')
                ->where('abreviatura', 'ilike', 'Efet%')
                ->orWhere('nm_vinculo', 'ilike', '%efetiv%')
                ->value('cod_funcionario_vinculo');

            return $id ? (int) $id : 3;
        }

        if (in_array($tipoVinculo, [2, 3, 4], true)) {
            $id = DB::table('portal.funcionario_vinculo')
                ->where('abreviatura', 'ilike', 'Cont%')
                ->orWhere('nm_vinculo', 'ilike', '%contrat%')
                ->value('cod_funcionario_vinculo');

            return $id ? (int) $id : 4;
        }

        return null;
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
