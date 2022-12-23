<?php

namespace App\Models;

use App\Http\Requests\ImportSchedule\ImportScheduleRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyPayment extends Model
{
    use HasFactory;

    protected $table = "company_payment";

    protected $primaryKey = 'cp_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'cp_no ',
        'co_no',
        'cp_method',
        'cp_bank',
        'cp_bank_number',
        'cp_bank_name',
        'cp_card_name',
        'cp_card_number',
        'cp_card_cvc',
        'cp_virtual_account',
        'cp_cvc',
        'cp_valid_period'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no');
    }


}
