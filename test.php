<?php 
require_once __DIR__.'/config/database.php';
use Illuminate\Database\Capsule\Manager as Capsule;
$p = Capsule::table('products')->get();
dump($p);
