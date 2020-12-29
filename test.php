<?php
$func = array('a','b');
foreach ($func as $v)
{
	$function = "func$v";
	$function(1);
}
function funca($a)
{
echo $a;
}
function funcb($b)
{
echo $b;
}


