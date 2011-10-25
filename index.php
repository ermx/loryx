<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

ob_start();

// debug limit
set_time_limit(2);

// my timezone
date_default_timezone_set('Europe/Rome');

// utility constants
define('BR',"<br\>");
define('NL',"\n");
define('TAB','    ');
define('NBSP','&#xA0;');

// utility functions
function nvl($a,$b)
{
    return $a?$a:$b;
}
function numf($s,$p=2)
{
    return number_format($s,$p,'.','');
}
function numm($s,$p=2)
{
    return number_format($s,$p,',','.');
}
function __cl_var(&$a)
{
    $mq = get_magic_quotes_gpc();
    foreach($a as $k=>$v)
    {
        if (is_array($v)) __cl_var($a[$k]);
        else
        {
            $v = trim($v);
            if ($mq) $v = stripslashes($v);
            $a[$k] = $v;
        }
    }
}

__cl_var($_GET);
__cl_var($_POST);

// static functions env::*
include('./lib/cls.env.php');

// firephp library
include('./lib/FirePHPCore/fb.php');

// resource tree struct
include('./lib/cls.rs.php');

// tag tree struct
include('./lib/cls.tag.php');

// default content-type
env::set_ctype('text/plain');

// execution resource "main@loryx.org"
return env::get_rs('main@loryx.org')->exe();
?>
