<?php namespace Layerok\TgMall\Models;

use October\Rain\Database\Model;
use \System\Models\File as SystemFile;

class File extends Model
{
    protected $table = 'layerok_tgmall_files';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public $fillable = [
        'system_file_id',
        'file_id',
    ];

    public $belongsTo = [
        'system_file' => SystemFile::class
    ];

}
