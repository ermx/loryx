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
	static function loryx($db,$form,$flds)
	{
		$fl = $form->pk;
		FB::log($flds,'loryx');
        //osy::err($flds);
        if (!is_array($flds)) $flds = array_map('trim',explode(',',$flds));
		foreach($flds as $fld)
		{
            $cmp = $form->get_cld($fld);
            if (!$cmp) osy::alert('componente non trovato : '.$fld);
			$fl['y'] = $cmp->get_prp('opensymap.org/loryx/type');
			$fl['x'] = $cmp->getValue();
			
			$db->lrx2store($fl,1);
		}
	}
    static function get_var($v)
    {
        return env::get_var('db')->merge("[{$v}]",$_POST,$_POST['_']['pky'],$_POST['_']['prt']);
    }
    function get_osy($k)
    {
        return $_POST['_']['osy'][$k];
    }
    function set_osy($k,$v)
    {
        $_POST['_']['osy'][$k]=$v;
    }
    function get_pky($k)
    {
        return $_POST['_']['pky'][$k];
    }
    function set_pky($k,$v)
    {
        $_POST['_']['pky'][$k] = $v;
    }
    function get_prt($k)
    {
        return $_POST['_']['prt'][$k];
    }
    function set_prt($k,$v)
    {
        $_POST['_']['prt'][$k] = $v;
    }
}
class Menu extends Tag
{
    public function __construct($root,$name,$ttl,$app='',$frm='')
    {
        parent::__construct('div');
        $this->Att('style','padding: 2px 5px 2px 10px;');
        if ($root)
        {
            $m = $this->Add(new Tag('b'));
        }
        else
        {
            $m = $this->Add(new Tag('span'));
        }
        $m->Att('style',' white-space:pre;')->Add($ttl);
        if ($frm)
        {
            $m->Att('onclick',"open_win('{$name}','{$app}','{$frm}')");
            $m->Att('class','option');
        }
    }
    public function addMenu($m)
    {
        $this->Add($m);
    }
}
class osyPage extends Page
{
    public function setTitle($ttl)
    {
        parent::setTitle($ttl);
        if ($this->form) $this->form->Att('osy_title',$ttl);
    }
}

include('osy_start.php');

