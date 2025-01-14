<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class File extends Model
{
    use HasFactory;

    protected $table = "file";

    protected $primaryKey = 'file_no';

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
        'file_no',
        'file_table',
        'file_table_key',
        'file_name_old',
        'file_name',
        'file_size',
        'file_extension',
        'file_position',
        'file_url'
    ];

    public function files()
    {
        return $this->hasMany('App\Comment');
    }

}
