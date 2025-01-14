<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\File;
use App\Models\Member;

class Notice extends Model
{
    use HasFactory;

    protected $table = "notice";

    protected $primaryKey = 'notice_no';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'notice_no',
        'mb_no',
        'notice_title',
        'notice_content',
        'notice_target',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function files()
    {
        return $this->hasMany(File::class, 'file_table_key', 'notice_no')->where('file_table', 'notice');
    }
    public function member()
    {
        return $this->belongsTo(Member::class, 'mb_no', 'mb_no')->with('company');
    }
}
