<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Bangkok');
$useDB = true;
require_once __DIR__.'/../vendor/autoload.php';
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Events\Dispatcher;
// use Illuminate\Container\Container;


if ( ! function_exists( 'implodeKV' ) && ! function_exists('consolelog') ) {

    function implodeKV($glueKV, $gluePair, $KVarray)  {
        if( is_object($KVarray) ) {
           $KVarray = json_decode(json_encode($KVarray),TRUE);
        }
        $t = array();
        foreach($KVarray as $key=>$val) {
            if(is_array($val)){
               $val = implodeKV(':',',',$val);
            } else if( is_object($val)){
                $val = json_decode(json_encode($val),TRUE);
                $val = implodeKV(':',',',$val);
            }

            if(is_int($key)){
                $t[] = $val;
            } else {
                $t[] = $key . $glueKV . $val;
            }
        }
        return implode($gluePair, $t);
    }

    function consolelog($status = 200)  {
            
            $lists = func_get_args();
            $status = '';
            $status = implodeKV( ':' , ' ' , $lists);
      
           if(isset($_SERVER["REMOTE_ADDR"]) && !empty($_SERVER["REMOTE_ADDR"])){
              $raddr =$_SERVER["REMOTE_ADDR"];
           } else {
              $raddr = '127.0.0.1';
           }

           if(isset($_SERVER["REMOTE_PORT"]) && !empty($_SERVER["REMOTE_PORT"])){
              $rport = $_SERVER["REMOTE_PORT"];
           } else {
              $rport = '8000';
           }

           if(isset($_SERVER["REQUEST_URI"]) && !empty($_SERVER["REQUEST_URI"])){
              $ruri = $_SERVER["REQUEST_URI"];
           } else {
              $ruri = '/console';
           }

           file_put_contents("php://stdout",
               sprintf("[%s] %s:%s [%s]:%s \n",
                   date("D M j H:i:s Y"),
                   $raddr,$rport,
                   $status,
                   $ruri
                   )
               );
    }  // end-of-consolelog

    if($useDB) {

      $capsule = new Capsule;
      
      $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => __DIR__.'/data.db',
        'prefix' => ''
      ],'default');

      // $capsule->addConnection([
      //     'driver'    => 'mysql',
      //     'host'      => '127.0.0.1',
      //     'database'  => 'test',
      //     'username'  => 'root',
      //     'password'  => '',
      //     'charset'   => 'utf8',
      //     'collation' => 'utf8_general_ci',
      //     'prefix'    => '',
      // ],'default');

      //use for multi database  you will check php.ini has enable your database driver  use phpinfo() to see.
      // $capsule->addConnection([
      //     //---sql server port --- 1433 -----
      //     'driver'   => 'sqlsrv',
      //     'host'     => '127.0.0.1', 
      //     'database' => 'test',
      //     'username' => 'sa',
      //     'password' => 'roottoor',
      //     'prefix'   => '',
      //     ], 'nameofref');


      // $capsule->setEventDispatcher(new Dispatcher(new Container));
      $capsule->setAsGlobal();
      $capsule->bootEloquent();
      require_once __DIR__.'/models.php';
    } // end of $usedb
  // Capsule::enableQuerylog();
  // dump(Capsule::getQueryLog());
} // end-of-check funtion exist


// $p = Capsule::table('products')->get();
// dump($p);