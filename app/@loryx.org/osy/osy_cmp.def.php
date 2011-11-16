<?php

/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

class osy_cmp_radio extends osy_cmp
{
    public function make($rs, $prp=null, $arg=null)
    {
        parent::make($rs);
        $rs->tag = $rs->tag->Add(new Table)
                       ->Att('width','')
                       ->Att('onmouseup',"lrx.search(this,'input')[0].checked = true;")
                       ->Att('class','option');
        $inp = $rs->tag->Cell(new TagInputPost($rs->get_prp('loryx.org/name'),'radio'),1);
        if ($inp->value == $rs->get_prp('loryx.org/value')) $inp->Prp('checked');
        $inp->Att('value',$rs->get_prp('loryx.org/value'));
        $inp->Att('onchange','this.form.upd = true;');
        $rs->tag->Cell($rs->get_prp('opensymap.org/label'))
            ->Att('style','vertical-align:bottom');
        return;
    }
}

class osy_cmp_check extends osy_cmp
{
    public function make($rs, $prp=null, $arg=null)
    {
        parent::make($rs);
        $inp = $rs->tag->Add(new TagInput($rs->get_prp('loryx.org/name'),'1','checkbox'));
        $inp->Att('onchange','this.form.upd = true;');
        if ($rs->value) $inp->Prp('checked');
    }
}

class osy_cmp_text extends osy_cmp
{
    public function make($rs, $prp=null, $arg=null)
    {
        parent::make($rs);
        if ($rs->get_prp('opensymap.org/line')>1)
        {
            $inp = new Tag('textarea');
            $inp->Att('name',$rs->name)
                ->Att('rows',$rs->get_prp('opensymap.org/line'))
                ->Att('style','width:100%')
                ->Add(htmlspecialchars($rs->value));
        }
        else
        {
            $inp = new TagInputPost($rs->name);
            $inp->Att('value',$rs->value);
            $inp->Att('type',$rs->get_prp('opensymap.org/type'));
        }
        if($rs->get_prp('opensymap.org/type/readonly'))
        {
            $inp->Prp('readonly');
        }
		
        // è richiesta una espressione regolare?
        if($re = $rs->get_prp('opensymap.org/value/regexp')) $inp->Att('osy_regexp',$re);
        $rs->tag->Att('osy_map',$rs->name);
        if ($rs->get_prp('opensymap.org/title/view')=='no')
        {
            $rs->tag->Add($inp);
            return;
        }
        $tbl = $rs->tag->Add(new Table);
        $tbl->Cell($rs->get_prp('opensymap.org/title'));
        $tbl->Cell($inp);
        return;
    }
}

class osy_cmp_view extends osy_cmp
{
    public function make($rs, $prp=null, $arg=null)
    {
        parent::make($rs);
        $cmp = new Tag('div');
        $inp = $cmp->Add(new TagInputPost($rs->name,'hidden'));
        $inp->Att('value',$rs->value);
        $cmp->Add(new Tag('span'))
            ->Add($inp->value);
        if ($rs->get_prp('opensymap.org/title/view')=='no')
        {
            $rs->tag->Add($cmp);
            return;
        }
        $tbl = $rs->tag->Add(new Table)
                       ->Att('width','');
        $tbl->Cell($rs->get_prp('opensymap.org/title'));
        $tbl->Cell($cmp);
        return;
    }
}
?>