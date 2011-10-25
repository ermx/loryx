<?php
if (count($this->menu['top']))
{
    // area autenticazione/registrazione/benvenuto
    $ul_top = new Tag('ul');

    if ($this->usr['id'])
    {
        $ul_top->Add(new Tag('li'))
           ->Att('class','bb_r')
           ->Add(new Tag('a'))
           ->Att('href',$this->url('/usr/'))
           ->Add($this->lbl->get('benvenuto').' '.$this->usr['uname']);
    }
    foreach($this->menu['top'] as $m)
    {
        //FB::log($m);
        if ($m['cmd']) 
        {
            // se esiste un codice di condizione viene valutato
            $f = create_function('',$m['cmd']);
            if (!$f()) continue;
        }
        $li = $ul_top->Add(new Tag('li'));
        $li->Add(new Tag('a'))
           ->Att('href',$this->url("/{$m['url']}/"))
           ->Att('ttl',$m['ttl'])
           ->Add($m['lbl']);
        if ($this->uri->sec == $m['id']) $li->Att('class','cur');
    }
}

$ul_sect = new Tag('ul');
foreach($this->menu['bar'] as $m)
{
    if ($i++) $ul_sect->Add(new Tag('li'))->Add('|');
    $li = $ul_sect->Add(new Tag('li'));
    $li->Att('onmouseover',"W(this).addClass('in')")
       ->Att('onmouseout',"W(this).removeClass('in')");
    $li->Add(new Tag('em'))
       ->Add(new Tag('a')) 
       ->Att('href',$this->url($m['url']?"/{$m['url']}/":'/'))
       ->Att('title',$m['ttl'])
       ->Add($m['lbl']);
    if ($this->uri->sec['id'] == $m['id']) $li->Att('class','cur');
} 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script type="text/javascript" src="<?php echo $this->uri->host?>/lib/jquery.1.6.4.js"></script>
    <script type="text/javascript" src="<?php echo $this->uri->host?>/lib/jqscript.js"></script>
    <title><?php $this->title?></title>
    <?php echo $this->head?>
    <style>
html,body {
    background-image : url('/loryx/img/bg1.png');
    background-repeat: repeat-x;
    margin:0px;
    padding:0px;
}
#main {color:#777777; margin:0px auto; width: 970px; min-height:300px;}
#header {height:40px;}
#header .menu{float:right;}
#content {
    background-image : url('/loryx/img/bga.png'); 
    background-repeat: no-repeat;
    padding:10px;
    min-height:100px;}
.menu ul li
{
    border : 1px solid transparent;
    text-align:center;
}
.menu ul li.in
{
    margin: 3px 0 0;
    white-space: nowrap;
    background: whiteSmoke;
    -webkit-border-radius: 10px;
    -moz-border-radius: 10px;
    border-radius: 10px;
}
#content .menu ul 
{
    margin: 3px 0 0;
    white-space: nowrap;
}
#content .menu ul li
{    
    display: inline;
    list-style-type: none;  
} 
.menu ul li em
{    
    padding:3px 10px 3px 10px;  
}
#footer{
    position:absolute;
    bottom:0px;
    background-color:#ceddef;
    width:100%;
}
    </style>
</head>
<body>
    <div id="main">
        <div id="header">
            <div class="    menu"><?php echo $ul_top?></div>
            <h1><?php echo $this->uri->server?></h1>
        </div>
        <div id="content">
            <div class="menu"><?php echo $ul_sect?></div>
            <div class="section"></div>
        </div>
    </div>
    <div id="footer">footer</div>
</body>
<?php
return;
$tbl = new Table;
$tbl->Cell(new Tag('div'))->Last
    ->Att('style','width:1px; height:600px;');
$div = $tbl->Cell(new Tag('div'))
           ->Att('width','100%')
           ->Last;
$div->Att('style','width:900px; margin:auto;');
$head = $div->Add(new Table);
$head->Cell(new Tag('a'))
     ->Att('rowspan',2)
     ->Att('width','1%')
     ->Last
     ->Att('href',$this->url('/'))
     ->Add('<h1>'.$this->uri->server.'</h1>');
