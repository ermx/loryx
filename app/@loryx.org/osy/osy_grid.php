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
		$rs->save = $rs->get_prp('opensymap.org/event/save');
        $rs->frm = $rs->get_prp('opensymap.org/form');
        $rs->upd = $rs->get_prp('opensymap.org/event/update');
        $rs->del = $rs->get_prp('opensymap.org/event/delete');
        $rs->mod = ($rs->upd || $rs->del);

        $grid = new Tag('div');
        $rs->tag
            ->Att('osy_type','datagrid')
            ->Att('osy_map',$rs->name)
            ->Add($grid);
        $dbg = I(new Tag('div'))->Att('class','debug')->Add(new Tag('pre'));
        if ($rs->get_prp('loryx.org/debug')) $grid->Add($dbg);
        switch($rs->save)
        {
        case 'no':
            // se � richiesto esplicitamente di non salvare i valori del form principale ...
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
            $rs->tag->Att('msg_del',nvl($rs->get_prp('opensymap.org/event/delete/msg'),utf8_encode("L'elemento verr� cancellato. Continuare?")))
                 ->Att('evn_del_itm',"if (!confirm(W(this).attr('msg_del'))) return; osy.event(W(this).closest('form'),'exec',{'osy':{'cmp':'{$rs->name}','evn':'delete'},'vars':W(args[0]).find({'osy_type':'nopost'})})");
        }
        if ($rs->mod)
        {
            $grid->Att('evn_reload',"lrx.ev_start(window,'reload')");
        }
        if ($rs->upd)
        {
            $rs->tag->Att('evn_save_itm',"var tr = W(args[0]), frm = W(this).closest('form'); osy.event(frm,'save',{'evn_ok':function(){osy.event(frm,'exec',{'osy':{'cmp':'{$rs->name}','evn':'save'},'vars':tr.find({'osy_type':'nopost'})})}})");
            //$rs->tag->Att('evn_save_itm',"var tr = W(args[0]), frm = W(this).closest('form'); osy.event(frm,'save',{'evn_ok':function(){console.log('{$rs->name}')}})");
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
        $tcmd = $grid->Add(new Tag('div'))
                    ->Att('align','center')
                    ->Att('style','position:relative')
                    ->Add(new Table)
                    ->Att('width','')
                    ->Att('style','font-size:10px;')
                    ->Att('cellspacing','5px;');
        
        $tcmd->Att('evn_set_page',"var el = W('input:first',this.parentNode).val(args[0]).closest('form').submit();");
        
        //if (is_array($_POST['_']['pky']) and strlen(implode('',$_POST['_']['pky'])))
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
        try
        {
            if ($cmd = $rs->get_prp('opensymap.org/db/query'))
                list($data,$par) = $db->getPage(array($cmd,$els_max,$pg_inp->value),
                                                     $_POST,
                                                     $_POST['_']['pky'],
                                                     $_POST['_']['prt']);
        }
        catch(Exception $e)
        {
            $grid->Add($e->getMessage());
        }
        $dbg->Add($db->last_sql());
        
        $pprec = max($par->page['cur']-1,1);
        $psuc = min($par->page['cur']+1,$par->page['max']);
        $tcmd->Cell('&laquo;')
            ->Att('style','font-size:10px; padding:2px 5px 2px 5px;')
            ->Att('class','link')
            ->Att('onclick',"osy.event(osy.par(this,'table'),'set_page','1')");
        $tcmd->Cell('&lt;')
            ->Att('style','font-size:10px; padding:2px 5px 2px 5px;')
            ->Att('class','link')
            ->Att('onclick',"osy.event(osy.par(this,'table'),'set_page','{$pprec}')");
        $tcmd->Head($pg_inp)
            ->Prp('nowrap')
            ->Add(Tag::mk('span',false,' / '.nvl($par->page['max'],1)));
        $tcmd->Cell('&gt;')
            ->Att('style','font-size:10px; padding:2px 5px 2px 5px;')
            ->Att('class','link')
            ->Att('onclick',"osy.event(osy.par(this,'table'),'set_page','{$psuc}')");
        $tcmd->Cell('&raquo;')
            ->Att('style','font-size:10px; padding:2px 5px 2px 5px;')
            ->Att('class','link')
            ->Att('onclick',"osy.event(osy.par(this,'table'),'set_page','{$par->page['max']}')");
        $tcmd->Par->Add(Tag::mk('div',array('style'=>'position:absolute; right:3px; font-size:8px; padding:2px;'),'# '.$par->group['elems']),1);
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
        if (!count($rs->cols)) $rs->cols = $data[0];
        if (!count($rs->cols)) 
        {
            $data = array(array('Errore'=>'<span style="color:silver;">Nessun dato trovato</span>'));
            $rs->cols = $data[0];
        }
        foreach($rs->cols as $k=>$ch)
        {
            if (is_object($ch))
            {
                if ($ch->get_prp('opensymap.org/hide')) continue;
                if (!($v=$ch->get_prp('opensymap.org/title'))) $v = $k;
                $th = $tbl->Head(new Tag('div'),1)->Add(NBSP.$v);
                if ($w = $ch->get_prp('opensymap.org/size/width')) $th->Att('style',"width:{$w};");
            }
            else
            {
                $th = $tbl->Head(new Tag('div'),1)->Add(NBSP.$k);
            }
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
            $tr->Att('evn_modified',"this.vvupd = true; osy.event(this,'focusout')")
               ->Att('evn_nomodified',"osy.event(this,'focusout')")
               ->Att('evn_focused',"clearTimeout(this.ttupd)")
               ->Att('evn_focusout',"if (!this.vvupd) return; this.vvupd=false; ".
                                    "this.ttupd = setTimeout(function(tr){".
                                            "osy.event((tr = W(tr)).closest({'osy_type':'datagrid'}),'save_itm',tr);".
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
            $ttl = '';
            if (is_object($ch))
            {
                if ($ch->get_prp('opensymap.org/hide')) continue;
                if (is_array($itm))
                {
                    if ($fld = $ch->get_prp('opensymap.org/db/field/title')) $ttl = $itm[$fld];
                    if (!($p = $ch->get_prp('opensymap.org/db/field'))) $p = $k;
                    if(count($rs->pky) and $rs->upd)
                    {
                        $name = "{$rs->name}[itm][{$row}][{$ch->name}]";
                        if (($ll = $ch->get_prp('opensymap.org/text/line'))>1)
                        {
                            $h1 = 22;
                            $h2 = $h1*$ll;
                            $val = new Tag('textarea');
                            $val->Att('name',$name)
                                ->Att('style',"xposition:absolute; height:{$h1}px;")
                                ->Att('onfocus',"W(this).css({'width':W(this).width()+'px','height':'{$h2}px','position':'absolute','z-Index':'10'})")
                                ->Att('onblur',"W(this).css({'height':'{$h1}px','z-Index':'1'})")
                                ->Add($itm[$p]);
                        }
                        else
                        {
                            $val = new TagInput($name,$itm[$p],'text');
                        }
                        $val->Att('osy_type','nopost');

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
            }
            else
            {
                $val = NBSP.$itm[$k];
            }
            $tbl->Cell(Tag::mk('div',false,$val))->Att('title',$ttl);
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
	private function mk_cols($rs,$cols)
	{
		$rs->fld = array();
		$rs->cols = array();
        foreach($cols as $nm => $ch)
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
	}
    public function evn_init($rs,$event)
    {
        if ($rs->_evn) return;
        
        $rs->pky = array();
        $rs->itm = $rs->value['itm'];
		
		$this->mk_cols($rs,$rs->get_clds('opensymap.org/column'));
        
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
                $tbl = $rs->get_prp('opensymap.org/db/table');
                if ($tbl{0}=='!') $tbl = substr($tbl,1);
                else $tbl = '[@'.$tbl.']';
                $db->delete($tbl,$wh);
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
                    $tbl = $rs->get_prp('opensymap.org/db/table');
                    if ($tbl{0}=='!') $tbl = substr($tbl,1);
                    else $tbl = '[@'.$tbl.']';
                    $db->update($tbl,$fl,$wh);
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
                    $tbl = $rs->get_prp('opensymap.org/db/table');
                    if ($tbl{0}=='!') $tbl = substr($tbl,1);
                    else $tbl = '[@'.$tbl.']';
                    //osy::alert(array($tbl,$fl));
                    $db->insert($tbl,$fl);
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