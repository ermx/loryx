<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

 class ExCode extends Exception {};
class ExAlert extends Exception {};

class osy
{
    static function code($c) {if (!is_string($c)) $c=print_r($c,1); throw new ExCode($c);}
    static function alert($c) {if (!is_string($c)) $c=print_r($c,1); throw new ExAlert($c);}
    static function err($c) {if (!is_string($c)) $c=print_r($c,1); throw new Exception($c);}
}

class osy_start
{
    private function decript(&$ar,$nm)
    {
        if (!nvl(env()->usr&&env()->usr->key, env()->cript_key)) return;
        
        if (is_array($ar))
        foreach($ar as $k=>$v)
        {
            $this->decript($ar[$k],"{$nm}[{$k}]");
        }
        else
        {
            if ($nm == '__osy[auth]') return;
            $ar = osy_decript($ar);
        }
    }
    
    public function make($rs, $prp=null, $arg=null)
    {
        $page = env::get_var('page',new osyPage());
        $req = env::get_var('request');
        env::if_read_file_exit($req->path);
        
        $page->form = $page->addBody(new Tag('form'))
                           ->Att('method','post')
                           ->Att('style','float:left');
        $page->resp = $page->addBody(new Tag('div'))
                           ->Att('style','display:none');
        $page->vars = $page->form->Add(new Tag('div'))
                        ->Att('style','display:none');
        $__osy = $page->vars;
        $oaut = $__osy->Add(new TagInputPost('_[osy][aut]'));
        $oapp = $__osy->Add(new TagInputPost('_[osy][app]'));
        $ofrm = $__osy->Add(new TagInputPost('_[osy][frm]'));
        $ocmp = $__osy->Add(new TagInputPost('_[osy][cmp]'));
        $oevn = $__osy->Add(new TagInputPost('_[osy][evn]'));
        $oarg = $__osy->Add(new TagInputPost('_[osy][arg]'));
        $osta = $__osy->Add(new TagInputPost('_[osy][sta]'));
        
        $db = env::set_var('db',env::get_var('dbx'));
        
        $page->setTitle('Opensymap.org');
        //FB::log($rs->typ->trl,$rs->get_urn());
        $inst = env::get_var('inst');
       //var_dump($rs->get_prp('opensymap.org/menu/title'));
        $page->AddCss($req->base.'/src/lib.main.css');
        //FB::log($req);
        try{
            switch($oapp->value)
            {
            case '':
                // DESKTOP
                //$page->AddScript($page->base_uri.'src/lib.base64.js');
                //$page->AddScript($page->base_uri.'src/lib.cript.js');
                //$page->AddScript($page->base_uri.'src/lib.tag.js');
                
                $page->AddScript($req->base.'/src/jquery-1.4.4.js');
                //$page->AddScript($req->base.'/src/jquery-1.5.1.min.js');
                //$page->AddScript($req->base.'/src/jquery-ui-1.8.14.custom.min.js');
                $page->AddScript($req->base.'/src/jqscript.js');
                $page->part('HTML')->Att('class',"dsk");
                $page->part('BODY')->Att('onload',"osy.win(window,{'osy':{'app':'@'}});");
                if ($rs->config and ($sty = $rs->config->get_prp('opensymap.org/style')))
                {
                    $page->Part('HEAD')->Add(new Tag('style'))
                                       ->Add(".dsk, .dsk body {{$sty}");
                }
                break;
            case '@':
                // MENU'
                // se l'utente non è loggatto allora parte la form di default
                // .. se la form di default non è impostata ... parte il login standard
                $scr = $page->AddScript('');
                $scr->Add('window.dsk = frameElement.dsk; window.box = frameElement.box; window.$ = dsk.$; window.W=dsk.W; window.osy = dsk.osy;');
                $usr = $this->aut($rs,$oaut->value);
                if (!$usr['id'])
                {
                    $scr->Add("osy.trigger(frameElement,'#cmd','center');");
                    //$page->AddScript()->Add("W(frameElement,'#cmd','rm','close');");
                    $frm_name = nvl($ofrm->value,$rs->get_prp('opensymap.org/form/default'));
                    $frm = $rs->get_cld($frm_name);
                    //FB::log($frm->get_urn(),'form');
                    //FB::log(array($frm->dump()));
                    //if (!$frm) $frm = $rs->get_cld('login');
                    if (!$frm)
                    {
                        $page->form->Add(new Tag('pre'))->Add('FORM non trovata : '.$rs->get_prp('opensymap.org/form/default').NL.NL.$rs->dump());
                        break;
                    }
                    //var_dump($rs->dump());
                    env::set_var('form',$frm);
                    //FB::log($frm->get_urn(),'form');
                    //$page->form->Add(new Tag('pre'))->Add($frm->dump());
                    $frm->evn = $oevn;
                    
                    $page->form->Add($frm->exe($oevn)->tag);
                }
                else
                {
                    $scr->Add("osy.trigger(frameElement,'#cmd','position,everyfocus',{'x':10,'y':10});");
                    $page->setTitle($rs->get_prp('opensymap.org/menu/title'));
					$apx = $rs->get_cld('config/app');
					
					foreach(nvl($usr['prs']['opensymap.org/app'],array()) as $a)
					{
						$app = $apx->get_cld($a);
						$page->form->Add(new Tag('div'))
								   ->Att('style','margin:5px; border:1px solid silver; padding:5px; background-color:#ceddef; white-space:pre;')
								   ->Add(nvl($app->get_prp('opensymap.org/title'),$a));
						foreach($app->get_clds('opensymap.org/menu') as $m)
						{
							$b = $m->get_prp('opensymap.org/form');
							$page->form->Add(new Tag('div'))
									   ->Att('style','margin:3px 3px 0px 15px; padding:3px; cursor:pointer;')
									   ->Att('onclick',"osy.win(this,{'osy':{'app':'{$a}','frm':'{$b}'},'pos':'right'})")
									   ->Add($m->get_prp('opensymap.org/title'));
						}
					}
                }
                break;
            default : 

                $usr=$this->aut($rs,$oaut->value);
                if (!$usr['id'])
                {
                    throw new Exception('Accesso negato');
                }
                $page->AddScript('')->Add('window.dsk = frameElement.dsk; window.box = frameElement.box; window.$ = dsk.$; window.W=window.$, window.osy = dsk.osy;');
                //$page->form->Att('ev_save',"this.upd?osy.exe(args[0],{'osy':{'evn':'save'},'form':this}):lrx.ev_start(args[0],'ok');");
                // tramite questa applicazione è possibile modificare l'interfaccia
				$app = $rs->get_cld('config/app/'.$oapp->value);
				
				$frm = $app->get_cld($ofrm->value);
				
                if (!$frm) throw new Exception('Form c()non trovata : '.$ofrm->value);
                env::set_var('form',$frm);
                $page->addScript('')->Add("osy.trigger(frameElement,'#cmd','init,reload,close')");
                
                if (!$ocmp->value)
                {
                    $frm->evn = $oevn;
                    $page->form->Add($frm->exe($oevn)->tag);
                }
                else
                {
                    $frm->init_cmp();
                    $cmp = $frm->get_cld($ocmp->value);
                    $page->form->Add($cmp->exe($oevn,'data')->tag);
                }
                break;
            }
        } 
        catch(ExCode $e)
        {
            $page->form
                ->Att('osy_type','command')
                ->Add(new Tag('code'))
                ->Add($e->getMessage());
        }
        catch(exAlert $e)
        {
            echo $e->getMessage();
            exit;
        }
        env::set_ctype('text/html');
        switch ($osta->value)
        {
        case 'form': 
            echo $page->form;
            break;
        default:
            echo $page;
        }
        return;
    }
    private function aut($rs, $val)
    {
        // verifica esistenza codice autenticazione
        // gestore utenti
        //$cman = $rs->get_prp('opensymap.org/usrman');
        $cman = 'osy_user_manager';
        $rs->uman = new $cman($rs);
        $rs->usr = $rs->uman->check($val);
        return $rs->usr;
    }
    
}

?>