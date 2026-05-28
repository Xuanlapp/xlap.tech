<?php

namespace App\Http\Livewire\Modals\Program;


use LivewireUI\Modal\ModalComponent;
use Illuminate\Support\Facades\Mail;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Http\Livewire\Traits\Notification;
use Livewire\WithFileUploads;

class SendMail extends ModalComponent
{
    use LivewireAlert, Notification, WithFileUploads;

    public $name, $email, $phone, $comment, $success, $title, $file, $send_email;
    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'comment' => 'nullable|string|max:500',
        'file' => 'nullable|file|mimes:jpg,png,pdf,xlsx,php,blade.php,csv|max:2048',
    ];

    public function stylistcontactFormSubmit()
    {
        $contact = $this->validate();
        $filePath = null;
        if ($this->file) {
            $filePath = $this->file->store('uploads', 'public');
        }
        Mail::send('emails.generic',
            [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'comment' => $this->comment,
            ],
            function ($message) use ($filePath) {
                //                $message->from($this->send_email);
                ////                $message->from($this->send_email);
                //                $message->to($this->email, $this->name)
                $message->from(config('mail.from.address'), config('mail.from.name'),
                    $this->send_email); // Vẫn dùng email từ .env
                $message->to($this->email, $this->name)
                    ->subject($this->title);

                if ($filePath) {
                    $message->attach(storage_path('app/public/' . $filePath));
                }
            }
        );

        $this->showAlertMessage('success', 'Send email success!');

        $this->clearFields();
    }

    private function clearFields()
    {
        $this->title = '';
        $this->name = '';
        $this->email = '';
        $this->comment = '';
        $this->phone = '';
        $this->file = null;
    }


    public function render()
    {
        return view('livewire.modals.program.send-mail');
    }

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }
}
