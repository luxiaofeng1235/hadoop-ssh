<?php
function gloadconf($fname)
{
	$ret = array('MYSQL','HIVEDB'); 
	$lines = explode("\n",file_get_contents($fname));
	foreach ($lines as $l)
	{
		if (preg_match('/(.+)=(.*)/',$l,$match))
		{
			$n = $match[1];
			$v = $match[2];	
		}
		else
		{
			continue;
		}
		if (strpos($n,'HIVEDB_') === 0)
		{
			$dbname = str_replace('HIVEDB_','',$n);


		}	
		
	}

}

gloadconf('main.conf');


?>
