<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

 class osy_grid extends osy_cmp
{
    public function make($rs, $prp=null, $arg=null)
    {
        parent::make($rs,$prp,$arg);
        
        $ret = $this->mk_evn($rs,$prp);
        
        if ($rs->evn->stop)
        {
            return $ret;
        }
        if ($prp) env::get_var('page')->form->Att('osy_type','map');
        // come vengono modificati i dati?
        $rs->frm = $rs->get_prp('opensymap.org/form');
        $rs->upd = $rs->get_prp('opensymap.org/event/update');
        $rs->del = $rs->get_prp('opensymap.org/event/delete');
        $rs->mod = ($rs->upd || $rs->del);

        $grid = new Tag('div');
        $rs->tag
            ->Att('osy_type','datagrid')
            ->Att('osy_map',$rs->name)
            ->Add($grid);
        switch($rs->get_prp('opensymap.org/event/save'))
        {
        case 'no':
            // se è richiesto esplicitamente di non salvare i valori del form principale ...
            $rs->tag->Att('evn_open' ,"osy.event(this,'win',args[0],args[1])");
            break;
        default:
            $rs->tag->Att('evn_open',"var a = args, el=this; osy.event($(this).closest('form'),'save',{'evn_ok':function(){osy.event(el,'win',a[0],a[1])}})");
            break;
        }
        if ($rs->frm) 
        {
            $rs->tag->Att('evn_win' ,"osy.win(args[0],{'osy':{'frm':'{$rs->frm}','evn':args[1]},'pky':$(args[0]).closest('tr').find({'osy_type':'pky'})},{evn_close:function(){osy.trigger(box,'reload')}})");
        }
        if ($rs->del)
        {
            $rs->tag->Att('msg_del',nvl($rs->get_prp('opensymap.org/event/delete/msg'),utf8_encode("L'elemento verrà cancellato. Continuare?")))
                 ->Att('evn_del_itm',"if (!confirm(W(this).attr('msg_del'))) return; osy.event(W(this).closest('form'),'exec',{'osy':{'cmp':'{$rs->name}','evn':'delete'},'vars':W(args[0]).find({'osy_type':'nopost'})})");
        }
        if ($rs->mod)
        {
            $grid->Att('evn_reload',"lrx.ev_start(window,'reload')");
        }
        if ($rs->upd)
        {
            $rs->tag->Att('evn_save_itm',"var tr = W(args[0]), frm = W(this).closest('form'); osy.event(frm,'save',{'evn_ok':function(){osy.event(frm,'exec',{'osy':{'cmp':'{$rs->name}','evn':'save'},'vars':tr.find({'osy_type':'nopost'})})}})");
        }
        // gestione ordinamento colonne
        $col_name = $rs->name.'[col]';
        $col_inp = $grid->Add(new TagInputPost($col_name,'hidden'));
        
        // gestione visualizzazione dati
        $tbl = $grid->Add(new Table)
                    ->Att('class','dg_data')
                    ->Att('cmp_name',$rs->name);
        
        // gestione paginazione
        $pg_name = $rs->name.'[pg]';
        $cmd = $grid->Add(new Tag('div'))
                    ->Att('align','center')
                    ->Add(new Table)
                    ->Att('width','')
                    ->Att('style','font-size:10px;')
                    ->Att('cellspacing','5px;');
        
        $cmd->Att('evn_set_page',"var el = lrx.search(this.parentNode,'input')[0]; el.value=args[0]; el.form.submit();");
        
        //if (is_array($_POST['_']['pky']) and strlen(implode('',$_POST['_']['pky'])))
        foreach($rs->get_clds('opensymap.org/db/query') as $qry)
        {
            if ($test = $qry->get_prp('opensymap.org/test'))
            {
                if(eval($test))
                {
                    $rs->qry = $qry;
                    break;
                }
            }
            else
            {
                $rs->qry = $qry;
                break;
            }
        }
        // pagina corrente
        //FB::log(array($rs->dump()));
        $pg_inp = new TagInputPost($pg_name);
        $pg_inp->Att('style','padding:1px; text-align:right; width:30px;');
        // elementi da visualizzare
        $els_max = nvl($rs->get_prp('opensymap.org/pag/elems'),10);
        $els = 0;
        if ($rs->upd) $els_max -= 1;
        
        // caricamento dati da visualizzare
        $db = env::get_var('db');
        if ($rs->qry)
        {
            list($data,$par) = $db->getPage(array($rs->qry->get_prp('loryx.org/value'),$els_max,$pg_inp->value),
                                                         $_POST,
                                                         $_POST['_']['pky'],
                                                         $_POST['_']['prt']);
        }
        
        $pprec = max($par->page['cur']-1,1);
        $psuc = min($par->page['cur']+1,$par->page['max']);
        $cmd->Cell('&laquo;')
            ->Att('style','font-size:10px; padding:2px 5px 2px 5px;')
            ->Att('class','link')
            ->Att('onclick',"lrx.ev_start(lrx.par(this,'table'),'set_page','1')");
        $cmd->Cell('&lt;')
            ->Att('style','font-size:10px; padding:2px 5px 2px 5px;')
            ->Att('class','link')
            ->Att('onclick',"lrx.ev_start(lrx.par(this,'table'),'set_page','{$pprec}')");
        $cmd->Head($pg_inp)
            ->Prp('nowrap')
            ->Add(Tag::mk('span',false,' / '.nvl($par->page['max'],1)));
        $cmd->Cell('&gt;')
            ->Att('style','font-size:10px; padding:2px 5px 2px 5px;')
            ->Att('class','link')
            ->Att('onclick',"lrx.ev_start(lrx.par(this,'table'),'set_page','{$psuc}')");
        $cmd->Cell('&raquo;')
            ->Att('style','font-size:10px; padding:2px 5px 2px 5px;')
            ->Att('class','link')
            ->Att('onclick',"lrx.ev_start(lrx.par(this,'table'),'set_page','{$par->page['max']}')");
 
        $pg_inp->Att('value',max(intval($pg_inp->value),1));
        
        if (!$rs->upd)
        {
            // l'elemento viene aperto in una form ?
            if (count($rs->pky) and $rs->frm)
            {
                $th = $tbl->Head(Tag::mk('span',array('class'=>"hide"),'+'))
                          ->Att('class','link')
                          ->Att('style',"width:20px")
                          ->Att('onmouseover',"W('span:first',this).removeClass('hide')")
                          ->Att('onmouseout',"W('span:first',this).addClass('hide')")
                          ->Att('onclick',"osy.event(W(this).closest({'osy_type':'datagrid'}),'open',this,'insert')");
                foreach($rs->pky as $nm=>$ch)
                {
                    if (!($p = $ch->get_prp('opensymap.org/db/field'))) $p = $nm;
                    $name = $ch->get_prp('opensymap.org/db/var');
                    $th->Add(new Tag('span'))
                       ->Att('class','nodisplay')
                       ->Att('osy_type','pky')
                       ->Att('osy_name',$name)
                       ->Add('');
                }
            }
        }
        // costruzione testata
        foreach($rs->cols as $k=>$ch)
        {
            if ($ch->get_prp('opensymap.org/hide')) continue;
            if (!($v=$ch->get_prp('opensymap.org/title'))) $v = $k;
            $th = $tbl->Head(new Tag('div'),1)->Add(NBSP.$v);
            if ($w = $ch->get_prp('opensymap.org/size/width')) $th->Att('style',"width:{$w};");
        }
        // l'elemento viene modificato in loco?
        if (count($rs->pky) and $rs->mod)
        {
            if ($rs->del) $th = $tbl->Head(NBSP)
                                    ->Att('style',"width:20px");
            if ($rs->upd) $this->mk_itm($tbl,$rs,array());
        }
        if (is_array($data)) foreach($data as $itm)
        {
            $els ++;
            $this->mk_itm($tbl,$rs,$itm);
        }
        for(; $els<$els_max; $els++)
        {
            $this->mk_itm($tbl,$rs,false);
        }
        return $grid;
    }
    private function mk_itm($tbl, $rs, $itm)
    {
        $row = (($rs->itm_row++)+0);
        $tr = $tbl->Row();
        
        $tr->Att('onmouseover',"W(this).addClass('option')")
           ->Att('onmouseout',"W(this).removeClass('option')");
        if ($rs->upd)
        {
            // modifica inline degli elementi
            $tr->Att('evn_modified',"this.vvupd = true;")
               ->Att('evn_focusin',"clearTimeout(this.ttupd)")
               ->Att('evn_focusout',"if (!this.vvupd) return; ".
                                    "this.ttupd = setTimeout(function(tr){".
                                            "tr = W(tr); osy.event(tr.closest({'osy_type':'datagrid'}),'save_itm',tr);".
                                    "}, 100, this);");
        }
        else
        {
            if (count($rs->pky) and $rs->frm)
            {
                $th = $tbl->Head('');
                if ($itm)
                {
                    $th->Att('class','link')
                       ->Att('onmouseover',"W('span:first',this).removeClass('hide')")
                       ->Att('onmouseout',"W('span:first',this).addClass('hide')")
                       ->Att('onclick',"osy.event(W(this).closest({'osy_type':'datagrid'}),'open',this,'load')")
                       ->Add(Tag::mk('span',array('class'=>"hide"),'...'));
                    foreach($rs->pky as $nm=>$ch)
                    {
                        if (!($p = $ch->get_prp('opensymap.org/db/field'))) $p = $nm;
                        $name = $ch->get_prp('opensymap.org/db/var');
                        $th->Add(new Tag('span'))
                           ->Att('class','nodisplay')
                           ->Att('osy_type','pky')
                           ->Att('osy_name',$name)
                           ->Add(htmlspecialchars($itm[$p]));
                       $lpks .= $itm[$p];
                    }
                }
                else
                {
                    $th->Add(NBSP);
                }
            }
        }
        foreach($rs->cols as $k=>$ch)
        {
            if ($ch->get_prp('opensymap.org/hide')) continue;
            if (is_array($itm))
            {
                if (!($p = $ch->get_prp('opensymap.org/db/field'))) $p = $k;
                if(count($rs->pky) and $rs->upd)
                {
                    $name = "{$rs->name}[itm][{$row}][{$ch->name}]";
                    if ($ch->get_prp('opensymap.org/text/cols')>1)
                    {
                        $val = new Tag('textarea');
                        $val->Att('name',$name)
                            ->Att('class','mini')
                            ->Att('style','position:absolute;')
                            ->Add($itm[$p]);
                    }
                    else
                    {
                        $val = new TagInput($name,$itm[$p],'text');
                    }
                    $val->Att('onfocus',"if (!this.tr) this.tr = W(this).closest('tr'); osy.event(this.tr,'focusin')")
                        ->Att('onblur',"osy.event(this.tr,'focusout')")
                        ->Att('osy_type','nopost');

                }
                else
                {
                    $val = NBSP.$itm[$p];
                }
            }
            else
            {
                $val = NBSP.'';
            }
            $tbl->Cell(Tag::mk('div',false,$val));
        }
        if (count($rs->pky) and $rs->mod)
        {
            $th = $tbl->Head('')
                      ->Att('class','nodisplay');
            $lpks = '';
            if (is_array($itm)) foreach($rs->pky as $nm=>$ch)
            {
                if (!($p = $ch->get_prp('opensymap.org/db/field'))) $p = $nm;
                $name = $ch->get_prp('opensymap.org/db/var');
                $th->Add(new TagInput("{$rs->name}[pky][{$row}][{$ch->name}]",$itm[$p]))->Att('osy_type','nopost');
                $lpks .= $itm[$p];
            }
            else
            {
                $th->Add(NBSP);
            }
            if ($rs->del)
            {
                $th = $tbl->Head('');
                if (strlen($lpks))
                {
                    $th->Att('class','link')
                       ->Att('onmouseover',"W('span:first',this).removeClass('hide')")
                       ->Att('onmouseout',"W('span:first',this).addClass('hide')")
                       ->Att('onclick',"osy.event(W(this).closest({'osy_type':'datagrid'}),'del_itm',W(this).closest('tr'))")
                       ->Add('<span class="hide"> - </span>');
                }
                else
                {
                    $th->Add(NBSP);
                }
            }
        }
    }
    public function evn_init($rs,$event)
    {
        if ($rs->_evn) return;
        
        $rs->pky = array();
        $rs->itm = $rs->value['itm'];
        $rs->cols = array();
        $rs->fld = array();
        foreach($rs->get_clds('opensymap.org/column') as $nm => $ch)
        {
            if ($f=$ch->get_prp('opensymap.org/db/field'))
            {
                $rs->fld[$f] = $ch;
            }
            if ($ch->get_prp('opensymap.org/db/pk'))
            {
                $rs->pky[$nm] = $ch;
            }
            $rs->cols[$nm] = $ch;
        }
        
        $rs->_evn = new stdClass;
        $rs->_evn->before = array();
        $rs->_evn->start = array();
        $rs->_evn->after = array();
        if (empty($event)) return;
        foreach($rs->get_clds('opensymap.org/event') as $ch)
        {
            if ($ch->get_prp('loryx.org/name')==$event)
            {
                switch($ch->get_prp('opensymap.org/type'))
                {
                case 'opensymap.org/event/before':
                    $rs->_evn->before[] = $ch;
                    break;
                case 'opensymap.org/event/after':
                    $rs->_evn->after[] = $ch;
                    break;
                default:
                    $rs->_evn->start[] = $ch;
                    break;
                }
            }
        }
    }
    public function evn_before($rs,$event)
    {
        $this->evn_init($rs,$event);
        $cnt = new Tag('');
        foreach($rs->_evn->before as $ch)
        {
            $cnt->Add($ch->exe());
            $found++;
        }
        return $cnt;
    }
    public function evn_start($rs,$event)
    {
        $this->evn_init($rs,$event);
        $cnt = new Tag('');
        foreach($rs->_evn->start as $ch)
        {
            $cnt->Add($ch->exe());
            $found++;
        }
        $db = env::get_var('db');
        if (!$found) switch($event)
        {
        case 'delete':
            foreach($rs->value['pky'] as $pky)
            {
                $wh = array();
                foreach($rs->pky as $k=>$ch)
                {
                    $wh[$ch->get_prp('opensymap.org/db/field')] = $pky[$k];
                }
                $db->delete('[@'.$rs->get_prp('opensymap.org/db/table').']',$wh);
            }
            break;
        case 'save':
            foreach($rs->value['pky'] as $k=>$pky)
            {
                // per ogni riga postata del datagrid 
                $wh = array();
                $fl = array();
                $spky = trim(implode('',array_values($pky)));
                $itm = $rs->value['itm'][$k];
                
                if (strlen($spky))
                {
                    // modifica elemento
                    foreach($rs->pky as $k=>$ch)
                    {
                        $wh[$ch->get_prp('opensymap.org/db/field')] = $pky[$k];
                    }
                    foreach($rs->cols as $k=>$ch)
                    {
                        FB::log(isset($itm[$k]),$ch->get_urn());
                        if (isset($pky[$k])) $ch->value = $pky[$k];
                        if (isset($itm[$k]))
                        {
                            $ch->value = $itm[$k];
                        }
                        else 
                        {
                            env::exe_prp($ch,'opensymap.org/event/code',array('event'=>'update'));
                            //?
                        }
                        if (isset($ch->value)) $fl[$ch->get_prp('opensymap.org/db/field')] = $ch->value;
                    }
                    $db->update('[@'.$rs->get_prp('opensymap.org/db/table').']',$fl,$wh);
                }
                else
                {
                    // inserimento nuovo elemento
                    $fl = array();
                    foreach($rs->fld as $f=>$ch)
                    {
                        if ($v = $ch->get_prp('opensymap.org/db/par'))
                        {
                            $ch->value = $db->merge('<['.$v.']>',$_POST,
                                                              $_POST['_']['pky'],
                                                              $_POST['_']['prt']);
                        }
                        else if (isset($itm[$ch->name]))
                        {
                            //if ($ch->get_prp('loryx.org/db/pk'))
                            $ch->value = $itm[$ch->name];
                        }
                        else
                        {
                            env::exe_prp($ch,'opensymap.org/event/code',array('event'=>'insert'));
                        }
                        $fl[$f] = $ch->value;
                    }
                    $db->insert('[@'.$rs->get_prp('opensymap.org/db/table').']',$fl);
                }
            }
            break;
        }
        return $cnt;
    }
    public function evn_after($rs,$event)
    {
        $this->evn_init($rs,$event);
        $cnt = new Tag('');
        foreach($rs->_evn->after as $ch)
        {
            $cnt->Add($ch->exe());
            $found++;
        }
        return $cnt;
    }
    
    private function mk_evn($rs,$evn)
    {
        $rs->evn = $evn;
        
        $event = $evn->value;
        $arg   = $evn->arg;
        $this->evn_init($rs,$event);
        
        if (!$event) return;
        
        $cnt = new Tag('');
        $cnt->Add($this->evn_before($rs,$event));
        $cnt->Add($this->evn_start($rs,$event));
        $cnt->Add($this->evn_after($rs,$event));
        
        return ;
    }
}

?>