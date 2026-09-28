<?php

namespace App\Models;

use Database\Factories\WardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'floor', 'type'])]
class Ward extends Model
{
    /** @use HasFactory<WardFactory> */
    use HasFactory;
}