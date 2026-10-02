<?php

namespace iEducar\Packages\Educacenso\Services\Version2026\Models;

use App\Models\Educacenso\Registro20;
use iEducar\Modules\Educacenso\Model\FormaOrganizacaoTurma;
use iEducar\Modules\Educacenso\Model\OrganizacaoCurricular;
use iEducar\Modules\Educacenso\Model\TipoItinerarioFormativo;
use iEducar\Packages\Educacenso\Services\Version2026\Registro20Import;
use Illuminate\Validation\ValidationException;

class Registro20Model extends Registro20
{
    public function hydrateModel($arrayColumns): void
    {
        array_unshift($arrayColumns, null);
        unset($arrayColumns[0]);

        if (is_null($arrayColumns[4]) || $arrayColumns[4] === '') {
            throw ValidationException::withMessages([
                'error' => 'Você está tentando importar um arquivo com turmas inválidas. o i-Educar aceita apenas arquivos oriundos do sistema do MEC.',
            ]);
        }

        $this->registro = $arrayColumns[1];
        $this->codigoEscolaInep = $arrayColumns[2];
        $this->codTurma = $arrayColumns[3];
        $this->inepTurma = $arrayColumns[4];
        $this->nomeTurma = mb_convert_encoding($arrayColumns[5], 'ISO-8859-1', 'UTF-8');
        $this->tipoMediacaoDidaticoPedagogico = $arrayColumns[6];

        $this->diaSemanaDomingo = $arrayColumns[7] ?? null;
        $this->diaSemanaSegunda = $arrayColumns[8] ?? null;
        $this->diaSemanaTerca = $arrayColumns[9] ?? null;
        $this->diaSemanaQuarta = $arrayColumns[10] ?? null;
        $this->diaSemanaQuinta = $arrayColumns[11] ?? null;
        $this->diaSemanaSexta = $arrayColumns[12] ?? null;
        $this->diaSemanaSabado = $arrayColumns[13] ?? null;

        // Horários baseados no primeiro dia da semana preenchido (geralmente segunda-feira)
        $primeiroHorario = $arrayColumns[8] ?: ($arrayColumns[9] ?: ($arrayColumns[10] ?: ($arrayColumns[11] ?: ($arrayColumns[12] ?: null))));
        $this->horaInicial = $this->getHoraInicial($primeiroHorario);
        $this->horaInicialMinuto = $this->getMinutoInicial($primeiroHorario);
        $this->horaFinal = $this->getHoraFinal($primeiroHorario);
        $this->horaFinalMinuto = $this->getMinutoFinal($primeiroHorario);

        $tipoTurma = (int) $arrayColumns[14];
        $this->tipoAtendimentoEscolarizacao = in_array($tipoTurma, [6, 9], true) ? 1 : 0;
        $this->tipoAtendimentoAtividadeComplementar = in_array($tipoTurma, [4, 9], true) ? 1 : 0;
        $this->tipoAtendimentoAee = ($tipoTurma === 5) ? 1 : 0;

        $this->tipoAtividadeComplementar1 = $arrayColumns[15];
        $this->tipoAtividadeComplementar2 = $arrayColumns[16];
        $this->tipoAtividadeComplementar3 = $arrayColumns[17];
        $this->tipoAtividadeComplementar4 = $arrayColumns[18];
        $this->tipoAtividadeComplementar5 = $arrayColumns[19];
        $this->tipoAtividadeComplementar6 = $arrayColumns[20];

        $this->localFuncionamentoDiferenciado = $arrayColumns[21];
        $this->classeEspecial = $arrayColumns[22];
        $this->etapaAgregada = $arrayColumns[23];
        $this->etapaEducacenso = $arrayColumns[24];
        $this->codEixoQualificacaoProfissional = $arrayColumns[25];
        $this->codCurso = $arrayColumns[26];
        $this->cargaHorariaTotal = $arrayColumns[27];

        $formaOrg = (int) $arrayColumns[28];
        $this->formasOrganizacaoTurma = array_filter([
            $formaOrg === 1 ? FormaOrganizacaoTurma::SERIE_ANO : null,
            $formaOrg === 2 ? FormaOrganizacaoTurma::SEMESTRAL : null,
            $formaOrg === 3 ? FormaOrganizacaoTurma::CICLOS : null,
            $formaOrg === 4 ? FormaOrganizacaoTurma::NAO_SERIADO : null,
            $formaOrg === 5 ? FormaOrganizacaoTurma::MODULES : null,
        ]);

        $this->formacaoAlternancia = $arrayColumns[29];

        $this->estruturaCurricular = array_filter([
            $arrayColumns[30] ? OrganizacaoCurricular::FORMACAO_GERAL_BASICA : null,
            $arrayColumns[31] ? OrganizacaoCurricular::ITINERARIO_FORMATIVO_APROFUNDAMENTO : null,
            $arrayColumns[32] ? OrganizacaoCurricular::ITINERARIO_FORMACAO_TECNICA_PROFISSIONAL : null,
        ]);

        $this->areaItinerario = array_filter([
            $arrayColumns[33] ? TipoItinerarioFormativo::LINGUANGENS : null,
            $arrayColumns[34] ? TipoItinerarioFormativo::MATEMATICA : null,
            $arrayColumns[35] ? TipoItinerarioFormativo::CIENCIAS_NATUREZA : null,
            $arrayColumns[36] ? TipoItinerarioFormativo::CIENCIAS_HUMANAS : null,
        ]);

        $this->tipoCursoIntinerario = $arrayColumns[37];
        $this->codCursoProfissionalIntinerario = $arrayColumns[38];

        $this->componentes = $this->getComponentesByImportFile(array_slice($arrayColumns, 38, 27));
        $this->classeComLinguaBrasileiraSinais = $arrayColumns[66];
    }

    private function getComponentesByImportFile($componentesImportacao)
    {
        $arrayComponentes = array_keys(Registro20Import::getComponentes());

        $componentesExistentes = [];
        foreach ($componentesImportacao as $key => $value) {
            if ($value != '1') {
                continue;
            }

            if (isset($arrayComponentes[$key])) {
                $componentesExistentes[] = $arrayComponentes[$key];
            }
        }

        return $componentesExistentes;
    }

    private function getHoraInicial($horario)
    {
        if (empty($horario)) {
            return null;
        }

        $hora = explode('-', $horario);
        $return = explode(':', $hora[0]);

        return $return[0] ?? null;
    }

    private function getMinutoInicial($horario)
    {
        if (empty($horario)) {
            return null;
        }

        $hora = explode('-', $horario);
        $return = explode(':', $hora[0]);

        return $return[1] ?? null;
    }

    private function getHoraFinal($horario)
    {
        if (empty($horario)) {
            return null;
        }

        $hora = explode('-', $horario);
        $return = explode(':', $hora[1] ?? '');

        return $return[0] ?? null;
    }

    private function getMinutoFinal($horario)
    {
        if (empty($horario)) {
            return null;
        }

        $hora = explode('-', $horario);
        $return = explode(':', $hora[1] ?? '');

        return $return[1] ?? null;
    }
}
