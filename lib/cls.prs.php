<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

class rs_prs extends rs_urn
{
    public function __construct($urn,$typ='')
    {
        parent::__construct($urn);
        if ($typ) $this->set_prp('loryx.org/type',$typ);
    }
}

class prs
{
    private $data;
    private $buff;
    private function tick()
    {
        if (!count($this->buff)) $this->tock();
        return array_shift($this->buff);
    }
    private function tock()
    {
        while (count($this->data))
        {
            if (!$d) $d = array_filter(explode(' ',trim(array_shift($this->data))),'strlen');
            $dd = array();
            // elimino le chiavi che non hanno elementi
            while($dd[] = array_shift($d));
            $d = $dd;
            switch($d[0])
            {
            case '[':
            // inizion elemento
                $tb = array();
                // se non ha nome ... allora ne assegno uno     
                if ($d[1]=='?') $d[1] = env::sid('tbl',7);
                $tb[0] = array('B',$d[1]);
                if ($d[2]!=']')
                {
                    $tb[0][2] = $d[2];
                    if ($d[3]==']') $tb[] = array('E');
                }
                else 
                {
                    $tb[]=array('E');
                }
                foreach($tb as $t) $this->buff[] = $t;
                return;
            case ']':
            // fine elemento
                $this->buff[] = array('E');
                return;
            case '%' :
            // traduzione proprietà precedente :: % [idioma] (valore)|'%' [endstr]
                if ($d[2]=='%') // prop multiriga
                {
                    $val = array();
                    while(count($this->data))
                    {
                        $dt = trim(array_shift($this->data));
                        if ($dt == $d[3]) break;
                        $val[] = $dt;
                    }
                    $d[2] = implode(NL,$val);
                    $d[3] = '';
                }
                $this->buff[] = array('T',$d[1],$d[2]);
                unset($d);
                break;
            case'':
                unset($d);
                break;
            case'#':
                unset($d);
                break;
            default:
                $d0 = $d[0]{0};
                $break = 0;
                switch($d0)
                {
                case '[': // begin rs
                case '#': // comment
                case '%': // begin traslation
                    $d[0] = substr($d[0],1);
                    array_unshift($d,$d0);
                    $break = 1;
                }
                if ($break) break;
                if ($d[1]=='%') // prop multiriga
                {
                    $val = array();
                    while(count($this->data))
                    {
                        $dt = trim(array_shift($this->data));
                        if ($dt == $d[2]) break;
                        $val[] = $dt;
                    }
                    $d[1] = implode(NL,$val);
                    $d[2] = '';
                }
                $p = array_shift($d);
                $e = array_pop($d);
                if ($e!=']') $d[] = $e;
                $this->buff[] = array('P',$p,trim(implode($d,' ')));
                if ($e==']') $this->buff[] = array('E');
                unset($d);
                break;
            }
        }
    }
    function parse($data,$opt=array())
    {
        if (!is_array($data)) $data = explode(NL,$data);
        $this->data = $data;
        $this->buff = array();
        $els = array();
        $i = 0;
        while($t = $this->tick())
        {
            switch($t[0])
            {
            case 'B':
                if (!$cur)
                {
                    $el = new rs_urn($t[1]);//env::get_rs($t[1]);
                    //if(!$el) $el = env::set_rs(new rs_urn($t[1]));
                    $els[$el->get_urn()] = $el;
                    $root = $el;
                }
                else
                {
                    $el = $cur->get_cld($t[1],3);
                }
                $el->set_prp('loryx.org/type',$t[2]);
                $cur = $el;
                unset($el);
                break;
            case 'P':
                if (!$cur)
                {
                    var_dump('prs.parse : prp su oggetto null',$t);
                    exit;
                }
                $cur->set_prp($t[1],utf8_encode($t[2]));
                $pcur = $t[1];
                if ($root->get_prp('loryx.org/debug',1))
                {
                    FB::log(array($root->dump()),$t[1]);
                }
                //var_dump($t,$cur->dump());
                break;
            case 'T':
                if (!$cur)
                {
                    var_dump('prs.parse : prp su oggetto null',$t);
                    exit;
                }
                $cur->trl[$pcur][$t[1]] = utf8_encode($t[2]);
                if ($root->get_prp('loryx.org/debug',1))
                {
                    FB::log(array($root->dump()),$t[1]);
                }
                //var_dump($t,$cur->dump());
                break;
            case 'E':
                if (!$cur)
                {
                    var_dump('prs.parse : end su oggetto null',$t);
                    exit;
                }
                //if (!$cur->get_par()) var_dump($cur->dump());
                $cur = $cur->get_par();
                break;
            default:
                var_dump('prs.parse : tocken sconosciuto',$t);
                exit;
            }
        }
        return $els;
    }
}
?>
