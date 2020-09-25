<?php

// TODO: colors! and maybe nicer graph
// TODO: truncate long first lines of descriptions?

$javac = '/usr/lib/jvm/java-11-openjdk-amd64/bin/javac';

// Example jar name (note: colons in the name would mess up ability to be executable):
//      magiccube4d.2020-08-16T121351-0700.146f56a16f44dbcb25f74773412eccd1359b9f1b.jar
// date from:
//   git show --no-patch --no-notes --pretty='%cI' ab2dbed99ae1a513e3b341b32209eab1ea1d2bc3
// Repo made via:
//   git clone https://github.com/cutelyaware/magiccube4d.git repo

function CHECK($cond) {
  if (!$cond) {
    print("ERROR: CHECK failed");
    exit(1);
  }
}

function exec_or_die($command) {
  print('  executing command "'.htmlspecialchars($command).'"<br>');
  ob_flush();
  flush();
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

function find_unique_prefix_length($sorted_commits) {
  $n = count($sorted_commits);
  for ($length = 0; $length < $n; $length++) {
    $good_so_far = true;
    for ($i = 1; $i < $n; ++$i) {
      if (substr($sorted_commits[$i-1], 0, $length) == substr($sorted_commits[$i], 0, $length)) {
        //print("$length is bad because i=$i  ".$sorted_commits[$i-1]." ".$sorted_commits[$i]."");
        $good_so_far = false;
        break;
      }
    }
    if ($good_so_far) {
      return $length;
    }
  }
  CHECK(false);
}  // find_unique_prefix_length



$commit = trim($_GET["commit"]);
if ($commit != '' && !preg_match('/^[0-9a-f]{40}$/i', $commit)) {
  print('<html lang="en" class="notranslate" translate="no"><body>ERROR: "'.htmlspecialchars($commit).'" does not look like a full commit hash</body></html>');
  exit(0);
}
// from now on, we don't need to escape $commit

print('<html>');
print('<head>');
print('<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>');
//print('<script src="jquery-3.5.1.min.js"></script>');
print('</head>');
print('<body>');

// Start by making sure the cache dir exists, and taking an advisory lock on it.
exec('mkdir -p cache');
exec('touch cache/lock');
$lock = fopen('./cache/lock', 'r');
if (!flock($lock, LOCK_EX|LOCK_NB)) {
  print("<small>");
  print("Waiting for lock... ");
  ob_flush();
  flush();
  if (!flock($lock, LOCK_EX)) {
    print("Failed to acquire lock. !?");
    exit(0);
  }
  print("acquired lock.<br>");
  print("</small>");
  ob_flush();
  flush();
}

if (array_key_exists('clear', $_GET)) {
    //print('  executing command "'.htmlspecialchars($command).'"<br>');
    exec('/bin/rm -f cache/magiccube4d*.jar', $output, $exitcode);
    //print('  output="'.htmlspecialchars($output).'"<br>');
    //print('  exitcode="'.htmlspecialchars($exitcode).'"<br>');
    print('Cleared!<br>');
}

$list = [];
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
rsort($list);

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
    print('Commit '.$commit.' seems to be built already:<br><a href="cache/'.$witness.'">'.htmlspecialchars($witness).'</a><br>');
  } else {
    print('Commit '.$commit.' doesn\'t seem to be built already; building...');
    print('<hr>');
    print('<button id="togglebuildtranscript" type="submit" name="clear" onclick="if ($(\'#togglebuildtranscript\').html() === \'Show build transcript\') {$(\'#buildtranscript\').show(250); $(\'#togglebuildtranscript\').html(\'Hide build transcript\');} else {$(\'#buildtranscript\').hide(250); $(\'#togglebuildtranscript\').html(\'Show build transcript\');}" style="display:none">Show build transcript</button>');
    print('<div id="buildtranscript">');
    ob_flush();
    flush();

    if (true)
    {
      if (!file_exists('cache/mc4d-4-3-216.jar')) {
        $url = 'https://github.com/cutelyaware/magiccube4d/releases/download/v4.3.216/mc4d-4-3-216.jar';
        print("fetching $url ...<br>");
        ob_flush();
        flush();
        if (true) {
          $command = "mkdir -p cache && curl -L $url > cache/mc4d-4-3-216.jar";
          exec_or_die($command);
        } else if (false) {
          // DOES NOT WORK
          $fh = fopen($url, 'r') or die($php_errmsg);
          $contents = '';
          while (! feof($fh)) {
            $contents .= fread($fh, 1048576);
          }
          fclose($fh);
          exec_or_die('touch cache/mc4d-4-3-216.jar');
        } else {
          // DOES NOT WORK
          $contents = file_get_contents($url);
        }
        print("contents = \"".htmlspecialchars($contents).'"<br>');
      }
      if (!file_exists('cache/repo')) {
        // Note that this `git clone` will create the cache directory if it doesn't exist too
        $command = 'GIT_TERMINAL_PROMPT=0 git clone --quiet https://github.com/cutelyaware/magiccube4d.git cache/repo 2>&1';
        $output = exec_or_die($command);
        if ($output !== '') {
          print('ERROR: output "'.htmlspecialchars($output).'" does not look as expected!<br>');
          exit(0);
        }
      } else {
        $command = '(cd cache/repo && GIT_TERMINAL_PROMPT=0 git checkout --quiet master && git pull --quiet --all --ff-only)';
        $output = exec_or_die($command);
        if ($output !== '') {
          print('ERROR: output "'.htmlspecialchars($output).'" does not look as expected!<br>');
          exit(0);
        }
      }

      $command = '(cd cache/repo && git show --quiet --no-patch --no-notes --pretty="%cI" '.$commit.') 2>&1';
      $output = exec_or_die($command);
      if (!preg_match('/^\\d\\d\\d\\d-\\d\\d-\\d\\dT\\d\\d:\\d\\d:\\d\\d-\\d\\d:\\d\\d$/', $output)) {
        print('ERROR: output "'.htmlspecialchars($output).'" does not look as expected!<br>');
        exit(0);
      }
      // I don't like that final ':' in "2020-09-19T01:23:04-07:00";
      // change it to "2020-09-19T01:23:04-0700" instead.
      // And actually, jar files apparently don't like ':'s in them
      // (it seems to ruin the executability), so remove them all.
      $timestamp = $output;
      $timestamp = preg_replace('/:/', '', $timestamp);
      print('  timestamp="'.htmlspecialchars($timestamp).'"<br>');

      $filename = 'magiccube4d.'.$timestamp.'.'.$commit.'.jar';
      print('  filename="'.htmlspecialchars($filename).'"<br>');

      if (true) {
        $command = '(cd cache/repo && git checkout --quiet '.$commit.') 2>&1';
        $output = exec_or_die($command);
        if ($output !== '') {
          print('ERROR: output "'.htmlspecialchars($output).'" does not look as expected!<br>');
          exit(0);
        }
      }

      if (true) {
        $command = '(cd cache/repo/src && rm -f */*/*/*.class && '.$javac.' -source 1.6 -target 1.6 -Xlint:-options */*/*/*.java 2>&1 | (egrep -v "deprecat|unchecked" || true)) 2>&1';
        $output = exec_or_die($command);
      }

      if (true) {
        $command = '(/bin/rm -rf cache/scratch && mkdir cache/scratch && cd cache/scratch && jar -xf ../mc4d-4-3-216.jar && /bin/rm -rf com && cp -a ../../cache/repo/src/com ./ && sed "s/Class-Path: ./Created-By: (Don Hatch, from '.$commit.')/" < META-INF/MANIFEST.MF > META-INF/MANIFEST.MF.TEMP && /bin/mv META-INF/MANIFEST.MF.TEMP META-INF/MANIFEST.MF && jar -cfm ../../cache/'.$filename.' META-INF/MANIFEST.MF .) 2>&1';
        $output = exec_or_die($command);
      }

  /*
  #  The individual commands were something like this...
  /bin/rm -rf scratch
  mkdir scratch
  cd scratch
  jar -xvf ../mc4d-4-3-216.jar
  /bin/rm -rf com
  cp -a ../com ./
  sed "s/Class-Path: ./Created-By: (Don Hatch, $(LANG=C date), from $COMMIT/" < META-INF/MANIFEST.MF > META-INF/MANIFEST.MF.TEMP
  mv META-INF/MANIFEST.MF.TEMP META-INF/MANIFEST.MF

  jar -cfm "../${OUT?}" META-INF/MANIFEST.MF .
  */

    }

    print('</div>');

    print('<hr>');
    print('Done.<br>');
    print('Hopefully that built: <a href="cache/'.$filename.'">'.$filename."</a><br>");

    print("<script>");

    print('  $("#buildtranscript").hide(250, function() {$("#togglebuildtranscript").show();})'."\n");
    print('  $("#togglebuildtranscript").show()'."\n");

    print("</script>\n");
  }
}  // $commit != ''

