<?php
include "job.php";

$data = Job\Http::getData('tyz910', false, false);
$runner = new Job\TaskRunner($data['task']);
$out = $runner->run();

$msg = '';
foreach ($out as $char) {
    $msg .= $char;
}

if (preg_match('/base64_decode\("(?P<hash>[^"]+)"\)/', $msg, $matches)) {
    $hash = $matches['hash'];
    $data = json_decode(file_get_contents(base64_decode($hash)), true);

    print_r($data);
} else {
    echo $msg;
}