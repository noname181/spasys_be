<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\Member;
use App\Models\File;


class Manual extends Model
{
    use HasFactory;

    protected $table = "manual";


    protected $primaryKey = 'man_no';

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
        'man_no',
        'menu_no',
        'mb_no',
        'man_title',
        'man_content',
        'man_note',
        'man_tab'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function mb_no()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }

    public function file()
    {
        return $this->hasOne(File::class, 'file_table_key', 'man_no')->where('file_table', 'manual');
    }
    

}
