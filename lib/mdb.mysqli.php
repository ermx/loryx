<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

abstract class mdb
{

    function setRs($rs=null)
    {
        if ($rs) $this->rs = $rs;
        return $this->rs;
    }    
    function rs2make($rs) {}
    function rs2store($rs,$tbl_lrx) {}
    abstract static function key($rs);
    
    function insert(){}
    function update(){}
    function delete(){}
    function select($tbl,$wh,$num=0){}
    function query(){}
    function getVal(){}
    function getFirst(){}
    function getList(){}
    function getPage(){}
    function getAll(){}
    function merge()
    {
        //if ($this->rs) $this->rs = env::get_rs($this->rs->get_urn());
        $args = func_get_args();
        // il primo argomento è la stringa da modificare
        // gli altri sono le variabili da sostituire.
        
        // Se un argomento è una array allora le variabili da sostituire sono
        // alphanumeriche altrimenti sono numerirche e quindi posizionali. 
        // E' possibile distinguere le variabili di diversi array attraverso il prefisso '#'*.
        
        // sostituzione :
        // <[..]> -- valore senza modifiche
        // [[..]] -- valore con quota degli apici ma senza inserire gli apici all'inizio o alla fine
        // [..]   -- valore con quota degli apici e inserendo gli apici all'inizio e alla fine
        $cmd = array_shift($args);
        // prima vengono impostati i nomi delle tabelle        
        if(is_array($this->rs->map)) foreach($this->rs->map as $pc=>$lst)
        {
            $pp = array_shift($lst)->name;
            $cmd = str_replace('[@'.$pc.']',$pp,$cmd);
        }
        
        $pre = '';
            //FB::log($args,'merge');
        foreach($args as $c=>$p)
        {
            if (is_array($p))
            {
                $sp = array();
                foreach($p as $pc=>$pp) if (!is_array($pp)) 
                {
                    $cmd = str_replace('<['.$pre.$pc.']>',$pp,$cmd);
                    $cmd = str_replace('[['.$pre.$pc.']]',$this->str($pp,''),$cmd);
                    $cmd = str_replace('['.$pre.$pc.']',$this->str($pp),$cmd);
                }
            }
            else 
            {
                $cmd = str_replace('<['.$c.']>',$p,$cmd);
                $cmd = str_replace('[['.$c.']]',$this->str($p,''),$cmd);
                $cmd = str_replace('['.$c.']',$this->str($p),$cmd);
            }
			$pre .= '#';
        }
        $this->rs->cmd = $cmd;
        //var_dump($cmd);
        return $cmd;
    }
    function str($s,$w=''){return $w.$s.$w;}
}

