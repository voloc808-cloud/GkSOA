<?php
namespace App\Models;
class User extends \Illuminate\Database\Eloquent\Model {
 protected $fillable=['username','name','password','role','sinh_vien_id'];
 protected $hidden=['password'];
 protected $casts=['sinh_vien_id'=>'integer'];
}
