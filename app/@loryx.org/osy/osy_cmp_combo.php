<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

 class osy_cmp_combo extends osy_cmp
{
    public function setValue($rs,$val,$rec=false)
    {
        $rs->empty_label = nvl($rs->get_prp('opensymap.org/option/empty/label'),'- select -');
        if (is_array($val))
        {
            $rs->value = $val;
        }
        else
        {
            $rs->value = array('key'=>$val.'');
            if (is_array($rec) and $f = $rs->get_prp('opensymap.org/db/field/descr') and $rs->value['key'])
            {
                $rs->value['dsc'] = $rec[$f];
            }
            else
            {
                // occorre calcolare dsc
                $opts = array();
                if ($rs->get_prp('opensymap.org/option/empty')!='no')
                {
                    $opts['']=$rs->empty_label;
                }
                foreach($rs->get_clds('opensymap.org/option') as $op)
                {
                    $opts[$op->get_prp('opensymap.org/key')] = nvl($op->get_prp('opensymap.org/dsc'),$op->get_prp('opensymap.org/key'));
                }
                foreach($rs->get_clds('opensymap.org/db/query') as $q)
                {
                    if ($qry) break;
                    $qry = $q;
                }
                if ($qry and $rs->value['key'])
                {
                    $db = env::get_var('db');
                    $res = $db->getAll($qry->get_prp('loryx.org/value'),$_POST,$_POST['_']['pky'],$_POST['_']['prt']);
                    if (count($res))
                    {
                        $kres = array_keys($res[0]);
                        if (count($kres)<2) $kres[] = $kres[0];
                        foreach($res as $r)
                        {
                            if ($r[$kres[0]] != $rs->value['key']) continue; // non serve memorizzarlo
                            $opts[$r[$kres[0]]] = $r[$kres[1]];
                        }
                    }
                }
                if ($rs->get_prp('opensymap.org/make/label')=='key')
                {
                    $rs->value['dsc'] = $rs->value['key'];
                }
                else
                {
                    // il valore della risorsa non è tra gli elementi?
                    $kks = array_keys($opts);
                    if (!in_array($rs->value['key'],$kks))
                    {
                        $rs->value['key'] = $kks[0];
                    }
                    $rs->value['dsc'] = $opts[$rs->value['key']];
                    //FB::log(array($rs->value,$opts),$rs->get_urn());
                }
            }
        }
		return $rs->value;
    }
    public function getValue($rs)
    {
        return $rs->value['key'];
    }
    public function make($rs, $prp=null, $arg=null)
    {
        parent::make($rs);
        if (!$prp)
        {
            $this->make_label($rs);
        }
        else
        {
            $this->make_data($rs);
        }
    }
    private function make_label($rs)
    {
        FB::log($rs->value,$rs->get_urn());
        $v = Tag::mk('div',array('osy_typ'=>'combo'));
        $v->Add($rs->value['dsc']);
        
        $c = Tag::mk('div',array('class'=>'cmp_combo'))
                ->Att('onclick',"this.box = osy.box(this,{'osy':{'cmp':'{$rs->name}'}}); this.chs = W(this).find({'osy_typ':'combo'}); ")
                ->Att('evn_select',"this.chs[0].value = args[0]; this.chs[1].value = args[1]; this.chs[2].innerHTML = args[1]; osy.event(this.box,'close'); this.chs[0].onchange()");
        $c->Add(new TagInput($rs->name.'[key]',$rs->value['key'],'hidden'))
            ->Att('osy_typ','combo')
            ->Att('onchange',"this.form.mod = true; ".$rs->get_prp('opensymap.org/cmp/onchange'));
        $c->Add(new TagInput($rs->name.'[dsc]',$rs->value['dsc'],'hidden'))
            ->Att('osy_typ','combo');
        $c->Add(new Tag('div'))->Att('style','height:0px; width:100px;');
        //$c->Add(new Tag('div'))->Att('style','height:0px; clear:both;');
        $c->Add($v);
        $rs->tag->Add($c);
    }
    
    private function make_data($rs)
    {
        $opts = array();
                
        if ($rs->get_prp('opensymap.org/option/empty')!='no')
        {
            $opts[''] = $rs->empty_label;
        }
        foreach($rs->get_clds('opensymap.org/option') as $op)
        {
            $opts[$op->get_prp('opensymap.org/key')] = nvl($op->get_prp('opensymap.org/dsc'),$op->get_prp('opensymap.org/key'));
        }
        foreach($rs->get_clds('opensymap.org/db/query') as $q)
        {
            if ($qry) break;
            $qry = $q;
        }
        if ($qry)
        {
            $db = env::get_var('db');
            //$db->noexe();
            $res = $db->getAll($qry->get_prp('loryx.org/value'),$_POST,$_POST['_']['pky'],$_POST['_']['prt']);
            if (count($res))
            {
                $kres = array_keys($res[0]);
                if (count($kres)<2) $kres[] = $kres[0];
                foreach($res as $r)
                {
                    $opts[$r[$kres[0]]] = $r[$kres[1]];
                }
            }
        }
        if ($code = $rs->get_prp('opensymap.org/make/data'))
        {
            $ret = eval($code);
            if (is_array($ret))
            {
                $opts = array_merge($opts,$ret);
            }
        }
        $c = Tag::mk('div');
        if (is_array($opts))
        {
            foreach($opts as $k=>$v)
            {
                $cls = 'mrg_2 pad_3 link';
                if ($k==$rs->value)
                {
                    $cls .= ' bold';
                }
                $c->Add(new Tag('div'))
                  ->Att('class',$cls)
                  ->Att('onmouseover',"W(this).addClass('option')")
                  ->Att('onmouseout',"W(this).removeClass('option')")
                  ->Att('onclick',"osy.event(box,'select',W(this).attr('opt_key'),this.innerHTML);")
                  ->Att('opt_key',$k)
                  ->Att('style','white-space:pre')
                  ->Add($v);
            }
        }
        else if (count($rs->cld))
        {
            foreach($rs->cld as $ch)
            {
                $c->Add($ch->exe()-tag);
                break;
            }
        }
        else
        {
            $c->Att('style','padding:10px; width:200px;');
            $c->Add('<div class="bold">Nessuna option impostata per il componente</div><code>'.$rs->get_urn().'</code>');
        }
        $rs->tag->Add($c);
    }
}
?>