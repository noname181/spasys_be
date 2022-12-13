<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxInvoiceDivide extends Model
{
    use HasFactory;

    protected $table = "tax_invoice_divide";

    protected $primaryKey = 'tid_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'tid_no',
        'rgd_no',
        'mb_no',
        'tid_number',
        'tid_price',
        'tid_price_left',
        'tid_index',
        'tid_supply_price',
        'tid_vat',
        'tid_sum',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];
}
