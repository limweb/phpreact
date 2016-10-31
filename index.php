<?php
require_once __DIR__.'/libs/RestfulServer.php';
use Illuminate\Database\Capsule\Manager as Capsule;

class  IdxService extends RestfulServer {

	protected $route = '/';
	public function __construct() {
			// $this->isroot = true;			
			// $this->hasroot = true;			
			// $this->usedb = true;
		parent::__construct();
	}

	public function index(){
		echo '<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="utf-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title>PHP CRUD with ReactJS</title>
			<!-- Bootstrap CSS -->
			<link href="/css/bootstrap.min.css" rel="stylesheet" media="screen">
			<!-- HTML5 Shiv and Respond.js IE8 support of HTML5 elements and media queries -->
			<!-- WARNING: Respond.js doesn\'t work if you view the page via file:// -->
			<!--[if lt IE 9]>
			<script src="/js/html5shiv.js"></script>
			<script src="/js/respond.min.js"></script>
			<![endif]-->

			<style>
				.text-align-center{
					text-align:center;
				}

				.margin-zero{
					margin:0;
				}

				.overflow-hidden{
					overflow:hidden;
				}

				.margin-bottom-1em{
					margin-bottom:1em;
				}

				.m-r-1em{
					margin-right:1em;
				}
			</style>

		</head>
		<body>
			<!-- container -->
			<div class="container">

				<div class="page-header">
					<h1>Loading...</h1>
				</div>
				<div id="content"></div>
			</div>
			<!-- /container -->

			<!-- react js -->
			<script src="/js/react.js"></script>
			<script src="/js/react-dom.js"></script>
			<script src="/js/babel.js"></script>

			<!-- main react components -->
			<script type="text/babel" src="js/react/main.js"></script>

			<!-- jQuery library -->
			<script src="/js/jquery.js"></script>

			<!-- bootstrap JavaScript -->
			<script src="/js/bootstrap.min.js"></script>
		</body>
		</html>
		';
	}



	 	public function  getAllproducts() {
	 		try {
		 		// dummy-------------- start ---
		 		// $products = [];
		 		// for($i=1;$i<=10;$i++) {
		 		// 	$p = new stdClass();
		 		// 	$p->id = $i;
		 		// 	$p->name = 'product'.$i;
		 		// 	$p->description = 'prodescription'.$i;
		 		// 	$p->price = $i*10;
		 		// 	$p->category_name = 'category_name'.$i;
		 		// 	$products[]  = $p;
		 		// }	
		 		// dummy-------------- start --- end;
		 		// ----- has DB only ----------------
		 				// $products = Capsule::table('products')->get();
		 				$products = Capsule::select('SELECT categories.`name` AS category_name, products.id, products.`name`, products.description, products.price FROM products INNER JOIN categories ON categories.id = products.category_id
');
		 		// ----- has DB only ----------------
		 		$o = new stdClass();
		 		$o->products = $products;
	 			$this->response($o,'json');
	 		} catch (Exception $e) {
	 			$this->rest_error(-1,$e->getMessage(),'json',0); //or
	 		}
	 		



	 	}	
	 	public function  getAllcats() {
	 		try {
	 			//---- dummy--------------
	 			// $cats = [];
	 			// for($i=1;$i<= 10;$i++) {
	 			// 	$c = new stdClass();
	 			// 	$c->id = $i; 
	 			// 	$c->name = 'catname'.$i;	
	 			// 	$cats[] = $c;
	 			// }
	 			//---- dummy--------------
	 			//-----db ----------------
	 				$cats = Capsule::table('categories')->get();
	 			//-----db ----------------
	 			$o = new stdClass();
	 			$o->cats = $cats;
	 			$this->response($o,'json');
	 		} catch (Exception $e) {
	 				$this->rest_error(-1,$e->getMessage(),'json',0); //or
	 			}
	 		}

	 		//getproduct by id
	 		public function postProductbyone() {
	 			try {
	 				$i = $this->posts['prod_id'];
	 				$o = new stdClass();
	 				//---------dummy----------------
		 				// $product = new stdClass();
		 				// $product->id = $i;
		 				// $product->name = 'product'.$i;
		 				// $product->description = 'prodescription'.$i;
		 				// $product->price = $i*10;
		 				// $product->category_name = 'category_name'.$i;
	 				//---------dummy----------------
	 				//----- db -----------------
	 					$product = Capsule::select('SELECT categories.`name` AS category_name, products.id, products.`name`, products.description, products.price,category_id FROM products INNER JOIN categories ON categories.id = products.category_id
							where products.id =  ? ',[$i])[0];
	 				//----- db -----------------

	 				$o->product = $product;
	 				$o->id =$i;
	 				$this->response($o,'json');
	 			} catch (Exception $e) {
	 	 			$this->rest_error(-1,$e->getMessage(),'json',0); //or
	 	 		}
	 	 		
	 	 	}

	 	 	//update
	 	 	public function postUpdateproduct(){
	 	 		try {
	 	 			$o = new stdClass();
	 	 			$id = $this->posts['id'];
	 	 			$rs = Capsule::table('products')->where('id',$id)->update($this->posts);
	 	 			$o->input = $this->input;
	 	 			$o->post = $this->posts;
	 	 			$o->rs = $rs;
	 	 			$o->id = $id;
	 	 			$this->response($o,'json');
	 	 		} catch (Exception $e) {
	 	 			$this->rest_error(-1,$e->getMessage(),'json',0); //or
	 	 		}
	 	 	}

	 	 	//delete
	 	 	public function postDeleteproducts(){
	 	 		try {
	 	 			$o = new stdClass();
	 	 			$o->input = $this->input;
	 	 			$o->post = $this->posts;
	 	 			$id = $this->posts['del_ids'][0];
	 	 			$rs = Capsule::table('products')->where('id',$id)->delete();
	 	 			$o->rs = $rs;
	 	 			$o->id = $id;
	 	 			$this->response($o,'json');
	 	 		} catch (Exception $e) {
	 	 			$this->rest_error(-1,$e->getMessage(),'json',0); //or
	 	 		}
	 	 	}

	 	 	public function  postProduct(){
	 	 		try {
	 	 			$o = new stdClass();
	 	 			$rs = Capsule::table('products')->insertGetId($this->posts);
	 	 			$o->data = $rs;
	 	 			$o->post = $this->posts;
	 	 			$o->input = $this->input;
	 	 			$this->response($o,'json');
	 	 		} catch (Exception $e) {
	 	 			$this->rest_error(-1,$e->getMessage(),'json',0); //or
	 	 		}
	 	 		
	 	 	}

	 	 	public function model(){
	 	 		return null;
	 	 	}
	 	 }

	 	 $idxservice = new IdxService();
	 	 $idxservice->run();
