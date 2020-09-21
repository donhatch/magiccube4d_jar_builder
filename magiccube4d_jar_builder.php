<?php

// Name?
//      magiccube4d.2020-08-16T12:13:51-07:00.146f56a16f44dbcb25f74773412eccd1359b9f1b.jar
// date from:
//   git show --no-patch --no-notes --pretty='%cI' ab2dbed99ae1a513e3b341b32209eab1ea1d2bc3
// Repo made via:
//   git clone https://github.com/cutelyaware/magiccube4d.git repo

function exec_or_die($command) {
  print('  executing command '.htmlspecialchars($command).'<br>');
  ob_flush();
  exec($command, $output, $exitcode);
  $output = implode("\n", $output);
  print('  output="'.htmlspecialchars($output).'"<br>');
  print('  exitcode="'.htmlspecialchars($exitcode).'"<br>');
  if ($exitcode != 0) {
    print("ERROR: exitcode $exitcode not as expected<br>");
    exit(0);
  }
  return $output;
}

$commit = trim($_GET["commit"]);
// TODO: validate!  commit should be 40 hex digits
if ($commit != '' && !preg_match('/^[0-9a-f]{40}$/i', $commit)) {
  print('<html><body>ERROR: "'.htmlspecialchars($commit).'" does not look like a full commit hash</body></html>');
  print("<hr>");
  exit(0);
}
// from now on, we don't need to escape $commit

print('<html>');
print('<body>');

$list = array();
if ($handle = opendir('./cache')) {
    while (false !== ($entry = readdir($handle))) {
      if ($entry != "." && $entry != "..") {
        if (!preg_match('/^magiccube4d\\..*\\.jar$/', $entry)) {
          // It doesn't look like a prebuilt jar file.
          continue;
        }
        array_push($list, $entry);
      }
    }
    closedir($handle);
}
sort($list);

