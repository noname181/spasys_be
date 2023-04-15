<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class ItemChannel extends Model
{
    use HasFactory;

    protected $table = "item_channel";

    protected $primaryKey = 'item_channel_no';

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
        'item_no',
        'item_channel_no',
        'item_channel_code',
        'item_channel_name'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function file()
    {
        return $this->hasOne(File::class, 'file_table_key', 'item_no')->where('file_table', 'item');
    }

}
