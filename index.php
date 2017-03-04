<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
use Handlebars\Handlebars;

$handlebars = new Handlebars(array(
  'loader' => new \Handlebars\Loader\FilesystemLoader(__DIR__ . '/templates/', ['extension' => 'html']),
  'partials_loader' => new \Handlebars\Loader\FilesystemLoader(
    __DIR__ . '/templates/', [
      'prefix' => '_'
    , 'extension' => 'html'
    ])
));

const GIT_CMD = '"c:\\Program Files\\Git\\bin\\git.exe"';
const PROJECT_PATH = 'c:\\www\\jobvector\\';
const SKIP_BRANCHES = ['origin/production', 'origin/master'];

//get local merged branches
chdir(PROJECT_PATH);

function execGit($command) {
  $command = GIT_CMD . ' ' . trim($command) . ' 2>&1';
  exec($command, $output, $return);

  $data = [];
  if (!$return) { // 0 - all ok
    $data['data'] = $output;
  } else {
//    throw new ErrorException('ERROR: ' . htmlentities($command) . " " . implode(" ", $output));
    $output = array_map(htmlentities, $output);
    $data['errors'][] = 'ERROR: ' . htmlentities($command) . "<br />" . implode("<br />", $output);
  }

  return $data;
}

function formatBranches(array $data) {
  $result = [];
  if(!empty($data)) {
    foreach($data as $out) {
      $out = trim($out);
      $branch = ['name' => $out, 'title' => str_replace('_', ' ', $out)];
      $result[] = $branch;
    }
  }

  return $result;
}

function processStart() {
  $data = ['branches' => [], 'errors' => []];

  $localBranches = execGit('branch --merged origin/master');
  if (!isset($localBranches['errors'])) {
    $data['branches'] += formatBranches($localBranches['data']);
  } else {
    $data['errors'] += $localBranches['errors'];
  }
  $remoteBranches = execGit('branch --remote --merged origin/master');
  if (!isset($remoteBranches['errors'])) {
    $data['branches'] += formatBranches($remoteBranches['data']);
  } else {
    $data['errors'] += $remoteBranches['errors'];
  }

  return $data;
}

function processPOST($post) {
  $branches = [];
  $result = ['errors' => []];

  foreach($post as $key => $val) {
    if ($val == 'on') {
      $branches[] = $key;
      $res = execGit('branch --delete ' . $key);
      if (isset($res['errors'])) {
        $result['errors'] = array_merge($result['errors'], $res['errors']);
      }
    }
  }

  return ['processed' => formatBranches($branches), 'errors' => $result['errors']];
}

$subTemplate;
$data;

if (empty($_POST)) {
  $subTemplate = 'branches';
  $data = processStart();
} else {
  $subTemplate = 'processed';
  $data = processPOST($_POST);
}

$handlebars->registerPartial('block', $subTemplate);
$data['raw'] = htmlentities(print_r($data, true));
echo $handlebars->render('main', $data);
