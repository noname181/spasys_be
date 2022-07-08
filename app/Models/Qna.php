<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'qna_content'
    ];
}
