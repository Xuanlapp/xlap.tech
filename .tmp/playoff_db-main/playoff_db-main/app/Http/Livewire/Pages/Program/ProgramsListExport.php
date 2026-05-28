<?php

namespace App\Http\Livewire\Pages\Program;

use App\Http\Livewire\Traits\Notification;
use App\Models\Programs;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\Program_forms;
use App\Models\sport_customers;


class ProgramsListExport implements FromCollection, WithHeadings
{
    use WithFileUploads, WithPagination, Notification, LivewireAlert;

    protected $programId;

    public function __construct($programId)
    {
        $this->programId = $programId;
    }

    public function collection()
    {
        $program = Programs::find($this->programId);
        if (!$program) {
            return collect([]);
        }

        $programForms = Program_forms::where('program_id', $this->programId)
            ->with('subPrograms')
            ->get();
        $DueDate_P = Programs::where('id', $this->programId)->first();
        $DueDate_Programs = $DueDate_P->ship;
        // JobType options
        $jobTypeOptions = ["IN", "PR", "PC", "TR"];
        $groupPathSub = substr($program->collection, 0, 10);
        $data = [];
        $jobCounter = 1;
        $programNumber = substr($program->code, -4);
        $jobTypeIndex = 0;
        //lấy customer name
        $customer = sport_customers::where('id', $program->customer_id)->first();
        $customer_name = $customer->customer_name ?? '';
        $data[] = [
            'Job' => $jobCounter++,
            'JobType' => "DP",
            'GroupPath' => $program->sp,
            'ProgramNumber' => $programNumber,
            'JobName' => "{$programNumber}_{$groupPathSub}",
            'Customer' => $customer_name,
            'DueDate' => $DueDate_Programs,
        ];
        foreach ($programForms as $programForm) {
            $groupPath = $programForm->insert_short_name;
            $GroupPath = $program->sp;
            $jobName = "{$programNumber}_FORM{$programForm->form}";

            // Giải mã JSON để lấy DueDate
            $fileData = json_decode($programForm->files, true);
            $dueDate_Form = $fileData['due'] ?? null;
//            dd($dueDate_Form);
            // Kiểm tra trùng lặp JobName trước khi thêm
            if (!collect($data)->contains('JobName', $jobName)) {
                $data[] = [
                    'Job' => $jobCounter++,
                    'JobType' => "FM",
                    'GroupPath' => $GroupPath,
                    'ProgramNumber' => $programNumber,
                    'JobName' => $jobName,
                    'Customer' => $customer_name,
                    'DueDate' => $dueDate_Form,
                ];
            }
            // Thêm Job phụ với các JobType
            foreach ($jobTypeOptions as $jobType) {
                $jobNameSub = "{$programForm->id}_{$programNumber}F{$programForm->form}_{$groupPath}";
                $data[] = [
                    'Job' => $jobCounter++,
                    'JobType' => $jobType,
                    'GroupPath' => $GroupPath,
                    'ProgramNumber' => $programNumber,
                    'JobName' => $jobNameSub,
                    'Customer' => $customer_name,
                    'DueDate' => $dueDate_Form,
                ];
            }
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Job',
            'JobType',
            'GroupPath',
            'ProgramNumber',
            'JobName',
            'Customer',
            'DueDate',
        ];
    }
}
