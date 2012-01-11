<?php
$menu = new stl_menu($this->lng, $this->id , '');
if (count($menu->get('top')))
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
    foreach($menu->get('top') as $m)
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
foreach($menu->get('bar') as $m)
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
    <title><?php echo $this->title.' :: '.$this->sec['ttl']?></title>
    <?php echo $this->head?>
    <style>
html,body {
    background-image : url('<?php echo $this->uri->host?>/img/bg1.png');
    background-repeat: repeat-x;
    margin:0px;
    padding:0px;
}
#main {color:#777777; margin:0px auto; width: 970px; min-height:300px;}
#header {height:40px;}
#header .menu{float:right;}
#content {
    background-image : url('<?php echo $this->uri->host?>/img/bga.png'); 
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
.section
{
    padding: 20px 5px 20px 5px;
}
    </style>
</head>
<body>
    <div id="main">
        <div id="header">
            <div class="menu"><?php echo $ul_top?></div>
            <h1><?php echo $this->uri->server?></h1>
        </div>
        <div id="content">
            <div class="menu"><?php echo $ul_sect?></div>
            <div class="section"><?php echo $this->content?></div>
        </div>
    </div>
</body>
