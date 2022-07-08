<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $table = "banner";

    protected $primaryKey = 'banner_no';

    public $timestamps = true;
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
        'banner_sliding_yn',
        'mb_no'
    ];

    public function files()
    {
        return $this->hasMany(File::class, 'file_table_key');
    }
    
}
