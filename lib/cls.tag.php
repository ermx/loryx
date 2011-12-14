<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

function I($o,$i=-1){if ($i<0) return $o; return $o[$i];}

  class Tag{
    public $Tag = '';
    public $Att = Array();
    public $NodeType;
    public $Par = null;
    public $First = null;
    public $Last = null;
    public $Prec = null;
    public $Succ = null;
    protected $Raw = '';
    private $Emp = 0;
    private static $_ids = array();
    const ELEMENT_NODE = 1;
    const ATTRIBUTE_NODE = 2;
    const TEXT_NODE = 3;
    const CDATA_SECTION_NODE = 4;
    const ENTITY_REFERENCE_NODE = 5;
    const ENTITY_NODE = 6;
    const PROCESSING_INSTRUCTION_NODE = 7;
    const COMMENT_NODE = 8;
    const DOCUMENT_NODE = 9;
    const DOCUMENT_TYPE_NODE = 10;
    const DOCUMENT_FRAGMENT_NODE = 11;
    const NOTATION_NODE = 12;
    public function __construct($tag,$emp=0,$typ=self::ELEMENT_NODE){
        $this->Tag = $tag;
        $this->Emp = $emp;
        $this->NodeType = $typ;
    }
    public function __get($Attr){
        return $this->Att[$Attr];
    }

    public function Add($a,$t=0){
        if (!$a) return;
        if (!is_object($a))
        {
            $this->Add(new Tag('',0,self::TEXT_NODE))
                 ->Raw = $a.'';
            return $this;
        }
        $a->Del();
        if($t)
        {
            if (!$this->Last) $this->Last = $a;
            $a->Succ = $this->First;
            if ($this->First) $this->First->Prec = $a;
            $this->First = $a;
        }
        else
        {
            if (!$this->First) $this->First = $a;
            $a->Prec = $this->Last;
            if ($this->Last) $this->Last->Succ = $a;
            $this->Last = $a;
        }
        $a->Par = $this;
        return $a;
    }
    function Cnt($a,$t=0)
    {
        $this->Add($a,$t);
        return $this;
    }
    public function Del(){
        if ($this->Prec) $this->Prec->Succ = $this->Succ;
        if ($this->Succ) $this->Succ->Prec = $this->Prec;
        if ($this->Par){
            if ($this->Par->First==$this) $this->Par->First = $this->Succ;
            if ($this->Par->Last ==$this) $this->Par->Last = $this->Prec;
        }
        $this->Par = $this->Succ = $this->Prec = null;
    }
    public function Build($depth){
        $Spc = $depth > 0 ? str_repeat('   ',$depth) : '';
        switch($this->NodeType)
        {
        case self::TEXT_NODE:
            $Raw = //$Spc.
                   $this->Raw;
            break;
        default:
            $Begin = $End = '';
            if ($this->Tag)
            {
                $Begin = "{$Spc}<{$this->Tag}";
                $SpcAtt = str_repeat(' ',strlen($Begin));
                $AttCc = 0;
                foreach($this->Att as $KAtt=>$VAtt)
                {
                    if (is_array($VAtt)) FB::log($VAtt);
                    if ($AttCc++ and $AttCc<=count($this->Att) and !$this->noAttNL) $Begin.=NL.$SpcAtt;
                    $Begin .= " ".$KAtt.'="'.htmlspecialchars($VAtt).'"';
                }
                if (!$this->Emp or $this->First)
                {
                    $Begin .= '>';
                    $End   = "</{$this->Tag}>";
                }
                else
                {
                    $Raw = $Begin.'/>';
                    break;
                }
            }
            if ($Cnt = $this->BuildChilds($depth,$inline) and !$inline) $Cnt.=NL.$Spc;
            /*
            if ($this->First and
                $this->First==$this->Last and 
                $this->First->NodeType == self::TEXT_NODE and
                $this->isInline()) $Cnt=trim($Cnt);*/
            if ($fnc = $this->fnc /*and function_exists($fnc)*/)  $Cnt = $fnc($Cnt);
            
            $Raw = $Begin.$Cnt.$End;
            
            break;
        }
        return str_replace("\n\n","\n",$Raw);
    }
    public function BuildChilds($depth,&$inline)
    {
        $a = $this->First;
        $nl = ($a?($a->NodeType==self::TEXT_NODE?'':NL):'');
        while($a) 
        {
            $Cnt .= $nl.$a->Get(empty($this->Tag)?$depth:$depth+1);
            $a = $a->Succ;
            if ($a) $nl = NL;
        }
        $inline = ($nl=='');
        return $nl.$Cnt;
    }
    public function Get($depth=0){
        return $this->Build($depth);
    }

    public function Att($p,$v=''){
        if (is_array($p)){
            foreach ($p as $k => $v){
              $this->Att($k,$v);
            }
        } else{
            $this->Att[$p] = $v;
            if ($p=='id') self::$_ids[$v] = $this;
        }
        return $this;
    }
    public function AttAp($p,$v)
    {
            $this->Att[$p] .= $v;
            return $this;
    }
    public function Prp($p)
    {
        return $this->Att($p,$p);
    }
    public function __toString()
    {
        return $this->Get();
    }
    public static function Id($s)
    {
        return self::$_ids[$s];
    }
    public function isInline(){return true;}
    public static function mk($tag,$attr=false,$cdata=false)
    {
        $t = new Tag($tag);
        if (!$attr) $attr = array();
        $t->Att($attr);
        $t->Add($cdata);
        return $t;
    }
    public function clear()
    {
        $this->First = null;
        $this->Last = null;
    }
  }
  
  class Page extends Tag{

     private $Part = Array();
     private $title;
     public function __construct(){
        $this->Part['DOCTYPE'] = 'HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"';
        $this->Part['HTML'] = $this->Add(new Tag('html'));
        $this->Part['HEAD'] = $this->Part['HTML']->Add(new Tag('head'));
        $this->Part['BODY'] = $this->Part['HTML']->Add(new Tag('body'));
        
        $this->title = $this->Part['HEAD']->Add(new Tag('title'))->Add(new Tag('',1,Tag::TEXT_NODE));
        $this->head = $this->Part['HEAD'];
        $this->body = $this->Part['BODY'];
     }
     
     public function AddBody($o){
        return $this->Part['BODY']->Add($o);
     }

     public function AddScript($src=''){
        return $this->Part['HEAD']->Add(new TagScript($src));
     }

     public function AddCss($path){
        $s = $this->Part['HEAD']->Add(new Tag('link'));
        $s->Att('rel' ,'stylesheet');
        $s->Att('href',$path);
        return $s;
     }
     public function AddStyle(){
        $s = $this->Part['HEAD']->Add(new Tag('style'));
        return $s;
     }
     
     public function Get(){
        $cnt = parent::Get(-1);
        return "<!DOCTYPE {$this->Part['DOCTYPE']}>\n".$cnt;
     }
     
     public function Part($p,$v=false){
        if ($v !== false) $this->Part[strtoupper($p)] = $v;
        return $this->Part[strtoupper($p)];
     }

     public function SetDocType($d){
        $this->Part['DOCTYPE'] = $d;
     }
     
     public function SetTitle($t){
        $this->title->Raw = $t;
     }
  }
  
