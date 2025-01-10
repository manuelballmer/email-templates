<?php

namespace Manuelballmer\EmailTemplates\Traits;

use Illuminate\Support\Facades\App;
use Manuelballmer\EmailTemplates\Facades\TokenHelper;
use Manuelballmer\EmailTemplates\Models\EmailTemplate;

trait BuildGenericEmail
{

    public $emailTemplate;

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->emailTemplate = EmailTemplate::findEmailByKey($this->template, App::currentLocale());

        if ($this->attachment ?? false) {
            $this->attach(
                    $this->attachment->getPath(),
                    [
                            'as'   => $this->attachment->filename,
                            'mime' => $this->attachment->mime_type,
                    ]
            );
        }

        $data = [
                'content'       => TokenHelper::replace($this->emailTemplate->content, $this),
                'preHeaderText' => TokenHelper::replace($this->emailTemplate->preheader, $this),
                'title'         => TokenHelper::replace($this->emailTemplate->title, $this),
                'theme'         => $this->emailTemplate->theme->colours,
                'logo'          => $this->emailTemplate->logo,
        ];

        return $this->from($this->emailTemplate->from['email'], $this->emailTemplate->from['name'])
                    ->view($this->emailTemplate->view_path)
                    ->subject(TokenHelper::replace($this->emailTemplate->subject, $this))
                    ->to($this->sendTo)
                    ->with(['data' => $data]);
    }
}