$dh = $head->Cell(new Tag('div'))->Last;
if (count($this->menu['top']))
{
    // area autenticazione/registrazione/benvenuto
    $ul = $dh->Add(new Tag('div'))
              ->Att('class','cntmenu')
              ->Att('style','float:right')
              ->Add(new Tag('ul'))  
              ->Att('class','menu1');

    if ($this->usr['id'])
    {
        $ul->Add(new Tag('li'))
           ->Att('class','bb_r')
           ->Add(new Tag('a'))
           ->Att('href',$this->url('/usr/'))
           ->Add($this->lbl->get('benvenuto').' '.$this->usr['uname']);
    }
    foreach($this->menu['top'] as $m)
    {
        //FB::log($m);
        if ($m['cmd']) 
        {
            // se esiste un codice di condizione viene valutato
            $f = create_function('',$m['cmd']);
            if (!$f()) continue;
        }
        $li = $ul->Add(new Tag('li'));
        $li->Add(new Tag('a'))
           ->Att('href',$this->url("/{$m['url']}/"))
           ->Att('ttl',$m['ttl'])
           ->Add($m['lbl']);
        if ($this->uri->sec == $m['id']) $li->Att('class','cur');
    }
}
// selezione idioma
$ul = $dh->Add(new Tag('div'))->Att('style','float:right;padding:10px;')
           ->Add(new Tag('ul'))->Att('class','menu1');
if (count($this->lngs)>1) foreach($this->lngs as $l)
{
    $ul->Add(new Tag('li'))
       ->Att('style','padding:0px; margin:0px;')
       ->Add(new Tag('a'))
       ->Att('href',$l['url'])
       ->Add(new TagImg("/img/b_{$l['lng']}.jpg"))
       ->Att('height','12px');
}
// area menù sezioni
$head->Row();
$ul = $head->Cell(new Tag('ul'))
        ->Last
        ->Att('class','menu');
$cmd = "
    select s.id,
           ifnull(t1.val,s.url) as url,
           ifnull(t2.val,s.ttl) as ttl,
           ifnull(t3.val,s.lbl) as lbl
    from stl_sec s
    left join [[2]] t1 on (s.id_trl = t1.id_trl and t1.lng = [0] and t1.nme='url')
    left join [[2]] t2 on (s.id_trl = t2.id_trl and t2.lng = [0] and t2.nme='ttl')
    left join [[2]] t3 on (s.id_trl = t3.id_trl and t3.lng = [0] and t3.nme='lbl')
    where s.id_stl=[1] 
      and s.lvl='bar' 
    order by ord";
//sit()->db->fb = 1; 
foreach($this->menu['bar'] as $m)
{
    $li = $ul->Add(new Tag('li'));
    $li->Att('onmouseover',"lrx.cl_add(this,'in')")
       ->Att('onmouseout',"lrx.cl_rm(this,'in')");
    $li->Add(new Tag('em'))
       ->Add(new Tag('a')) 
       ->Att('href',$this->url($m['url']?"/{$m['url']}/":'/'))
       ->Att('title',$m['ttl'])
       ->Add($m['lbl']);
    if ($this->uri->sec['id'] == $m['id']) $li->Att('class','cur');
} 
// menù di secondo livello
if ($this->uri->sec['id'])
{
    $mm = $this->uri->sec;
    $msub = new Tag('ul');
    $cmd = "
        select s.id,
               ifnull(t1.val,s.url) as url,
               ifnull(t2.val,s.ttl) as ttl,
               ifnull(t3.val,s.lbl) as lbl
        from stl_sec s
        left join [[2]] t1 on (s.id_trl = t1.id_trl and t1.lng = [0] and t1.nme='url')
        left join [[2]] t2 on (s.id_trl = t2.id_trl and t2.lng = [0] and t2.nme='ttl')
        left join [[2]] t3 on (s.id_trl = t3.id_trl and t3.lng = [0] and t3.nme='lbl')
        where s.id_stl=[1] 
          and s.id_par=[3] 
        order by ord";
    //sit()->db->fb = 1; 
    foreach($this->db->getAll($cmd,$this->lng,$this->id ,$this->trl_tbl,$mm['id']) as $m)
    {
        $li = $msub->Add(new Tag('li'));
        $li->Att('onmouseover',"lrx.cl_add(this,'in')")
           ->Att('onmouseout',"lrx.cl_rm(this,'in')");
        $li->Add(new Tag('em'))
           ->Add(new Tag('a')) 
           ->Att('href',$this->url($mm['url']?"/{$mm['url']}/":'/').($m['url']?$m['url'].'/':''))
           ->Att('title',$m['ttl'])
           ->Add($m['lbl']);
        if ($this->uri->sec['sec']['id'] == $m['id']) $li->Att('class','cur');
    } 
}

$div->Add(new Tag('div'))->Att('style','padding-top:20px;');
$div->Add($this->content);

echo  $tbl;
?>
    <div style="border-top:2px solid #FF510C; margin-top:20px">
        <div style="margin:auto; width:900px; padding:10px; text-align:center;  font-size:10px;">
        <div style="float:right; font-size:10px;">realizzato da <a href="http://www.spinit.it/" title="gestionale studi professionali">www.spinit.it</a></div>
        Copyright © <?php echo date('Y')?> <?php echo $this->uri->server?> [<?php echo env::get_time()?>]
        </div>
    </div>
</body>
</html>