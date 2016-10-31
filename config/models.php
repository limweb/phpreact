<?php
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

consolelog('Model App');
class App  extends  Model  { 

        protected $table='apps';
        protected  $primaryKey='id';
        //public $timestamps = true;
        //protected $guarded = array('id');
        //protected $fillable = [];
        //protected $hidden = [];
        //protected $connection = '';
        //use SoftDeletingTrait;
        //protected $dates = ['deleted_at'];

        //protected $casts = [
        //     ""       => '',
        //];

        //public static function boot()     {
        //    parent::boot();
        //}

 }