class osy_user_manager
{
    function __construct($rs)
    {
        $this->rs = $rs;
    }
    function check($val)
    {
        //FB::log($val);
        // se non è impostato il token di autenticazione ... non c'è stata autenticazione
        if (!$val) return false;
        $db = env::get_var('dbx');
        // se non c'è un database di riferimento non è possibile fare l'autenticazione
        if (!$db) return false;
        // se non è possibile effettuare la query non si può fare l'autenticazione
        try
        {
            $usr = $db->getFirst('
                select usr.* 
                from [@osy_usr_acc] acc
                inner join [@osy_usr] usr on (acc.id_usr = usr.id)
                where acc.id = [0]',$val);
        }catch(Exception $ex)
        {
            return false;
        }
        // impostazione idioma
        env::set_lng($usr['lng']);
        $usr['prs'] = array();
        foreach($db->getAll('
                select prp, val
                from [@osy_usr_prs] prs
                where prs.id_usr = [0]',
                $usr['id']) as $dat)
        {
            $usr['prs'][$dat['prp']][] = $dat['val'];
        }
        return $usr;
    }
}
            ////////////////////////////////////////////////////////////////////
            ////////////////////////////////////////////////////////////////////
            
include ('osy_form.php');
include ('osy_cmp.php');
include ('osy_panel.php');
include ('osy_cmp.def.php');

include ('osy_grid.php');
include ('osy_cmp_combo.php');

class osy_cmp_button extends osy_cmp
{
    public function make($rs, $prp=null, $arg=null)
    {
        parent::make($rs);
        // un pulsante non è di submit se non espressamente richiesto
        $form  = $rs->get_prp('opensymap.org/form');
        $event = $rs->get_prp('opensymap.org/event');
        $evnty = $rs->get_prp('opensymap.org/event/type');
        switch($evnty)
        {
        case 'frame':
            $arg = array('type'=>'button', 'osy_type'=>$rs->get_prp('opensymap.org/type'),
                         'onclick'=>"osy.frm(this,{'osy':{'frm':'{$form}','evn':'{$event}'},'osycp':1,'frm':this.form},window)");
            break;
        default:
            $arg = array('type'=>'button', 'osy_type'=>$rs->get_prp('opensymap.org/type'),
                         'onclick'=>"osy.trigger(this,'exec',{'osy':{'frm':'{$form}','evn':'{$event}'}})");
        }
        //$arg[$click] = ;
        $rs->tag->Add(Tag::mk('button',$arg,nvl($rs->get_prp('opensymap.org/label'),'[ opensymap.org/label ]')));
        return; 
        //////////////////////////////////////////////////////////////////////////////
        // se ha una richiesta
        $debug = intval($rs->get_prp('loryx.org/debug'));
        $opt = array('osy'=>array('evn'=>"GET(this,'osy_evn')"));
        if ($debug) $opt['debug']=$debug;
        switch($rs->get_prp('opensymap.org/form/post'))
        {
        case 'all':
            $opt['form']="this.form";
            break;
        }
        $event = $rs->get_prp('opensymap.org/event');
        $form = $rs->get_prp('opensymap.org/form');
        if ($event)
        {
            switch($rs->get_prp('opensymap.org/event/save'))
            {
            case 'no':
                $arg = array('type'=>nvl($rs->get_prp('opensymap.org/type'),'button'),
                             'onclick'=>"osy.exe(this,".$this->mk_opt($opt).")",
                             'osy_evn'=>$event);
                break;
            default:
                $arg = array('type'=>nvl($rs->get_prp('opensymap.org/type'),'button'),
                             'onclick'=>"osy.exe(this,{'osy':{'evn':'save'},'form':this.form})",
                             'evn_ok'=>"osy.exe(window,".$this->mk_opt($opt).")",
                             'osy_evn'=>$event);
                break;
            }
        }
        else
        {
            if ($form)
            {
                    $arg = array('type'=>nvl($rs->get_prp('opensymap.org/type'),'button'),
                                 'onclick'=>"osy.win(this,{'osy':{'frm':'{$form}'},'pos':pos_})");
            }
        }
        $rs->tag->Add(Tag::mk('button',$arg,$rs->get_prp('opensymap.org/label')));
    }
    private function mk_opt($opt)
    {
        if (!is_array($opt)) return $opt;
        $str = array();
        foreach($opt as $a=>$b)
        {
            $str []= "'$a':".$this->mk_opt($b);
            
        }
        return '{'.implode(',',$str).'}';
    }
}
class osy_descr extends osy_cmp
{
    public function make($rs, $prp=null, $arg=null)
    {
        parent::make($rs);
        if ($val = $rs->get_prp('loryx.org/value')) $rs->value = $val;
        $rs->tag->Add(Tag::mk('div',$arg,$rs->value));
    }
}
/*
class TagEvnOk extends Tag
{
    function __construct($arg=array())
    {
        parent::__construct('div');
        $msg_id = '__ok_arg_'.rand();
        $this->Att('id',$msg_id);
        foreach($arg as $a=>$k)
        {
            $this->Add(new Tag('span'))
                 ->Att('osy_name',$a)
                 ->Add($k);
        }
        $src = $this->Add(new TagScript);
        if (count($arg))
        {
            $src->Add("
        lrx.childs(lrx.id('{$msg_id}'),'span').foreach(function(sp)
        {
            lrx.ev_start(window.frameElement,'#set_pky',GET(sp,'osy_name'),sp.innerHTML);
        });");
        }
        $src->Add("lrx.ev_start(window.frameElement,'ok');");
    }
}
class TagEvn extends TagScript
{
    function __construct($ev)
    {
        $this->Add("lrx.ev_start(window.frameElement,'{$ev}');");
    }
}
class TagEvnError extends Tag
{
    function __construct($msg)
    {
        parent::__construct('div');
        if (!is_string($msg)) $msg = print_r($msg,1);
        $this->Add($msg);
        ob_start();
        throw new Exception($msg);
    }
}
*/
class osy_event 
{
    function make($rs,$prp,$arg)
    {
        $ret = env::exe_prp($rs,'loryx.org/code',array('event'=>$rs,'form'=>$rs->get_par(),'db'=>env::get_var('db')));
        if (is_object($ret)) $rs->tag = $ret;
    }
}
?>
