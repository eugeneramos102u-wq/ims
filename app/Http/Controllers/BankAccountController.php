<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(): View
    {
        $accounts = BankAccount::orderByDesc('is_default')->orderBy('bank_name')->get();

        return view('bank-accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('bank-accounts.form', ['account' => new BankAccount(['status' => 'active'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $this->saveWithDefault(new BankAccount(), $data);

        return redirect()->route('bank-accounts.index')->with('flash', 'Bank account added.');
    }

    public function edit(BankAccount $bankAccount): View
    {
        return view('bank-accounts.form', ['account' => $bankAccount]);
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $data = $this->validateData($request, $bankAccount->id);
        $this->saveWithDefault($bankAccount, $data);

        return redirect()->route('bank-accounts.index')->with('flash', 'Bank account updated.');
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'bank_name'      => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_name'   => ['required', 'string', 'max:150'],
            'branch'         => ['nullable', 'string', 'max:100'],
            'is_default'     => ['nullable', 'boolean'],
            'status'         => ['required', 'in:active,inactive'],
            'notes'          => ['nullable', 'string'],
        ]);
    }

    private function saveWithDefault(BankAccount $account, array $data): void
    {
        DB::transaction(function () use ($account, $data) {
            $account->fill($data);
            $account->is_default = (bool) ($data['is_default'] ?? false);

            if ($account->is_default) {
                BankAccount::where('id', '!=', $account->id ?? 0)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
            $account->save();
        });
    }
}
