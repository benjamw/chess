
function getObject(obj)
{
	if (document.getElementById)
	{ // Mozilla, FireFox, Explorer 5+, Opera 5+, Konqueror, Safari, iCab, Ice, OmniWeb 4.5
		if (typeof obj == "string")
		{
			if (document.getElementById(obj))
			{
				return document.getElementById(obj);
			}
			else
			{
				return document.getElementsByName(obj)[0];
			}
		}
		else
		{
			return obj.style;
		}
	}
	if (document.all)
	{ // Explorer 4+, Opera 6+, iCab, Ice, Omniweb 4.2-
		if (typeof obj == "string")
		{
			return document.all(obj);
		}
		else
		{
			return obj.style;
		}
	}
	if (document.layers)
	{ // Netscape 4, Ice, Escape, Omniweb 4.2-
		if (typeof obj == "string")
		{
			return document.layers(obj);
		}
		else
		{
			return obj.style;
		}
	}
	return null;
}
