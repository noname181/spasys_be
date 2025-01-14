<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class AdjustmentGroup extends Model
{
    use HasFactory;

    protected $table = "adjustment_group";

    protected $primaryKey = 'ag_no';

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
        'ag_no',
        'co_no',
        'mb_no',
        'ag_name',
        'ag_manager',
        'ag_email',
        'ag_email2',
        'ag_hp',
        'ag_auto_issue',
    ];
}
