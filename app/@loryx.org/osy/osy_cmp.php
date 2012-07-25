<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

class osy_cmp
{
    public function make($rs, $prp=null, $arg=null)
    {
        $rs->tag = $rs->tag?$rs->tag:
                         new Tag('div');
    }
    public function check($rs,$evn)
    {
        //var_dump($rs->get_prp('opensymap.org/check'));
    }
    public function setValue($rs,$val,$rec=false)
    {
        $rs->value = $val;
        return $val;
    }
    public function getValue($rs)
    {
        return $rs->value;
    }
    public function disable($rs)
    {
        $rs->opt['disable'] = true;
    }
    public function disabled($rs)
    {
        if ($rs->opt['disable']) return true;
        if (!($cmd = $rs->get_prp('opensymap.org/test'))) return false;
        foreach($rs->get_par()->get_clds() as $nm => $ch) $$nm = $ch;
        if(eval($cmd)) return false;
        return true;
    }
    public function setted($rs)
    {
        return isset($_POST[$rs->name]);
    }

}

?>
