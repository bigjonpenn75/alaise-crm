<?php

namespace App\Models;

use App\Enums\Channel;
use Illuminate\Database\Eloquent\Model;

class ChannelSource extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['channel' => Channel::class];
    }
}
