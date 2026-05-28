<?php

namespace App\Http\Livewire\Modals\Logos\Nba;

use App\Http\Livewire\Traits\Notification;
use App\Models\logo_nba_versions;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use LivewireUI\Modal\ModalComponent;

class AddLogoVersion extends ModalComponent
{
    use WithPagination, LivewireAlert, Notification, WithFileUploads;

    protected $listeners = [
        'updateList' => '$refresh',
        'confirm' => 'removePlayer'
    ];
    public $team_id, $begin, $end, $pri_tc, $sec_tc, $special_pickup, $status, $version_id, $version, $on_white, $on_black, $on_primary, $on_secondary, $logo_version_json, $nba_team;

    protected $rules = [
        'begin' => 'required',
        'end' => 'required',
        'pri_tc' => 'required',
        'sec_tc' => 'required',
    ];

    public function mount()
    {
        if ($this->status == 'edit') {
            $this->version = logo_nba_versions::find($this->version_id);
            $this->begin = $this->version->begin;
            $this->end = $this->version->end;
            $this->pri_tc = $this->version->pri_tc;
            $this->sec_tc = $this->version->sec_tc;
            $this->on_primary = $this->version->on_primary;
            $this->on_secondary = $this->version->on_secondary;
            $this->on_white = $this->version->on_white;
            $this->on_black = $this->version->on_black;
            $this->logo_version_json = json_decode($this->version->logo_version_json, true);
            $this->team_id = $this->version->team_id;
            $this->nba_team = logo_nba_versions::with('nba_team')
                ->where('team_id', $this->team_id)
                ->where('id', $this->version_id)
                ->first();
        }
    }

    public function UpdateVersion()
    {
        try {
            $this->validate();
            // Loại bỏ các phần tử trống trong logo_version_json
            if (is_array($this->logo_version_json)) {
                $this->logo_version_json = array_filter($this->logo_version_json, function ($value) {
                    return $value !== "";
                });
                // Đánh lại index cho mảng (0, 1, 2...) sau khi lọc
                $this->logo_version_json = array_values($this->logo_version_json);
            }
            $version = logo_nba_versions::find($this->version_id);
            $version->begin = $this->begin;
            $version->end = $this->end;
            $version->pri_tc = $this->pri_tc;
            $version->sec_tc = $this->sec_tc;
            $version->logo_version_json = json_encode($this->logo_version_json);
            $version->on_white = $this->on_white;
            $version->on_black = $this->on_black;
            $version->on_primary = $this->on_primary;
            $version->on_secondary = $this->on_secondary;

            $version->save();
            $this->closeModal();
            $this->emit('updateList');
            $this->showAlertMessage('success', 'Update Version Basketball success!'); // Thông báo thành công
        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'Update Version Basketball failed!'); // Thông báo thất bại
        }
    }

    public function addLogoVersionField()
    {
        // Ensure logo_version_json is initialized
        if (!is_array($this->logo_version_json)) {
            $this->logo_version_json = [];
        }
        // Get the next index
        $nextIndex = count($this->logo_version_json);
        // Add a new empty field
        $this->logo_version_json[$nextIndex] = '';
    }

    public function deleteLogoVersionField($key)
    {
        if (isset($this->logo_version_json[$key])) {
            unset($this->logo_version_json[$key]);
            // Nếu bạn muốn đảm bảo các khóa là liên tục, có thể sử dụng:
            // $this->logo_version_json = array_values($this->logo_version_json);
        }
    }

    public function deleteLastLogoVersionField()
    {
        if (!empty($this->logo_version_json)) {
            $keys = array_keys($this->logo_version_json);
            $lastKey = end($keys);
            unset($this->logo_version_json[$lastKey]);
        }
    }

    public function AddVersion()
    {
        try {
            if (is_array($this->logo_version_json)) {
                $this->logo_version_json = array_filter($this->logo_version_json, function ($value) {
                    return $value !== "";
                });
                // Đánh lại index cho mảng (0, 1, 2...) sau khi lọc
                $this->logo_version_json = array_values($this->logo_version_json);
            }
            $this->validate();
            logo_nba_versions::create([
                'team_id' => $this->team_id,
                'begin' => $this->begin,
                'end' => $this->end,
                'pri_tc' => $this->pri_tc,
                'sec_tc' => $this->sec_tc,
                'logo_version_json' => json_encode($this->logo_version_json),
                'on_white' => $this->on_white,
                'on_black' => $this->on_black,
                'on_primary' => $this->on_primary,
                'on_secondary' => $this->on_secondary,

            ]);
            $this->closeModal();
            $this->emit('updateList');
            $this->showAlertMessage('success', 'Add Version Basketball success!'); // Thông báo thành công

        } catch (\Exception $e) {
            $this->showAlertMessage('error', 'Add Version Basketball failed!'); // Thông báo thất bại
        }
    }

    public function render()
    {
        return view('livewire.modals.logos.nba.add-logo-version', []);
    }
}
