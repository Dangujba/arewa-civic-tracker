<?php
return [
 'paths'=>['api/*'],
 'allowed_methods'=>['GET','POST','OPTIONS'],
 'allowed_origins'=>['http://localhost','https://localhost','capacitor://localhost'],
 'allowed_origins_patterns'=>[],
 'allowed_headers'=>['Accept','Authorization','Content-Type'],
 'exposed_headers'=>[],
 'max_age'=>3600,
 'supports_credentials'=>false,
];
