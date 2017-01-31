<?php

define('BASEPATH', true);
include 'application/config/pacifica.php';

function tempdir($dir=false,$prefix='php') {
    $tempfile=tempnam(sys_get_temp_dir(),'');
    if (file_exists($tempfile)) { unlink($tempfile); }
    mkdir($tempfile);
    if (is_dir($tempfile)) { return $tempfile; }
}

function delTree($dir) { 
   $files = array_diff(scandir($dir), array('.','..')); 
   foreach ($files as $file) { 
     (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
   } 
   return rmdir($dir); 
}

$my_tar_dir = 'pacifica-upload-status-'.$config['application_version'];
$my_tar_name = $my_tar_dir.'.tar.gz';
$my_wrk_dir = getcwd();
$dest_tar_path = join('/', array($my_wrk_dir, $my_tar_name));
$my_tmp_dir = tempdir();
$my_src_dir = join('/', array($my_tmp_dir, $my_tar_dir));

mkdir($my_src_dir);
system('git archive HEAD | tar -C '.$my_src_dir.' -xf -');
chdir('websystem');
system('git archive HEAD | tar -C '.$my_src_dir.' --exclude=application -xf -');
chdir('..');
system('tar -C '.dirname($my_src_dir).' -czf '.$dest_tar_path.' '.$my_tar_dir);
delTree($my_tmp_dir);
