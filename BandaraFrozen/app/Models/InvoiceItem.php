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
        'product_sell_unit_id',
        'b2b_order_mode',
        'requested_piece_count',
        'requested_weight_kg',
        'actual_weight_kg',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_amount',
        'total',
        'item_weight',
        'sell_unit',
        'pricing_unit',
    ];

    protected $casts = [
        'quantity'    => 'float',
        'unit_price'  => 'float',
        'subtotal'    => 'float',
        'tax_amount'  => 'float',
        'total'       => 'float',
        'item_weight' => 'float',
        'sell_unit'   => 'string',
        'pricing_unit'=> 'string',
        'product_sell_unit_id' => 'integer',
        'b2b_order_mode' => 'string',
        'requested_piece_count' => 'integer',
        'requested_weight_kg' => 'decimal:3',
        'actual_weight_kg' => 'decimal:3',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function sellUnit()
    {
        return $this->belongsTo(ProductSellUnit::class, 'product_sell_unit_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