class TagScript extends Tag{
    public function __construct($src=''){
        parent::__construct('script');
        $this->Att('type','text/javascript');
        if($src) $this->Att('src',$src);
    }
    public function BuildChilds($depth)
    {
        if (!$this->First) return '';
        return NL.
               '//<[CDATA['.NL.
               parent::BuildChilds(-1,$inline).NL.
               '//]]>'.NL;
    }
    public function isInline(){return false;}
  }

  class Table extends Tag{
     
     private $CurRow;
     private $CurCell;
     
     public function __construct(){
        parent::__construct('table');
        
        $this->Att('width','100%')
             ->Att('cellpadding','0px')
             ->Att('cellspacing','0px');
     }
     
     public function Row(){
        $this->CurRow = parent::Add(new Tag('tr'));
        return $this->CurRow;
     }
     
     public function Head($val,$ret_val=0){
        if (!$this->CurRow) $this->Row();
        $this->CurCell = $this->CurRow->Add(new Tag('th'));
        $this->CurCell->Add($val);
        switch($ret_val)
        {
        case 0 : return $this->CurCell;
        case 1 : return $val;
        }
        return array($this->CurCell,$val);
     }
     
     public function Cell($val,$ret_val=0){
        if (!$this->CurRow) $this->Row();
        $this->CurCell = $this->CurRow->Add(new Tag('td'));
        $this->CurCell->Add($val);
        switch($ret_val)
        {
        case 0 : return $this->CurCell;
        case 1 : return $val;
        }
        return array($this->CurCell,$val);
     }
     
     public function GetRow(){
        return $this->CurRow;
     }
     public function Add($ch){
        if (!$this->CurCell) return $this->Cell($ch,1);
        return $this->CurCell->Add($ch);
     }
  }
  
  class TagInput extends Tag
  {
     public function __construct($name,$value='',$type='text',$id='')
     {
        parent::__construct('input',1);
        $this->noAttNL = true;
        if ($id) $this->Att('id',$id);
        $this->Att('name',$name)
             ->Att('type',$type)
             ->Att('value',$value);
    }
    public function getValue()
    {
        return $this->value;
    }
    public function setValue($v)
    {
        return $this->Att('value',$v);
    }
    public function Get($d=0)
    {
        $this->Att('value',$this->getValue());
        return parent::Get($d);
    }
  }
  class TagButton extends Tag
  {
     public function __construct($label,$cmd='')
     {
        parent::__construct('button');
        $this->noAttNL = true;
        $this->Att('type','button')
             ->Add($label);
        if ($cmd) $this->Att('onclick',$cmd);
    }
  }
