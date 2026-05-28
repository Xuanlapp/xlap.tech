<?php

namespace App\Services\Program;

use App\Models\Programs;
use App\Models\Program_forms;
use App\Models\Program_subforms;
use App\Models\insert_name_color;

class ProgramListServices
{
    public function getPrograms($filter, $search, $perPage = 15)
    {
        $query = Programs::query();

        if ($filter) {
            $query->where('sp', $filter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('collection', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    // This function for Sport list, show the whole list
    public function getAllPrograms()
    {
        return Programs::query()->get();
    }

    public function deleteProgram($programId)
    {
        $program = Programs::find($programId);

        if ($program) {
            $program->delete();
            Program_subforms::where('program_id', $programId)->delete();
            return true;
        }

        return false;
    }

    public function updateInsertShortNames()
    {
        $programs = Program_forms::all();
        $insert_name_colors = insert_name_color::all();

        foreach ($programs as $program) {
            $shortenedInsertName = $this->shortenInsertName($program->insert_name);
            $program->update(['insert_short_name' => $shortenedInsertName]);
        }

        foreach ($insert_name_colors as $insert_name_color) {
            $shortenedInsertName = $this->shortenInsertName($insert_name_color->insert_name);
            $insert_name_color->update(['insert_short_name' => $shortenedInsertName]);
        }
    }

    public function shortenInsertName($input)
    {
        $input = preg_replace('/\s*\(.*?\)\s*/', ' ', $input);
        $input = preg_replace('/[^a-zA-Z0-9\s]/', '', $input);
        $wordsArray = array_filter(explode(' ', trim($input)));
        $totalWords = count($wordsArray);

        $groupLengths = match ($totalWords) {
            1 => [10],
            2 => [5, 5],
            3 => [4, 3, 3],
            default => [3, 2, 2, 3],
        };

        if ($totalWords > 3) {
            $lastWord = end($wordsArray);
            $wordsArray = array_slice($wordsArray, 0, 3);
            $wordsArray[] = $lastWord;
        }

        $output = [];
        $remaining = 0;

        foreach ($groupLengths as $index => $length) {
            if (isset($wordsArray[$index])) {
                $currentLength = $length + $remaining;
                $portion = substr($wordsArray[$index], 0, $currentLength);
                $output[] = ucfirst(strtolower($portion));
                $remaining = $currentLength - strlen($portion);
            }
        }

        return substr(implode('', $output), 0, 10);
    }

    public function getColorForSP($sp)
    {
        $colors = [
            'FB' => '#85270E',
            'FB1' => '#85270E',
            'EN' => '#940194',
            'ENT' => '#940194',
            'CL' => '#02FCF1',
            'SC' => '#000000',
            'BB' => '#1EBE19',
            'RA' => '#D6C418',
            'RC' => '#F9E421',
            'BK' => '#FF8118',
            'MK' => '#0727FA',
            'Golf' => '#666763',
            'LIV' => '#666763',
            'FIT' => '#F24734',
            'WWE' => '#F24734',
            'DI' => '#D10800',
            'BC' => '#C3C9E8',
            'CBK' => '#008080',
            'CFB' => '#FFA500',
        ];

        return $colors[$sp] ?? '#CCCCCC';
    }
}
