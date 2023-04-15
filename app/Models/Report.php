<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\File;
use App\Models\Report;
use App\Models\Export;
use App\Models\ImportExpected;
use App\Models\Import;
use App\Models\Member;
class Report extends Model
{
    use HasFactory;

    protected $table = "report";

    protected $primaryKey = 'rp_no';

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
        'rp_no',
        'mb_no',
        'w_no',
        'rp_number',
        'rp_cate',
        'rp_content',
        'rp_h_bl',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function files(){
        return $this->HasMany(File::class, 'file_table_key', 'rp_no')->where('file_table', 'report');
    }

    public function reports_child(){
        return $this->HasMany(Report::class, 'rp_parent_no','rp_no')->with('files');
    }

    public function reports_child_mobi(){
        return $this->HasMany(Report::class, 'rp_parent_no','rp_no')->whereRaw('rp_no != rp_parent_no')->with('files');
    }
    public function warehousing()
    {
        return $this->belongsTo(Warehousing::class, 'w_no', 'w_no')->with(['import_schedule','co_no','warehousing_item','receving_goods_delivery']);
    }
    public function warehousing_by_te()
    {
        return $this->belongsTo(Warehousing::class, 'w_no', 'te_carry_out_number')->with(['import_schedule','co_no','warehousing_item','receving_goods_delivery']);
    }
    public function warehousing_by_ti()
    {
        return $this->belongsTo(Warehousing::class, 'w_no', 'ti_carry_in_number')->with(['import_schedule','co_no','warehousing_item','receving_goods_delivery']);
    }
    public function warehousing_by_tie()
    {
        return $this->belongsTo(Warehousing::class, 'w_no', 'logistic_manage_number')->with(['import_schedule','co_no','warehousing_item','receving_goods_delivery']);
    }
    public function export()
    {
        //$this->belongsTo(Export::class, 'rp_h_bl', 'te_h_bl')->with(['import_expected']);
        return $this->belongsTo(Export::class, 'rp_h_bl', 'te_h_bl')->with(['import_expected']);
    }
    public function import()
    {
        return $this->belongsTo(Import::class, 'rp_h_bl', 'ti_h_bl')->with(['import_expected']);
    }
    public function import_expect()
    {
        return $this->belongsTo(ImportExpected::class, 'rp_h_bl', 'tie_h_bl')->with(['company','company_spasys']);
    }
    public function member()
    {
        return $this->belongsTo(Member::class, 'mb_no', 'mb_no')->with('company');
    }
  
}
