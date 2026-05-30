<?php

namespace App\Livewire\Pages\Admin;

use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogs extends Component
{
    use WithPagination;

    #[Session(key: 'admin.logs.search')]
    public string $search = '';

    #[Session(key: 'admin.logs.event')]
    public string $event = '';

    #[Session(key: 'admin.logs.actor-type')]
    public string $actorType = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEvent(): void
    {
        $this->resetPage();
    }

    public function updatedActorType(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'event', 'actorType']);
        $this->resetPage();
    }

    public function render(): View
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->when($this->event !== '', fn ($query) => $query->where('event', $this->event))
            ->when($this->actorType !== '', fn ($query) => $query->where('actor_type', $this->actorType))
            ->when(trim($this->search) !== '', function ($query): void {
                $search = '%'.trim($this->search).'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('description', 'like', $search)
                        ->orWhere('event', 'like', $search)
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search));
                });
            })
            ->latest('occurred_at')
            ->paginate(50);

        return view('livewire.pages.admin.activity-logs', [
            'logs' => $logs,
            'events' => ActivityLog::query()->select('event')->distinct()->orderBy('event')->pluck('event'),
            'actorTypes' => ActivityLog::query()->select('actor_type')->distinct()->orderBy('actor_type')->pluck('actor_type'),
        ])->layout('layouts.app');
    }
}
