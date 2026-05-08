<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Collection;
use App\Models\Deposit;
use App\Models\DocumentSequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function index(Request $request): View
    {
        $query = Deposit::with(['bankAccount', 'creator'])->withCount('collections');

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }
        if ($bank = $request->integer('bank_account_id')) {
            $query->where('bank_account_id', $bank);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('deposit_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('deposit_date', '<=', $request->input('to_date'));
        }

        $deposits = $query->latest('deposit_date')->latest('id')->paginate(15)->withQueryString();
        $accounts = BankAccount::where('status', 'active')->orderBy('bank_name')->get();

        // Un-deposited collections summary (for the index page hint)
        $undepositedCount = Collection::whereNull('deposit_id')->count();
        $undepositedTotal = (float) Collection::whereNull('deposit_id')->sum('amount');

        return view('deposits.index', compact('deposits', 'accounts', 'undepositedCount', 'undepositedTotal'));
    }

    public function create(): View
    {
        // Only physical instruments need a bank deposit slip.
        // Bank transfers are already in the bank; card payments settle via the merchant.
        $undeposited = Collection::with(['invoice.customer', 'collector'])
            ->whereNull('deposit_id')
            ->whereIn('payment_method', ['cash', 'check'])
            ->orderBy('collection_date')
            ->orderBy('id')
            ->get();

        return view('deposits.form', [
            'deposit' => new Deposit([
                'deposit_date' => now()->toDateString(),
            ]),
            'accounts' => BankAccount::where('status', 'active')->orderByDesc('is_default')->orderBy('bank_name')->get(),
            'collections' => $undeposited,
            'nextDepositNumber' => $this->peekSequence('deposit'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'deposit_date'    => ['required', 'date'],
            'slip_number'     => ['nullable', 'string', 'max:60'],
            'notes'           => ['nullable', 'string'],
            'collection_ids'  => ['required', 'array', 'min:1'],
            'collection_ids.*'=> ['integer', 'exists:collections,id'],
        ], [
            'collection_ids.required' => 'Select at least one collection to deposit.',
            'collection_ids.min'      => 'Select at least one collection to deposit.',
        ]);

        $postNow = $request->input('action') === 'post';

        if ($postNow && ! $request->user()->hasRole('Admin', 'Manager')) {
            abort(403, 'Only Admin or Manager can post a deposit.');
        }

        $deposit = DB::transaction(function () use ($data, $request, $postNow) {
            // Lock the selected collections to prevent races with another deposit being created concurrently
            $collections = Collection::whereIn('id', $data['collection_ids'])
                ->lockForUpdate()
                ->get();

            $alreadyDeposited = $collections->filter(fn ($c) => ! is_null($c->deposit_id));
            if ($alreadyDeposited->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'collection_ids' => 'One or more selected collections are already part of another deposit. Refresh and try again.',
                ]);
            }

            $invalidMethods = $collections->reject(fn ($c) => in_array($c->payment_method, ['cash', 'check'], true));
            if ($invalidMethods->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'collection_ids' => 'Only cash and check collections can be deposited. Bank transfers and card payments are settled directly.',
                ]);
            }

            $cash  = (float) $collections->where('payment_method', 'cash')->sum('amount');
            $check = (float) $collections->where('payment_method', 'check')->sum('amount');
            $other = (float) $collections->whereNotIn('payment_method', ['cash', 'check'])->sum('amount');
            $total = $cash + $check + $other;

            $deposit = Deposit::create([
                'deposit_number'  => DocumentSequence::next('deposit'),
                'bank_account_id' => $data['bank_account_id'],
                'deposit_date'    => $data['deposit_date'],
                'slip_number'     => $data['slip_number'] ?? null,
                'cash_amount'     => $cash,
                'check_amount'    => $check,
                'other_amount'    => $other,
                'total_amount'    => $total,
                'status'          => 'draft',
                'notes'           => $data['notes'] ?? null,
                'created_by'      => $request->user()->id,
            ]);

            Collection::whereIn('id', $collections->pluck('id'))
                ->update(['deposit_id' => $deposit->id]);

            if ($postNow) {
                $deposit->update([
                    'status'    => 'posted',
                    'posted_by' => $request->user()->id,
                    'posted_at' => now(),
                ]);
            }

            return $deposit;
        });

        $message = $postNow
            ? "Deposit {$deposit->deposit_number} created and posted."
            : 'Deposit slip drafted.';

        return redirect()->route('deposits.show', $deposit)->with('flash', $message);
    }

    public function show(Deposit $deposit): View
    {
        $deposit->load(['bankAccount', 'collections.invoice.customer', 'collections.collector', 'creator', 'poster']);

        return view('deposits.show', compact('deposit'));
    }

    public function post(Request $request, Deposit $deposit): RedirectResponse
    {
        if (! $deposit->isPostable()) {
            abort(422, 'Only draft deposits can be posted.');
        }

        $deposit->update([
            'status'    => 'posted',
            'posted_by' => $request->user()->id,
            'posted_at' => now(),
        ]);

        return redirect()->route('deposits.show', $deposit)
            ->with('flash', 'Deposit posted to bank — collections locked.');
    }

    public function cancel(Request $request, Deposit $deposit): RedirectResponse
    {
        if (! $deposit->isCancellable()) {
            abort(422, 'Only draft deposits can be cancelled. Posted deposits cannot be undone here.');
        }

        $data = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($deposit, $data, $request) {
            // Release collections back to the un-deposited pool
            Collection::where('deposit_id', $deposit->id)->update(['deposit_id' => null]);

            $deposit->update([
                'status'        => 'cancelled',
                'cancel_reason' => $data['cancel_reason'] ?? null,
                'cancelled_by'  => $request->user()->id,
                'cancelled_at'  => now(),
            ]);
        });

        return redirect()->route('deposits.index')
            ->with('flash', "Deposit {$deposit->deposit_number} cancelled. Collections released.");
    }

    private function peekSequence(string $key): string
    {
        $seq = DocumentSequence::where('seq_key', $key)->first();

        return $seq->prefix . str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
    }
}
