<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\File;
use App\Models\Company;
use App\Models\ImportSchedule;

class ImportSchedule extends Model
{
    use HasFactory;

    protected $table = "import_schedule";

    protected $primaryKey = 'is_no';

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
        'is_date' => "date:Y.m.d H:i",
    ];

    public function co_no()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'file_table_key', 'is_no')->where('file_table', 'import_schedule');
    }
}
