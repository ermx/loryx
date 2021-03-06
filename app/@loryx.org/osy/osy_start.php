<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */
function q($a,$srv=array())
{
    //return $a;
    if (!is_array($srv)) $srv = explode(',',$srv);
    foreach($srv as $s)
    {
        $a[$s] = $_SERVER[$s];
    }
    return http_build_query($a);
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
    private function qry_osy($rs)
    {
        $context = stream_context_create(array( 
            'http' => array( 
              'method'  => 'POST', 
              'header'  => "Content-type: application/x-www-form-urlencoded\r\n", 
              'content' => q(Array('OSY_NAME'=>$rs->get_prp('loryx.org/id')),'SERVER_ADDR,SERVER_NAME'), 
              'timeout' => 5, 
            ), 
          )); 
        echo file_get_contents('http://www.opensymap.org/osy.php', false, $context);
        return;
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
		$scr = $page->AddScript('');
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
                $scr = $page->AddScript();
                $scr->Add("W(function(){osy.win(window,{'osy':{'app':'@'}})})");
                if ($rs->config and ($sty = $rs->config->get_prp('opensymap.org/style')))
                {
                    $page->Part('HEAD')->Add(new Tag('style'))
                                       ->Add(".dsk, .dsk body {{$sty}");
                }
                break;
            case '?':
                return $this->qry_osy($rs);
            case '@':
                // MENU'
                // se l'utente non � loggatto allora parte la form di default
                // .. se la form di default non � impostata ... parte il login standard
                $scr->Add('window.dsk = frameElement.dsk; window.box = frameElement.box; window.$ = dsk.$; window.W=dsk.W; window.osy = dsk.osy;');
				$scr->Add("W(window).bind('show.osy',function(){W('input:text:visible:first',document.body).focus()});");
                $usr = $this->aut($rs,$oaut->value);
                if (!$usr['id'])
                {
                    $scr->Add("W(function(){osy.trigger(frameElement,'#cmd','center')});");
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
                    $scr->Add("W(function(){osy.frm(dsk,{'osy':{'app':'?'}})})");
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
							//var_dump( $m->get_prp('opensymap.org/form'));
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
                $scr->Add('window.dsk = frameElement.dsk; window.box = frameElement.box; window.$ = dsk.$; window.W=window.$, window.osy = dsk.osy;');
                //$page->form->Att('ev_save',"this.upd?osy.exe(args[0],{'osy':{'evn':'save'},'form':this}):lrx.ev_start(args[0],'ok');");
                // tramite questa applicazione � possibile modificare l'interfaccia
				$app = $rs->get_cld('config/app/'.$oapp->value);

                // esecuzione eventuale codice di inizializzazione
				$page->Add($app->exe()->tag,1);
				$frm = $app->get_cld($ofrm->value);
				
                if (!$frm) throw new Exception('Form non trovata : '.$ofrm->value);
                env::set_var('form',$frm);
                $scr->Add("osy.trigger(frameElement,'#cmd','init,reload,close');");
                $sdk = env::get_rs('sdk@opensymap.org');
                // se l'utente ha accesso all'applicazione osdk@opensymap.org e pu� visualizzare l'icona di "make app" 
                // a meno che l'sdk non sia l'app corrente ed esso non sia nel DB
                $apx =  $rs->get_cld('config/app');
                if ($app->get_styp()!=$sdk->get_urn() or ($sdk->get_prp('loryx.org/store')!='no'))
                foreach($usr['prs']['opensymap.org/app'] as $kapp) 
                {
                    if ($apx->get_cld($kapp)->get_styp() != $sdk->get_urn() ) continue;
                    $sdk_app = $kapp;
                    break;
                }
                
                if ($sdk_app)
                {
                    $sdk_frm  = $app->get_typ()->get_cld($ofrm->value);
                    $scr->Add("osy.trigger(frameElement,'#cmd',{'o':function(){osy.win(window,{'osy':{'app':'{$sdk_app}','frm':'frm_frm'},
                              'pky':{'hdn_sys':'{$sdk_frm->sys}','hdn_base':'{$sdk_frm->base}',txt_name:'{$sdk_frm->name}'}})}});");
                }
				$scr->Add("W(window).bind('show.osy',function(){W('input:text:visible:first',document.body).focus()});");
                
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
                ->Att('osy_type','exe')
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
