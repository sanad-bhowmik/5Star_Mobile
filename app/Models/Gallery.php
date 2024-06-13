<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    protected $fillable = ['product_id','photo','type'];
    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo('App\Models\Product')->withDefault(function ($data) {
            foreach($data->getFillable() as $dt){
                $data[$dt] = __('Deleted');
            }
        });
    }

}
