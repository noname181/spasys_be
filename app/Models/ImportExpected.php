<?php

namespace App\Models;
use App\Models\Import;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportExpected extends Model
{
    use HasFactory;

    protected $table = "t_import_expected";

    protected $primaryKey = 'tie_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'tie_no',
        'tie_status',
        'tie_status_2',
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
        return $this->hasOne(Import::class,'ti_logistic_manage_number','tie_logistic_manage_number')->with('export');
    }

    public function company()
    {
        return $this->hasOne(Company::class,'co_license','tie_co_license')->with('co_parent');
    }

}
