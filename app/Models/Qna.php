<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;
use App\Models\File;

class Qna extends Model
{
    use HasFactory;

    protected $table = "qna";

    protected $primaryKey = 'qna_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'qna_no',
        'mb_no',
        'qna_status',
        'mb_no_target',
        'qna_title',
        'qna_content',
        'answer_for',
        'depth_path',
        'depth_level',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function mb_no_target()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no_target');
    }

    public function mb_no()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'file_table_key', 'qna_no')->where('file_table', 'qna');
    }

    public function childQna()
    {
        return $this->hasMany(Qna::class, 'answer_for', 'qna_no');
    }
}
