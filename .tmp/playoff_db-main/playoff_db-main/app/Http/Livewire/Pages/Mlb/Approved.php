<?php

namespace App\Http\Livewire\Pages\Mlb;

use App\Models\panini_mlb_player;
use App\Models\mlb_team;
use Livewire\Component;
use Livewire\WithPagination;
use App\Http\Livewire\Traits\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\Http;


class Approved extends Component
{
    use WithPagination, LivewireAlert, Notification;

    protected $listeners = [
        'reload' => 'loadData',
        'updateList' => '$refresh',
        'updateSelectedItems' => 'fixSelectedItems',
        'selectedTeamItem' => 'selectedTeamItem'
    ];
    public $sport = 'mlb';
    public $kind = 'MLB';
    public $selected_team = '';
    public $active = 'both';
    public $search_player = '';
    public $detail = false;

    public $selectedItems = [];
    public $selectAll = false;
    public $bulkDisabled = true;

    public function render()
    {
        $className = "App\Models\\" . $this->sport . "_team";
        return view("livewire.pages.{$this->sport}.approved", [
            'players' => $this->loadData(),
            'team_kind' => $className::get()->groupBy('kind')
        ]);
    }

    /**
     * This is so important, after selected option
     * Select2 still active
     *
     * @return void
     */
    public function hydrate()
    {
        $this->emit('select2');
    }

    /**
     * This trigger by emit
     *
     * @param mixed $item
     * @return void
     */
    public function selectedTeamItem($item)
    {
        if ($item) {
            $this->selected_team = $item;
        } else {
            $this->selected_team = "";
        }
    }

    public function loadData()
    {
        $query = panini_mlb_player::where('marked', 4);
        if ($this->active == 'active') {
            $query = $query->where('active', 1);
        }
        if ($this->active == 'retired') {
            $query = $query->where('active', 0);
        }
        if ($this->search_player !== '') {
            $query = $query->where('player', 'like', '%' . $this->search_player . '%');
        }
        if ($this->selected_team !== '') {
            $query = $query->where('last_played_team', $this->selected_team);
        }
        $query = $query->orderBy('last_name');

        return $query->paginate($perPage = 50);
    }

    /**
     * updatedSelectAll This will be trigger when $selectAll valualble is uopdated
     *
     * @param mixed $value
     * @return void
     */
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedItems = $this->loadData()->pluck('id');
        } else {
            $this->selectedItems = [];
        }
    }
    private function handleStatData($splits)
    {
        $seasonsProcessed = [];
        $minorsData = [];
        $allData = [];
        $minorsSeasons = [];
        foreach ($splits as $split) {
            if (isset($split['splits']) && is_array($split['splits'])) {
                foreach ($split['splits'] as $innerSplit) {
                    $abbreviation = $innerSplit['sport']['abbreviation'] ?? '';
                    $season = $innerSplit['season'];
                    $position = $split['group']['displayName'] ?? '';
                    $stats = $innerSplit['stat'] ?? [];
                    if ($abbreviation === 'Minors') {
                        $minorsSeasons[] = $season;
                        if (in_array($season, $seasonsProcessed)) {
                            continue;
                        }
                        $levels = [];
                        foreach ($split['splits'] as $checkSplit) {
                            if ($checkSplit['season'] == $season) {
                                $checkAbbreviation = $checkSplit['sport']['abbreviation'];
                                if ($checkAbbreviation === 'Minors') {
                                    continue;
                                }
                                if (in_array($checkAbbreviation, ['AA', 'AAA'])) {
                                    $levels[] = $checkAbbreviation;
                                } else {
                                    $levels[] = 'Class A';
                                }
                            }
                        }
                        $levelString = implode('/', array_unique($levels));
                        if (strpos($levelString, 'Class A/') === 0) {
                            $levelString = str_replace('Class A/', 'A/', $levelString);
                        }
                        $minorsData[$season] = [
                            'level' => $levelString,
                            'stat' => json_encode($stats),
                            'position' => $position,
                            'season' => $season,
                        ];
                        $seasonsProcessed[] = $season;
                    } else {
                        if (!in_array($season, $minorsSeasons)) {
                            if (!in_array($abbreviation, ['AA', 'AAA'])) {
                                $abbreviation = 'Class A';
                            }
                            $allData[] = [
                                'level' => $abbreviation,
                                'stat' => json_encode($stats),
                                'position' => $position,
                                'season' => $season,
                            ];
                        }
                    }
                }
            }
        }
        $filteredStats = array_merge(array_values($minorsData), $allData);
        usort($filteredStats, function ($a, $b) {
            return $a['season'] <=> $b['season'];
        });
        if (empty($filteredStats)) {
            return null;
        }

        return $filteredStats;
    }

    public function fixSelectedItems($removeItemList)
    {
        if ($removeItemList instanceof \Illuminate\Support\Collection) {
            $removeItemList = $removeItemList->toArray();
        }
        if ($this->selectedItems instanceof \Illuminate\Support\Collection) {
            $originalArr = $this->selectedItems->toArray();
        } else {
            $originalArr = $this->selectedItems;
        }
        $this->selectedItems = array_diff($originalArr, $removeItemList);
    }
}
