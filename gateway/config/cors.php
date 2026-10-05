<?php
return ['paths'=>['api/*'],'allowed_methods'=>['GET','POST','PUT','DELETE','OPTIONS'],'allowed_origins'=>[env('FRONTEND_URL','http://127.0.0.1:8080')],'allowed_headers'=>['Content-Type','Accept','Authorization'],'exposed_headers'=>[],'max_age'=>0,'supports_credentials'=>false];
