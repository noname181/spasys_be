<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\File;
use App\Models\Member;
use App\Models\ImportSchedule;

class ImportSchedule extends Model
{
    use HasFactory;

    protected $table = "import_schedule";

    protected $primaryKey = 'is_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'is_no',
        'co_no',
        'co_license',
        'is_date',
        'm_bl',
        'h_bl',
        'logistic_manage_number',
        'is_ship',
        'logistic_type',
        'is_number',
        'is_weight',
        'is_weight_unit',
        'is_name_eng',
        'is_cargo_eng',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function mb_no()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'file_table_key', 'is_no')->where('file_table', 'import_schedule');
    }
}
