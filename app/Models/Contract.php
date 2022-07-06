<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $table = "contract";


    protected $primaryKey = 'c_no';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'co_no',
        'mb_no', 
        'c_calculate_deadline_yn',
        'c_integrated_calculate_yn',
        'c_payment_cycle',
        'c_payment_group',
        'c_money_type',
        'c_start_date',
        'c_end_date',
        'c_transaction_yn',
        'c_calculate_method',
        'c_card_number',
        'c_account_number',
        'c_deposit_day',
        'c_deposit_date',
        'c_deposit_price',
        'c_file_insulance',
        'c_file_license',
        'c_file_contract',
        'c_file_bank_account',
        'c_deposit_return_price',
        'c_deposit_return_date',
        'c_deposit_return_reg_date',
        'c_deposit_return_expiry_date'
    ];
}
