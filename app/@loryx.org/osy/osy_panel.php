<?php

class TagTab extends Tag
{
    function __construct($nm)
    {
        parent::__construct('div');
        $this->Att('osy_type','TagTab')
             ->Att('class','TagTab')
             ->Att('evn_select',"W('input:first',this).val(args[0]); osy.par(this,'form').submit()");
        $this->inp = parent::Add(new TagInputPost($nm,'hidden'));
        $this->tab = parent::Add(new Tag('div'));
        parent::Add(new Tag('div'))->Att('style','clear:both;border-top:1px solid silver;');
        $this->grp = array();
        $this->kks = array();
    }
    function Add($el,$opt=array())
    {
        $grp = '_'.$opt['grp'];
        if (!$this->grp[$grp]) 
        {
            // la prima volta che si crea il gruppo si imposta l'allineamento
            $this->grp[$grp] = $this->tab->Add(new Tag('ul'));
            $this->grp[$grp]->Att('style','float:'.nvl($opt['align'],'left'));
        }
        $li = $this->grp[$grp]->Add(new Tag('li'));
        $li->Att('osy_key',nvl($opt['key'],$el))
           ->Att('onclick',"osy.event(osy.par(this,{'osy_type':'TagTab'}),'select',W(this).attr('osy_key'))")
           ->Add($el);
        $this->kks[$li->osy_key] = $li;
        return $this;
    }
    function getValue()
    {
        if (!$this->kks) return '';
        $kks = array_keys($this->kks);
        if (!in_array($this->inp->value,$kks)) $this->setValue($kks[0]);
        return $this->inp->getValue();
    }
    function setValue($v)
    {
        $this->inp->setValue($v);
        return $this;
    }
    function Get($depth=0)
    {
        if($li = $this->kks[$this->getValue()]) $li->Att('class','selected');
        return parent::Get($depth);
    }
}
/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

