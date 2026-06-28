<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'order_item_id',
        'description',
        'hsn_sac_code',
        'gst_rate',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total',
        'item_weight',
        'sell_unit',
        'pricing_unit',
    ];

    protected $casts = [
        'quantity'    => 'float',
        'gst_rate'    => 'float',
        'unit_price'  => 'float',
        'subtotal'    => 'float',
        'tax_amount'  => 'float',
        'cgst_amount' => 'float',
        'sgst_amount' => 'float',
        'igst_amount' => 'float',
        'total'       => 'float',
        'item_weight' => 'float',
        'sell_unit'   => 'string',
        'pricing_unit'=> 'string',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
