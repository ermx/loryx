<?php
class stl
{
    static function set_cookie($name,$value,$expire=0)
    {
        setcookie($name,$value,$expire,'/',$_SERVER['SERVER_NAME']);
        $_COOKIE[$name] = $value;
    }
    static function get_cookie($name)
    {
        return $_COOKIE[$name];
    }
}
class stl_db_init
{
    function make($rs,$a,$b)
    {
        $db = env::get_var('dbx');
        $req = env::get_var('request');
        // testata
        $stl = array('id'=>env::sid(),
                     'id_trl'=>env::sid(),
                     'dom'=>$req->server.$req->base,
                     'lng_def'=>'it');
        $db->insert('[@stl]',$stl);
        
        $db->insert('[@stl_lng]',array('id'=>env::sid(),
                                             'id_stl'=>'',
                                             'lng'=>'it',
                                             'ttl'=>'Italiano',
                                             'ord'=>'1'));
        // sezioni
        foreach(
            array(
                // home page
                array('id'=>env::sid(),
                      'id_stl'=>$stl['id'],
                      'id_trl'=>env::sid(),
                      'bld'=>'bld.home.php'),
                // sezione Loryx
                array('id'=>env::sid(),
                      'id_stl'=>$stl['id'],
                      'id_trl'=>env::sid(),
                      'ttl'=>'LoryX : open web module system',
                      'url'=>'loryx',
                      'lbl'=>'LoryX',
                      'bld'=>'bld.loryx.php',
                      'lvl'=>'bar'),
                // sezione Opensymap
                array('id'=>env::sid(),
                      'id_stl'=>$stl['id'],
                      'id_trl'=>env::sid(),
                      'ttl'=>'OpenSymap : Sistema aperto per la creazione di applicazioni web',
                      'url'=>'opensymap',
                      'lbl'=>'OpenSymap',
                      'bld'=>'bld.osyx.php',
                      'lvl'=>'bar')) as $pag)
        {
            $db->insert('[@stl_sec]',$pag);
            $trl = array('id'=>$pag['id_trl'],
                         'res_typ'=>'TABLE',
                         'res_nme'=>'[@stl_sec]',
                         'res_id'=>$pag['id']);
            $db->insert('[@stl_trl]',$trl);
        }
    }
}
class stl_menu
{
    public function __construct($lng, $id_stl, $id_par='',$dbg=0)
    {
        $db  = env::get_var('dbx');
        $tbl = env::get_var('trl_tbl');
        $cmd = "
            select s.id, s.lvl,
                   ifnull(t1.val,s.url) as url,
                   ifnull(t2.val,s.ttl) as ttl,
                   ifnull(t3.val,s.lbl) as lbl,
                   s.cls
            from [@stl_sec] s
            left join [@{$tbl}] t1 on (s.id_trl = t1.id_trl and t1.lng = [0] and t1.nme='url')
            left join [@{$tbl}] t2 on (s.id_trl = t2.id_trl and t2.lng = [0] and t2.nme='ttl')
            left join [@{$tbl}] t3 on (s.id_trl = t3.id_trl and t3.lng = [0] and t3.nme='lbl')
            where s.id_stl=[1] 
              and ifnull(s.id_par,'') = [2]
            order by s.ord";
        $this->menu = array();
        foreach($db->getAll($cmd,$lng,$id_stl,$id_par) as $m)
        {
            $this->menu[$m['lvl']][] = $m;
        }
        if ($dbg) var_dump("<pre>".$db->last_sql()."</pre>");
    }
    function get($lvl)
    {
        return nvl($this->menu[$lvl],array());
    }
}
class stl_start
{
	var $usr;
    function email($tos,$sbj,$bdy)
    {
        // To send HTML mail, the Content-type header must be set
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        // Additional headers
        $headers .= 'From: '.sit()->ttl.' <'.sit()->eml_def.'>' . "\r\n";
        foreach(explode(',',$tos) as $to) mail(trim($to),$sbj,$bdy,$headers);
    }
	function __construct()
	{
        $this->db = env::get_var('dbx');
        env::set_var('sit',$this);
        env::set_var('page',new Page());
        $req = env::get_var('request');
        
		$this->uri = new stdClass;
		list(,$this->uri->get) = explode('?',$_SERVER['REQUEST_URI']);
        $this->uri->request = $req->path;
		$this->uri->server = $_SERVER['SERVER_NAME'].rtrim($req->base,'/');
		$this->uri->host = rtrim("http://{$this->uri->server}",'/');
		$this->uri->url = $this->uri->host.rtrim($this->uri->request,'/');
		
        $this->path = array_filter(explode('/',trim($this->uri->request,'/')),'strlen');
        
        $fname = '.'.$this->uri->request;
        $bname = explode('.',$fname);
        $ext = array_pop($bname);
        $bname = implode('.',$bname);        
	}
	function url($str,$lang='')
	{
        if ($str{0}=='/')
        {
            if ($str{1}=='/')
            {
                // si vuole partire dalla root;
                $url = trim($this->uri->host,'/').'/'.trim($str,'/').'/';
            }
            else
            {
                // si vuole partire dalla root;
                $url = trim($this->uri->lng,'/').$str;
            }
        }
        else
        {
            // è un path relativo
            $url = trim($this->uri->base,'/').'/'.$str;
        }
		return $url;
	}

