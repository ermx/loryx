<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?=$this->title?></title>
    <script type="text/javascript" src="/lib/loryx.js"></script>
    <style>
* {font-family: arial;}
td, th {vertical-align:top; text-align:left; padding:3px;}

.strong{font-weight:bold;}
.option{background-color:#ceddef;}

.c_box a {color:#777777;}
.info{border-top:1px solid silver; border-right:1px solid silver;}
.info td, .info th{border-bottom:1px solid silver; border-left:1px solid silver; padding:3px;}
.info td{background-color:#ceddef;}
.tag_tab td {padding:0px;}

.link {cursor:pointer;}

.in{background-color:#efefef;}
.br_t {border-top:1px solid silver;}
.br_b {border-bottom:1px solid silver;}
.br_l {border-left:1px solid silver;}
.br_r {border-right:1px solid silver;}
html{ overflow:hidden;}
html, body, form {margin:0px; padding:0px;}
.cmd_bar {border-top: 1px solid silver; background-color:dedeff;}
    </style>
</head>
<body>
    <?=$this->content.NL?>
</body>
</html>