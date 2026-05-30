<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Account;
use App\Models\LedgerEntry;

final class AccountingController extends Controller
{
    public function accounts(): void
    {
        $this->view('layouts/app', [
            'title' => 'Chart of Accounts',
            'active' => 'accounting',
            'content' => 'accounting/accounts',
            'accounts' => (new Account())->all(),
        ]);
    }

    public function ledger(): void
    {
        $this->view('layouts/app', [
            'title' => 'Ledger',
            'active' => 'accounting',
            'content' => 'accounting/ledger',
            'entries' => (new LedgerEntry())->all(),
        ]);
    }

    public function ledgerEntry(string $id): void
    {
        $entry = (new LedgerEntry())->findWithLines((int) $id);

        if (!$entry) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Ledger entry not found',
                'message' => 'That ledger entry could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => $entry['entry_number'],
            'active' => 'accounting',
            'content' => 'accounting/ledger-entry',
            'entry' => $entry,
        ]);
    }
}