if ($commit != '') {
  // Is commit in the list already?
  $found = false;
  $witness = '';
  foreach ($list as $item) {
    if (strpos($item, '.'.$commit.'.') !== false) {
      $found = true;
      $witness = $item;
      break;
    }
  }
  if ($found) {
    print('Commit '.$commit.' seems to be built already:<br><a href="cache/$witness">'.htmlspecialchars($witness).'</a><br>');
  } else {
    print('Commit '.$commit.' doesn\'t seem to be built already; building...<br>');
    print('<hr>');
    ob_flush();

    if (true)
    {
      /*
      if (cache/repo doesnt exist) {
        $command = 'git clone https://github.com/cutelyaware/magiccube4d.git cache/repo';
        print('  executing command '.htmlspecialchars($command).'<br>');
        ob_flush();
        exec($command, $output, $exitcode);
        $output = implode("\n", $output);
        print('  output="'.htmlspecialchars($output).'"<br>');
        print('  exitcode="'.htmlspecialchars($exitcode).'"<br>');
        if ($exitcode != 0) {
          print("ERROR: exitcode $exitcode not as expected<br>");
          exit(0);
        }
      } else {
      }
      */

      $command = '(cd repo && git checkout --quiet master && git pull --quiet --ff-only && git show --quiet --no-patch --no-notes --pretty="%cI" '.$commit.') 2>&1';
      print('  executing command '.htmlspecialchars($command).'<br>');
      ob_flush();
      exec($command, $output, $exitcode);
      $output = implode("\n", $output);
      print('  output="'.htmlspecialchars($output).'"<br>');
      print('  exitcode="'.htmlspecialchars($exitcode).'"<br>');
      if ($exitcode != 0) {
        print("ERROR: exitcode $exitcode not as expected<br>");
        exit(0);
      }

      ob_flush();
      if (!preg_match('/^\\d\\d\\d\\d-\\d\\d-\\d\\dT\\d\\d:\\d\\d:\\d\\d-\\d\\d:\\d\\d$/', $output)) {
        print('ERROR: output "'.htmlspecialchars($output).'" does not look as expected!<br>');
        exit(0);
      }
      // I don't like that final ':' in "2020-09-19T01:23:04-07:00";
      // change it to "2020-09-19T01:23:04-0700" instead
      $timestamp = $output;
      $timestamp = preg_replace('/:(\\d\\d)$/', '\\1', $timestamp);
      print('  timestamp="'.htmlspecialchars($timestamp).'"<br>');

      $filename = 'magiccube4d.'.$timestamp.'.'.$commit.'.jar';
      print('  filename="'.htmlspecialchars($filename).'"<br>');

      if (true) {
        $command = '(cd repo && git checkout --quiet '.$commit.') 2>&1';
        print('  executing command '.htmlspecialchars($command).'<br>');
        ob_flush();
        exec($command, $output, $exitcode);
        $output = implode("\n", $output);
        print('  output="'.htmlspecialchars($output).'"<br>');
        print('  exitcode="'.htmlspecialchars($exitcode).'"<br>');
        if ($exitcode != 0) {
          print("ERROR: exitcode $exitcode not as expected<br>");
          exit(0);
        }
        if ($output !== '') {
          print('ERROR: output "'.htmlspecialchars($output).'" does not look as expected!<br>');
          exit(0);
        }
      }

      if (true) {
        $command = '(cd repo/src && rm -f */*/*/*.class && javac -source 1.6 -target 1.6 -Xlint:-options */*/*/*.java 2>&1 | (grep -v deprecat || true)) 2>&1';
        print('  executing command '.htmlspecialchars($command).'<br>');
        ob_flush();
        exec($command, $output, $exitcode);
        $output = implode("\n", $output);
        print('  output="'.htmlspecialchars($output).'"<br>');
        print('  exitcode="'.htmlspecialchars($exitcode).'"<br>');
        if ($exitcode != 0) {
          print("ERROR: exitcode $exitcode not as expected<br>");
          exit(0);
        }
      }

      if (true) {
        // TODO: dependency on mc4d-4-3-216.jar
        $command = '(rm -rf scratch && mkdir scratch && cd scratch && jar xvf ../mc4d-4-3-216.jar 2>&1 | (egrep -v " created: | inflated: " || true) && rm -rf com && cp -a ../repo/src/com ./ && sed "s/Class-Path: ./Created-By: (Don Hatch, from '.$commit.')/" < META-INF/MANIFEST.MF > META-INF/MANIFEST.MF.TEMP && mv META-INF/MANIFEST.MF.TEMP META-INF/MANIFEST.MF && jar -cfm ../cache/'.$filename.' META-INF/MANIFEST.MF .) 2>&1';
        print('  executing command '.htmlspecialchars($command).'<br>');
        ob_flush();
        exec($command, $output, $exitcode);
        $output = implode("\n", $output);
        print('  output="'.htmlspecialchars($output).'"<br>');
        print('  exitcode="'.htmlspecialchars($exitcode).'"<br>');
        if ($exitcode != 0) {
          print("ERROR: exitcode $exitcode not as expected<br>");
          exit(0);
        }
      }

  /*
  #  The individual commands were something like this...
  /bin/rm -rf scratch
  mkdir scratch
  cd scratch
  jar xvf ../mc4d-4-3-216.jar
  /bin/rm -rf com
  cp -a ../com ./
  sed "s/Class-Path: ./Created-By: (Don Hatch, $(LANG=C date), from $COMMIT/" < META-INF/MANIFEST.MF > META-INF/MANIFEST.MF.TEMP
  mv META-INF/MANIFEST.MF.TEMP META-INF/MANIFEST.MF

  jar -cfm "../${OUT?}" META-INF/MANIFEST.MF .
  */

    }

    print('Done.<br>');
    print('Hopefully that built: <a href="cache/'.$filename.'">'.$filename."</a><br>");
  }
}  // $commit != ''

print('<form method="get">');
print("<hr>");
print('Build at this commit: <input type="text" name="commit" size="50"><br>');
print('(see <a href="https://github.com/cutelyaware/magiccube4d/commits">here<a> for commit history)<br>');
print("<hr>");
print("Previously built:");
print("<br>");

foreach ($list as $item) {
    print('  <a href="cache/'.htmlspecialchars($item).'">'.htmlspecialchars($item).'<a>');
    print('  <br>');
}

$exists = false; // XXX
if (!$exists) {
}

print('</form>');

print('</body>');
print('</html>');
?>
