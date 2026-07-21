<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class JornadaModel extends Model
{
    public $timestamps = false;

    protected $keyType = 'int';

    public $incrementing = true;
}
