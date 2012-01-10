<?
$this->ctype('text/html');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style>
*{font-family: arial; color:silver; font-weight:bold; text-decoration:none;}
#i0
{
    margin:auto; 
    width:800px; 
    height:400px; 
    position:relative; 
    margin-top:80px;
}
#i1
{
    width:400px;
    height:400px;
    position:absolute;
    top:0px;
    left:0px;
    background-image:url(/img/splash_testa.png);
    background-repeat : no-repeat;
}
#i2
{
    width:330px;
    height:330px;
    position:absolute;
    top:200px;
    left:300px;
    background-image:url(/img/splash_luce.png);
    background-repeat : no-repeat;
}
#i3
{
    width:400px;
    height:200px;
    position:absolute;
    top:40px;
    left:300px;
    background-image:url(/img/logo.png);
    background-repeat : no-repeat;
}
#i4
{
    position:absolute;
    top:170px;
    left:260px;
}
ul li
{	
    font-size:16px;
	padding:5px 5px 5px 5px; 
	margin:0px 2px 0px 2px; 
	list-style:none; 
	float:left; 
	text-align:center; 
	border:2px solid transparent;
}
li:hover{border-bottom: 2px dotted silver; color:white;}
    </style>
</head>
<body style="background-color:#000000;">
    <div id="i0">
        <div id="i1"></div>
        <div id="i2"></div>
        <div id="i3"></div>
        <div id="i4">
<?
$lbl = new cTrlDic('LABEL');
$cmd = "
    select l.id, t.nme, t.val
    from stl_lng l
    left join stl_trl_rif t on (l.id=t.lng and t.id_trl=[0] and t.nme in([1],[2]))
    order by l.ord, t.nme";
$ll = array();
$this->db->fb= 1;
foreach($this->db->getAll($cmd,$lbl->id,'entra','entra[title]') as $l)
{
    $ll[$l['id']][$l['nme']] = $l['val'];
}
FB::log($ll);
$ul = new Tag('ul');
foreach($ll as $lng=> $l)
{
    $ul->Add(new Tag('li'))
       ->Add(new Tag('em'))
       ->Add(new Tag('a'))
       ->Att('href',"/{$lng}/")
       ->Att('title',$l['entra[title]'])
       ->Add(nvl($l['entra'],'Entra'));
}
echo $ul;
?>
        </div>
    </div>
</body>
</html>