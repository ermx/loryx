<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

 class lrx_mod
{
    function make($rs,$p,$v)
    {
        // caricamento configurazione
        $fname = $rs->get_prp('loryx.org/config');  
        if (is_file($fname)) include($fname);
        $dbx = env::get_var('dbx');
        
        $rs->config = $rs->get_cld('config',5); 

        if (!$rs->config)
		{
			if (!$rs->get_prp('loryx.org/mod/autoinstall')) return;
			if (!$this->install($rs)) return;
		}
        // nome di default dell'elemento "database principale"
        $db = $rs->config->get_cld($dbx?$dbx->name:'dbx');
        // se precedentemente non era stato impostato il db .. ri effettua la rilettura
        if (!$dbx) env::reload_rs($db,$rs);
        env::set_var('dbx',$db);
        // se è stato impostato il db ... viene impostato come default
        if ($rs->get_prp('loryx.org/mod/start'))
        {
            $rs->set_prp('loryx.org/builder',$rs->get_prp('loryx.org/mod/start'));
            if ($dbg = $rs->get_prp('loryx.org/debug')) $rs->set_prp('loryx.org/debug',$dbg+1);
            $rs->make($rs);
        }
        return ;
    }
    function install($rs)
    {
        $rs->sync();
        $old_root = env::chdir();
        $x = $rs->get_cld('install',5);
        if (count($x->get_clds('opensymap.org/form')))
        {
            env::get_rs('osy@loryx.org')
                ->exe_inc();
            $x->set_prp('loryx.org/builder','osy_start');
            $x->exe();
            $ret = false;
        }
        else
        {
            $db  = env::get_var('dbx');
            // configurazione tabelle
            $rt = $rs->get_root();
            $rt->exe_inc();
            // impostazione config
            $rs->config = $rs->get_cld('config',3);
            //$db->rs2store($rs);
            env::set_var('dbx',$rs->config
                                  ->set_cld($db->name,$db->get_urn())
                                  ->sync(1));
            $db->rs2store($rs);
            foreach($x->get_clds('loryx.org/db/conf') as $c)
            {
                env::get_var('dbx')->rs2make($c);
            }
            $ret = true;
        }
        env::chdir($old_root);
        return $ret;
    }
}
class lrx_start
{
    function make($rs,$prp,$v='')
    {
        // impostazioni generali sulla url
        env::get_var('main',$rs);
        
        // database di riferimento
        //$db = env::set_var('dbx',$rs->get_cld('config/dbs'));
        $db = env::get_var('dbx');
        $vhs = env::set_var('vhs',$rs->get_cld('config/vhs',4));
        if (!count($vhs->get_clds())) // è stato configurato un virtual host?
        {
            $req = env::get_var('request');
            $vlocal = $vhs->set_cld(env::sid('tbl'))
                          ->set_prp('loryx.org/name',trim($req->server.$req->base,'/'));
            $posy = $vlocal->set_cld(env::sid('tbl'),'osy@loryx.org')
                           ->set_prp('loryx.org/path',$req->path)
                           ->sync();
            // -- no : istallazione manuale di osy
            $db->rs2store($vhs);
        }
        $this->run($vhs);
        
        FB::log('total time');
    }
    function run($rs)
    {
        $req = env::get_var('request');
		$sname = trim($req->server.$req->base,'/');
        if (is_array($rs->map[$sname]) and $host = array_shift($rs->map[$sname]))
        {
            $xapp = null;
            foreach($host->get_clds() as $app)
            {
                $path = trim($app->get_prp('loryx.org/path'),'/');
                if (strlen($path)) $path = "/{$path}/";
                else $path = '/';
                // viene presa la corrispondenza + lunga
                $xpath = $req->path;
                if ($xpath!='/') $xpath = $req->path.'/';
                if (substr($xpath,0,strlen($path))==$path)
                {
                    if ($xapp and strlen($xapp[0])>strlen($path)) continue;
                    $xapp = array($path,$app);
                }
            }
        }
        if (!$xapp) 
        {
            $xapp = array(1 =>env::get_rs('stl@loryx.org')->set_prp('loryx.org/debug',''));
        }
        else
        {
           
            $b = rtrim($xapp[0],'/');
            $req->base .= $b;
            $req->path = nvl(substr($req->path,strlen($b)),'/');
        }
        env::set_var('inst',$xapp[1]);
        $xapp[1]->exe();
    }
}
class lrx_db
{
    function make($rs,$p,$v)
    {
        $opath = env::chdir();
        require_once('./lib/mdb.'.$rs-> get_prp('loryx.org/db/conf/type').'.php');
        env::chdir($opath);
        $clname = 'mdb_'.$rs->get_prp('loryx.org/db/conf/type');
        $key = call_user_func_array("$clname::key",array($rs));
        
        $rs->cn = env::get_db($key);
        if (!$rs->cn)
        {
            $rs->cn = env::set_db($key,new $clname);
        }
    }
    function __call($f,$arg)
    {
        $rs = array_shift($arg);
        if (!$rs->cn) $rs->exe();
        $rs->cn->setRs($rs);
        return call_user_func_array(array($rs->cn,$f),$arg);
    }
}
class lrx_dbs
{
    function cc($rs)
    {
        return count($rs->get_clds());
    }
    function __call($f,$arg)
    {
        $get = 0;
        $rs = array_shift($arg);
        if (!$rs->dbs) $rs->dbs = $rs->get_clds('loryx.org/db');
        switch(strtolower($f))
        {
        case 'select':
        case 'getall':
        case 'getfirst':
        case 'getpage':
        case 'getlist':
        case 'getval':
            $get = 1;
        }
        foreach($rs->dbs as $db)
        {
            $rst = call_user_func_array(array($db,$f),$arg);
            if (!$i++) $rs = $rst;
            if ($get) break;
        }
        return $rs;
    }
}

