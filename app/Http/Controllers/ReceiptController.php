<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * UC-04 Mencetak / Menyimpan Struk.
 */
class ReceiptController extends Controller
{
    public function show(Request $request, Transaction $transaction): View
    {
        $this->authorizeView($request, $transaction);

        return view('receipts.show', [
            'transaction' => $transaction->load('items', 'user'),
            'isNew' => $request->boolean('baru'),
        ]);
    }

    /** Struk digital dalam bentuk PDF (lebar kertas 80 mm). */
    public function pdf(Request $request, Transaction $transaction): Response
    {
        $this->authorizeView($request, $transaction);

        $transaction->load('items', 'user');
        $height = 330 + $transaction->items->count() * 30;

        return Pdf::loadView('receipts.pdf', compact('transaction'))
            ->setPaper([0, 0, 226.77, $height]) // 80 mm dalam satuan point
            ->download("struk-{$transaction->kode_transaksi}.pdf");
    }

    private function authorizeView(Request $request, Transaction $transaction): void
    {
        $user = $request->user();

        abort_if($user->hasRole('kasir') && $transaction->user_id !== $user->user_id, 403);
    }
}
