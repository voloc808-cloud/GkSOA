<?php
// One-time transfer. Never resets or deletes an existing database.
set_exception_handler(function(Throwable $e) {
    fwrite(STDERR, "Transfer failed. Source SQLite and existing .env are preserved.\n");
    if ($e instanceof PDOException) fwrite(STDERR, 'MySQL/PDO error code: '.($e->errorInfo[1] ?? $e->getCode())."\n");
    elseif ($e instanceof RuntimeException) fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
});
$database=$argv[1] ?? 'quanly_doan';
if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/',$database)) throw new RuntimeException('Use a simple MySQL database name: letters, digits, underscores.');
foreach ([8002,8003,8004] as $port) {
    $socket=@fsockopen('127.0.0.1',$port,$errorNumber,$errorMessage,0.2);
    if ($socket) { fclose($socket); throw new RuntimeException("Stop the running project before transfer (port $port is active)."); }
}
$root=dirname(__DIR__);
$envPath=$root.'/backend/.env';
$originalEnv=file_get_contents($envPath);
$app=require $root.'/backend/bootstrap/app.php';
if ($app['config']->get('database.default')!=='sqlite') throw new RuntimeException('Transfer expects the current backend connection to be SQLite. Nothing was changed.');
$sourcePath=$app['config']->get('database.connections.sqlite.database');
if (!is_string($sourcePath) || !is_file($sourcePath)) throw new RuntimeException('Configured SQLite database file does not exist.');
$config=$app['config']->get('database.connections.mysql');
$mysql=new PDO('mysql:host='.$config['host'].';port='.$config['port'].';charset=utf8mb4',$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$exists=$mysql->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?');
$exists->execute([$database]);
$tables=['SINHVIEN','users','DETAI','DANGKY'];
$allowed=array_map('strtolower',array_merge($tables,['migrations','access_tokens']));
foreach ($exists->fetchAll(PDO::FETCH_COLUMN) as $table) {
    if (!in_array(strtolower($table),$allowed,true)) throw new RuntimeException('Target contains unrelated tables. Use a separate empty database.');
    $count=(int)$mysql->query('SELECT COUNT(*) FROM `'.$database.'`.`'.str_replace('`','``',$table).'`')->fetchColumn();
    if (strtolower($table)!=='migrations' && $count>0) throw new RuntimeException('Target database already contains data. Transfer stopped; no data was overwritten.');
}
$source=new PDO('sqlite:'.$sourcePath,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$stamp=date('Ymd_His').'_'.bin2hex(random_bytes(3));
$backup=$root.'/backend/database/backup_before_mysql_'.$stamp.'.sqlite';
$source->exec('VACUUM INTO '.$source->quote($backup));
$rows=[];
$source->beginTransaction();
try {
    foreach ($tables as $table) $rows[$table]=$source->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $source->commit();
} catch(Throwable $e) { $source->rollBack(); throw $e; }
$mysql->exec('CREATE DATABASE IF NOT EXISTS `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$app['config']->set('database.default','mysql');
$app['config']->set('database.connections.mysql.database',$database);
$app['db']->setDefaultConnection('mysql');
$status=$app->make(Illuminate\Contracts\Console\Kernel::class)->call('migrate',['--force'=>true]);
if ($status!==0) throw new RuntimeException('Migration failed. SQLite remains unchanged. An empty or partially migrated target may be retried.');
$conn=$app['db']->connection('mysql');
$conn->transaction(function() use($conn,$rows,$tables) {
    foreach ($tables as $table) {
        if ($conn->table($table)->count()!==0) throw new RuntimeException('Target changed during transfer. Import rolled back.');
        foreach ($rows[$table] as $row) $conn->table($table)->insert($row);
    }
    foreach ($tables as $table) {
        $actual=$conn->table($table)->orderBy('id')->get()->map(fn($row)=>(array)$row)->all();
        $normalize=function(array $data) {
            return array_map(function($row){ksort($row);return array_map(fn($value)=>$value===null?null:(string)$value,$row);},$data);
        };
        if ($normalize($actual)!==$normalize($rows[$table])) throw new RuntimeException('Verification failed for '.$table.'. Import rolled back.');
    }
});
if (file_get_contents($envPath)!==$originalEnv) throw new RuntimeException('Data copied, but .env changed during transfer. Configuration was not overwritten; review it manually.');
$envBackup=$root.'/backend/.env.before-mysql-'.$stamp;
if (file_put_contents($envBackup,$originalEnv)===false) throw new RuntimeException('Data copied, but could not back up .env. Configuration remains unchanged.');
$newEnv=$originalEnv;
foreach (['DB_CONNECTION'=>'mysql','DB_DATABASE'=>$database] as $key=>$value) {
    if (preg_match('/^'.preg_quote($key,'/').'=/m',$newEnv)) $newEnv=preg_replace('/^'.preg_quote($key,'/').'=.*$/m',$key.'='.$value,$newEnv);
    else $newEnv.="\n".$key.'='.$value."\n";
}
$tmp=$envPath.'.transfer-'.$stamp;
if (file_put_contents($tmp,$newEnv)===false || !rename($tmp,$envPath)) throw new RuntimeException('Data copied, but could not update .env. Set DB_CONNECTION=mysql and DB_DATABASE='.$database.' manually.');
echo "Transfer verified. MySQL database: $database\n";
foreach ($tables as $table) echo $table.': '.count($rows[$table])." rows\n";
echo 'SQLite backup: '.$backup."\n";
echo "Account passwords are preserved. Login again: old sessions were not transferred.\n";
echo "Start the project with ./scripts/start.ps1 (without -SQLite).\n";