class lrx_install 
{
    function make($rs,$p='',$v='')
    {
        if ($rs->hidden) return;
        $f = env::get_var('form');
        // nome tabella
        $tbl_base = $f->get_cld('txt_lrx_base');
        // tipo database scelto
		//var_dump($_POST);
        $db_typ = $f->get_cld($f->get_cld('pnl_db_typ')->value);
        $m = $db_typ->get_prp('loryx.org/function');
        if (empty($m)) 
        {
            if ($code = $db_typ->get_prp('loryx.org/code'))
            {
                // localizzazione oggetti in variabili
                foreach($f->get_clds() as $ch)
                {
                    $vn = $ch->name;
                    $$vn = $ch;
                }
                eval($code);
                return;
            }
            osy::alert('metodo non impostato');
        }
        if (!method_exists($this,$m)) osy::alert('metodo non supportato : '.$m);
        $this->$m($rs,$p);
    }
    function mk_mysql($evn,$p)
    {
        $rs = $evn->get_par();
        $mysqli = mysqli_init();
        set_time_limit(20);
        $cnt = env::get_var('page')->form->Att('osy_type','map fadein')
                            ->Add(new Tag('div'))
                            ->Att('osy_map','pnl_msg');
        try
        {
            if (@$mysqli->real_connect($rs->get_cld('inp_mysql_host')->value, 
                              $rs->get_cld('inp_mysql_usr')->value, 
                              $rs->get_cld('inp_mysql_pwd')->value))
            {
                $info = $mysqli->host_info.' : '.$mysqli->server_info;
                $mysqli->close();
                switch($evn->get_prp('loryx.org/name'))
                {
                case 'install':    
                    $cnf = $rs->get_root()->set_cld('config');
                    $dbs = $cnf->set_cld('dbx','loryx.org/dbs')->sync();

                    // impostazione database principale
                    $db  = $dbs->set_cld('db','loryx.org/db')
                            ->set_prp('loryx.org/db/conf/type',     'mysqli')
                            ->set_prp('loryx.org/db/conf/host',     $rs->get_cld('inp_mysql_host')->value)
                            ->set_prp('loryx.org/db/conf/user',     $rs->get_cld('inp_mysql_usr')->value)
                            ->set_prp('loryx.org/db/conf/password', $rs->get_cld('inp_mysql_pwd')->value)
                            ->set_prp('loryx.org/db/conf/dbname',   $rs->get_cld('inp_mysql_dbname')->value)
                            ->set_prp('loryx.org/db/table/prefix',  $rs->get_cld('txt_lrx_base')->value)
                            ->sync();
                    // creazione tabelle dell'applicazione di base
                    $dbs->rs2make($rs->get_cld('db'));
                    
                    $dbs->rs2store($cnf);
                    
                    // salvataggio impostazioni su file
                    $opath = env::chdir();
                    env::store_rs($cnf,$rs->get_root()->get_prp('loryx.org/config'),array('all'=>1));
                    env::chdir($opath);
                    
                    osy::code("osy.trigger('form:first','submit')");
                    break;
                default:
                    $tbl = $cnt->Add(new Tag('div'))
                               ->Att('class','message')
                               ->Add(new Table);
                    $tbl->Head('Connessione');
                    $tbl->Row();
                    $tbl->Cell($info);
                    $tbl->Row();
					$fp = @fopen($rs->get_root()->get_prp('loryx.org/config'),'w');
					// se il filesystem è scrivibile ...
					if ($fp)
					{
						$tbl->Cell(new TagButton('Installa',"osy.trigger(this,'exec',{'osy':{'evn':'install'}})"))
							->Att('style','text-align:center; padding-top:10px;');
					}
					else
					{
						// ci sarà il download del file
						$tbl->Cell(new TagButton('Installa',"osy.frm(this,{'osy':{'evn':'install'},'frm':this.form})"))
							->Att('style','text-align:center; padding-top:10px;');
					}
                }
            }
            else
            {
                $cnt->Add(new Tag('div'))
                    ->Att('class','alert')
                    ->Add('Connessione non trovata');
            }
        }
        catch(exCode $e) { throw $e;}
        catch(exAlert $e){ throw $e;}
        catch(Exception $e)
        {
            $cnt->Add(new Tag('div'))
                ->Att('class','alert')
                ->Add($e->getMessage());
            $cnt->Add(new TagButton('Riprova',"osy.trigger(this,'exec',{'osy':{'evn':'install'}})"));
        }
    }
    function mk_pgsql($rs)
    {
    }
    function mk_sqlite($rs)
    {
    }
    function setValue($v)
    {
    }
}


?>
