<?php

namespace Manuelballmer\EmailTemplates\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Manuelballmer\EmailTemplates\Models\EmailTemplate;

class EmailTemplatePreviewController extends Controller
{
    public function show(EmailTemplate $emailTemplate): Response
    {
        $html = base64_decode($emailTemplate->getBase64EmailPreviewData());

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }
}
