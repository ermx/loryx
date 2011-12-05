<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

 class osy_view
{
    public function disabled($rs)
    {
        if (!($cmd = $rs->get_prp('opensymap.org/test'))) return false;
        foreach($rs->get_par()->get_clds() as $nm => $ch) $$nm = $ch;
        if(eval($cmd)) return false;
        return true;
    }
    public function make($rs, $prp=null, $arg=null)
    {
        $page = env::get_var('page');
        $page->setTitle($rs->get_prp('opensymap.org/title'));
        $tag = new Tag('div');
        $w = intval($rs->get_prp('opensymap.org/size/width'));
        $tag->Add(new Tag('div'))
            ->Att('style',"width: {$w}px; height:1px;");
        $dg = new osy_grid;
        //FB::log(array($rs->dump()),$rs->get_urn());
        $rs->set_prp('opensymap.org/event/save','no');
        $dg->make($rs);
        $tag->Add(Tag::mk('div',array('style'=>'padding:3px')))
            ->Add($rs->tag);
        $rs->tag = $tag;
    }
}

class osy_form
{
    public function disabled($rs)
    {
        if (!($cmd = $rs->get_prp('opensymap.org/test'))) return false;
        foreach($rs->get_par()->get_clds() as $nm => $ch) $$nm = $ch;
        if(eval($cmd)) return false;
        return true;
    }
    public function make($rs, $prp=null, $arg=null)
    {
	    $page = env::get_var('page');
        $page->setTitle($rs->get_prp('opensymap.org/title'));
        $page->form->Att('evn_save',"osy.event(this,'exec',{'osy':{'evn':'save'}},args[0]);");
        $rs->cmp = array();
        $rs->prt = array();
        $rs->pky = array();
        $rs->fld = array();
        
        $rs->tag_cmp = array();
        $rs->tag_prt = array();
        $rs->tag_pky = array();
        $rs->tag = new Tag('div');
		
        $this->init_cmp($rs);
        $this->tree($rs);

        if(!$this->mk_evn($rs,$prp)) return;
        if ($rs->evn->stop) return;
		
        $pnl = new osy_panel();
        $pnl->make($rs);
        $custom = ($rs->get_prp('opensymap.org/type')=='custom' ||
                  $rs->get_prp('opensymap.org/type')=='wizard');
        //FB::log($custom,'custom');
        if (!$custom)
        {
            $cmd = $rs->tag->Add(new Tag('div'))
                           ->Add(new Table)
                           ->Att('cellspacing','2px');
            if (strlen($rs->pkstr))
            {
                $cmd->Cell(new TagButton('elimina',"osy.exe(this,{'osy':{'evn':'delete'},'form':this.form})"))
                    ->Last->Att('class','bt_frm_del')
                          ->Att('exe_msg',"Eliminare l'elemento?")
                          ->Att('evn_ok',"osy.event(box,'close')");
            }
            $dcmd = $cmd->Cell(new Table)
                        ->Att('width','100%')
                        ->Att('style','text-align:center')
                        ->Last
                        ->Att('width','')
                        ->Att('align','center');
            foreach($rs->get_clds('opensymap.org/command') as $bt)
            {
                $dcmd->Cell($bt->exe()->tag);
            }
            $cmd->Cell(new TagButton('salva',"osy.event(this.form,'save',this)"))
                ->Last->Att('class','bt_frm_cnf')
                      ->Att('evn_ok',"osy.msg('operazione effettuata',document.body)");
            $cmd->Cell(new TagButton('chiudi',"osy.event(box,'close')"))
                ->Last->Att('class','bt_frm_cls');
        }

        // FB::log('end form');
        return ;
    }
    private function mk_evn($rs,$evn)
    {
        $page = env::get_var('page');
        $rs->evn = $evn;
        $__pky = $page->vars;
        if (is_array($_POST['_']['pky'])) foreach($_POST['_']['pky'] as $k=>$v)
        {
            $rs->tag_pky[$k] = $__pky->Add(new TagInput("_[pky][{$k}]",$v))->Att('osy_name',$k);
        }
        $__prt = $page->vars;
        if (is_array($_POST['_']['prt'])) foreach($_POST['_']['prt'] as $k=>$v)
        {
            $rs->tag_prt[$k] = $__prt->Add(new TagInput("_[prt][{$k}]",$v))->Att('osy_name',$k);
        }
        // chiave compatta elemento corrente
        $rs->pkstr = '';
        foreach($rs->tag_pky as $o) $rs->pkstr.= trim($o->value);
		
        $db = env::get_var('db');
        $event = $rs->evn->value;
        $arg   = $rs->evn->arg;

		// lettura volori sul DB
		$wh = array();
		foreach($rs->pky as $k=>$ch)
		{
			if ($rs->tag_pky[$k])
			{
				$val = $rs->tag_pky[$k]->value;
			}
			else
			{
				$val = $ch->get_prp('loryx.org/value');
			}
			$wh[] = $ch->get_prp('opensymap.org/db/field').'='.$db->str($val);
		}
		$qry = array_shift($rs->get_clds('opensymap.org/db/query'));
		
		if ($qry)
		{
			$rs->data = $db->getFirst("select x.* from (".$qry->get_prp('loryx.org/value').
								 ") x where ".implode(' and ',$wh),$_POST,$_POST['_']['pky'],$_POST['_']['prt']);
		}
		else if ($tbl = $rs->get_prp('opensymap.org/db/table'))
		{
			$cmd = "select * from [@".$tbl."] where ".implode(' and ',$wh);
			$rs->data = $db->getFirst($cmd);
		}
        // verifica del valore dei componenti
        foreach($rs->cmp as $ch)
        {
			if (!isset($_POST[$ch->name])) 
			{
				$ch->setValue($rs->data[nvl($ch->get_prp('opensymap.org/db/field/load'),$ch->get_prp('opensymap.org/db/field'))],$rs->data);
			}
            else $ch->check($rs,$event);
        }

        if (!$event) return true;
        $ev_before = array();
        $ev_start  = array();
        $ev_after  = array();
        $evn = array();
        if ($rs->map[$event]) $evn = array_merge($evn,$rs->map[$event]);
        $avn = $evn;
        foreach($rs->cmp as $ch)
        {
            if ($ch->map[$event])
            {
                $evn = array_merge($evn,$ch->map[$event]);
            }
        }

        foreach($evn as $ch)
        {
            if ($ch->get_styp()!='opensymap.org/event')continue;
            switch($ch->get_prp('opensymap.org/type'))
            {
            case 'before':
                $ev_before[] = $ch;
                break;
            case 'after':
                $ev_after[] = $ch;
                break;
            case '':
                $ev_start[] = $ch;
                break;
            }   
        }
        
        $cnt = $rs->tag->add(new Tag(''));
		
        // exec before
        foreach($ev_before as $e)
        {
            $cnt->Add($e->exe()->tag);
        }
                
        // exec start
        if (count($ev_start))
        foreach($ev_start as $e)
        {
            $ev_found ++;
            $cnt->Add($e->exe()->tag);
            if ($e->get_prp('opensymap.org/event/stop'))
            {
                $rs->evn->stop = true;
            }
        }
        else
        {
            // gestione eventi standard
            switch($event)
            {
            case 'load':
                $rs->evn->Att('value','');
                $ev_found++;
                $rs->evn->stop = false;
                foreach($rs->cmp as $k=>$ch)
                {
                    $ch->setValue($rs->data[nvl($ch->get_prp('opensymap.org/db/field/load'),$ch->get_prp('opensymap.org/db/field'))],$rs->data);
                }
                break;
            case 'insert':
                $rs->evn->Att('value','');
                $ev_found++;
                $rs->evn->stop = false;
                foreach($rs->cmp as $k=>$ch)
                {
                    if ($val = $ch->get_prp('opensymap.org/db/insert'))
					{
						$ch->setValue($db->merge ($val,
												$_POST,
												$_POST['_']['pky'],
												$_POST['_']['prt']));
					}
                }
					FB::log($val,$ch->get_urn());
                break;
            case 'delete':
                $rs->evn->stop = true;
                $ev_found++;
                $wh = array();
                foreach($rs->pky as $k=>$ch)
                {
                    $wh[$ch->get_prp('opensymap.org/db/field')] = $ch->value;
                }
                //return new TagEvnError($wh);
                //$db->noexe();
                $db->delete('[@'.$rs->get_prp('opensymap.org/db/table').']',$wh);
				$code = $cnt->Add(new Tag('code'));
                $page->form->Att('osy_type','exe');
				$code->Add("osy.event(this,'ok')");

                break;
            case 'save':
                $rs->evn->stop = true;
                $ev_found++;
                $wh = array();
                
                if (strlen($rs->pkstr))
                {
                    // update
                    $wh = array();
                    $fl = array();
                    $pk = array();
					$pkn = array();
                    foreach($rs->pky as $k=>$ch)
                    {
                        if ($rs->tag_pky[$k])
                        {
                            $val = $rs->tag_pky[$k]->value;
                        }
                        else
                        {
                            $val = $ch->value;
                        }
                        $wh[$ch->get_prp('opensymap.org/db/field')]= $val;
                        $pkn[$ch->name] = $pk[$ch->get_prp('opensymap.org/db/field')] = $val;
                    }
                    foreach($rs->fld as $f=>$ch)
                    {
                        if ($ch->get_prp('opensymap.org/db/field/extern')) continue;
                        $fl[$f] = $ch->getValue();
                        if ($ch->get_prp('opensymap.org/db/pk'))
                        {
                            if (empty($fl[$f]))
                            {
                                // se non può essere vuota
                                if (!$ch->get_prp('opensymap.org/db/empty'))
                                {
                                    // se esiste un comando per il calcolo della chiave
                                    if ($cmd = $ch->get_prp('opensymap.org/db/sid'))
                                    {
                                        $fl[$f] = env::sid(explode(',',$cmd));
                                    }
                                    if (empty($fl[$f]))
                                    {
                                        $ttl = $ch->get_prp('opensymap.org/label');
                                        return osy::err("{$ttl} : Chiave non impostata. ".$ch);
                                    }
                                    //$ttl = nvl($ch->get_prp('opensymap.org/label'),$ch->get_urn());
                                    //return new TagEvnError("{$ttl} : Chiave non impostata.");
                                }
                            }
                            $pkn[$ch->name] = $pk[$f] = $fl[$f];
                        }
                    }
                    //FB::log(array($fl,$_POST,$wh));
                    //$db->noexe();
                    $db->update('[@'.$rs->get_prp('opensymap.org/db/table').']',$fl,$wh);
                }
                else
                {
                    // insert
                    $fl = array();
                    $pk = array();
					$pkn = array();
                    foreach($rs->fld as $f=>$ch)
                    {
                        if ($ch->get_prp('opensymap.org/db/field/extern')) continue;
                        //if (false)//$ch->has_prp('opensymap.org/db/par'))
                        //{
                            //$fl[$f] = $db->merge($ch->get_prp('opensymap.org/db/par'),
                            //                                 $_POST,$_POST['_']['pky'],$_POST['_']['prt']);
                        //}
                        //else
                        //{
                            $fl[$f] = $ch->getValue();
                        //}
                        if ($ch->get_prp('opensymap.org/db/pk'))
                        {
                            if ( empty($fl[$f]))
                            {
                                // se non può essere vuota
                                if (!$ch->get_prp('opensymap.org/db/empty'))
                                {
                                    // se esiste un comando per il calcolo della chiave
                                    if ($cmd = $ch->get_prp('opensymap.org/db/sid'))
                                    {
                                        $fl[$f] = env::sid(explode(',',$cmd));
                                    }
                                    if (empty($fl[$f]))
                                    {
                                        $ttl = $ch->get_prp('opensymap.org/label');
                                        osy::alert("{$ttl} : Chiave non impostata. ".$ch);
                                    }
                                }
                            }
                            $pkn[$ch->name] = $pk[$f] = $fl[$f];
                        }
                        else
                        {
                            if ( empty($fl[$f]))
                            {
                                if ($ch->get_prp('opensymap.org/db/noempty'))
                                {
                                    $ttl = $ch->get_prp('opensymap.org/label');
                                    return new TagEvnError("{$ttl} : Campo obbligatorio.");
                                }
                            }
                        }
                    }
                    //$db->noexe();
                    $db->insert('[@'.$rs->get_prp('opensymap.org/db/table').']',$fl);
                } // if update or insert
                $rs->pk = $pk;
                $rs->pkn = $pkn;
				$code = $cnt->Add(new Tag('code'));
                $page->form->Att('osy_type','exe');
				foreach($pkn as $n=>$v)
				{
					$code->Att('val_'.$n,$v)
						 ->Add("osy.get_input(this,'_[pky][{$n}]').val(W(args[0]).attr('val_{$n}'));")
						 ->Add("osy.get_input(this,'{$n}').val(W(args[0]).attr('val_{$n}'));");
				}
				$code->Add("osy.event(this,'ok')");
            }
        }
        
        if (!count($ev_before) and !count($ev_after) and !$ev_found)
        {
            // evento sconosciuto
            var_dump("Evento non implementato : ".$event);
            exit;
        }
        // exec after
        foreach($ev_after as $e)
        {
            $cnt->Add($e->exe()->tag);
            //FB::log(array($e->dump()));
        }
        return true;
    }

