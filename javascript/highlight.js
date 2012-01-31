
/* simple functions for highlighting and un-highlighting objects */

function highlight(object, classname)
{
	object.className += ' ' + classname;

	return true;
}


function unhighlight(object)
{
	var classes = object.className.split(' ');
	var newclass = '';

	for (var i = 0; i < classes.length; ++i)
	{
		if (('highlighted' != classes[i])
			&& ('taken_highlighted' != classes[i])
			&& ('curmove_highlighted' != classes[i])
			&& ('' != classes[i]))
		{
			newclass += ' ' + classes[i];
		}
	}

	object.className = newclass;

	return true;
}