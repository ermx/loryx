<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

class TraceEx extends Exception {};

class rs
{
    public $sys;
    public $base;
    public $name;
    public $par;
    public $trl;

    public function __construct($sys,$base,$name)
    {
        $this->sys = $sys;
        $this->base = $base;
        $this->name = $name;
        $this->prp = array();
        $this->cld = array();
        $this->map = array();
        $this->lck = false;
        $this->bmk = false;
        $this->trl = array();
    }
    function lock()
    {
        $this->lck = true;
        foreach($this->cld as $c) $c->lock($l);
    }
    function get_styp()
    {
        return nvl($this->prp['loryx.org/type'],'');
    }
    function get_urn($nmchild='',$compact=true)
    {
        if ($nmchild) $nmchild = "/{$nmchild}";
		$urn = "{$this->base}/{$this->name}{$nmchild}@{$this->sys}";
		if ($compact) return trim(trim($urn,'/'),'@');
		 return $urn;
    }
    function get_path($sub='')
    {
        if ($this->base) $base = '/'.trim($this->base,'/');
        $path = "@{$this->sys}{$base}/{$this->name}";
        if ($sub) $path .= '/'.$sub;
        return str_replace('/', DIRECTORY_SEPARATOR,$path);
    }
    function get_root()
    {
        if (!$this->par) return $this;
        return $this->par->get_root();
    }
    function get_typ()
    {
        return $this->typ;
    }
	function exe_prp($prp,$env=array(),$path_root='')
	{
        $str = $this->get_prp($prp);
        $len = strlen(trim($str));
        if (!$len) return;
        foreach($env as $n=>$v) $$n = $v;
        if (!$dbx) $dbx = env::get_var('dbx');
        // per evitare esplicitamente il merge ...
        if ($this->get_prp('loryx.org/db/merge')!='no' and $dbx) $str = $dbx->merge($str,nvl($_POST,array()),
                                                                                nvl($_POST['_']['pky'],array()),
                                                                                nvl($_POST['_']['prt'],array()));
		if (!empty($path_root))
		{
			$fname = $path_root.
						$this->get_path('prp').'.'.
						base64_encode($prp).'.php';
			
            //FB::log($fname, 'exe '.$prp);
			
			$dname = dirname($fname);
			if (!is_dir($dname)) mkdir($dname,0777,true);
			
            if ($this->get_prp('loryx.org/exe/rewrite'))
            {
                $fp = @fopen($fname, "w");
            }
            else
            {
                $fp = @fopen($fname, "x");
            }
			if ($fp)
			{
				fwrite($fp,"<?php ".NL.$str.NL." ?>");
				fclose($fp);
			}
            else
            {
                //FB::log(array($str));
                return eval($str);
            }
			return include($fname);
		}
		else
		{
			return eval($str);
		}
	}
    function sync($resync = 0)
    {
        //if ($this->synked) return $this;
        //$this->synked = true;
        if (!$resync and $this->typ) return $this;
        if ($this->get_styp() and $this->get_urn()!= $this->get_styp())
                $this->typ = env::get_rs($this->get_styp());
        //FB::log($this->get_urn().(count($this->cld)?' >> ':' > ').$this->get_styp(),'sync');
        if ($this->get_prp('loryx.org/sync')=='nochild') return;
        if ($this->typ and $this->get_prp('loryx.org/sync')=='copychild')
        {
            //var_dump('copy '.$this->typ->get_urn());
            // eventuale copia dei figli del tipo
            foreach($this->typ->get_clds() as $h=>$v) 
            {
                if (!$v->get_styp()) continue;
                $el = $this->copy($v,0);
            }
        };
        foreach($this->cld as $ch) $ch->sync();

        return $this;
    }
    function is_a($t)
    {
        $ti = $this->get_urn();
        if ($t==$ti) return true;
        if ($this->typ and $this->typ!=$this) return $this->typ->is_a($t);
        return false;
    }
    function set_prp($p,$v='',$overwrite=1)
    {
        if (is_array($p))
        {
            foreach($p as $pp=>$pa) $this->set_prp($pp,$pa);
            return $this;
        }
        // se l'elemento è lockato
		if ($this->lck)  return $this;
        switch($p)
        {
        case 'loryx.org/name':
            if ($this->par) $this->par->map[$v][] = $this;
            break;
        }
        if ($overwrite  or !isset($this->prp[$p])) $this->prp[$p] = $v;
		return $this;
    }
    function get_prp($p,$onlySelf = 0)
    {
        $lng = env::get_lng();

        switch($onlySelf)
        {
        case 0:
            if (isset($this->prp[$p])) return nvl($this->trl[$p][$lng],$this->prp[$p]);
            if ($this->typ) return $this->typ->get_prp($p);
            break;
        case 1:
            if (isset($this->prp[$p])) return nvl($this->trl[$p][$lng],$this->prp[$p]);
            break;
        case 2:
            // only type
            if ($this->typ) return $this->typ->get_prp($p);
        }
        return '';
    }
    function clean()
    {
        unset($this->prp);
        unset($this->cld);
        unset($this->map);
        $this->prp = array();
        $this->cld = array();
        $this->map = array();
    }
    function get_par()
    {
        return $this->par;
    }
    function set_par($el)
    {
        $lname = $this->get_prp('loryx.org/name');
        if ($this->par  && ($el->cld != $this->par->cld))
        {
            unset($this->par->cld[$this->name]);
            if ($lname and $this->par->map[$lname]) $this->par->map[$lname] = array_diff($this->par->map[$lname],array($this));
        }
        $this->par = $el;
        $this->sys = $el->sys;
        $this->base = "{$el->base}/{$el->name}";
        if ($lname)
        {
            if (!$this->par->map[$lname])$this->par->map[$lname] = array();
            if (!in_array($this,$this->par->map[$lname]))
            {
                $this->par->map[$lname][] = $this;
            }
        }
        foreach($this->cld as $n=>$c) $c->set_par($this);
        return $this;
    }
    function set_cld($name,$arg = array())
    {
        $overwrite = 0;
        if (is_object($name))
        {
            $c = $name;
            $overwrite = $arg;
            $arg = array();
        }
        else $c = new rs($this->sys,$this->base.'/'.$this->name,$name);
        if (is_string($arg)) $arg = array('loryx.org/type'=>$arg);
        if (is_array($arg)) foreach($arg as $k=>$v)
        {
            $c->set_prp($k,$v);
        }
        if ($this->cld[$c->name])
        {
            $this->cld[$c->name]->dup($c,$overwrite);
        }
        else
        {
            $this->cld[$c->name ] = $c;
            $c->set_par($this);
        }

        return $this->cld[$c->name ];
    }
    function dup($c,$overwrite=1)
    {
        if (is_array($c->trl)) 
            foreach($c->trl as $p=>$t) 
                foreach($t as $l=>$v) 
                    $this->trl[$p][$l] = $v;
        foreach($c->prp as $k=>$v) $this->set_prp($k,$v,$overwrite);
        foreach($c->cld as $k=>$v) 
        {
            $el = $this->copy($v,$overwrite);
            if($c->get_prp('loryx.org/copy')=='noRestoreChild')
            {
                $el->set_prp('loryx.org/store','no');
            }
        }
        return $this;
    }
    function copy($rs,$overwrite=1)
    {
        //var_dump('copy '.$this->get_urn().'--'.$rs->get_urn());
        $el = $this->set_cld($rs->name)->dup($rs,$overwrite);
        $el->sync();
        return $el;
    }
    function get_bld($bld='')
    {
        if($bld === '') $bld = $this->get_prp('loryx.org/builder');
        if (empty($bld)) return false;
        // se è stato impostato un bld_maker viene richiamato
        //FB::log($this->get_urn().' > '.$bld,'get_bld');
        if ($this->bmk) $bld = $this->bmk->make($bld);
        return env::get_bld($bld);
    }
    function get_cld($name,$self=2)
    {
        $lname = array_filter(explode('/',$name),'strlen');
        if (count($lname)>1)
        {
            $rs = $this;
            foreach($lname as $ln) if ($rs) $rs = $rs->get_cld($ln,$self);
            return $rs;
        }
        $name = array_shift($lname);
        if (!$name) return false;
        // 0 : effettua una ricerca nel fs e se non lo trova non fa nulla
        // 1 : effettua una ricerca nel fs e se non lo trova crea una risorsa nulla
        // 2 : effettua una ricerca nel fs e se non lo trova cerca tra i figli del tipo
        // 3 : non effettua una ricerca nel fs e db e crea quindi una risorsa vuota
        $el = $this->cld[$name];
        // è un figlio non caricato?
        if (!$el and $self!=3) $el = env::load_rs($this->get_urn($name),($self==5)?2:0);
        if ($this->sys=='opensymap.org')
        {
           //FB::log($el?$el->dump():'[empty]',$name);
        }
        switch($self)
        {
        case 0 : // solo figli dell'oggetto
            break;
        case 2 : // ammessi anche figli del tipo
        case 5 :
            //if (!$el) var_dump($this->get_urn()); 
            if (!$el and $this->typ) $el =  $this->typ->get_cld($name,$self);
            break;
        case 4 : // come 2 ma se non trova nulla crea una risorsa vuota
            if (!$el and $this->typ) $el =  $this->typ->get_cld($name,$self);
            // nobreak
        default:
            // creazione nuovo elemento
            if(!$el) $el = $this->set_cld($name);
            break;
        }
        return $el;
    }
    function get_clds($arg = array())
    {
        if (!is_array($arg)) $arg = array('loryx.org/type'=>$arg);
        if (!count($arg))
        {
            return $this->cld;
        }
        $res = array();
        foreach($this->cld as $n=>$c)
        {
            $found = true;
            foreach($arg as $t=>$v) if ($c->get_prp($t)!=$v){$found=false;break;}
            if ($found) $res[$n]=$c;
        }
        return $res;
    }
    function dump($opt='',$depth=0,$root=true)
    {
        $name = $this->name;
        if ($root)
        {
            $name = $this->get_urn();
        }
        $ty = $this->get_prp('loryx.org/type');
        $str = str_repeat(TAB,$depth)."[ {$name} {$ty}\n";
        foreach($this->prp as $k=>$vv)
        {
            if (in_array($k,array('loryx.org/type','loryx.org/include'))) continue;
            if (in_array($k,array('loryx.org/urn','loryx.org/store/data/time')) and $opt=='purge') continue;
            $v = $vv;
            if (strpos($v,"\n")!==false)
            {
                do
                {
                    $mark = env::sid();
                }
                while(strpos($v,$mark)!==false);
                $v = "% {$mark}\n{$v}\n{$mark}";
            }
            $vstr = str_repeat(TAB,$depth+1)."$k $v";
            $str .= $vstr.($vstr?NL:'');
        }
        foreach($this->cld as $c)
        {
            $str .= $c->dump($opt,$depth+1,false);
        }
        $str .= str_repeat(TAB,$depth)."]\n";
        return $str;
    }
    public function __call($name,$args)
    {
        $bld = $this->get_bld();
        if (!$bld)
        {
            return false;
        }
        if (!is_array($args)) $args = array();
        $a = $args;
        //if (!($args[0] instanceof rs))
        // per i metodi richiamati direttamente .. il primo argomento è la risorsa sulla quale è stata invocata
        array_unshift($args,$this);
        if (!method_exists($bld,$name))
        {
            if (!method_exists($bld,'__call')) throw new TraceEx("metodo [$name] non trovato nel builder ".get_class($bld));
        }
        return call_user_func_array(array($bld,$name),$args);
    }
    public function __toString()
    {
        return $this->dump();
    }
    public function exe($prp=null,$arg=null)
    {
        $this->sync();
        $this->exe_inc();
        return $this->make($this,$prp,$arg);
    }
    public function exe_inc()
    {
        list($fname,$chdir) = array_filter(explode(' ',$this->get_prp('loryx.org/include',1)),'strlen');
        if ($this->typ)
        {
            if ($fname)
            {
                $a =  $this->typ->get_prp('loryx.org/include/chdir',1);
                $this->typ
                     ->set_prp('loryx.org/include/chdir','no')
                     ->exe_inc()
                     ->set_prp('loryx.org/include/chdir',$a);
            }
            else
            {
                $this->typ
                     ->exe_inc();
            }
        }
        if ($fname)
        {
            //$fname = './'.trim($fname);
            if ($chdir and $this->get_prp('loryx.org/include/chdir',1)!='no')
            {
                //var_dump(getcwd(),dirname($fname),basename($fname),$this->dump());
                FB::log(array(dirname($fname),basename($fname),getcwd()),$fname);
                $dname = dirname($fname);
                $fname = basename($fname);
                if (!is_dir($dname))
                {
                    // non è presente nella dir corrente quindi viene considerato la dir di base
                    // questo significa che occorrerà rimettere il path corrente alla fine.
                    $__olddir = env::chdir();
                    if (!is_dir($dname))
                    {
                        // non è presente
                        return;
                    }
                }
                chdir($dname);
            }
            require_once($fname);
            if ($__olddir) env::chdir($__olddir);
        }
        return $this;
    }
    public function make($rs,$prp=null,$arg=null)
    {
        //FB::log(($rs->typ?'+':'').$rs->get_urn(),'make');
        $spec = array('loryx.org/builder/init',
                      'loryx.org/builder',
                      'loryx.org/eval');
        if ($this->get_prp('loryx.org/debug')==2) env::dump($this);
        foreach($spec as $s)
        {
            switch ($s)
            {
            case 'loryx.org/eval':
            case 'loryx.org/builder/init':
                env::exe_prp($rs,$s);
                break;
            case 'loryx.org/builder':
                if ($rs->get_prp('loryx.org/builder/make')) 
                {
                    env::exe_prp($rs,'loryx.org/builder/make');
                    break;
                }
                else
                {
                    $bld = env::get_bld($rs->get_prp($s));
                    if ($bld) $bld->make($rs,$prp,$arg);
                }
                env::exe_prp($rs,'loryx.org/builder/finish');
                break;
            }
        }
        return $this;
    }
}


class rs_urn extends rs
{
    public function __construct($urn,$ty='')
    {
        list($base,$sys) = explode('@',trim($urn,'/'));
        $base = explode('/',$base);
        $name = array_pop($base);
        parent::__construct($sys,implode('/',$base),$name);
        $this->set_prp('loryx.org/type',$ty);
    }

}
?>