class mdb_mysqli extends mdb
{
    static function key($rs)
    {
        return implode(':',array($rs->get_prp('loryx.org/db/conf/type'),
                                 $rs->get_prp('loryx.org/db/conf/host'),
                                 $rs->get_prp('loryx.org/db/conf/user'),
                                 $rs->get_prp('loryx.org/db/conf/dbname')));
    }
    function connect()
    {
        $this->cn = @new mysqli($this->rs->get_prp('loryx.org/db/conf/host'),
                               $this->rs->get_prp('loryx.org/db/conf/user'),
                               $this->rs->get_prp('loryx.org/db/conf/password'));
                           
        if (!$this->cn->select_db($this->rs->get_prp('loryx.org/db/conf/dbname')))
        {
            // creazione DB
            $this->query('CREATE DATABASE '.$this->rs->get_prp('loryx.org/db/conf/dbname'));
            $this->cn->select_db($this->rs->get_prp('loryx.org/db/conf/dbname'));
        }
        if ($this->cn->connect_errno) 
        {
            throw new Exception('Errore di connessione : ' . $this->cn->connect_error);
        }
    }
    function close()
    {
        $this->cn->close();
    }
	public function GetNextRecord($rs,$typ=''){
        if ($rs){
            switch($typ){
             case 'ASSOC' : return $rs->fetch_array(MYSQLI_ASSOC); break;
             case 'NUM'   : return $rs->fetch_array(MYSQLI_NUM);   break;
             default      : return $rs->fetch_array(MYSQLI_BOTH);  break;
           }
        } else {
            return array();
        }
    }
    public function GetAllRecord($rs,$type=''){
    
        $all_rec = array();
        
        if (!$rs) return $all_rec;
        
		while ($rec = $this->GetNextRecord($rs,$type)) $all_rec[] = $rec;

		$this->FreeRs($rs);

		return $all_rec;
	}
    public function FreeRs($rs)
    {
        if (is_resource($rs)) $rs->free();
    }
    function noexe()
    {
        $this->no_exe = true;
    }
    function query()
    {
        $start = env::get_time();
        $args = func_get_args();
        //var_dump($this->rs->dump());
        if (!$this->cn) $this->connect();
        $cmd = call_user_func_array(array($this,'merge'),$args);
        if ($this->no_exe) throw new Exception($cmd);
        $begin = env::get_time();
        if (empty($cmd)) throw new Exception('Query vuota');
        $rs = $this->cn->query($cmd);
        $end = env::get_time();
        //if (!$this->fb_disabled) FB::log(array(trim($cmd),$start.' : '.$begin.' : '.$end),'query');
        if (!$rs) throw new Exception('Query errata '.NL.NL.$cmd.NL);
        $this->cmd = $cmd;
        return $rs;
    }
    function last_sql()
    {
        return $this->cmd;
    }
    function str($s,$wrap="'")
    {
        if (is_array($s))
        {
            var_dump($s);
            exit;
            throw new TraceEx();
        }
        return $wrap.$this->cn->real_escape_string($s).$wrap;
    }
    function nolimit()
    {
        $this->_nolimit = true;
        return $this;
    }
    function getFirst()
    {
        $args = func_get_args();
        if (!$this->_nolimit) $args[0] .= " limit 0,1";
        $rs = call_user_func_array(array($this,'query'),$args);
        if ($rs)
        {
            $dt = $this->GetNextRecord($rs,'ASSOC');
            $this->FreeRs($rs);
        }
        if (!$dt) $dt = array();
        return $dt;
    }
    function getVal()
    {
        $args = func_get_args();
        $dt = call_user_func_array(array($this,'getFirst'),$args);
        return array_shift($dt);
    }
    function getAll()
    {
        $args = func_get_args();
        $rs = call_user_func_array(array($this,'query'),$args);
        if (!$rs) return array();
        return $this->GetAllRecord($rs,'ASSOC');
    }
    function getList()
    {
        $args = func_get_args();
        $rs = call_user_func_array(array($this,'getAll'),$args);
        $ret = array();
        if(!is_array($rs[0])) return $ret;
        $kk = array_keys($rs[0]);
        switch(count($kk))
        {
        case 0 : break;
        case 1 : 
            foreach($rs as $r) $ret[] = $r[$kk[0]];
            break;
        case 2 :
            foreach($rs as $r) $ret[$r[$kk[0]]] = $r[$kk[1]];
            break;
        default :
            foreach($rs as $r) $ret[$r[$kk[0]]] = $r;
            break;
        }
        return $ret;
    }
    function getPage($cmd_opt, $var=array(), $pky=array(), $prt=array())
    {
        list($sql,$elems,$pag,$group) = $cmd_opt;
        $dt = new stdClass;
        $group['elems'] = 'count(*)';
        $gf = array();
        foreach($group as $f=>$c) $gf[] = "{$c} as $f";
        $dt->group = $this->getFirst('select '.implode(',',$gf).' from ('.$sql.') x',$var,$pky,$prt);
        //var_dump($this->sql);
        $dt->page = array('max'=>max(ceil($dt->group['elems']/$elems),1),'cur'=>max($pag,1));
        $dt->page['cur'] = min($dt->page['cur'],$dt->page['max']);
        $limit = ($dt->page['cur']-1)*$elems;        
        $data = $this->getAll($sql." limit {$limit},{$elems}",$var,$pky,$prt);
        return array($data,$dt);
    }
    function select($tbl,$wh,$num=0)
    {
        $cmd = "select s.* from ({$tbl}) s where ".implode(' and ',$wh)."";
        if ($num) return $this->getFirst($cmd,$_POST,$_POST['_']['pky'],$_POST['_']['pry']);
        return $this->getAll($cmd,$_POST,$_POST['_']['pky'],$_POST['_']['pry']);
    }
    function insert($tbl,$arg)
    {
        $into = array();
        $values = array();
        $cmd = 'insert into '.$tbl;
        foreach($arg as $k=>$v)
        {
            $into [] = $k;
            $values [] = "[{$k}]";
        }
        $cmd .= '('.implode(',',$into).') values ('.implode(',',$values).')';
        $this->FreeRs($this->query($cmd,$arg));
    }
    function update($tbl,$arg,$cnd)
    {
        $fld = array();
        foreach($arg as $k=>$v)
        {
            $fld[] = "{$k} = [{$k}]";
        }
        $wh = array();
        if (!is_array($cnd)) $cnd = array('id'=>$cnd);
        foreach($cnd as $k=>$v)
        {
            if ($v=='now')
            {
                $v = "now()";
            }
            else
            {
                $v = "[#{$k}]";
            }
            $wh[] = $k.'='.$v;
        }
        $cmd .= 'update '.$tbl.' set '.implode(', ',$fld).' where '.implode(' and ',$wh);
        $this->FreeRs($this->query($cmd,$arg,$cnd));
    }
    function delete($tbl,$cnd)
    {
        $cmd = 'delete from '.$tbl;
        $wh = array();
        if (!is_array($cnd)) $cnd = array('id'=>$cnd);
        foreach($cnd as $k=>$v)
        {
            $wh[] = "{$k} = [{$k}]";
        }
        $cmd .= ' where '.implode(' and ',$wh);
        $this->FreeRs($this->query($cmd,$cnd));
        
    }
    function rs2make($rs)
    {
        $this->nolimit();
        
        foreach($rs->get_clds() as $ch)
        {
            FB::log($ch->get_urn(),'make db obj');
            switch($ch->get_styp())
            {
            case 'loryx.org/db/table':
                $tbl_name = nvl($ch->get_prp('loryx.org/db/table'),$ch->get_prp('loryx.org/name'));
                // se il nome è già presente ...
                if ($this->getVal('SHOW TABLES LIKE [0]',$tbl_name))
                {
                    // se è richiesto che l'applicazione lavori su questa specifica tabella 
                    // essa non viene ricreata 
                    // altrimenti viene individuato un nuovo nome
                    if ($ch->get_prp('loryx.org/db/table'))
                    {
                        $ch->set_prp('loryx.org/db/table/nocreate',1);
                    }
                    else
                    {
                        $cnt_tbl = env::sid('low',30);
                        $pfx_tbl = $this->rs->get_prp('loryx.org/db/table/prefix');
                        $tbl_name = $pfx_tbl."_".$cnt_tbl;
                    }
                }
                
                $tch = $this->rs->set_cld($tbl_name)
                            ->set_prp('loryx.org/type','loryx.org/db/table')
                            ->set_prp('loryx.org/name',$ch->get_prp('loryx.org/name'));
                if ($ch->get_prp('loryx.org/db/table/nocreate'))
                {
                    $this->rs->set_prp('loryx.org/db/table/noreate',1);
                    continue;
                }
                //$cmd = NL."DROP TABLE IF EXISTS {$tbl_name} ;".NL;
            FB::log(array($this->rs->dump()));
                try{
                    $this->query($cmd);
            FB::log(array($this->rs->dump()),'post quer');
                }catch(Exception $e)
                {}
                
                $cmd = NL."CREATE TABLE {$tbl_name} ".NL;
                $flds = array();
                foreach($ch->get_clds() as $fld)
                {
                    $size = $fld->get_prp('loryx.org/db/field/size');
                    switch($ty = $fld->get_prp('loryx.org/db/field/type'))
                    {
                    case 'varchar':
                        $type = "{$ty} ({$size})";
                        break;
                    case 'text':
                        $type = "{$ty}";
                        break;
                    case 'number':
                        $type = "integer ({$size})";
                        break;
                    case 'datetime':
                        $type = "timestamp";
                        break;
                    default:
                        $type = $ty;
                    }
                    
                    switch($chs = $fld->get_prp('loryx.org/db/charset'))
                    {
                    case 'utf8':
                        $chs .= ' collate utf8_bin';
                    }
                    if (strlen($chs)) $chs = " character set $chs";
                    
                    if($def=$fld->get_prp('loryx.org/db/field/default'))
                    {
                        switch($ty)
                        {
                        case 'varchar':
                        case 'text':
                            $def = $this->str($def);
                            break;
                        case 'datetime':
                            if ($def=='now') $def ="now()";
                            break;
                        }
                        $def = " default {$def}";
                    }
                    
                    if($fld->get_prp('loryx.org/db/field/default/null')>0)
                    {
                        $def = " default null";
                    }
                    switch($fld->get_styp())
                    {
                    case 'loryx.org/db/field':
                    	$flds [] = $fld->get_prp('loryx.org/name')." ".$type.$chs.$def.($fld->get_prp('loryx.org/db/pkey')?' primary key':'');
                    	break;
                    case 'loryx.org/db/pkey':
                        $flds[] = "primary key (".$fld->get_prp('loryx.org/db/field').")";
                        break;
                    }
                }
                $cmd .= "(".implode(",".NL,$flds).");".NL;
            FB::log(array($this->rs->dump()));
                $this->query($cmd);
                break;
            // se non so cos'è ...
            default:
                $ch->exe();
                break;
            }
        }
        $this->rs2store($this->rs);
        return $cmd;
    }
	function lrx2store($fld,$opt='')
	{
        $algo = 'sha512';

		switch($opt)
		{
		case 'upd':
			if(empty($fld['k'])) $fld['k']=env::sid('',40);
			$fld['s']=hash($algo,"{$fld['o']}/{$fld['r']}@{$fld['l']}#{$fld['y']}");
			$this->update('[@loryx]',$fld,array('l'=>$fld['l'],'o'=>$fld['o'],'r'=>$fld['r'],'y'=>$fld['y']));
			break;
		case 'del':
		case 1:
			$this->delete('[@loryx]',array('l'=>$fld['l'],'o'=>$fld['o'],'r'=>$fld['r'],'y'=>$fld['y']));
			// no break;
		default:
			$fld['k']=env::sid('',50);
			$fld['s']=hash($algo,"{$fld['o']}/{$fld['r']}@{$fld['l']}#{$fld['y']}");
			$this->insert('[@loryx]',$fld);
		}
		//FB::log($fld,$algo);
		return array($fld['s'],$fld['k']);
	}
    function rs2store($rs,$opt=array())
    {
        
        switch($rs->get_prp('loryx.org/store'))
        {
        case 'no' : return;
        default:
            break;
        }
        if (!$opt['nodelete'])
        {
            $cmd = "
            /* */ 
                delete from [@loryx] 
                where l=[0] and ((o = [1] and r=[2]) or 
                                 (substr(concat(o,'/'),1,length('<[1]>/<[2]>/'))='<[1]>/<[2]>/'))";
            $this->query($cmd,$rs->sys,$rs->base,$rs->name);
        }
        $fb = $this->fb_disabled;
        if (!$this->fb_disabled) FB::log(array($rs->dump()),'rs2store '.$rs->get_urn());
        $this->fb_disabled = true;
        $has_prp;
        foreach(array('loryx.org/type'=>$rs->get_styp(),
                      'loryx.org/urn'=>$rs->get_urn('',false)) as $p=>$v)
        {
            list($sha) = $this->lrx2store(array('l'=>$rs->sys,'o'=>$rs->base,'r'=>$rs->name,'y'=>$p,'x'=>$v));
            // salvataggio traduzioni
            if (is_array($rs->trl[$p])) foreach($rs->trl[$p] as $lng=>$val)
            {
                $this->insert('[@loryt]',array('s'=>$sha,'l'=>$lng,'v'=>$val));
            }
        }
        foreach($rs->prp as $p=>$v)
        {
            $continue = 0;
            switch($p)
            {
            case 'loryx.org/type':
            case 'loryx.org/urn':
                $continue = 1;
                break;
            case 'loryx.org/store/data/time':
            case 'loryx.org/load/data':
                if ($rs->get_prp('loryx.org/load/data/nostore')) $continue = 1;
                break;
            }
            if ($continue) continue;
            list($sha) = $this->lrx2store(array('l'=>$rs->sys,'o'=>$rs->base,'r'=>$rs->name,'y'=>$p,'x'=>$v));
            // salvataggio traduzioni
            if (is_array($rs->trl[$p])) foreach($rs->trl[$p] as $lng=>$val)
            {
                $this->insert('[@loryt]',array('s'=>$sha,'l'=>$lng,'v'=>$val));
            }
            $has_prp++;
        }

        foreach($rs->get_clds() as $ch)
        {
            self::rs2store($ch,array('nodelete'=>1));
        }
        $this->fb_disabled = $fb;
    }
}
?>