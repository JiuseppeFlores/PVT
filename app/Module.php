<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use Traits\EloquentGetTableNameTrait;

    public $timestamps = false;
    public $guarded = ['id'];
    protected $fillable = ['name', 'display_name'];

    public function roles()
    {
        return $this->hasMany(Role::class);
    }
}