    public function init_cmp($rs)
    {
        //FB::log('tree','osy_form');
        // lista dei figli del pannello corrente
        $rs->pnl = array('childs'=>array());
        foreach($rs->get_clds() as $k=>$ch)
        {
            $cmd = $ch->get_prp('opensymap.org/test');
            // se non è un componente ... passapress
            if (!$ch->get_prp('opensymap.org/type/is_cmp')) continue;
            if (false)//$ch->is('opensymap.org/const'))
            {
                $ch->value = $ch->get_prp('loryx.org/value');
            }
            else
            {
                if ($cmd = $ch->get_prp('loryx.org/value/eval'))
                {
                    $val = eval($cmd);
                }
                else
                {
					$b = $_POST[$ch->name];
                    $val = nvl($_POST[$ch->name],nvl($ch->get_prp('loryx.org/value'),$ch->value));
                }
                $val = $ch->setValue($val);
            }
            $_POST[$ch->name] = $val;
            $rs->cmp[$ch->name] = $ch;
        }
    }
    private function tree($rs)
    {
		//var_dump($rs->dump());
        foreach($rs->cmp as $ch)
        {
            if ($ch->get_prp('opensymap.org/db/pk'))
            {
                $rs->pky[$ch->name] = $ch;
            }
            if ($f = $ch->get_prp('opensymap.org/db/field'))
            {
                $rs->fld[$f] = $ch;
            }
			if ($ch->get_prp('opensymap.org/display')=='none') continue;
            $par = $ch->get_prp('loryx.org/parent');
            
            // non è stato specificato un pannello genitore ..
            // viene associato al pannello corrente
            if (!$par)
            {
                $rs->pnl['childs'][] = $ch;
                $ch->pnl['parent'] = $rs;
                continue;
            }
            // il pannello specificato non è presente ...
            // idem come sopra
            $cch = $rs->get_cld($par,1);
            if (!$cch)
            {
                $rs->pnl['childs'][] = $ch;
                $ch->pnl['parent'] = $rs;
                continue;
            }
            // il pannello specificato è stato trovato ...
            // viene configurato
            if (!$cch->pnl['childs'])
            {
                $cch->pnl['childs'] = array();
            }
            $cch->pnl['childs'][] = $ch;
            $ch->pnl['parent'] = $cch;
        }
    }
}

?>