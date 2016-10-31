<?php
use Illuminate\Database\Eloquent\Model;

class Role  extends  Model  { 

        protected  $table='roles';
        protected  $primaryKey='id';
        public $timestamps = true;
        //const CREATED_AT = 'create_date';
        //const UPDATED_AT = 'update_date';
        //protected $guarded = array('id');
        //protected $fillable = [];
        //protected $hidden = [];
        //protected $connection = '';
        //use SoftDeletingTrait;
        //protected $dates = ['deleted_at'];
        //integer, real, float, double, string, boolean, object, array, collection, date and datetime.
        //protected $casts = [
        //     // field => integer
        //     ''       => '',
        //];
        //public static function boot()     {
        //    parent::boot();
        // }
        
}

class Permissions  extends  Model  { 

        protected  $table='permissions';
        protected  $primaryKey='id';
        public $timestamps = true;
        //const CREATED_AT = 'create_date';
        //const UPDATED_AT = 'update_date';
        //protected $guarded = array('id');
        //protected $fillable = [];
        //protected $hidden = [];
        //protected $connection = '';
        //use SoftDeletingTrait;
        //protected $dates = ['deleted_at'];
        //integer, real, float, double, string, boolean, object, array, collection, date and datetime.
        //protected $casts = [
        //     // field => integer
        //     ''       => '',
        //];
        //public static function boot()     {
        //    parent::boot();
        // }
        
}

trait RbacTrait {

}

class Role
{
    protected $permissions;

    protected function __construct() {
        $this->permissions = array();
    }

    // return a role object with associated permissions
    public static function getRolePerms($role_id) {
        $role = new Role();
        $sql = "SELECT t2.perm_desc FROM role_perm as t1
                JOIN permissions as t2 ON t1.perm_id = t2.perm_id
                WHERE t1.role_id = :role_id";
        $sth = $GLOBALS["DB"]->prepare($sql);
        $sth->execute(array(":role_id" => $role_id));

        while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $role->permissions[$row["perm_desc"]] = true;
        }
        return $role;
    }

    // check if a permission is set
    public function hasPerm($permission) {
        return isset($this->permissions[$permission]);
    }
}


/*

REATE TABLE roles (
  role_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  role_name VARCHAR(50) NOT NULL,

  PRIMARY KEY (role_id)
);

CREATE TABLE permissions (
  perm_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  perm_desc VARCHAR(50) NOT NULL,

  PRIMARY KEY (perm_id)
);

CREATE TABLE role_perm (
  role_id INTEGER UNSIGNED NOT NULL,
  perm_id INTEGER UNSIGNED NOT NULL,

  FOREIGN KEY (role_id) REFERENCES roles(role_id),
  FOREIGN KEY (perm_id) REFERENCES permissions(perm_id)
);

CREATE TABLE user_role (
  user_id INTEGER UNSIGNED NOT NULL,
  role_id INTEGER UNSIGNED NOT NULL,

  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

 */