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
    public function disabled($rs)
    {
        if (!($cmd = $rs->get_prp('opensymap.org/test'))) return false;
        foreach($rs->get_par()->get_clds() as $nm => $ch) $$nm = $ch;
        if(eval($cmd)) return false;
        return true;
    }
    public function save_prp_lrx($rs)
    {
        $cmp = $rs;
        $rs  = $rs->get_par();
        $db = env::get_var('dbapp');
        
        $tbl = $rs->get_prp('opensymap.org/db/table');
        $prp = $cmp->get_prp('loryx.org/prp');
        $cmd = "
            delete from [@{$tbl}] 
            where l = [{$rs->fld['l']->name}] 
              and o = [{$rs->fld['o']->name}] 
              and r = [{$rs->fld['r']->name}] 
              and y = [#y]";
        $db->query($cmd,$rs->pk,array('y'=>$prp));
        if ($cmp->getValue())
        {
            $cmd = "
                insert into [@{$tbl}] (l,o,r,y,x) 
                values ([{$rs->fld['l']->name}],
                        [{$rs->fld['o']->name}],
                        [{$rs->fld['r']->name}],
                        [#y],
                        [#x])";
            $db->query($cmd,$rs->pk,array('y'=>$prp,'x'=>$cmp->getValue()));
        }
    }
    public function load_prp_lrx($rs)
    {
        $cmp = $rs;
        $rs  = $rs->get_par();
        $db = env::get_var('dbapp');
        
        $tbl = $rs->get_prp('opensymap.org/db/table');
        $prp = $cmp->get_prp('loryx.org/prp');
        $cmd = "
            select x from [@{$tbl}] 
            where l = [{$rs->fld['l']->name}] 
              and o = [{$rs->fld['o']->name}] 
              and r = [{$rs->fld['r']->name}] 
              and y = [#y]";
        $cmp->setValue($db->getVal($cmd,$_POST['_']['pky'],array('y'=>$prp)));
    }
}

?>