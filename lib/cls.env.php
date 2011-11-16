<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

class c_timer
{
    private $_start;
    public function __construct()
    {
        $this->_start = $this->microtime();
    }
    public function microtime()
    {
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }
    public function get_time($flt=4)
    {
        return number_format($this->microtime()-$this->_start,$flt,'.','');
    }
}
function _cry($s)
{
    return $s;
    return base64_encode($s);
}
function _uncry($s)
{
    return $s;
    return base64_decode($s);
}
function rrmdir($dir) {
    FB::log($dir,'rm_dir');
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
 } 
/*
class ENV 
@author ermanno.astolfi@gmail.com
@description
    Namespace principale contenente le funzioni di accesso alle principali risorse 
    messe a disposizione dal sistema.
    
    set_db / get_db : permette di memorizzare nel sistema connessioni a più database 
                      associandogli una chiave per poterli poi richiamare quando servono.
                      
    set_var / get_var : associa una etichetta globale ad un oggetto qualsiasi così da poter
                        essere recuperato in qualsiasi contesto.
    
    get_bld : richiesta dell'oggetto builder associato al nome di classe passato per parametro.
    
    get_rs  : richiesta dell'oggetto risorsa associato alla uri passata come parametro.
*/
class env
{
    // contesto ambiente
    private static $ctx;
    
