<?php

namespace App\Models;
use App\Models\Import;
use App\Models\Export;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\ReceivingGoodsDelivery;
class ImportExpected extends Model
{
    use HasFactory;

    protected $table = "t_import_expected";

    protected $primaryKey = 'tie_no';

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
        'tie_no',
        'tie_status',
        'tie_status_2',
        'tie_warehouse_code',
        'tie_logistic_manage_number',
        'tie_register_id',
        'tie_is_date',
        'tie_is_ship',
        'tie_co_license',
        'tie_is_cargo_eng',
        'tie_is_number',
        'tie_is_weight',
        'tie_is_weight_unit',
        'tie_m_bl',
        'tie_h_bl',
        'tie_is_name_eng',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
        'tie_is_date' => "date:Y.m.d",
    ];

    public function import()
    {
        return $this->hasOne(Import::class,'ti_logistic_manage_number','tie_logistic_manage_number')->with('import_expect');
    }

    public function company()
    {
        return $this->hasOne(Company::class,'co_license','tie_co_license')->with(['co_parent','rate_data_1']);
    }

    public function company_spasys()
    {
        return $this->hasOne(Company::class,'warehouse_code','tie_warehouse_code')->with(['co_parent','rate_data_1']);
    }
    
    public function export()
    {
        return $this->hasOne(Export::class, 'te_logistic_manage_number', 'tie_logistic_manage_number')->with('receiving_goods_delivery');
    }

    public function receiving_goods_delivery()
    {
        return $this->hasMany(ReceivingGoodsDelivery::class, 'is_no', 'te_carry_out_number');
    }

}
