<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\File;
use App\Models\Report;
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
        'item_no',
        'rp_number',
        'rp_cate',
        'rp_content',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

    public function files(){
        return $this->HasMany(File::class, 'file_table_key', 'rp_no')->where('file_table', 'report');
    }

    public function reports_child(){
        return $this->HasMany(Report::class, 'rp_parent_no','rp_no')->with('files');
    }

}