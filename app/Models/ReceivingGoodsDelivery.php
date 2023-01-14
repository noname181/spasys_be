<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;
use App\Models\Warehousing;
use App\Models\RateMetaData;
use App\Models\Export;
use App\Models\CancelBillHistory;

class ReceivingGoodsDelivery extends Model
{
    use HasFactory;

    protected $table = "receiving_goods_delivery";

    protected $primaryKey = 'rgd_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'rgd_no',
        'mb_no',
        'w_no',
        'ss_no',
        'rgd_contents',
        'rgd_address',
        'rgd_address_detail',
        'rgd_receiver',
        'rgd_settlement_number',
        'rgd_bill_type',
        'rgd_hp',
        'rgd_memo',
        'rgd_status1',
        'rgd_status2',
        'rgd_status3',
        'rgd_delivery_company',
        'rgd_tracking_code',
        'rgd_delivery_man',
        'rgd_delivery_man_hp',
        'rgd_delivery_schedule_day',
        'rgd_arrive_day',
        'service_korean_name',
        'rgd_paid_date',
        'rgd_tax_invoice_number',
        'rgd_integrated_calculate_yn',
        'rgd_calculate_deadline_yn',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        // 'created_at' => "date:Y.m.d H:i",
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
        'rgd_tax_invoice_date' => "date:Y.m.d H:i",
        //'rgd_delivery_schedule_day'=> "date: Y.m.d",
        'rgd_paid_date' => "date: Y.m.d H:i",
        'rgd_monthbill_start' => "date: Y.m.d",
        'rgd_monthbill_end' => "date: Y.m.d",
        'rgd_confirmed_date' => "date:Y.m.d H:i",
        'rgd_issue_date' => "date:Y.m.d H:i",
    ];

    public function mb_no()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }

    public function w_no()
    {
        return $this->hasOne(Warehousing::class, 'w_no', 'w_no')->with(['co_no', 'warehousing_item','w_import_parent','warehousing_child', 'warehousing_request', 'import_expect','w_ew'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number');
    }

    public function warehousing()
    {
        return $this->hasOne(Warehousing::class, 'w_no', 'w_no')->with(['co_no', 'warehousing_item','w_import_parent', 'warehousing_child', 'warehousing_request', 'company']);
    }
    public function rgd_child()
    {
        return $this->hasOne(ReceivingGoodsDelivery::class, 'rgd_parent_no', 'rgd_no')->where('rgd_status5', '!=', 'cancel')->select(['rgd_status5', 'rgd_status4', 'rgd_status6', 'rgd_no', 'rgd_parent_no']);
    }

    public function rgd_parent()
    {
        return $this->belongsTo(ReceivingGoodsDelivery::class, 'rgd_parent_no', 'rgd_no')->select(['rgd_status5', 'rgd_status4', 'rgd_status6', 'rgd_no']);
    }

    public function rate_data_general(){
        return $this->hasOne(RateDataGeneral::class, 'rgd_no', 'rgd_no')->with(['rgd_no_final']);
    }
    public function rate_meta_data(){
        return $this->hasMany(RateMetaData::class, 'rgd_no', 'rgd_no')->with(['rate_data']);
    }
    public function rate_meta_data_parent(){
        return $this->hasMany(RateMetaData::class, 'rgd_no', 'rgd_parent_no')->with(['rate_data']);
    }
    public function t_export(){
        return $this->belongsTo(Export::class, 'rgd_tracking_code', 'te_logistic_manage_number')->with(['import','import_expected']);
    }
    public function settlement_number(){
        return $this->hasMany(ReceivingGoodsDelivery::class, 'rgd_settlement_number', 'rgd_settlement_number')->with(['rate_data_general']);
    }
    public function cancel_bill_history(){
        return $this->hasOne(CancelBillHistory::class, 'rgd_no', 'rgd_no');
    }

    public function t_import(){
        return $this->belongsTo(Import::class, 'rgd_tracking_code', 'ti_logistic_manage_number');
    }


}
