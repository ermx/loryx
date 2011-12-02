
/*
 * (c) 2011 by Loryx project members.
 * See LICENSE for license.
 *
 */

Array.prototype.IS_ARRAY = 1;
Array.prototype.each = function(f)
{
	for(var i=0; i< this.length; i++)
	{
		if (f.apply(this,[i,this[i]])===false) break;
	}
	return this;
}
Array.prototype.remove = function(el)
{
	this.each(function(i,e){if (e!=el) return; this.slice(i,1); return false;});
	return el;
}
if (!window['console'])
{
    var console = {'log':function(){}};
}
(function($)
{
    window['W'] = $;
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
	var __trigger   = $.fn.trigger;
    
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
			if (!as) return [];
			if (as.IS_ARRAY) return as;
			if (as.length== undefined) return [as];
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
            var ret = _trigger.apply(this,arguments);
			return ret;
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
			try{
				return _closest.apply(this,arguments);
			}
			catch(e)
			{
				return new $();
			}
        },
		'observer':function(el,ev)
		{
			if (!el) return;
			ev = nvl(ev,'*');
			var obs = nvl(this.data('obs.osy'),{});
			obs[ev] = nvl(obs[ev],[]);
			function obs_itm(el)
			{
				this.el = el;
				this.remove = function()
				{
					obs[ev].remove(this);
				}
				this.trigger = function(arg)
				{
					__trigger.apply(el,arg);
				}
			}
			var o = new obs_itm($(el));
			obs[ev].push(o);
			this.data('obs.osy',obs);
			return o;
		},
		'trigger':function(ev,data)
		{
			var ev_name = ev.type?ev.type:ev;
			var ar = arguments;
            var ret = __trigger.apply(this,ar);
			var obs = nvl($(this).data('obs.osy'),{}) 	;
			//console.log('trigger',ev_name,this,obs);
			$.AR(obs['*']).each(function(idx,el){el.trigger(ar)});
			$.AR(obs[ev_name]).each(function(idx,el){el.trigger(ar)});
			return ret;
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
        var fcp = $('<form></form>');
		$(f).find('input,select,textarea').each(function()
		{
			var $this = $(this);
			if ($this.attr('osy_type')=='nopost') return;
			var $these = $this.clone();
			if ($this.attr('name').substr(0,2)='_[')
			{
				var lnme = $this.attr('name').split('[');
				if (lnme.shift()!='_') return;
				var ctx = lnme.shift().split(']').shift();
				var nme = lnme.shift().split(']').shift();
				if (opt[ctx] && opt[ctx][nme]) $this.val(opt[ctx][nme]);

			}
			else $these.val($this.val()).appendTo(fcp);
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
        return fcp;
    }
    function move_obj(el,ev)
    {
        var $el = $(el);
        $el.css('position','absolute');
        var pos = $el.offset();
        var skp = {'x':pos.left-ev.pageX,'y':pos.top-ev.pageY};
        // copertura oggetto
        var cov = $('<div style="position:absolute; background-color:red; top:0px; left:0px;"/>')
                    .css('height',$el.height())
                    .css('width',$el.width())
                    .bind('mouseup',function(){$(document).trigger('movestop.osy')})
                    .appendTo(el);
        $(document).bind('mousemove.osy',function(evm)
        {
            $el.css('left',max(evm.pageX+skp.x,0));
            $el.css('top',max(evm.pageY+skp.y,0));
            if (min(evm.pageX,evm.pageY)<0) $(document).trigger('movestop.osy');
        }).one('movestop.osy',function()
        {
            cov.remove();
            // stop movimento
            $(this).unbind('mousemove.osy');
        });
    }
    function mk_box(el,opt,obs)
    {
        var frm = $(el).closest('form');
        if(!frm.length) frm=$('form:first');
		var box = $('<div class="box" style="position:absolute; visibility:hidden; width:100px;">'+
                        '<div class="titlebar"><table cellspacing="3px" cellpadding="5px" width="100%">'+
                            '<tr class="cmd"><th class="title" width="100%"></th></tr>'+
                        '</table></div>'+
                        '<div class="content"></div>'+
                        '<div class="foot"></div>'+
                    '</div>')
                             .appendTo('body');
		box.observer(el);
		box.observer(obs);
		opt = nvl(opt,{});
        // impostazione elementi principali
        box.find('.title, .cmd, .content, .foot, .titlebar')
           .each(function(){box.data(this.className,$(this))});
        box.data('title').bind('mousedown',function(ev){move_obj(box,ev)});
		box.data('titlebar').hide();
        // impostazione elementi del contenuto
        box.data('content')
           .append('<iframe frameborder="no" style="width:100%" name="'+rand('win_')+'" onload="osy.event(this, \'#init\', this.contentWindow, this)"></iframe>')
           .find(':first').each(function()
           {
                this.dsk = dsk;
                this.box = box;
                box.data('iframe',$(this));
                box.data('form',box.data('content')
                   .append('<form method="post" target="'+$(this).attr('name')+'"></form>')
                   .find(':last').hide());
            });
        box.data('content').css('position','relative')
           .append('<div></div>')
           .find(':last')
           .each(function()
           {
                $(this).attr('style','position:absolute; top:0px; left:0px; width:100%; background-color:yellow;');
                box.data('cover',$(this));
            });
        box.bind('unfocus',function()
        {
            if (!box.data('everyfocus')) box.data('cover').css('height',box.data('content').height());
            box.css('z-index',0);
        });
        box.bind('focus',function() 
        {
            box.data('cover').css('height',0);
            box.css('z-index',10);
        });
        box.bind('click',function(){focus(box)});
        box.bind('close',function(){$(this).remove()});
        box.data('iframe').bind('#cmd',function()
        {
            var args = $.AR(arguments);
            var evn = args.shift();
            var data = args.shift();
            box.data('cmd').find('td').remove();
            $.each(data.split(','),function(idx,val)
            {
                switch(val)
                {
                case 'close':
                    $('<td>x</td>').bind('click',function()
                    {
                        try{
                            osy.event(box.data('iwin'),'close.osy'); 
                            osy.event(box,'close');
                        }catch(e){}
                    })
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
                case 'everyfocus':
                    box.data('everyfocus',1);
                    break;
                case 'center':
					box.data('center',1);
					break;
                case 'position':
                    var pos = args.shift();
                    if (pos.x) box.css('left',pos.x);
                    if (pos.y) box.css('top',pos.y);
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
			if(opt.cposy)
			{
				if (!opt[ctx]) opt[ctx] = {};
				if (!opt[ctx][nme]) opt[ctx][nme] = $(this).val();
			}
			else
			{
				switch(ctx)
				{
				case 'pky': ctx='prt'; // nobreak;
				case 'osy':
					if (!opt[ctx]) opt[ctx] = {};
					if (!opt[ctx][nme]) opt[ctx][nme] = $(this).val();
					break;
				}
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
        $(opt.frm).find('input')
			  .each(function ()
        {
			if ($(this).attr('name').substr(0,2)=='_[') return;
			$(this).clone().appendTo(box.data('form'));
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
		function event_proc(el,b,ev)
		{
			var f = b.find(':first').first();
			if (!f.length)
			{
				alert(b.text());
				return;
			}
			console.log('event_proc',f,ev,el);
		}
        box.bind('#init',function(evn,win,ifr)
        {
            var doc = win.document;
            box.data('iwin',$(win));
            box.data('iform',doc && doc.body && $(doc.body).find('form'));
            box.data('idata',doc && doc.body && $(doc.body).find(':last'));
            function init_input(sel)
            {
				console.log(sel);
                sel.find('input,textarea')
                   .each(function()
                   {
                        var $this = $(this);
                        $this.data('osy_value',this.value);
						if ($this.data('input_inited')) return;
						$this.data('input_inited',1);
                        if ($this.is('input')) $this.keyup(function(ev)
                        {
                            if(ev.keyCode==13)
                            {
                                $(this.form).find({'osy_type':'submit'}).first().click();
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
                            if (this.value==$this.data('osy_value')) $this.trigger('nomodified');
                            else $this.trigger('modified');
                        });
						$this.bind('focus',function(ev)
						{
							$this.trigger('focused');
						});
                   });
                return sel;
            }
            var frm = box.data('iform');
            if (!frm) return;
			osy.event(box,'set_dim',frm);
			if (box.data('center'))
			{
				box.css('left',max(($(document.body).width()-box.width())/2,0));
				box.css('top',max(($(document.body).height()-box.height())/2,0));
				box.data('center',0);
			}
            box.css('visibility','');
			
			box.data('iwin').trigger('show.osy');
            init_input(box.data('iform'));
            
            box.data('iform')
               .bind('exec',function(evn,data,obs)
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
                        var frm = this.html(xhr.responseText).find('form:first');
                        if (!frm.length) 
                        {
							event_proc(evn.target,this,evn);
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
                        case 'exe':
                            frm.find('code').each(function()
                            {
								var h = [];
								var tg = $(evn.target);
								h.push(tg.observer(obs));
                                (new Function('args',$(this).html())).apply(evn.target,[$(this),frm]);
								h.each(function(idx,el){console.log('remove',el);el.remove()});
                            });
                            break;
                        default : // copia
                            box.data('iform').html(frm.html());
                            init_input(box.data('iform'));
                        }
						var frm = box.data('iform');
						if (!frm) return;
						osy.event(box,'set_dim',frm);
                    }
                });
            });
        }).bind('reload',function()
        {
            if (box.data('iform') && box.data('iform').length) box.data('iform').submit();
            else box.data('form').submit();
        }).bind('set_dim',function(ev,frm)
		{
            if (frm.height()) box.data('iframe').css('height',frm.height()+'px');
            if (frm.width())  box.css('width',(frm.width())+'px');
		});
    
        box.data('form').submit();
        return box;
    }
    var wfocus = $(null);
    function focus(box)
    {
        wfocus.trigger('unfocus');
        wfocus = box;
        wfocus.trigger('focus');
        return box;
    }
	this.frm = function(el,opt,obs)
	{
		/*
        var fst  = $(el).parents('form');
        if(!fst.length) fst=$('form:first');
        if (obs) 
		{
			if (obs.IS_ARRAY) obs.unshift(el);
			else obs = [el,obs];
		}
		*/
        var box = mk_box(el,opt,obs);
		box.bind('#init',function(){box.remove()});
	}
    this.win = function(el,opt,obs)
    {
        /*/ form di riferimento dal quale viene fatta la richiesta
        var fst  = $(el).parents('form');
        if(!fst.length) fst=$('form:first');
		*/
        var box = focus(mk_box(el,opt,obs));
		box.data('titlebar').show();
        
        var box_self = $(el.ownerDocument && el.ownerDocument.defaultView && el.ownerDocument.defaultView.box);
        
        box.bind('#init',function(evn,win,ifr){box.data('title').text(win.document.title)})
           .bind('close',function(){focus(box_self); osy.event(box_self,'closechild')})
		   .bind('closechild',function(){osy.trigger(this,'reload')});
        
        switch(typeof(opt.pos))
        {
        case 'string':
            switch(opt.pos)
            {
            case 'right':
                box.css('top',box_self.css('top'));
                box.css('left',parseInt(box_self.css('left'))+parseInt(box_self.width())+10);
                break;
            }
            break;
        case 'function':
            opt.pos.apply(box,[box_self]);
            break;
        case 'object':
            if(opt.pos.x) box.css('top',opt.pos.x);
            if(opt.pos.y) box.css('lef',opt.pos.y);
            break;
        default:
            var pos = box_self.offset();
            if(pos)
            {
                box.css('top',pos.top+15);
                box.css('left',pos.left+15);
            }
        }
    }
    this.trigger = function()
    {
        var args = $.AR(arguments);
		var el = $(args.shift());
		var ev = args.shift();
        el.trigger(ev,args);
		return this;
    },
    this.event = function()
    {
        var args = $.AR(arguments);
		var el = $(args.shift());
		var ev = args.shift();
        el.trigger(ev,args,false);
		return this;
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
    },
	this.exe = function(el,opt,obs)
	{
		this.trigger(el,'exec',opt,obs);
	},
	this.box = function(el,opt,obs)
	{
        var box_self = $(el.ownerDocument && el.ownerDocument.defaultView && el.ownerDocument.defaultView.box);
		var pos = box_self.data('iframe').offset();
		var $el = $(el);
		var elpos = $el.offset();
		opt['frm'] = _cp_frm($el.closest('form'),opt);
		var cover = $('<div></div>').attr('style','width: 100%; height:'+$(document).height()+'; position:absolute; top:0px; left:0px; z-Index:100;').appendTo('body');
		opt['cposy'] = 1;
		var box = mk_box(el,opt,obs);
		cover.bind('click',function()
		{
			osy.event(box,'close'); 
		});
		var _t = box.trigger;
		box.bind('close',function(){cover.remove();});
		box.css('top',pos.top+elpos.top+$el.outerHeight(true));
		box.css('left',pos.left+elpos.left);
		box.css('z-index',100);
		return box;
	},
	this.msg = function(msg,b,tt)
	{
		b = nvl(b,document.body);
		var m = $('<div class="msg" style="margin:0px; widht:200px; height:100px; display:none; padding:10px; border:2px solid green; background-color:#fdecba;"></div>').appendTo(b);
		m.html(msg);
		m.show('slow');
	setTimeout(function(){$(m).hide().remove()},3000);
	}
})();