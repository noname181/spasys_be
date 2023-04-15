<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Banner extends Model
{
    use HasFactory;

    protected $table = "banner";

    protected $primaryKey = 'banner_no';

        public $timestamps = true;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->timezone('Asia/seoul')->format('Y-m-d H:i:s');
    }

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
        'banner_start' => "date:Y.m.d",
        'banner_end' => "date:Y.m.d",
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'banner_no',
        'banner_title',
        'banner_location',
        'banner_start',
        'banner_end',
        'banner_use_yn',
        'banner_position',
        'banner_position_detail',
        'banner_sliding_yn',
        'banner_link1',
        'banner_link2',
        'banner_link3',
        'mb_no'
    ];

    public function files()
    {
        return $this->hasMany(File::class, 'file_table_key', 'banner_no')->where('file_table', 'banner')->orderBy('file_position');
    }

    public function mb_no()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }
}
