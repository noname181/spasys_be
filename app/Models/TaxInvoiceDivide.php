<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class TaxInvoiceDivide extends Model
{
    use HasFactory;

    protected $table = "tax_invoice_divide";

    protected $primaryKey = 'tid_no';

        public $timestamps = true;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->timezone('Asia/seoul')->format('Y-m-d H:i:s');
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'tid_no',
        'rgd_no',
        'rgd_number',
        'mb_no',
        'tid_number',
        'tid_price',
        'tid_price_left',
        'tid_index',
        'tid_supply_price',
        'tid_sum',
        'tid_vat',
        'tid_type',
        'co_license',
        'co_owner',
        'co_name',
        'co_major',
        'co_email',
        'co_email2',
        'co_address',
        'co_address2',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];
}
