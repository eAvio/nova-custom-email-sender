<?php

namespace Dniccum\CustomEmailSender\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;

class CustomMessageMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @var string $subject
     */
    public $subject;

    /**
     * @var string $message
     */
    public $message;

    /**
     * @var array|null $sender
     */
    public $sender;

    /**
     * @var array|null $sender
     */
    public $files;

    /**
     * @var array|null $sender
     */
    public $event;

    /**
     * Create a new message instance.
     *
     * @param string $subject
     * @param string $message
     * @param array|null $sender
     * @return void
     */
    public function __construct(string $subject, string $message, $sender = null, $files = [], $event = null)
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->sender = $sender;
        $this->files = $files;
        $this->event = $event;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $view = config('novaemailsender.template.view');

        if (!view()->exists($view)) {
            \View::addLocation(base_path('vendor/dniccum/custom-email-sender/resources/views'));
            $view = 'email';
        }

        if (is_null($this->sender)) {
            $from_options = config('novaemailsender.from.options', []);
            $sender_email = $from_options[0]['address'] ?? '';
            $sender_name = $from_options[0]['name'] ?? '';
        } else {
            $sender_email = $this->sender['address'];
            $sender_name = $this->sender['name'];
        }

        $this->subject($this->subject)
            ->onQueue(config('novaemailsender.priority'))
            ->from($sender_email, $sender_name)
            ->with([
                'content' => $this->message
            ]);

        if (config('novaemailsender.template.markdown')) {
            $this->markdown($view);
        } else {
            $this->view($view);
        }
        if ($this->event) {
            $this->attachData($this->event, 'ical.ics');
        }
        foreach ($this->files as $filename) {
            $this->attachFromStorageDisk('s3', $filename);
        }

        return $this;
    }
}