    private function __construct()
    {
        $this->bld = array();
        $this->rs = array();
        $this->dbs = array();
        $this->vrs = array();
        $this->time = new c_timer();
        
        $this->app_root = './app/';
        $this->apx_root = sys_get_temp_dir().'/org.loryx.www/apx/';
		if (!is_dir($this->apx_root)) mkdir($this->apx_root,0777,true);
        
        $this->mtime = 1?filemtime(__FILE__):0;
        $this->lng = 'it';
        $this->root = getcwd();
        $this->exe_in_file = 0;
    }
    public static function path_app($fname)
    {
        return self::$ctx->app_root.$fname;
    }
    public static function init()
    {
        if (self::$ctx) return; 
        
        self::$ctx = new self();
        
        $req = new stdClass; 
        list($req->uri,) = explode('?',$_SERVER['REQUEST_URI']);
        $req->server = $_SERVER['SERVER_NAME'];
        $req->path = nvl($_SERVER['PATH_INFO'],'/');
        $req->base = $req->uri;
        $req->base = substr($req->base,0,strlen($req->base)-strlen($req->path));
        self::set_var('request', $req); 
    }
    public static function exe_prp($rs,$prp,$env=array())
    {
        $str = $rs->get_prp($prp);
        $len = strlen(trim($str));
        if (!$len) return;
        foreach($env as $n=>$v) $$n = $v;
        $fname = self::$ctx->apx_root.
                    $rs->get_path().'.'.
                    base64_encode($prp).'.php';
        
        
        $dname = dirname($fname);
        if (!is_dir($dname)) mkdir($dname,0777,true);
        
        $fp = @fopen($fname, "x");
        if ($fp)
        {
            fwrite($fp,"<?php ".NL.$str.NL." ?>");
            fclose($fp);
        }
        return include($fname);
    }
    public static function if_read_file_exit($fname,$path=array('.'))
    {
        foreach($path as $p)
        {
            $fpname = $p.$fname;
            $struct = explode('.',$fpname);
            $fload = 'readfile';
            $ext = array_pop($struct);
            if (!is_file($fpname))
            {
                $struct[] = 'php';
                $fload = 'include';
                $fpname = implode('.',$struct);
            }
        }
        if (!is_file($fpname)) return;
        switch($ext)
        {
        case 'php':
            $fload = 'include';
            break;
        case 'css':
            env::set_ctype('text/css');
            break;
        case 'js':
            env::set_ctype('text/javascript');
            break;
        case 'jpg';
            $ext = 'jpeg';
        case 'jpeg';
        case 'png':
        case 'gif':
            env::set_ctype('image/'.$ext);
            break;
        }
        $fload($fpname);
        exit;
    }
    public static function set_lng($lng)
    {
        self::$ctx->lng = $lng;
    }
    public static function get_lng()
    {
        return self::$ctx->lng;
    }
    public static function set_db($key,$db)
    {
        self::$ctx->dbs[$key] = $db;
        return self::get_db($key);
    }
    public static function get_db($key)
    {
        return self::$ctx->dbs[$key];
    }
    public static function get_time($fl=4)
    {
        return self::$ctx->time->get_time($fl);
    }
    public static function set_var($name,$obj)
    {
        self::$ctx->vrs[$name] = $obj;
        return $obj;
    }
    public static function get_var($name,$dfl=false)
    {
        if (!isset(self::$ctx->vrs[$name]) && $dfl!==false)
        {
            self::set_var($name,$dfl);
        }
        return self::$ctx->vrs[$name];
    }
    public static function get_bld($name)
    {
        if (empty($name)) return false; 
        
        if (!self::$ctx->bld[$name])
        {
            if (!class_exists($name)) throw new Exception($name);
            self::$ctx->bld[$name] = new $name;
        }
        return self::$ctx->bld[$name];
    }
    public static function serialize_rs($rss,$fname)
    {
        // vuole essere serializzato?
        @unlink($fname);
        $rsa = array();
        foreach($rss as $rs) 
        {
            $dname = self::$ctx->apx_root.$rs->get_path();
            if (is_dir($dname)) 
            {
                rrmdir($dname);
            }
            if ($rs->get_prp('loryx.org/serialize')!='no')
            {
                $rs->serialized = true;
                $rsa[] = $rs;
            }
        }
        if(count($rsa))
        {
            $dir = dirname($fname);
            if (!is_dir($dir)) mkdir($dir,0777,true);
            $fp = @fopen($fname,'w');
        }
        if ($fp)
        {
            $srs = _cry(serialize($rsa));
            FB::log($fname,'serialize');
            fwrite($fp,$srs);  
            fclose($fp);
            return true;
        }
        return false;
    }
    public static function unserialize_rs($fname)
    {
        if (!is_file($fname)) return false;
        $rrs = unserialize(_uncry(file_get_contents($fname)));
        foreach($rrs as $rs) $rs->serialized = false;
        //FB::log(array($fname,$rrs[0]->dump()),'unserialize');
        return $rrs;
    }
    public static function chdir($path='')
    {
        $old = getcwd();
        if (empty($path)) chdir(self::$ctx->root);
        else chdir($path);
        return $old;
    }
    public static function load_rs($rs,$fs = 0)
    {
        if (is_string($rs))
        {
            switch($fs)
            {
            case 2:
                $set_rs = 1;
                $rs = new rs_urn($rs);
                break;
            default:
                $rs = env::set_rs(new rs_urn($rs));
            }
        }
        //throw new Exception($rs->get_urn());
        if($rs->get_prp('loryx.org/load_rs/exception')) throw new TraceEx('load_rs');
        if ($rs->get_urn()=='') throw new TraceEx($rs->dump());
        // reimpostazione della root 
        $curr_root = self::chdir();
        foreach(array($rs->get_path(),$rs->get_path()."/index") as $f)
        {
            $lrx = self::$ctx->app_root.$f.'.lrx';
            $lro = self::$ctx->apx_root.$f.'.lro';
            $php = self::$ctx->app_root.$f.'.php';
            if(is_file($php)) $php_inc = $php;
            // a prescindere del sorgente o meno ... ho trovato un compilato 
            if(is_file($lro)) $fs_rs = array('lro'=>$lro,'otime'=>filemtime($lro));
            
            if(!is_file($lrx)) continue;
            $fs_rs = array('lrx'=>$lrx,
                           'lro'=>$lro,
                           // il file oggetto viene ricompilato sia se è stato aggiornato il sorgente
                           // sia se è stato aggiornato questo medesimo file.
                           'xtime'=>max(filemtime($lrx),self::$ctx->mtime),
                           'otime'=>is_file($lro)?filemtime($lro):0);
        }
        //var_dump($fs_rs);exit;
        if ($fs==1 and !$fs_rs) 
        {
            self::chdir($curr_root);
            return false;
        }
        //if ($fs_rs['otime'])
        {
            $rss = array();
            if ($fs_rs['xtime']>$fs_rs['otime'])
            { 
                require_once('./lib/cls.prs.php');
                $rss = I(new prs())->parse(file_get_contents($fs_rs['lrx']));
                $fs_rs['oupdate'] = env::serialize_rs($rss,$fs_rs['lro']);
            }
            else 
            {
                if ($fs_rs['otime']) $rss = env::unserialize_rs($fs_rs['lro']);
            }
            foreach($rss as $rr)
            {
                env::set_rs($rr);
            }
        }
        self::chdir($curr_root);
        //var_dump($rs->dump());

        //$rs = env::get_rs($rs->get_urn());
        // caricamento script default. La configurazione su DB può modificare tale valore.
         if ($php_inc) $rs->set_prp('loryx.org/include',$php_inc.' 1');
        // è caricabile dal DB?
        
        env::reload_rs(env::get_var('dbx'),$rs);
        if (!$fs_rs and !$rs->stored) return false;
        /*
        var_dump($curr_root, $rs);exit;

        if (!$fs_rs and !$rs->stored) return false;
        
        if ($rs->get_prp('loryx.org/store/data/time')<$fs_rs['xtime'])
        {
            if ($fs_rs['otime']<$fs_rs['xtime'] and !$fs_rs['oupdate'])
            {
                // se la versione sul DB è più vecchia di quella del sorgente
                require_once('./lib/cls.prs.php');
                $rss = I(new prs())->parse(file_get_contents($fs_rs['lrx']));
                env::serialize_rs($rss,$fs_rs['lro']);
            } 
            // TODO :: oggorre aggiornare i dati sul DB
            // $rs->set_prp('loryx.org/store/data/time',$fs_rs['lrx']);
            // env::get_var('dbx')->store_rs($rs);
        }
        */
        if ($set_rs)
        {
            $rs = env::get_rs($rs->get_urn());
        }
        $rs->sync();
        return $rs;
    }
    public static function get_rs($urn,$opt=0)
    {
        list($path,$sys) = explode('@',$urn);
        if (!is_array($opt)) $opt = array('autocreate' => $opt);
        $path = array_filter(explode('/',trim($path,'/')),'strlen');
        $base = array_shift($path);
        $lnk = trim("{$base}@{$sys}",'@');
        $el = self::$ctx->rs[$lnk];
        //var_dump($urn,$el);exit;
        if (!$el)
        {
            $el = self::load_rs($lnk);
        }
        $lbase = array();
        while ($el and count($path))
        {
            $lbase[] = $el->name;
            $lname = $lbase;
            $ch_name = array_shift($path);
            $lname[] = $ch_name;
            $ch = $el->get_cld($ch_name,$opt['autocreate']);
            //if (!$ch) $ch = self::load_rs(implode('/',$lname).'@'.$sys,1);
            if (!$ch and $opt['autocreate']) $ch = $el->set_cld($ch_name);
            $el = $ch;
        }
        return $el;
    }
    public static function reload_rs($db,$rs,&$data=array())
    {
        if (!$db or !$db->cc()) return $rs;
        
        $start = env::get_time();
        $reloaded = $rs->reloaded;
        if ($rs->reloaded) return $rs;
        $rs->reloaded = true;
        // funzione ricorsiva ..
        // se $data non è impostato allora è la prima chiamata e quindi si deve
        // inizializzare con la query
        if (!count($data))
        {
            switch($rs->get_prp('loryx.org/load'))
            {
            case 'onlychild':
                // non viene caricato tutta la risorsa ma solo la root con i relativi figli
                $cld = $rs->get_prp('loryx.org/load/child');
                $cmd = "
                    /* * * ".$rs->get_urn()." * */
                    select p.*
                    from [@loryx] p 
                    left join [@loryx] o on (p.l=o.l and 
                                            p.o=o.o and 
                                            p.r=o.r and 
                                            o.y='loryx.org/ord')
                    left join ( select y.x as x, yy.x as o 
                                from [@loryx] y
                                inner join [@loryx] yy on (yy.l = y.l and 
                                                           yy.o = y.o and 
                                                           yy.r = y.r and 
                                                           yy.y = 'loryx.org/ord')
                      where y.y = 'loryx.org/urn') y on (p.y = y.x )
                    where p.l=[0] and
                            /* SELF + CHILDS* */
                         ((p.o=[1] and p.r=[2]) or 
                          (p.o='<[1]>/<[2]>' and p.r='{$cld}') or
                          (substr(concat(p.o,'/'),1,length('<[1]>/<[2]>/{$cld}/'))='<[1]>/<[2]>/{$cld}/')) 
                    order by p.o,ifnull(o.x+0,1000), p.r, ifnull(y.o+0,1000)
                    ";
                    break;
            default:
                $cmd = "
                    /* * * ".$rs->get_urn()." * */
                    select p.*
                    from [@loryx] p 
                    left join [@loryx] o on (p.l=o.l and 
                                            p.o=o.o and 
                                            p.r=o.r and 
                                            o.y='loryx.org/ord')
                    left join ( select y.x as x, yy.x as o 
                                from [@loryx] y
                                inner join [@loryx] yy on (yy.l = y.l and 
                                                           yy.o = y.o and 
                                                           yy.r = y.r and 
                                                           yy.y = 'loryx.org/ord')
                      where y.y = 'loryx.org/urn') y on (p.y = y.x )
                    where p.l=[0] and
                            /* SELF + CHILDS* */
                         ((p.o=[1] and p.r=[2]) or 
                          (substr(concat(p.o,'/'),1,length('<[1]>/<[2]>/'))='<[1]>/<[2]>/')) 
                    order by p.o,ifnull(o.x+0,1000), p.r,ifnull(y.o+0,1000)
                    ";
            }
            $data = $db->getAll($cmd,$rs->sys,$rs->base,$rs->name);
            //var_dump($db->item(0)->cmd);
            $cc = count($data);
            //echo $rs->get_urn().NL;
        }
        if (!is_array($data))
        {
            $data = array();
        }
        $rs->stored = count($data); // i valori vengono caricati da db
        $begin = env::get_time();
        while($dt = array_shift($data))
        {
            if (array($dt['o'],$dt['r']) == array($rs->base,$rs->name))
            {
                // proprietà della risorsa corrente
                $rs->set_prp($dt['y'],$dt['x']);
                continue;
            }
            $base = $rs->base.'/'.$rs->name.'/';
            if (substr($dt['o'].'/',0,strlen($base))==$base)
            {
                // proprietà di un figlio
                $name = array_filter(explode('/',substr($dt['o'],strlen($base))),'strlen');
                $ch=$rs;
                while($nm = array_shift($name)) $ch = $ch->get_cld($nm,3);
                $ch = $ch->get_cld($dt['r'],3);
                array_unshift($data,$dt);
                self::reload_rs($db,$ch,$data);
            }
            else
            {
                // proprietà di un fratello
                array_unshift($data,$dt);
                break;
            }
        }
        $end = env::get_time();
        
        if ($cc) $rs->sync();
        
        return $rs;
    }
    public static function set_rs($rs)
    {
        if (!is_object($rs)) throw new TraceEx($rs);
        list($a,$b) = explode('@',$rs->get_urn());
        $a = array_filter(explode('/',trim($a,'/')),'strlen');
        $nm = trim(implode('@',array(array_shift($a),$b)),'@');
        $el = self::$ctx->rs[$nm];
        if (!$el)
        {
            $el = $rs;
            if ($nm  != $rs->get_urn())
            {
                $el = new rs_urn($nm);
            }
            self::$ctx->rs[$nm] = $el;
        }
        while($n = array_shift($a))
        {
            // $n : nome livello corrente
            if (count($a))
            {
                // $n è un livello intermedio
                //var_dump($a);
                if (!is_object($el)) {var_dump($rs->get_urn(),$n,$nm,$a);throw new Exception();}
                $el = $el->get_cld($n,'new');
                continue; // nodo successivo;
            }
            // $n è l'ultimo livello
            $el = $el->set_cld($rs);
        }
        $el->dup($rs);
        return $el;
    }
    public static function store_rs($rs,$fname='',$opt=array())
    {
        switch($opt['mode'])
        {
        case 'lrx':
            $code = $rs->dump('purge');
            break;
        case 'php':
        default:
            $lnk = $rs->get_urn();
            if ($fname)
            {
                $code = NL."env::get_rs('{$lnk}',3)";
            }
            else
            {
                $code = NL.TAB."->get_cld('{$rs->name}',3)";
            }
            foreach($rs->prp as $p=>$v)
            {
                $code .= NL.TAB."->set_prp('$p','".str_replace("'","\'",$v)."')";
            }
            foreach($rs->get_clds() as $ch)
            {
                if ($ch->get_prp('loryx.org/store')=='no') continue;
                $code .= self::store_rs($ch);
                $code .= NL.TAB.'->get_par()';
            }
            if ($fname) $code = "<?php \n{$code}->sync();\n?>";
            break;
        }
        if (!empty($fname))
        {
            $handle = 0;
            if (!$opt['upload'])
            {
                // si prova a scrivere
                $handle = fopen($fname, "w");
                if ($handle)
                {
                    fwrite($handle,$code);
                    fclose($handle);
                }
            }
            if (!$handle)
            {
				$fname = basename($fname);
				FB::log($fname,'upload');
                ob_clean();
                //$fname = basename($fname);
                // upload del file
                header("Content-Type: text/plain");
                header('Content-Disposition: attachment; filename="'.$fname.'"');
                header("Content-Length: " . strlen($code));
                echo $code;
                exit;

            }
        }
        return $code;
    }
    public static function sid($t='sys',$size=0)
    {
        $sid = '';
        if(!is_array($t)) $t=array($t);
        if ($size) $t[] = $size;
        switch($t[0])
        {
        case 'small':
            // piiicolo numerico
            return rand(0,99999);
        case 'tbl':
            // primo carattere una lettera .. con lettere minuscole
            if (!$t[1]) $t[1] = 5;
            $chs = 'qwertyuiopasdfghjklzxcvbnm';
            $sid = $chs{rand(0,strlen($chs)-1)};
            $chs .= '0123456789_';
            
            for($i=1; $i<$t[1] ; $i++)
            {
                $sid .= $chs{rand(0,strlen($chs)-1)};
            }
            return $sid;
        default:
            // inizia con 2 
            $sid = date('YmdHis');
            $t[1] = max($t[1],20);
            if ($t[0]=='low')
            {
                $chs = 'qwertyuiopasdfghjklzxcvbnm0123456789';
            }
            else
            {
                $chs = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0123456789';
            }
            for($i=strlen($sid); $i<$t[1] ; $i++)
            {
                $sid .= $chs{rand(0,strlen($chs)-1)};
            }
            return $sid;
        }
    }
    
    public static function set_ctype($t,$cs='utf-8')
    {
        header("Content-type: {$t};".(!empty($cs)?" charset={$cs}":''));
    }
    
    public static function ob_content()
    {
        $cnt = ob_get_clean();
        //ob_start();
        return $cnt;
    }
    public static function get_ctx()
    {
        return self::$ctx;
    }
    
    public static function dump($rs,$noexit=0)
    {
        if (!$rs) $msg = '< empty >';
        else $msg = $rs->dump();
        var_dump(NL.$msg);
        if ($noexit) return;
        throw new Exception('dump');
        exit;
    }
}
env::init();
?>
