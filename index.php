<?php
set_time_limit(10 * 60);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
use Handlebars\Handlebars;

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
    $data = array_map(trim, $data);
    $data = array_diff($data, SKIP_BRANCHES);
    foreach($data as $out) {
      $issue = '';
      if (preg_match(REG_JIRA_ISSUE, $out, $match)) {
        $issue = $match[0];
      }
      $branch = ['name' => $out, 'title' => str_replace('_', ' ', $out), 'issue' => $issue];
      $result[] = $branch;
    }
  }

  return $result;
}

function processStart() {
  $data = ['branches' => [], 'errors' => [], 'jiraUrl' => JIRA_URL];

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
  pclose(popen(LOAD_KEY_CMD, 'r'));

  if (!isset($post['branches'])) {
    return ['errors' => ['no branches']];
  }

  foreach($post['branches'] as $key => $val) {
    $remoteBranch = false;
    if (preg_match('|^origin/|', $key)) {
      $remoteBranch = true;
      $key = str_replace('origin/', '', $key);
    }
    if (!preg_match(REG_VALID_BRANCH, $key)) {
      $result['errors'] = array_merge($result['errors'], ['Incorrect branch name ' . $key]);
      continue;
    }
    if ($val == 'on') {
      if ($remoteBranch) {
        $branches[] = 'origin/' . $key;
        $res = execGit('push origin --delete ' . $key);
      } else {
        $branches[] = $key;
        $res = execGit('branch --delete ' . $key);
      }
      if (isset($res['errors'])) {
        $result['errors'] = array_merge($result['errors'], $res['errors']);
      }
    }
  }

  return ['processed' => formatBranches($branches), 'errors' => $result['errors']];
}


chdir(PROJECT_PATH);

$subTemplate;
$data;

if (empty($_POST)) {
  $subTemplate = 'branches';
  $data = processStart();
} else {
  $subTemplate = 'processed';
  $data = processPOST($_POST);
}

$handlebars = new Handlebars(array(
  'loader' => new \Handlebars\Loader\FilesystemLoader(__DIR__ . '/templates/', ['extension' => 'html']),
  'partials_loader' => new \Handlebars\Loader\FilesystemLoader(
    __DIR__ . '/templates/', [
      'prefix' => '_'
    , 'extension' => 'html'
    ])
));

$handlebars->registerPartial('block', $subTemplate);
$data['raw'] = htmlentities(print_r($data, true));
echo $handlebars->render('main', $data);
