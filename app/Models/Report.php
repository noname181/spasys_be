<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\File;
use App\Models\Report;
use App\Models\Export;
use App\Models\Member;
class Report extends Model
{
    use HasFactory;

    protected $table = "report";

    protected $primaryKey = 'rp_no';

    public $timestamps = true;
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
    public function export()
    {
        return $this->belongsTo(Export::class, 'w_no', 'te_carry_out_number')->with(['import_expected']);
    }
    public function member()
    {
        return $this->belongsTo(Member::class, 'mb_no', 'mb_no')->with('company');
    }
  
}