if (false) {
  print('<form method="get">');
  print("<hr>");
  print('Build at this commit: <input type="text" name="commit" size="50">');
  print('<br>');
  print('(see <a href="https://github.com/cutelyaware/magiccube4d/commits">https://github.com/cutelyaware/magiccube4d/commits<a> for commit history)<br>');
  print("<hr>");

// weird-- If I end the form in any reasonable place, it adds an obnoxious blank line somewhere

  if (count($list) == 0) {
    print("Nothing previously built.");
  } else {
    print("Previously built:<br>");
    foreach ($list as $item) {
        print('  <a href="cache/'.htmlspecialchars($item).'">'.htmlspecialchars($item).'<a>');
        print('  <br>');
    }
    print('</form>');
    print('<form>');
    print('<button type="submit" name="clear">Clear</button><br>');
  }
  print('</form>');
}

if (true) {

  // Make a mapping from commit to previously (or just-now) built.
  $commit2filename = [];
  if (preg_match('/^[0-9a-f]{40}$/', $commit)) {
    $commit2filename[$commit] = $filename;  // CBB: not sure if this fills it in when "seems to be built already", should be more principled
  }
  foreach ($list as $filename) {
    $commitOfFilename = preg_replace('/^.*([0-9a-f]{40}).*$/', '\1', $filename);
    if (preg_match('/^[0-9a-f]{40}$/', $commitOfFilename)) {
      $commit2filename[$commitOfFilename] = $filename;
    }
  }
  //var_dump($commit2filename);


  print('<hr>');
  print('<form>');
  // TODO: --color=always, and convert to html colors
  //$command = '(cd cache/repo && git log --graph --all --pretty=oneline) 2>&1';
  $command = '(cd cache/repo && git log --graph --all --pretty=format:"%H -%d %s (%cr) <%an>") 2>&1';
  //$command = '(cd cache/repo && git log --graph --all --pretty=format:"%H (%cr) <%an> -%d %s") 2>&1';
  exec($command, $output, $exitcode);
  if ($exitcode != 0) {
    print("ERROR: exitcode $exitcode not as expected from command ".htmlspecialchars($command)."<br>");
    exit(0);
  }

  $line2commit = [];
  $commits = [];
  foreach ($output as $line) {
    $commit = preg_replace('/^[^0-9a-f]*([0-9a-f]{40}).*$/', '\1', $line);
    if (preg_match('/^[0-9a-f]{40}$/', $commit)) {
      array_push($commits, $commit);
      array_push($line2commit, $commit);
    } else {
      array_push($line2commit, NULL);
    }
  }
  CHECK(count($line2commit) == count($output));
  sort($commits);
  $prefix_len = find_unique_prefix_length($commits);

  print('(See <a href="https://github.com/cutelyaware/magiccube4d/commits">https://github.com/cutelyaware/magiccube4d/commits<a> for a more detailed commit list.)<br>'."\n");
  print('<pre>');
  print('<table cellspacing="0" cellpadding="0">'."\n");
  $nlines = count($output);
  for ($i = 0; $i < $nlines; ++$i) {
    $line = $output[$i];
    $commit = $line2commit[$i];

    $escaped_line = htmlspecialchars($line);

    if ($commit != NULL) {
      $commit_prefix = substr($commit, 0, $prefix_len);
      $escaped_line = preg_replace("/$commit/", '<a href="https://github.com/cutelyaware/magiccube4d/commit/'.$commit.'">'.$commit_prefix.'</a>', $escaped_line);
    }

    print("<tr>");
    if ($commit != NULL) {
      //print('commit="'.$commit.'"');
      if (array_key_exists($commit, $commit2filename)) {
        print('<td style="text-align:center">');
        print('<span style="font-size: 10px"><a href="cache/'.htmlspecialchars($commit2filename[$commit]).'">Download</a></span>');
        print("<td>");
        print('&nbsp;'.$escaped_line);
      } else {
        print('<td style="text-align:center">');
        //print('<button type="submit" name="commit" value="'.$commit.'" style="font-size: 10px;">Build&nbsp;jar</button>');
        print('<button type="submit" name="commit" value="'.$commit.'" style="font-size: 10px;">Build</button>');
        print("<td>");
        print('&nbsp;'.$escaped_line);
      }
    } else {
      // no commit on this line, just graphics
      print("<td>");
      print("<td>");
      print('&nbsp;'.$escaped_line);
    }
    print("\n");
  }
  print('</table>');
  print('</pre>');
  print('</form>');

}

// use post, so that clear doesn't end up in the url bar
print('<form>');
print('<button type="submit" name="clear">Clear</button><br>');
print('</form>');

print('</body>');
print('</html>');
?>
