<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use App\Models\Member;
use App\Models\File;
use App\Models\Company;

class Qna extends Model
{
    use HasFactory;

    protected $table = "qna";

    protected $primaryKey = 'qna_no';

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
        'qna_no',
        'co_no_target',
        'mb_no',
        'mb_no_question',
        'qna_status',
        'mb_no_target',
        'qna_title',
        'qna_content',
        'answer_for',
        'depth_path',
        'depth_level',
        'spasys_no',
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
        return $this->hasOne(Member::class, 'mb_no', 'mb_no')->with('company');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'file_table_key', 'qna_no')->where('file_table', 'qna');
    }

    public function childQna()
    {
        return $this->hasMany(Qna::class, 'answer_for', 'qna_no')->with('company','member','member_question');
    }
    public function member()
    {
        return $this->belongsTo(Member::class, 'mb_no', 'mb_no')->with('company');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'co_no_target', 'co_no')->with('co_parent');
    }
    public function member_question()
    {
        return $this->belongsTo(Member::class, 'mb_no_question', 'mb_no')->with('company');
    }
}
