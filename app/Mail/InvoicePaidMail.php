<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoicePaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function build()
    {
        $mail = $this->subject('Payment received for invoice ' . $this->invoice->invoice_number)
            ->view('emails.invoices.paid', [
                'invoice' => $this->invoice,
            ]);

        if ($this->invoice->pdf_path) {
            $mail->attachFromStorageDisk(
                'public',
                $this->invoice->pdf_path,
                $this->invoice->invoice_number . '.pdf'
            );
        }

        return $mail;
    }
}
