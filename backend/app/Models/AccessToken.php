<?php
namespace App\Models;
class AccessToken extends \Illuminate\Database\Eloquent\Model {
 protected $fillable=['user_id','token_hash','expires_at'];
 protected $hidden=['token_hash'];
 protected $casts=['expires_at'=>'datetime'];
}
