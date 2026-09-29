<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class MailboxController extends Controller
{
    public function index(): View
    {
        return view('mailbox.index', [
            'webmailUrl' => (string) config('mailbox.webmail_url'),
        ]);
    }
}
