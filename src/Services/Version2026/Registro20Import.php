<?php

namespace iEducar\Packages\Educacenso\Services\Version2026;

use App\Models\Educacenso\Registro20;
use App\Models\Educacenso\RegistroEducacenso;
use iEducar\Packages\Educacenso\Services\Version2025\Registro20Import as Registro20Import2025;
use iEducar\Packages\Educacenso\Services\Version2026\Models\Registro20Model;

class Registro20Import extends Registro20Import2025
{
    /**
     * @return Registro20|RegistroEducacenso
     */
    public static function getModel($arrayColumns)
    {
        $registro = new Registro20Model();
        $registro->hydrateModel($arrayColumns);

        return $registro;
    }

    public static function getComponentes()
    {
        return [
            1 => 'Química',
            2 => 'Física',
            3 => 'Matemática',
            4 => 'Biologia',
            5 => 'Ciências',
            6 => 'Língua/Literatura portuguesa',
            7 => 'Língua/Literatura estrangeira - Inglês',
            8 => 'Língua/Literatura estrangeira - Espanhol',
            9 => 'Língua/Literatura estrangeira - Outra',
            10 => 'Artes (educação artística, teatro, dança, música, artes plásticas e outras)',
            11 => 'Educação física',
            12 => 'História',
            13 => 'Geografia',
            14 => 'Filosofia',
            16 => 'Informática/Computação',
            17 => 'Disciplinas dos Cursos Técnicos Profissionais;',
            23 => 'LIBRAS',
            25 => 'Disciplinas pedagógicas',
            26 => 'Ensino religioso',
            27 => 'Língua indígena',
            28 => 'Estudos sociais',
            29 => 'Sociologia',
            30 => 'Língua/Literatura estrangeira - Francês',
            31 => 'Língua Portuguesa como Segunda Língua',
            32 => 'Estágio Curricular Supervisionado',
            33 => 'Projeto de vida',
            99 => 'Outras disciplinas',
        ];
    }
}
