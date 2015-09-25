<?php
/*
* Rabin-Karp-Matcher Algorithm
* @author Mustafa Qamar-ud-Din <mustafa.mahrous.89@gmail.com>
*/

define("MODULUS_OFFSET",9999);

/**
* param $T string the input text
* param $P string the pattern to look for
* param $d the d-ary of the sigma alphabet
* param $q the modulus used in modulus equivalence
* return void
*/
function RABIN_KARP_MATCHER($T, $P, $d, $q)
{
  $spurious_hits = 0;
  $ret = array();
  $n = strlen($T);
  $m = strlen($P);
  $h = pow($d, $m-1) % $q;
  $p_value = 0;
  $t_0 = 0;
  for($i = 0; $i < $m; $i++){ // preprocessing
    $p_value = ($d * $p_value + $P[$i]) % $q;
    $t_0 = ($d * $t_0 + $T[$i]) % $q;
  }
  $ts = $t_0;
  for($s = 0; $s < $n-$m;$s++){ // matching
    if($p_value == $ts){
      $found = false;
      for($i = 0; $i < $m; $i++){ // if P[1..m] == T[s+1..s+m]
        if($P[$i] != $T[$s+$i+1]){
          $found = false;
          $spurious_hits++;
          break;
        }else{
          $found = true;
        }
      }
      if($found){
        $context = '';
        for($i = $s-10; $i < $s+$m+10; $i++)
        {
          $context .= $T[$i];
        }
        $ret['indices'][] = array(
          'shift' => $s,
          'context' => $context
        );
      }
    }
    // maintain loop invariant
    if($s+1 < $n-$m){
      $ts = ($d*($ts-$T[$s+1]*$h)+$T[$s+$m+1]) % $q;
    }
  }
  $ret['spurious_hits'] = $spurious_hits;
  return $ret;
}

function print_outputs($f_output, $pattern, $results, $run_time){
  $formatted_lines = 'Pattern:';
  $formatted_lines .= $pattern;
  $formatted_lines .= PHP_EOL;

  $formatted_lines .= 'Run Time(Seconds):';
  $formatted_lines .= number_format($run_time, 2);
  $formatted_lines .= PHP_EOL;

  $formatted_lines .= '# of Spurious Hits(n/q):';
  $formatted_lines .= $results['spurious_hits'];
  $formatted_lines .= PHP_EOL;

  if(isset($results['indices']) && count($results['indices'])){
    $formatted_lines .= 'Indices:';
    foreach($results['indices'] as $record){
      $formatted_lines .= $record['shift'] . " --> " . $record['context'] . PHP_EOL;
    }
  }else{
    $formatted_lines .= "Invalid Shift";
  }
  $formatted_lines .= PHP_EOL;
  $formatted_lines .= PHP_EOL;
  file_put_contents($f_output, $formatted_lines, FILE_APPEND);
}

function main(){
  $f_input = 'inputs.dat';
  $f_output = 'outputs.dat';
  if($_SERVER['argc']){
    $f_input = $_SERVER['argv'][1];
    $f_output = $_SERVER['argv'][2];
  }
  // http://www.hackingwithphp.com/8/1/2/file_get_contents-and-file
  $filestring = file_get_contents($f_input);
  $filearray = explode("\n", $filestring);
  while (list($var, $val) = each($filearray)) {
    if($var == 0){
      $text = strtolower(trim($val));
      continue;
    }
    $pattern = strtolower(trim($val));
    if(!strlen($pattern))
    continue;
    $modulus = strlen($pattern) + rand(MODULUS_OFFSET, 2 * MODULUS_OFFSET);
    $start_time = microtime(true);
    $results = RABIN_KARP_MATCHER($text, $pattern, 26, $modulus);
    $run_time = microtime(true) - $start_time;
    print_outputs($f_output, $pattern, $results, $run_time);
  }
}

/*
* $ php rabin_karp_matcher.php inputs.dat outputs.dat
*/
main();
