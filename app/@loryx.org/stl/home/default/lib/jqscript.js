
/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

Array.prototype.IS_ARRAY = 1;
if (!window['console'])
{
    var console = {'log':function(){}};
}
(function($)
{
    window.W = $;
    function _cc(c)
    {
        if (typeof(c)=='object') 
        {
			var s = '';
            var ar = [];
            for (e in c) switch(e)
			{
			case '#': s = c[e];
				break;
			default:
				ar.push(e+"='"+c[e]+"'");
			}
            c = s+"["+ar.join(' ')+"]";
        }
        return c;
    }

    var _find       = $.fn.find;
    var _closest    = $.fn.closest;
    
    var _attr = $.attr;
    var _each = $.each;
    
    var _trigger    = $.event.trigger;    
    
    var prefix_event = 'evn_';
    $.extend($,
    {
        'attr':function( elem, name, value, pass )
        {
            try
            {
                return nvl(_attr.apply($, [elem, name, value, pass]),'');
            }catch(e){
                return '';
            }
        },
        'AR':function(as)
        {
            var _args = [];
            $.each(as, function (i,a)
            {
                _args.push(a); return true;
            });
            return _args;
        },
        'each' : function (o,f)
        {
            if (!o) return;
            return _each.apply($, [o,f]);
        }
    });
    
    $.extend($.event, 
    {
        'trigger':function(evn,data,elem)
        {
			var evn_name = evn.type?evn.type:evn;
			
            if (elem)
            {
                var ev = GET(elem, prefix_event+evn_name);
                var fev = ev;
				try{
					if(ev) 
					{
                        if (typeof(ev) != 'function')
                        {
                            var ww = nvl(elem.ownerDocument && elem.ownerDocument.defaultView,window);
                            fev = (new ww.Function('evn,args',ev));
                        }
                        fev.apply(elem,[evn,data]);
					}
				}catch(e)
				{
					console.log(elem,ev,e);
				}
            }
            return _trigger.apply(this,arguments);
        }
    });
    $.extend($.fn, 
    {
        'find':function()
        {
            var ar = [];
			$.each(arguments,function(k,v){ar.push(_cc(v))});
            return _find.apply(this,[ar.join(',')]);
        },
        'closest':function()
        {
            arguments[0] = _cc(arguments[0]);
            return _closest.apply(this,arguments);
        }
    });
}
)(jQuery);

function lower(s){return (s+'').toLowerCase();}
function upper(s){return (s+'').toUpperCase();}
function max()
{
    var r = undefined;
    $(arguments).each(function(idx,el)
    {
        if (r==undefined || el>r) r=el;
    });
    return r;
}
function min()
{
    var r = undefined;
    $(arguments).each(function(idx,el)
    {
        if (r==undefined || el<r) r=el;
    });
    return r;
}
function nvl()
{
    var arg = $.AR(arguments);
    var val = undefined;
    while(!(val = arg.shift())&& arg.length) ;
    return val;
}
function GET(el,p)
{
    if (!el) return '';
    if (el.getAttribute)
    {
        switch(p)
        {
        case 'class': return el.className;
        case 'style': return el.style && el.style.cssText;
        }
        return el.getAttribute(p);
    }
    return el[p];
}
var dsk = window;

