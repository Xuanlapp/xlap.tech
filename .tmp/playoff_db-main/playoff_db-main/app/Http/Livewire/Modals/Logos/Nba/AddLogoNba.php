<?php

namespace App\Http\Livewire\Modals\Logos\Nba;

use App\Http\Livewire\Traits\Notification;
use App\Models\logo_nba_versions;
use App\Models\Nba_team;
use App\Models\sport_customers;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use LivewireUI\Modal\ModalComponent;

class AddLogoNba extends ModalComponent
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads;

    public $status, $team_name, $team_name_accent, $team_abb, $team_color, $team_id, $team_nba_id, $kind, $stat_name, $init_letters, $pickup_name, $city, $parent_id, $generic;
    protected $listeners = [
        'updateList' => '$refresh',
        'confirm' => 'removePlayer'
    ];
    protected $rules = [
        'team_name' => 'required',
        'team_abb' => 'required',
        'team_id' => 'required',
        'kind' => 'required',
        'stat_name' => 'required',
        'init_letters' => 'required',
        'pickup_name' => 'required',
        'city' => 'required',
    ];

    public function mount()
    {

        if ($this->status == 'edit') {
            $team = Nba_team::with('parent_team')->find($this->team_nba_id);
            $this->team_name = $team->team_name;
            $this->team_name_accent = $team->team_name_accent;
            $this->team_abb = $team->team_abb;
            $this->team_color = $team->team_color;
            $this->team_id = $team->team_id;
            $this->kind = $team->kind;
            $this->stat_name = $team->stat_name;
            $this->init_letters = $team->init_letters;
            $this->pickup_name = $team->pickup_name;
            $this->city = $team->city;
            if (!$team->parent_id) {
                $this->parent_id = 'None';
            } else {
                $this->parent_id = $team->parent_team->id;
            }
        }
    }

    public function UpdateTeam()
    {
        $this->parent_id = $this->parent_id === 'None' ? null : (int)$this->parent_id;
        try {
            $this->validate();

            $team = Nba_team::find($this->team_nba_id);
            $team->team_name = $this->team_name;
            $team->team_name_accent = $this->team_name_accent;
            $team->team_abb = $this->team_abb;
            $team->team_color = $this->team_color;
            $team->team_id = $this->team_id;
            $team->kind = $this->kind;
            $team->stat_name = $this->stat_name;
            $team->init_letters = $this->init_letters;
            $team->pickup_name = $this->pickup_name;
            $team->city = $this->city;
            $team->parent_id = $this->parent_id;
            $team->save();
            if (is_null($team->parent_id)) {
                $team->update(['parent_id' => $team->id]);
            }
            $this->closeModal();
            $this->emit('updateList');
            $this->showAlertMessage('success', 'Update Nba Name success!'); // Thông báo thành công

        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'Update Nba Name failed!'); // Thông báo thất bại
        }
    }

    public function AddBKBTeam()
    {
        $this->parent_id = $this->parent_id === 'None' ? null : (int)$this->parent_id;
        try {
            $team = Nba_team::create([
                'team_name' => $this->team_name,
                'team_name_accent' => $this->team_name_accent,
                'team_abb' => $this->team_abb,
                'team_color' => $this->team_color,
                'team_id' => $this->team_id,
                'kind' => $this->kind,
                'stat_name' => $this->stat_name,
                'init_letters' => $this->init_letters,
                'pickup_name' => $this->pickup_name,
                'city' => $this->city,
                'parent_id' => $this->parent_id,
            ]);
            if (is_null($team->parent_id)) {
                $team->update(['parent_id' => $team->id]);
            }
            $this->closeModal();
            $this->dispatchBrowserEvent('refresh-page');
            $this->showAlertMessage('success', 'Add Nba Name success!'); // Thông báo thành công

        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'Add Nba Name failed!'); // Thông báo thất bại
        }
    }


    public function render()
    {
        return view('livewire.modals.logos.nba.add-logo-nba', [
            'team_kind' => Nba_team::get()->pluck('kind', 'kind'),
            'parent_ids' => Nba_team::get()->pluck('id', 'team_name')


        ]);
    }
}