    function __get($k)
    {
        return $this->_dat[$k];
    }
    function lay_def()
    {
        return $this->lay == $this->_dat['lay'];
    }
    function init()
    {
    //var_dump($_SERVER);
        // prima connessione?
        $arg = array('id'=>env::sid(),
                     'id_stl'=>$this->id,
                     'url'=>$_SERVER['REQUEST_URI'],
                     'proto'=>$_SERVER['SERVER_PROTOCOL'],
                     'agent'=>$_SERVER['HTTP_USER_AGENT'],
                     'ip_addr'=>$_SERVER['REMOTE_ADDR'],
                     'referer'=>$_SERVER['HTTP_REFERER']);
        if (stl::get_cookie('__sit_track')) 
        {
            // ip uguale?
            $trk = $this->db->getFirst('select * from [@stl_trk] where id = [0]',stl::get_cookie('__sit_track'));
            if ($trk['id'])
            {
                if ($trk['ip_addr']!=$arg['ip_addr'])
                {
                    $arg['id_par'] = $trk['id'];
                    unset($trk);
                }
            }
        }
        if (!$trk['id'])
        {
            try{
                $this->db->insert('[@stl_trk]',$arg);
            }catch(Exception $e)
            {
                sleep(1);
                // seconda possibilità
                $arg['id'] = env::sid();
                $this->db->insert('[@stl_trk]',$arg);
            }
            stl::set_cookie('__sit_track',$arg['id']);
        }
        // utente loggato?
        if (stl::get_cookie('__sit_log'))
        {
            $this->usr = $this->db->getFirst('
                select l.id as id_log, u.id, u.id_rol, u.id_ana, ifnull(u.nickname,u.login) as uname, u.lst
                from [@stl_usr_log] l
                inner join stl_usr u on (l.id_usr = u.id)
                where l.id  = [0] 
                and l.dat_del is null
                and u.dat_del is null',stl::get_cookie('__sit_log'));
             
            if (!$this->usr['id']) stl::set_cookie('__sit_log','');
        }
        if (isset($_GET['_v'])) stl::set_cookie('__sit_ver',$_GET['_v']);
        // individuazione idioma
        $path = $this->path;
        $a = array_shift($path);
        $this->lngs = $this->db->getList("select lng, ttl, concat('/',lng,'/') url, trl_tbl from [@stl_lng] where ifnull(id_stl,'') in ('',[0]) order by ord",$this->_dat['id']);
        $this->lng = $this->lngs[$a]['lng'];
        if ($this->lng) 
        {
            $a = array_shift($path); // trovato idioma, passo alla sezione
        }
        else 
        {
            // l'idioma di default è quello trovato per primo o l'italiano in sua assenza
            $this->lng = $this->lng_def;  
        }
        $this->trl_tbl = nvl($this->lngs[$this->lng]['trl_tbl'],'stl_trl_det');
        $this->prp_tbl = nvl($this->prp_tbl,'stl_prp_det');
        env::set_var('trl_tbl',$this->trl_tbl);
        $base = array();
        $id_par = '';
        $burl = array('.','bld');
        do
        {
            // individuazione sezione corrente nel DB
            $lsec = $this->db->getAll(" 
                    select s.id, s.id_trl, s.lbl, s.ttl, ifnull(t.val,s.url) as url, s.url as burl, s.bld
                    from [@stl_sec] s 
                    left join [@{$this->trl_tbl}] t on (t.id_trl = s.id_trl and t.nme='url' and t.lng=[0]) 
                    where s.id_stl=[1] 
                      and ifnull(ifnull(t.val,s.url),'') in ('',[2])
                      and ifnull(s.id_par,'') = [3]", $this->lng, $this->id, $a ,$id_par);
            $break = 0;
            switch(count($lsec))
            {
            case 0:
                // non è stato trovato
                $break = 1;
                break;
            case 1:
                // è stato trovata la sezione ricercata o la ''?
                $this->sec = $lsec[0];
                if ($lsec[0]['url']==$a)
                {
                    $b = array_shift($path);
                }
                else
                {
                    // bisogna vedere se $a è una subsection di ''
                }
                break;
            default:
                // due soli casi
                $this->sec = ($lsec[0]['url']==$a)?$lsec[0]:$lsec[1];
                $b = array_shift($path);
                break;
            }
            if ($break) break;
            
            $id_par = $this->sec['id'];
            if (!$this->uri->sec)
            {
                $this->uri->sec = $this->sec;
                $asec = &$this->uri->sec;
            }
            else
            {
                $bsec = &$asec;
                unset($asec);
                $bsec['sec'] = $this->sec;
                $asec = &$bsec['sec'];
                unset($bsec);
            }
            // calcolo del builder
            $burl[] = $this->sec['burl'];
            $fbld = implode('/',$burl);
            $xbld = '';            
            if (is_file($fbld.'.php')) $xbld = $fbld.'.php';
            if (is_file($fbld.'/index.php')) $xbld = $fbld.'/index.php';
            $fbld = "./bld/".$this->sec['bld'];
            $nbld = is_file($fbld)?$fbld:$xbld;
            if ($nbld) 
            {
                $base [] = $a;
                $bld = $nbld;
            }
            // proprietà della sezione
            $cmd = "select nme,val from [@{$this->prp_tbl}] where id_prp = [0]";
            $asec['prp'] = $this->db->getList($cmd,$this->sec['id']);
            
            if ($a)
            {
                // individuazione parte di url relativo agli altri idiomi
                $cmd = "select lng,val from [@{$this->trl_tbl}] where id_trl = [0] and nme =[1]";
                $tr = $this->db->getList($cmd,$this->sec['id_trl'],'url');
                foreach($this->lngs as $l=>$v)
                {
                    
                    $this->lngs[$l]['url'] .= nvl($tr[$l],$sec['url']).'/';
                }
            }
            $a = $b;
            $asec['trl'] = new cTrl($asec['id_trl']);
        } 
        while($this->sec['id'] and $a);
        if ($a) array_unshift($path,$a);
        if (!$bld)
        {
            // il builder viene cercato nel file system
            $pp = array('.','pub');
            $path = $this->path;
            $base = array();
            while($a = array_shift($path))
            {
                $pp[] = $a;
                $pa = implode('/',$pp);
                $fname = $pa.'/index.php';
                FB::log($fname);
                if (is_file($fname))
                {
                    $bld = $fname;
                    $base[] = $a;
                    continue;
                }
                $fname = $pa.'.php';
                FB::log($fname);
                if (is_file($fname))
                {
                    $bld = $fname;
                    $base[] = $a;
                    continue;
                }
                array_unshift($path,$a);
                break;
            }
        }
        array_unshift($base,$this->lng);
        $this->uri->lng = $this->uri->host.'/'.$this->lng.'/';
        $this->uri->base = $this->uri->host.'/'.implode('/',$base).'/';
        $this->uri->bld = $bld;
        $this->uri->arg = array();
        foreach($path as $p) $this->uri->arg[] = $p;
    }
    function lay_info()
    {
        $this->lay = nvl($this->_dat['lay_inf'],'info.php');
    }
	function make($rs)
	{
        // caricamento dati di pertinenza del sito
        $this->_dat = $this->db->getFirst("select * from [@stl] where dom = [0]",$this->uri->server);
        if (!$this->id)
        {
            //$this->_dat = $this->db->getFirst("select * from [@stl] order by id",$this->uri->server);
			env::set_ctype('text/html');
            return include('error.php');
        }
        //env::set_var('trl_tbl',nvl($this->_dat['trl_tbl'],'stl_trl_det'));
        $this->prp = $this->db->getList('select nme,val from stl_prp_det where id_prp=[0]',$this->id);
        // determinazione root del sito
        if (!$this->home) $this->home = 'default';
        $root = "./home/{$this->home}";
        if (stl::get_cookie('__sit_ver'))
        {
            $mroot = $root.'.'.stl::get_cookie('__sit_ver');
            if (is_dir($mroot)) $root = $mroot;
        }
        if (!is_dir($root)) $root = './home/default';
        chdir($root);   
        
        $req = env::get_var('request');
        
        env::if_read_file_exit($req->path);
        
        $this->lay = nvl($this->_dat['lay_std'],'index.php');
        if (!count($this->path))
        {
            $fname = nvl($this->_dat['lay_spl'],'splash.php');
            if (is_file($fname))
            {
                env::set_ctype('text/html');
                return include($fname);
            }
        }

        if ($_POST['cmd']) 
        {
            $this->lay = nvl($this->_dat['lay_cmd'],'cmd.php');
        } 
        // ricerca builder
        $this->init();
        
        // dizionario generico
        $this->lbl = I(new cTrlDic('LABEL','lng',$this->lng))->load();
        
        // wrapper
        $this->content = I(new Tag('div'));
        
		// esecuzione
        $bld = $this->uri->bld;
		if (!is_file($bld)) 
		{
            $bld = $this->def;
        }
		if (is_file($bld)) 
		{
            try{
                $this->content->Add($this->exe($bld));
            }
            catch(BldException $e)
            {
                $bld = "./stl/{$this->root}/app/{$e->bld}";
                if (!is_file($bld)) $bld = "./app/{$e->bld}";
                if (is_file($bld)) $this->content->Add($this->exe($bld));
            }
            if (!$this->noform)
            {
                $this->content = I(new TagForm)->Cnt($this->content);
            }
        }
        // generazione menù di primo livello
        /*
        $cmd = "
            select s.id, s.lvl,
                   ifnull(t1.val,s.url) as url,
                   ifnull(t2.val,s.ttl) as ttl,
                   ifnull(t3.val,s.lbl) as lbl,
                   s.cls
            from [@stl_sec] s
            left join [@{$this->trl_tbl}] t1 on (s.id_trl = t1.id_trl and t1.lng = [0] and t1.nme='url')
            left join [@{$this->trl_tbl}] t2 on (s.id_trl = t2.id_trl and t2.lng = [0] and t2.nme='ttl')
            left join [@{$this->trl_tbl}] t3 on (s.id_trl = t3.id_trl and t3.lng = [0] and t3.nme='lbl')
            where s.id_stl=[1] 
              and ifnull(s.id_par,'') = ''
            order by s.ord";
            $this->db->fb = 1;
        $this->menu = array();
        $this->menu = array();
        foreach($this->db->getAll($cmd,$this->lng,$this->id) as $m)
        {
            $this->menu[$m['lvl']][] = $m;
        }
        $cmd = "
            select s.id,
                   ifnull(t1.val,s.url) as url,
                   ifnull(t2.val,s.ttl) as ttl,
                   ifnull(t3.val,s.lbl) as lbl,
                   s.cls
            from [@stl_sec] sm
            inner join [@stl_sec] s on (s.id_par = sm.id and sm.id_stl=s.id) 
            left join [@{$this->trl_tbl}] t1 on (s.id_trl = t1.id_trl and t1.lng = [0] and t1.nme='url')
            left join [@{$this->trl_tbl}] t2 on (s.id_trl = t2.id_trl and t2.lng = [0] and t2.nme='ttl')
            left join [@{$this->trl_tbl}] t3 on (s.id_trl = t3.id_trl and t3.lng = [0] and t3.nme='lbl')
            where s.id_stl=[1] 
              and sm.id = [2]
            order by s.ord";
        //sit()->db->fb=1; 
        $this->menu['sub'] = $this->db->getAll($cmd, $this->lng, $this->id, $this->uri->sec['id']);
        env::set_ctype('text/html');
		// scrittura del layout
        */
        env::set_ctype('text/html');
		$this->incfile($this->lay);
	}
    function new_trl($nme,$rid,$typ='TABLE')
    {
        $arg = array('id'=>$this->sid(),
                     'res_typ'=>$typ,
                     'res_nme'=>$nme,
                     'res_id'=>$rid);
        $this->db->insert('[@stl_trl]',$arg);
        return $arg['id'];
    }
    function new_prp($nme,$rid,$typ='TABLE')
    {
        $arg = array('id'=>$this->sid(),
                     'res_typ'=>$typ,
                     'res_nme'=>$nme,
                     'res_id'=>$rid);
        $this->db->insert('[@stl_prp]',$arg);
        return $arg['id'];
    }
    function ob_get($encode=1)
    {
        $cnt = ob_get_clean();
        ob_start();
        //if ($encode) $cnt = utf8_encode($cnt);
        return $cnt;
    }
    function incfile($fname,$env=array())
    {
        foreach($env as $n=>$o) $$n = $o; 
        return include($fname);
    }
	function exe($bld,$ch=0)
	{
        $odir = env::chdir(dirname($bld));
        try{
            $ret = $this->incfile(basename($bld));
            $fnc = $this->prp['fnc_encode_cnt'];
            $str = $this->ob_get();
            if ($fnc and function_exists($fnc) and !$this->noencode) $str = $fnc($str);
        }
        catch(Exception $e)
        {
            env::chdir($odir);
            throw $e;
        }
        env::chdir($odir);
        $cnt = new Tag('');
        if (!empty($str)) $cnt->Add($str);
        if (is_object($ret)) $cnt->Add($ret);
        return $cnt;
	} 
    function protect_page()
    {
        throw new BldException('bld.protect.php');
    }
}

class cTrl
{
    function __construct($id_trl,$typ='lng',$val='')
    {
        $this->voc = array();
        $this->typ = $typ;
        $this->val = $val;
        $this->id = $id_trl;
        if (!$id_trl) return;
        switch($this->typ)
        {
        case 'l': // $val :lingua nel quale caricare il dizionario ... le altre non interessano
        case 'lng':
            if (empty($this->val)) $this->val = env::get_var('lng');
            $cmd = '
                select r2.id, [2] as lng, r1.nme,ifnull(r2.val,r1.val) as val
                from [@'.env::get_var('trl_tbl').'] r1 
                left join [@'.env::get_var('trl_tbl').'] r2 on (r1.id_trl=r2.id_trl and r2.lng = [2] and r1.nme=r2.nme)
                where r1.id_trl = [0]
                  and r1.lng = [1]
                ';
            break;
        case 'n': // $val : nome da tradurre in tutte le lingue ... gli altri vocaboli non interessano
        case 'nme':
            // da rivedere
            $cmd = '
                select r2.id, [2] as lng, r1.nme,ifnull(r2.val,r1.val) as val
                from [@'.env::get_var('trl_tbl').'] r1 
                left join [@'.env::get_var('trl_tbl').'] r2 on (r1.id_trl=r2.id_trl and r2.lng = [2] and r1.nme=r2.nme)
                where r1.id_trl = [0]
                  and r1.lng = [1]
                ';
            break;
        default:
            // da rivedere
            $cmd = '
                select r2.id, [2] as lng, r1.nme,ifnull(r2.val,r1.val) as val
                from [@'.env::get_var('trl_tbl').'] r1 
                left join [@'.env::get_var('trl_tbl').'] r2 on (r1.id_trl=r2.id_trl and r2.lng = [2] and r1.nme=r2.nme)
                where r1.id_trl = [0]
                  and r1.lng = [1]
                ';
        }
        $res = env::get_var('dbx')->getAll($cmd,$this->id,'it',$this->val);
        // memorizzazione
        foreach($res as $r)
        {
            $this->voc[$r['nme']][$r['lng']] = array($r['val'],$r['id']);
        }
    }
    function get($nl,$nme='')
    {
        switch($this->typ)
        {
        case'l':
        case 'lng': 
            return nvl($this->voc[$nl][$this->val][0],$nl);
        case 'n':
        case 'nme': 
            return nvl($this->voc[$this->val][$nl][0],$nl);
        default:
            return $this->voc[$nme][$nl][0];
        }
    }
    function gets($nl='')
    {
        $res = array();
        switch($this->typ)
        {
        case 'l':
        case 'lng': 
            foreach($this->voc as $nme=>$dat) $res[$nme] = $dat[$this->val];
            break;
        case 'n':
        case 'nme': 
            foreach($this->voc[$this->val] as $lng => $dat) $res[$lng] = $dat;
            break;
        default:
            return array();
        }
        return $res;
    }
}
class cTrlDic
{
    function __construct($res_id,$typ='',$val='')
    {
        $this->db = env::get_var('dbx');
        $this->voc = array();
        $this->typ = $typ;
        $this->val = $val;
        $this->res_typ = 'CNCT';
        $this->res_nme = 'DICT';
        $this->res_id = $res_id;
        $cmd = '
            select id
            from [@stl_trl] t
            where t.res_typ = [0]
              and t.res_nme = [1]
              and t.res_id = [2]
            ';
        $this->id = $this->db->getVal($cmd, $this->res_typ, $this->res_nme, $this->res_id);
        if (!$this->id)
        {   
            //   creazione dizionario
            $this->id = env::sid();
            $arg = array('id'=>$this->id,
                        'res_typ'=>$this->res_typ,
                        'res_nme'=>$this->res_nme,
                        'res_id'=>$this->res_id);
            $this->db->insert('[@stl_trl]',$arg);
        }
    }
    function load()
    {
        $cmd = '
            select r.id, r.lng, r.nme, r.val 
            from [@'.env::get_var('trl_tbl').'] r
            where r.id_trl = [0]
            ';
        $opt = '';
        $this->voc = array();
        switch($this->typ)
        {
        case 'l': // $val :lingua nel quale caricare il dizionario ... le altre non interessano
        case 'lng':
            $cmd .=' and r.lng = [1]';
            break;
        case 'n': // $val : nome da tradurre in tutte le lingue ... gli altri vocaboli non interessano
        case 'nme':
            $cmd .= ' and r.nme = [1]';
            break;
        }
        $res = $this->db->getAll($cmd,$this->id,$this->val);
        // memorizzazione
        foreach($res as $r)
        {
            $this->voc[$r['nme']][$r['lng']] = array($r['val'],$r['id']);
        }
        return $this;
    }
    function get($nl,$nme='')
    {
        switch($this->typ)
        {
        case'l':
        case 'lng': 
            return nvl($this->voc[$nl][$this->val][0],$nl);
        case 'n':
        case 'nme': 
            return nvl($this->voc[$this->val][$nl][0],$nl);
        default:
            return $this->voc[$nme][$nl][0];
        }
    }
    function gets($nl='')
    {
        $res = array();
        switch($this->typ)
        {
        case 'l':
        case 'lng': 
            foreach($this->voc as $nme=>$dat) $res[$nme] = $dat[$this->val];
            break;
        case 'n':
        case 'nme': 
            foreach($this->voc[$this->val] as $lng => $dat) $res[$lng] = $dat;
            break;
        default:
            return array();
        }
        return $res;
    }
    function mk()
    {
        switch($this->typ)
        {
        case 'l':
        case 'lng':
            $id = env::sid();
            $this->db->insert('[@'.env::get_var('trl_tbl').']',array('id'=>$id,'id_trl'=>$this->id,'lng'=>$this->val));
            return $id;
        }
        return '';
    }
}

?>