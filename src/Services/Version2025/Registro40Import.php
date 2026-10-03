<?php

namespace iEducar\Packages\Educacenso\Services\Version2025;

use App\Models\Educacenso\RegistroEducacenso;
use App\Models\Employee;
use App\Models\EmployeeInep;
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

            $vinculoId = $this->getFuncionarioVinculoId($model->tipoVinculo);

            $funcaoId = DB::table('pmieducar.funcao')
                ->where('ref_cod_instituicao', $institutionId)
                ->where(function ($q) {
                    $q->where('nm_funcao', 'ilike', '%diretor%')
                        ->orWhere('nm_funcao', 'ilike', '%gestor%');
                })
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
                    'carga_horaria' => '40:00:00',
                    'periodo' => 1,
                    'ano' => $year,
                    'data_admissao' => now()->toDateString(),
                ]);
            } else {
                $updateData = [];
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

    protected function getEmployee(): ?Employee
    {
        $inepNumber = $this->model->inepGestor;
        if (empty($inepNumber)) {
            return null;
        }

        $employeeInep = EmployeeInep::where('cod_docente_inep', $inepNumber)->first();

        if (empty($employeeInep)) {
            return null;
        }

        return $employeeInep->employee ?? null;
    }
}