class osy_panel extends osy_cmp
{
    public function make($rs, $prp=null, $arg=null)
    {
//        FB::log($rs->get_urn().'['.$rs->get_prp('opensymap.org/type').']','begin make');
        $rs->set_prp('loryx.org/load_rs/exception',1);
        if ($rs->disabled()) return;
        parent::make($rs);
        $w = $rs->get_prp('opensymap.org/size/width');
        //var_dump(array($rs->dump()));
        $h = $rs->get_prp('opensymap.org/size/height');
        $root = $rs->tag;
        //FB::log($w,'width');
        if ($w || $h)
        {
            if ($rs->get_prp('opensymap.org/size/overflow'))
            {
                $root->Att('style','overflow:auto;');
                if ($w) 
                {
                    $root->AttAp('style',"width:{$w}px;");
                }
                if ($h) 
                {
                    $root->Att('style',"height:{$h}px;");
                }
            }
            else
            {
                $root = $root->Add(new Table);
                if ($w) 
                {
                    $root->Att('width',"{$w}px;");
                }
                if ($h) 
                {
                    $root->Att("height","{$h}px;");
                }
            }
        }
        $pnl = $root->Add(new Tag('div'))
                    ->Att('lrx_urn',$rs->get_urn());
                    
        // il contenuto verrà modificato dinamicamente?
        if ($rs->get_prp('opensymap.org/map')) $pnl->Att('osy_map',$rs->name);
        
        if ($st = $rs->get_prp('opensymap.org/style'))
        {
            $pnl->Att('style',$st);
        }
        switch($rs->get_prp('opensymap.org/type'))
        {
        case 'tab':
            $this->mk_tab($rs,$pnl);
            break;
        case 'wizard':
            $this->mk_wizard($rs,$pnl);
            break;
        default:
            $this->mk_std($rs,$pnl);
            break;
        }
//        FB::log($rs->get_urn(),'end make');
        return;
    }
    private function mk_wizard($rs,$cnt)
    {
        $rs_form = env::get_var('form');
        $cnt->Par->Att('box_typ','wizard')
                 ->Att('evn_pag',"$(this).find({'box_typ':'cmd'}).trigger('pag',[args[0]])");
        $cnt->Att('box_typ','view');
        $div = $cnt->Par
                   ->AttAp('style','padding-bottom:25px;')->Add(new Tag('div'))
                        ->Att('box_typ','cmd')
                        ->Att('evn_pag',"$('input',this).first().attr('value',args[0]).trigger('submit');")
                        ->Att('style','position:absolute; bottom:0px; width:100%; height:20px;');
        $wiz_evnt = $div->Add(new TagInputPost($rs->name.'[evnt]','hidden'));
        $wiz_curr = $div->Add(new TagInputPost($rs->name.'[curr]','hidden'));
        $wiz_list = $div->Add(new TagInputPost($rs->name.'[list]','hidden'));
        //FB::log(count($rs->pnl['childs']),'pnl');exit;
        if (!$wiz_curr->value)
        {
            $wiz_curr->Att('value',$rs->pnl['childs'][0]->name);
        }
        $list = array_filter(explode(',',$wiz_list->value),'strlen');
        
        //var_dump($rs->pnl['childs']);
        switch($wiz_evnt->value)
        {
        case 'prec':
            $wiz_curr->Att('value',array_pop($list));
            break;
        case 'next':
            $list[] = $wiz_curr->value;
            $rs_curr = $rs_form->get_cld($wiz_curr->value);
            $next = $rs_curr->get_prp('opensymap.org/next');
            $wiz_curr->Att('value',$next);
            break;
        }
        $wiz_list->Att('value',implode(',',$list));
        $wiz_evnt->Att('value','');
        
        $rs_curr = $rs_form->get_cld($wiz_curr->value);
        FB::log($wiz_curr->value);
        $cmd = $div->Add(new Table);
        
        $prec = $cmd->Head(NBSP)
                    ->Att('width','50%');
        if ($wiz_list->value)
        {
            $prec->Add('<<')
                 ->Att('class','option')
                 ->Att('onclick',"$(this).closest({'box_typ':'wizard'}).trigger('pag',['prec'])");
        };
        $succ = $cmd->Head(NBSP)
                    ->Att('width','50%');
        if ($rs_curr->get_prp('opensymap.org/next'))
        {
            $succ->Add('>>')
                 ->Att('onclick',"$(this).closest({'box_typ':'wizard'}).trigger('pag',['next'])")
                 ->Att('class','option');
        }
        if ($rs->pnl['childs']) foreach($rs->pnl['childs'] as $p)
        {
            $arg = array();
            if ($p->name != $wiz_curr->value) 
            {
                $arg['style']='display:none';
                $p->hidden = true;
            }
            $ctag = $cnt->Add(Tag::mk('div',$arg))
                        ->Add($p->exe()->tag);
        }
    }
    private function mk_std($rs,$cnt)
    {
        $mat = array();
        $lpos = array();
        //FB::log(count($rs->pnl['childs']));
        if ($rs->pnl['childs'])
        {
            foreach($rs->pnl['childs'] as $ch)
            {
                if ($ch->get_prp('opensymap.org/debug')) FB::log(array($ch->dump()),'debug ');
                if ($ch->disabled()) continue;
                $continue = 0;
                switch($ch->get_prp('opensymap.org/panel/draw'))
                {
                case 'private':
                    $cnt->Par->Add($ch->exe()->tag,1);
                    $continue = 1;
                    break;
                case 'no':
                    $continue = 1;
                    break;
                }
                
                if ($continue) continue;
                $pos = $ch->get_prp('opensymap.org/pos');
                if (!strlen($pos))
                {
                    $lpos[] = $ch;
                    continue;
                }
                list($x,$y) = array_map('intval',explode(',',$pos));
                if (!$x) $x=0;
                if (!$y) $y=0;
                if ($mat[$x][$y]) $mat[$x][] = $ch;
                else $mat[$x][$y] = $ch;
            }
            if (count($mat))
            {
                $t_cnt = $cnt->Add(new Table);
            }
            ksort($mat);
            foreach($mat as $r)
            {
                ksort($r);
                $t_cnt->Row();
                foreach($r as $ch)
                {
                    $lb = $ch->get_prp('opensymap.org/label');
                    $colspan = $ch->get_prp('opensymap.org/colspan');
                    if (!$colspan) $colspan = 1;
                    switch($ch->get_prp('opensymap.org/panel/label'))
                    {
                    case 'no':
                        $td = $t_cnt->Cell($ch->exe()->tag)
                                  //->Att('width','100%')
                                  ;
                        $colspan = $colspan*2;
                        break;
                    case 'hidden':
                        $t_cnt->Head(NBSP);
                        $t_cnt->Cell($ch->exe()->tag)
                              ->Att('style','padding:3px;');
                        $colspan = $colspan*2-1;
                        break;
                    default:
                        $ct = $ch->typ;
                        $t_cnt->Head($lb)
                            ->Att('style','text-align:left; padding:3px;')
                            ->Att('width','1%')
                            ->Att('lrx_urn',$ct->get_urn())
                            //->Att('onclick',"osy.trigger(window,'sdkwin',{'osy':{'frm':'frm_cmp','evn':'load'},'pky':{'hdn_sys':'{$ct->sys}','hdn_base':'{$ct->base}','txt_name':'{$ct->name}'}})")
                            ->Prp('nowrap');
                        $ch->set_prp('opensymap.org/label/view','no');
                        $td = $t_cnt->Cell($ch->exe()->tag)
                                  ->Att('style','padding:3px;');
                        if ($w=$ch->get_prp('opensymap.org/size/width'))
                        {
                            $td->Att('width',$w); 
                        }
                        $colspan = $colspan*2-1;
                    }
                    
                    if ($colspan>1)
                    {
                        $td->Att('colspan',$colspan);
                    }
                }
            }
            foreach($lpos as $ch)
            {
                $cnt->Add(new Tag('div'))
                    ->Add($ch->exe()->tag);
            }
        }
        return;
    }
    private function mk_tab($rs,$cnt)
    {
        $ttl = $cnt->Add(new TagTab($rs->name));
        $cc = 0;
        $els = array();
        $eln = array();
        $nopos = array();
        // posizionamento degli elementi
        if ($rs->pnl['childs']) 
        {
            foreach($rs->pnl['childs'] as $ch)
            {
                if ($ch->disabled()) continue;
                $pos = $ch->get_prp('opensymap.org/pos');
                $nm_el[$ch->name] = $ch;
                if (strlen($pos))
                {
                    $pos = intval($pos);
                    if ($els[$ch->get_prp('opensymap.org/pos/align')][$pos]) $nopos[$ch->get_prp('opensymap.org/pos/align')][] = $ch;
                    else $els[$ch->get_prp('opensymap.org/pos/align')][$pos] = $ch;
                }
                else $nopos[$ch->get_prp('opensymap.org/pos/align')][] = $ch;
                
                $eln[] = $ch->name;
            }
            foreach($nopos as $a=>$np) foreach($np as $n) $els[$a][] = $n;
        }
        // ordinamento elementi
        foreach($els as $a=>$v) 
        {
            ksort($els[$a]);
        }
        foreach($els as $a=>$chs) foreach($chs as $ch)
        {
            $ttl->Add(nvl($ch->get_prp('opensymap.org/title'),$ch->name),
                      array('key'=>$ch->name, 'align'=>$a, 'grp'=>$a));
        }
        if ($ch = $nm_el[$ttl->getValue()])
        {
            $cnt->Add(new Tag('div'))
                ->Add($ch->exe()->tag);
        }
        return;
    }    
}

?>