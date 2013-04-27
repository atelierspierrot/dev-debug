/* DevDebug Scripts */

// For accessibility : hidden blocks will be visibles if JS disabled
document.write("<style type='text/css'>.hide{display:none;visibility:hidden}</style>");

/**
 * Show or hide the block with ID "id" with class "show_class"
 * If showing and "handler" is set, add the hash tag "#handler" to document location
 */
function show_hide(id, show_class, handler)
{
	if (show_class == undefined) 
		show_class = 'show';
	if (document.getElementById(id)) {
		var _div = document.getElementById(id), 
			_class = _div.className,
			classes = _class.split(' ');
		for (i=0; i<classes.length; i++) {
			if (classes[i] == 'hide') {
				classes[i] = show_class;
				if (handler != undefined)
					window.location.hash = handler;
			} else if (classes[i] == 'show' || classes[i] == show_class) {
				classes[i] = "hide";
				if (handler != undefined && window.location.hash == '#'+handler)
					window.location.hash='#';
			}
		}
		_div.className = classes.join(' ');
	}
}

function show_prompt( str, dflt, href )
{
	var name = prompt(str,dflt);
	if (name!=null && name!="")
	{
		document.location.href = href+name;
	}
	return false;
}

/**
 * Checks or unckecks all checkboxes with name "check_name" in form "form" 
 */
function checkAll( form, check_name, handler ) 
{
	var _form = typeof(form)=='object' ? form : eval("document."+form);
	var check_all = handler || check_name+'_all',
		form_chkall = typeof(form)=='object' ? form.check_all : eval("document."+form+"."+check_all);
	if (form_chkall == undefined) {
		for (i = _form.elements.length - 1; i >= 0; i = i - 1) {
			if (_form.elements[i].name == check_all) {
				form_chkall = _form.elements[i];
			}
		}
	}
	if (form_chkall != undefined) {
		setTimeout(function(){
			for (i = _form.elements.length - 1; i >= 0; i = i - 1) {
				var el = _form.elements[i];
				if (el.name == check_name)
					el.checked = form_chkall.checked;
				else if (el.name == check_name+"[]")
					el.checked = form_chkall.checked;
			}
		},10);
		return true;
	}
	return false;
}

/**
 * Adds the class "_class" to "block_id" if "check_id" is checked
 * Removes the class if it is unchecked
 */
function changeClassOnCheck(_class, check_id, block_id) 
{
	var checkref = document.getElementById(check_id),
		blockref = document.getElementById(block_id),
		_patrn = new RegExp(_class, 'i');
	if(checkref){ 
		setTimeout(function(){
			if (checkref.checked && blockref) {
				blockref.className += ' '+_class;
			} else if(blockref) {
				blockref.className = blockref.className.replace(_patrn, '');
			}
		},10);
	}
	return true;
}

/**
 * Adds the class "_class" to "block_id" and check "check_id"
 * Removes the class and uncheck if it was checked
 */
function toggleClassAndCheck(_class, check_id, block_id) 
{
	var checkref = document.getElementById(check_id),
		blockref = document.getElementById(block_id),
		_patrn = new RegExp(_class, 'i');
	if (checkref)
	{ 
		if (checkref.checked && blockref) {
			checkref.checked = false;
			blockref.className = blockref.className.replace(_patrn, '');
		} else if(blockref) {
			checkref.checked = 'checked';
			blockref.className += ' '+_class;
		}
	}
	return true;
}

// Keep traces of all fields models by id
var FIELDTOGGLER_MODELS={};

// Keep traces of all dom original innerHTML by id
var FIELDTOGGLER_INNERHTML={};

/**
 * Toggle a field in a dom object
 * @param string id The ID string of the collection holder (parent) | required
 * @param string field_name The name of the toggled field
 * @param string field_model The HTML string to put in the collection item | optional, if not set, the function will try to get
 *                    the "data-prototype" attribute of the parent
 */
function field_toggler( id, field_name, field_model )
{
	console.debug('[toggle_uploader] toggling field ['+field_name+'] id['+id+'] model=['+field_model+']');
	var _parent = document.getElementById( id );
	if (_parent)
	{
		// make sure we have a model
		var mod = field_model || FIELDTOGGLER_MODELS[id] || _parent.getAttribute('data-prototype');
		if (mod==undefined)
		{
			console.error('No model set and no data-prototype attribute found!');
			return;
		}
		if (FIELDTOGGLER_MODELS[id]==undefined)
			FIELDTOGGLER_MODELS[id] = mod;
		console.debug('getting field model : ['+mod+']');

		FIELDTOGGLER_INNERHTML[id] = _parent.innerHTML;
	    _parent.innerHTML = FIELDTOGGLER_MODELS[id];
	}
}

// Keep traces of all original font-sizes by id
var FONTSIZE_ORIGINALS={};

/**
 * Change a font-size of a DOM block by its ID.
 * @param string action The action to execute on font-size : '+' to increase it, '-' to decrease it and '0' for the original size
 * @param string id A dom block ID for selection
 * @param float range The range to use for increase/decrease font-size
 * @param string def The default original font-size to use if it's not set in CSS
 */
function font_size ( action, id, range, def )
{
console.debug('[text_size] for action ['+action+'] on dom ID ['+id+']');

	var unity_table = new Array(
		[ 'px', 5 ],
		[ 'pt', 3 ],
		[ 'em', 0.3 ],
		[ '%', 25 ]
	), _unit;

	var domobj = document.getElementById( id );
	if (domobj)
	{
		var current_fs = domobj.style.fontSize,
			_newobj = domobj;
		while (_newobj!=undefined && (current_fs==null || current_fs==''))
		{
			_newobj = _newobj.parentNode;
			if (_newobj!=undefined && _newobj.style!=undefined)
			{
				if (_newobj.currentStyle)
					current_fs = el.currentStyle['fontSize'];
				else if (document.defaultView && document.defaultView.getComputedStyle)
					current_fs = document.defaultView.getComputedStyle(_newobj,null).getPropertyValue('font-size');
				else
					current_fs = _newobj.style.fontSize; 
			}
		}
		if (current_fs==null || current_fs=='')
		{
			if (def!=undefined && def!=null) current_fs = def;
			else {
				console.error('No font-size defined for dom ID ['+id+']!');
				return;
			}
		}
		if (FONTSIZE_ORIGINALS[id]==undefined)
			FONTSIZE_ORIGINALS[id]=current_fs;
		
		var current_fs_val;
		for(i=0; i<unity_table.length; i++)
		{
			_str = current_fs.replace(unity_table[i][0], '');
			if (_str != current_fs)
			{
				_unit = unity_table[i][0];
				current_fs_val = parseFloat(_str);
				if (range==undefined || range==null) range = unity_table[i][1];
			}
		}
		if (action == '+') _new_size = (current_fs_val+range)+_unit;
		else if (action == '-') _new_size = (current_fs_val-range)+_unit;
		else _new_size = FONTSIZE_ORIGINALS[id];
		domobj.style.fontSize = _new_size;
	}
}
