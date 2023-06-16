<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\Member;
use App\Models\Warehousing;
use App\Models\RateMetaData;
use App\Models\Export;
use App\Models\CancelBillHistory;
use App\Models\Payment;


class ReceivingGoodsDelivery extends Model
{
    use HasFactory;

    protected $table = "receiving_goods_delivery";

    protected $primaryKey = 'rgd_no';

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
        'rgd_memo_settle',
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
        'rgd_ti_carry_in_number',
        'rgd_te_carry_out_number',
        'rgd_discount_rate',
        'created_at',
        'updated_at',
        'is_no'
    ];

    protected $casts = [
        // 'created_at' => "date:Y.m.d H:i",
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
        'rgd_tax_invoice_date' => "date:Y.m.d H:i",
        //'rgd_delivery_schedule_day'=> "date: Y.m.d",
        'rgd_paid_date' => "date:Y.m.d H:i",
        'rgd_monthbill_start' => "date:Y.m.d",
        'rgd_monthbill_end' => "date:Y.m.d",
        'rgd_confirmed_date' => "date:Y.m.d H:i",
        'rgd_issue_date' => "date:Y.m.d H:i",
    ];

    public function mb_no()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }
    public function member()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }

    public function w_no()
    {
        return $this->hasOne(Warehousing::class, 'w_no', 'w_no')->with(['co_no', 'warehousing_item','w_import_parent','warehousing_child', 'warehousing_request','w_ew'])->withSum('warehousing_item_IW_spasys_confirm', 'wi_number');
    }

    public function warehousing()
    {
        return $this->hasOne(Warehousing::class, 'w_no', 'w_no')->with(['warehousing_item','w_import_parent', 'warehousing_child', 'warehousing_request', 'company', 'settlement_cargo','import_expect', 'w_ew']);
    }
    public function rgd_child()
    {
        return $this->hasOne(ReceivingGoodsDelivery::class, 'rgd_parent_no', 'rgd_no')->where(function($q) {
            $q->where('rgd_status5', '!=', 'cancel')->orWhereNull('rgd_status5');
        })->select(['rgd_status5', 'rgd_status4', 'rgd_status6', 'rgd_no', 'rgd_parent_no', 'rgd_paid_date', 'rgd_tax_invoice_date', 'rgd_settlement_number']);
    }

    public function rgd_parent()
    {
        return $this->belongsTo(ReceivingGoodsDelivery::class, 'rgd_parent_no', 'rgd_no')->select(['rgd_status5', 'rgd_status4', 'rgd_status6', 'rgd_no']);
    }
    public function rgd_parent_payment()
    {
        return $this->belongsTo(ReceivingGoodsDelivery::class, 'rgd_parent_no', 'rgd_no')->with(['rate_data_general']);
    }
    public function rgd_settlement()
    {
        return $this->hasMany(ReceivingGoodsDelivery::class, 'rgd_settlement_number', 'rgd_settlement_number')->with(['rate_data_general', 'rate_meta_data', 'rate_meta_data_parent'])->select(['rgd_status5', 'rgd_status4', 'rgd_status6', 'rgd_no', 'rgd_parent_no', 'rgd_settlement_number']);
    }
    public function rate_data_general(){
        return $this->hasOne(RateDataGeneral::class, 'rgd_no', 'rgd_no')->with(['rgd_no_final', 'adjustment_group']);
    }
    public function rate_meta_data(){
        return $this->hasMany(RateMetaData::class, 'rgd_no', 'rgd_no')->with(['rate_data'])->orderBy('set_type');
    }
    public function rate_meta_data_parent(){
        return $this->hasMany(RateMetaData::class, 'rgd_no', 'rgd_parent_no')->with(['rate_data']);
    }
    public function t_export(){
        return $this->belongsTo(Export::class, 'rgd_te_carry_out_number', 'te_carry_out_number');
    }
    public function settlement_number(){
        return $this->hasMany(ReceivingGoodsDelivery::class, 'rgd_settlement_number', 'rgd_settlement_number')->with(['rate_data_general']);
    }
    public function cancel_bill_history(){
        return $this->hasOne(CancelBillHistory::class, 'rgd_no', 'rgd_no')->where('cbh_type', 'cancel');
    }

    public function t_import(){
        return $this->belongsTo(Import::class, 'rgd_ti_carry_in_number', 'ti_carry_in_number');
    }

    public function t_import_expected(){
        return $this->belongsTo(ImportExpected::class, 'rgd_tracking_code', 'tie_logistic_manage_number')->with('export');
    }

    public function import_table(){
        return $this->belongsTo(Import::class, 'is_no', 'ti_carry_in_number');
    }
    public function payment(){
        return $this->belongsTo(Payment::class, 'rgd_no', 'rgd_no');
    }

    public function tax(){
        return $this->belongsTo(TaxInvoiceDivide::class, 'rgd_no', 'rgd_no');
    }

}
