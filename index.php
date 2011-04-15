<?php

if (get_magic_quotes_gpc()) {
  $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
  while (list($key, $val) = each($process)) {
    foreach ($val as $k => $v) {
      unset($process[$key][$k]);
      if (is_array($v)) {
	$process[$key][stripslashes($k)] = $v;
	$process[] = &$process[$key][stripslashes($k)];
      } else {
	$process[$key][stripslashes($k)] = stripslashes($v);
      }
    }
  }
  unset($process);
}

// General use functions

function play($x,$y) {
  header('Content-Type: application/json');
  echo json_encode(compact('x','y'));
  exit;
}

$board = json_decode($_POST['board']);
$color = $_POST['color'];

function adjacent($board,$x,$y,$color)
{
  for ($dx = -1; $dx <= 1; ++$dx) 
    for ($dy = -1; $dy <= 1; ++$dy) 
      if ($dx * $dy == 0) 
	if ($board[$x+$dx][$y+$dy] === $color) 	  	  
	  return array($x+$dx,$y+$dy);

  return null;
}

// Various strategies

function strategy_random() 
{
  global $board;

  do {
    $x = rand(0,23);
    $y = rand(0,11);
  } while ($board[$x][$y] !== '');

  play($x,$y);
}

function strategy_expand()
{
  global $board;
  global $color;

  for ($x = 0; $x < 24; ++$x) 
    for ($y = 0; $y < 12; ++$y) 
      if ($board[$x][$y] === $color) 
	if ($adj = adjacent($board,$x,$y,''))
	  play($adj[0],$adj[1]);
  
  strategy_random();
}

function strategy_fearful()
{
  global $board;
  global $color;

  $enemy = $color === 'b' ? 'r' : 'b';

  for ($x = 0; $x < 24; ++$x) 
    for ($y = 0; $y < 12; ++$y) 
      if ($board[$x][$y] === '') 
        if (adjacent($board,$x,$y,$enemy))
	  if ($adj = adjacent($board,$x,$y,$color))
	    play($adj[0],$adj[1]);
          
  strategy_expand();
}

function strategy_contain()
{
  global $board;
  global $color;

  $enemy = $color === 'b' ? 'r' : 'b';

  for ($x = 0; $x < 24; ++$x) 
    for ($y = 0; $y < 12; ++$y) 
      if ($board[$x][$y] === '') 
        if (adjacent($board,$x,$y,$enemy))
	  if ($adj = adjacent($board,$x,$y,$color))
	    play($adj[0],$adj[1]);

  $avgx = 0;
  $avgy = 0;
  $avgn = 0;
          
  for ($x = 0; $x < 24; ++$x) 
    for ($y = 0; $y < 12; ++$y) 
      if ($board[$x][$y] === $enemy) {
	$avgx += $x;
	$avgy += $y;
	$avgn ++;
      }

  if ($avgn == 0) strategy_expand();

  $avgx /= $avgn;
  $avgy /= $avgn;

  $maxx = 0;
  $maxy = 0;
  $best = 9999;

  for ($x = 0; $x < 24; ++$x)
    for ($y = 0; $y < 12; ++$y)
      if ($board[$x][$y] === '') 
	if (adjacent($board,$x,$y,$color)) {
	  $dist = abs($x - $avgx) + abs($y - $avgy);
	  if ($dist < $best) {
	    $best = $dist;
	    $maxx = $x;
	    $maxy = $y;
	  }
	}

  if ($best < 9999) play($maxx,$maxy);	  

  strategy_random();
}


// pick a strategy

foreach ($_GET as $strategy => $ignore) {
  switch($strategy) {
  case 'random' : strategy_random();
  case 'expand' : strategy_expand();
  case 'fearful' : strategy_fearful();
  case 'contain' : strategy_contain(); 
  }
}

strategy_random();