class TagInputPost extends TagInput
{
    public function __construct($name,$type='text',$id='')
    {
        $v = $_POST;
        foreach(explode('[',$name) as $nm)
        {
            $nm = trim($nm,']');
            $v = $v[$nm];
        };
        parent::__construct($name, $v,$type,$id);
    }
}
class TagImg extends Tag
{
	function __construct($src)
	{
		parent::__construct('img',1);
        $this->Att('src',$src);
	}
}
class TagForm extends Tag
{
    function __construct($method='POST')
    {
        parent::__construct('form');
        $this->Att('method',$method);
    }
}

class TagSelect extends Tag
{
    function __construct($nme='',$val='',$id='')
    {
        static $cc = 0;
        parent::__construct('select');
        if (!$nme) $nme = '_select_'.($c++);
        $this->value = $val;
        $this->Att('name',$nme);
        if ($id) $this->Att('id',$id);
        $this->args = array();
    }
    function setQuery()
    {
        $this->args = func_get_args();
        return $this;
    }
    function hasEmpty($t=true)
    {
        $this->e = $t;
        return $this;
    }
    function Get($dep=0)
    {
        if ($this->e) $this->Add(new Tag('option'))->Att('value','')->Add('- select -');
        if ($this->args[0])
        {
            $ds = call_user_func_array(array(sit()->db,'getAll'),$this->args);
            FB::log($ds);
            if (count($ds))
            {
                $k = array_keys($ds[0]);
                $ngrp = '';
                if (!$this->grp)
                foreach($ds as $d)
                {
                    $op = $this->Add(new Tag('option'))->Att('value',$d[$k[0]])->Add($d[$k[1]]);
                    if ($op->value==$this->value)
                    {
                        $op->Prp('selected');
                    }
                }
                else
                foreach($ds as $d)
                {
                    if ($ngrp != $d[$this->grp])
                    {
                        $ngrp = $d[$this->grp];
                        $ogrp = $this->Add(new Tag('optgroup'))->Att('label',$d[$k[1]]);
                    }
                    else 
                    {
                        $op = $ogrp->Add(new Tag('option'))->Att('value',$d[$k[0]])->Add($d[$k[1]]);
                        if ($op->value==$this->value)
                        {
                            $op->Prp('selected');
                        }
                    }
                }
            }
        }
        return parent::Get($dep);
    }
    function setGroup($fld)
    {
        $this->grp = $fld;
    }
}
class TagSelectPost extends TagSelect
{
    public function __construct($name='',$id='')
    {
        $v = $_POST;
        foreach(explode('[',$name) as $nm)
        {
            $nm = trim($nm,']');
            $v = $v[$nm];
        };
        parent::__construct($name, $v,$id);
    }
}
/*  echo memory_get_usage() . "\n"; // 57960
  $p = new Page();
  $p->SetTitle('HelloWord! page');
  $p->AddScript('ciao');
  
  $t = $p->AddBody(new Tag('form'))->Add(new Table());
  $t->Head('Hello')->Att('style','font-size: 20px;');
  $t->Row();
  $t->Cell('Word!sdgdfgdfgdfgdfg');
  $t->Row();
  $t->Cell('Word!sdgdfgdfgdfgdfg');
  echo memory_get_usage() . "\n"; // 57960
  echo $p->Get();
  $prova = <<<EOF
Ciao sto provando HEREDOC
EOF;

echo $prova;*/
?>
