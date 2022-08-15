<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemChannel extends Model
{
    use HasFactory;

    protected $table = "item_channel";

    protected $primaryKey = 'item_channel_no';

    public $timestamps = true;
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