var osy = new (function()
{
    function rand(b,m)
    {
        if (!b) b = '_base_';
        if (!m) m = 10000000;
        return b+Math.ceil(Math.random()*m);
    }
    function _cp_frm(f,opt)
    {
        // form che conterrà le variabili da postare
        var fcp = $(f).clone();
        var vv = {};
        // impostazione parametri di sistema
        fcp.find('input[osy_type="nopost"]').remove();
        fcp.find('input[ name^="_[" ]')
         .each(function ()
        {
            var lnme = $(this).attr('name').split('[');
            if (lnme.shift()!='_') return;
            var ctx = lnme.shift().split(']').shift();
            var nme = lnme.shift().split(']').shift();
            if (opt[ctx] && opt[ctx][nme]) $(this).val(opt[ctx][nme]);
        });
        // modifica parametro osy
        $.each(opt['vars'],function(a,b)
        {
            if (typeof(b)=='object')
            {
                $(b).clone().appendTo(fcp);
            }
            else
            {
                $('<input />').attr( {'name':a,'value':b})
                              .appendTo(fcp);
            }
        });
        console.log(opt);
        return fcp;
    }
    function move_obj(el,ev)
    {
        console.log(el,ev);
    }
    function mk_box(frm,opt)
    {
        var box = $('<div class="box"><div class="titlebar"><table cellspacing="0" cellpadding="3px" width="100%"><tr class="cmd"><th class="title" width="100%"></th></tr></table></div><div class="content"></div><div class="foot"></div></div>')
                             .appendTo('body');
        // impostazione elementi principali
        box.find('.title, .cmd, .content, .foot')
           .each(function(){box.data(this.className,$(this))});
        box.data('title').bind('mousedown',function(ev){move_obj(box,ev)})
                         .bind('mouseup',function(ev){move_obj(false,ev));
        // impostazione elementi del contenuto
        box.data('content')
           .append('<iframe frameborder="no" name="'+rand('win_')+'" onload="osy.event(this, \'#init\', this.contentWindow, this)"></iframe>')
           .find(':first').each(function()
           {
                this.dsk = dsk;
                this.box = box;
                box.data('iframe',$(this));
                box.data('form',box.data('content')
                   .append('<form method="post" target="'+$(this).attr('name')+'"></form>')
                   .find(':last').hide());
            });
        box.data('iframe').bind('#cmd',function(evn,data)
        {
            box.data('cmd').find('td').remove();
            $.each(data.split(','),function(idx,val)
            {
                switch(val)
                {
                case 'close':
                    $('<td>x</td>').bind('click',function(){box.remove()})
                                   .appendTo(box.data('cmd'));
                    break;
                case 'reload':
                    $('<td>r</td>').bind('click',function(){osy.event(box,'reload')})
                                   .appendTo(box.data('cmd'));
                    break;
                case 'init':
                    $('<td>i</td>').bind('click',function(){box.data('form').submit()})
                                   .appendTo(box.data('cmd'));
                    break;
                }
            });
        });
        frm.find(':first input[name^="_["]')
           .each(function ()
        {
            var lnme = $(this).attr('name').split('[');
            if (lnme.shift()!='_') return;
            var ctx = lnme.shift().split(']').shift();
            var nme = lnme.shift().split(']').shift();
            switch(ctx)
            {
            case 'pky': ctx='prt'; // nobreak;
            case 'osy':
                if (!opt[ctx]) opt[ctx] = {};
                if (!opt[ctx][nme]) opt[ctx][nme] = $(this).val();
                break;
            }
        });
        $(['osy','prt','pky']).each(function(idx,ctx)
        {
            $.each(opt[ctx],function(k,v)
            {
                if (typeof(v)=='object')
				{
                    if ($(v).is('input,textarea,select'))
                    {
                        k = v.name;
                        v = v.value;
                    }
                    else
                    {
                        k = $(v).attr('osy_name');
                        v = $(v).text();
                    }
				}
                $('<input/>').attr('name','_['+ctx+']['+k+']')
                            .attr('value',v)
                            .appendTo(box.data('form'));
            });
        });
        function ck_regexp(el)
        {
            var $el = $(el);
            var re = $el.attr('osy_regexp');
            if (!re) return;
            switch(re)
            {
            case 'int':
            case 'integer':
                re = /^[0-9]*$/;
                break;
            case 'float':
                re = /^[0-9]*(\.[0-9]*)?$/;
                break;
            default:
                re = new RegExp(re);
                break;
            }
            $el.removeClass('error');
            if (!re.test(el.value)) $el.addClass('error');
        }
        box.bind('#init',function(evn,win,ifr)
        {
            console.log(win,ifr);
            var doc = win.document;
            box.data('iform',doc && doc.body && $(doc.body).find('form'));
            box.data('idata',doc && doc.body && $(doc.body).find(':last'));
            function init_input(sel)
            {
                sel.find('input,textarea')
                   .each(function()
                   {
                        var $this = $(this);
                        $this.data('osy_value',this.value); 
                        if ($this.is('input')) $this.keyup(function(ev)
                        {
                            if(ev.keyCode==13)
                            {
                                $(this.form).find({'osy_type':'submit'}).first().click();
								console.log($(this.form).find({'osy_type':'submit'}).first());
                                return false;
                            }
                        });
                        if (GET(this,'osy_regexp')) 
                        {
                            $this.keydown(function(){clearTimeout($this.data('ttupd'))})
                                 .keyup(function(){$this.data('ttupd',setTimeout(ck_regexp,500,this))});
                        }
                        $this.bind('blur',function(ev)
                        { 
                            if (this.value==$this.data('osy_value')) return;
                            $this.trigger('modified');
                        });
                   });
                return sel;
            }
            var frm = box.data('iform');
            if (!frm) return;
            if (frm.height()) box.data('iframe').css('height',frm.height()+'px');
            if (frm.width()) box.data('iframe').css('width',frm.width()+'px');
            
            init_input(box.data('iform'));
            
            box.data('iform')
               .bind('exec',function(evn,data)
            {
                data['osy']['sta'] = 'form';
                var frm = _cp_frm(this,data);
                // richiesta ajax
                $.ajax({
                    'url':location.href,
                    'context' : box.data('idata'),
                    'type':'POST',
                    'data':frm.serializeArray(),
                    'complete':function(xhr)
                    {
                        ;
                        var frm = this.html(xhr.responseText).find('form:first');
                        if (!frm.length) 
                        {
                            alert(this.html());
                            return;
                        }
                        var ttl = frm.attr('osy_title');
                        var typ = frm.attr('osy_type').split(' ');
                        switch(typ[0])
                        {
                        case 'map': // modifica dei soli dati mappati
                            var map = {};
                            frm.find('[osy_map]').each(function()
                            {
                                $this = $(this);
                                map[$this.attr('osy_map')] = $this.is('input,select,textarea') ? $this.val() : $this.html();; 
                            });
                            box.data('iform').find('[osy_map]').each(function()
                            {
                                $this = $(this);
                                
                                if ($this.is('input,select,textarea')) $this.val(map[$this.attr('osy_map')]); 
                                else 
                                {
                                    $this.html(map[$this.attr('osy_map')])
                                    init_input($this);
                                }
                                
                                switch(typ[1])
                                {
                                case 'fadein':
                                    $this.fadeIn();
                                    break;
                                default:
                                    $this.show();
                                    break;
                                }
                            });
                            break;
                        case 'command':
                            frm.find('code').each(function()
                            {
                                (new Function('',$(this).html())).apply(evn.target,[]);
                            });
                            break;
                        default : // copia
                            box.data('iform').html(frm.html());
                            init_input(box.data('iform'));
                        }
                    }
                });
            });
        }).bind('reload',function()
        {
            if (box.data('iform') && box.data('iform').length) box.data('iform').submit();
            else box.data('form').submit();
        });
    
        box.data('form').submit();
        return box;
    }
    this.win = function(el,opt)
    {
        // form di riferimento dal quale viene fatta la richiesta
        var fst  = $(el).parents('form');
        if(!fst.length) fst=$('form:first');
        
        var box = mk_box(fst,opt);
        box.bind('#init',function(evn,win,ifr){box.data('title').text(win.document.title)});
        box.bind('title_down',function()
        {
            console.log('move');
        });
    }
    this.trigger = function()
    {
        var args = $.AR(arguments);
        $(args.shift()).trigger(args.shift(),args);
    },
    this.event = function()
    {
        var args = $.AR(arguments);
        $(args.shift()).trigger(args.shift(),args,false);
    },
    this.get_input = function(el,nm)
    {
        if (!nm)
        {
            nm = el;
            el = $('form:first');
        }
        if (typeof(nm)!='object') nm = {'name':nm};
		nm['#'] = 'input';
        return $(el).closest('form').find(nm);
    }
})();