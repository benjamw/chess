/*

There are several sort types available in this function:

Number     - Sort by number (if the element is not a number, it may cause problems)
ExclNumber - Sort by any number in the element (ie Item 2 is before Area 12)
NumberK    - Sort by computer scientific notation numbers (ie 12MB is before 1.2GB, valid modifiers are K, M, G, and T)
FirstNumber- Sort by first number in string all others ignored
UsCurrency - Sort by U.S. Dollar amount (dollar signs ( $ ), commas ( , ), and decimal points ( . ) are all OK)
ZipCode    - Sort by Zip Code (5-4 OK)

String     - Sort by case-sensitive string (ie Item 12 is before Item 2 and Banana is before apple)
StringCI   - Sort by case-insensitive string (ie Item 12 is before Item 2 and apple is before Banana)

Date       - Sort by a date (only valid format is YYYY-MM-DD)
DateTime   - Sort by a date time display (YYYY-MM-DD HH:MM:SS, delimiters may be anything)
LongDate   - Sort by a date formatted as Jan[uary] 14[th][,] 2007[ 12:45[:41][ ][p[m]]]

*/

/*----------------------------------------------------------------------------\
|                            Sortable Table 1.12                              |
|-----------------------------------------------------------------------------|
|                         Created by Erik Arvidsson                           |
|                  (http://webfx.eae.net/contact.html#erik)                   |
|                      For WebFX (http://webfx.eae.net/)                      |
|-----------------------------------------------------------------------------|
| A DOM 1 based script that allows an ordinary HTML table to be sortable.     |
|-----------------------------------------------------------------------------|
|                  Copyright (c) 1998 - 2004 Erik Arvidsson                   |
|-----------------------------------------------------------------------------|
| This software is provided "as is", without warranty of any kind, express or |
| implied, including  but not limited  to the warranties of  merchantability, |
| fitness for a particular purpose and noninfringement. In no event shall the |
| authors or  copyright  holders be  liable for any claim,  damages or  other |
| liability, whether  in an  action of  contract, tort  or otherwise, arising |
| from,  out of  or in  connection with  the software or  the  use  or  other |
| dealings in the software.                                                   |
| - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - |
| This  software is  available under the  three different licenses  mentioned |
| below.  To use this software you must chose, and qualify, for one of those. |
| - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - |
| The WebFX Non-Commercial License          http://webfx.eae.net/license.html |
| Permits  anyone the right to use the  software in a  non-commercial context |
| free of charge.                                                             |
| - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - |
| The WebFX Commercial license           http://webfx.eae.net/commercial.html |
| Permits the  license holder the right to use  the software in a  commercial |
| context. Such license must be specifically obtained, however it's valid for |
| any number of  implementations of the licensed software.                    |
| - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - |
| GPL - The GNU General Public License    http://www.gnu.org/licenses/gpl.txt |
| Permits anyone the right to use and modify the software without limitations |
| as long as proper  credits are given  and the original  and modified source |
| code are included. Requires  that the final product, software derivate from |
| the original  source or any  software  utilizing a GPL  component, such  as |
| this, is also licensed under the GPL license.                               |
|-----------------------------------------------------------------------------|
| 2003-01-10 | First version                                                  |
| 2003-01-19 | Minor changes to the date parsing                              |
| 2003-01-28 | JScript 5.0 fixes (no support for 'in' operator)               |
| 2003-02-01 | Sloppy typo like error fixed in getInnerText                   |
| 2003-07-04 | Added workaround for IE cellIndex bug.                         |
| 2003-11-09 | The bDescending argument to sort was not correctly working     |
|            | Using onclick DOM0 event if no support for addEventListener    |
|            | or attachEvent                                                 |
| 2004-01-13 | Adding addSortType and removeSortType which makes it a lot     |
|            | easier to add new, custom sort types.                          |
| 2004-01-27 | Switch to use descending = false as the default sort order.    |
|            | Change defaultDescending to suit your needs.                   |
| 2004-03-14 | Improved sort type None look and feel a bit                    |
| 2004-08-26 | Made the handling of tBody and tHead more flexible. Now you    |
|            | can use another tHead or no tHead, and you can chose some      |
|            | other tBody.                                                   |
|-----------------------------------------------------------------------------|
| Created 2003-01-10 | All changes are in the log above. | Updated 2004-08-26 |
\----------------------------------------------------------------------------*/


function SortableTable(oTable, oSortTypes) {

	this.sortTypes = oSortTypes || [];

	this.sortColumn = null;
	this.descending = null;

	var oThis = this;
	this._headerOnclick = function (e) {
		oThis.headerOnclick(e);
	};

	if (oTable) {
		this.setTable( oTable );
		this.document = oTable.ownerDocument || oTable.document;
	}
	else {
		this.document = document;
	}


	// only IE needs this
	var win = this.document.defaultView || this.document.parentWindow;
	this._onunload = function () {
		oThis.destroy();
	};
	if (win && typeof win.attachEvent != "undefined") {
		win.attachEvent("onunload", this._onunload);
	}
}

SortableTable.gecko = navigator.product == "Gecko";
SortableTable.msie = /msie/i.test(navigator.userAgent);
// Mozilla is faster when doing the DOM manipulations on
// an orphaned element. MSIE is not
SortableTable.removeBeforeSort = SortableTable.gecko;

SortableTable.prototype.onsort = function () {};

// default sort order. true -> descending, false -> ascending
SortableTable.prototype.defaultDescending = false;

// shared between all instances. This is intentional to allow external files
// to modify the prototype
SortableTable.prototype._sortTypeInfo = {};

SortableTable.prototype.setTable = function (oTable) {
	if ( this.tHead )
		this.uninitHeader();
	this.element = oTable;
	this.setTHead( oTable.tHead );
	this.setTBody( oTable.tBodies[0] );
};

SortableTable.prototype.setTHead = function (oTHead) {
	if (this.tHead && this.tHead != oTHead )
		this.uninitHeader();
	this.tHead = oTHead;
	this.initHeader( this.sortTypes );
};

SortableTable.prototype.setTBody = function (oTBody) {
	this.tBody = oTBody;
};

SortableTable.prototype.setSortTypes = function ( oSortTypes ) {
	if ( this.tHead )
		this.uninitHeader();
	this.sortTypes = oSortTypes || [];
	if ( this.tHead )
		this.initHeader( this.sortTypes );
};

// adds arrow containers and events
// also binds sort type to the header cells so that reordering columns does
// not break the sort types
SortableTable.prototype.initHeader = function (oSortTypes) {
	if (!this.tHead) return;
	var cells = this.tHead.rows[0].cells;
	var doc = this.tHead.ownerDocument || this.tHead.document;
	this.sortTypes = oSortTypes || [];
	var l = cells.length;
	var img, c;
	for (var i = 0; i < l; i++) {
		c = cells[i];
		if (this.sortTypes[i] != null && this.sortTypes[i] != "None") {
//			img = doc.createElement("IMG");
//			img.src = "images/blank.png";
//			c.appendChild(img);
			if (this.sortTypes[i] != null)
				c._sortType = this.sortTypes[i];
			if (typeof c.addEventListener != "undefined")
				c.addEventListener("click", this._headerOnclick, false);
			else if (typeof c.attachEvent != "undefined")
				c.attachEvent("onclick", this._headerOnclick);
			else
				c.onclick = this._headerOnclick;
		}
		else
		{
			c.setAttribute( "_sortType", oSortTypes[i] );
			c._sortType = "None";
		}
	}
//	this.updateHeaderArrows();
};

// remove arrows and events
SortableTable.prototype.uninitHeader = function () {
	if (!this.tHead) return;
	var cells = this.tHead.rows[0].cells;
	var l = cells.length;
	var c;
	for (var i = 0; i < l; i++) {
		c = cells[i];
		if (c._sortType != null && c._sortType != "None") {
			c.removeChild(c.lastChild);
			if (typeof c.removeEventListener != "undefined")
				c.removeEventListener("click", this._headerOnclick, false);
			else if (typeof c.detachEvent != "undefined")
				c.detachEvent("onclick", this._headerOnclick);
			c._sortType = null;
			c.removeAttribute( "_sortType" );
		}
	}
};

//SortableTable.prototype.updateHeaderArrows = function () {
//	if (!this.tHead) return;
//	var cells = this.tHead.rows[0].cells;
//	var l = cells.length;
//	var img;
//	for (var i = 0; i < l; i++) {
//		if (cells[i]._sortType != null && cells[i]._sortType != "None") {
//			img = cells[i].lastChild;
//			if (i == this.sortColumn)
//				img.className = "sort-arrow " + (this.descending ? "descending" : "ascending");
//			else
//				img.className = "sort-arrow";
//		}
//	}
//};

SortableTable.prototype.headerOnclick = function (e) {
	// find TD or TH element
	var el = e.target || e.srcElement;
	while ((el.tagName != "TH") && (el.tagName != "TD"))
//	while (el.tagName != "TH")
		el = el.parentNode;

	this.sort(SortableTable.msie ? SortableTable.getCellIndex(el) : el.cellIndex);
};

// IE returns wrong cellIndex when columns are hidden
SortableTable.getCellIndex = function (oTd) {
	var cells = oTd.parentNode.childNodes
	var l = cells.length;
	var i;
	for (i = 0; cells[i] != oTd && i < l; i++)
		;
	return i;
};

SortableTable.prototype.getSortType = function (nColumn) {
	return this.sortTypes[nColumn] || "String";
};

// only nColumn is required
// if bDescending is left out the old value is taken into account
// if sSortType is left out the sort type is found from the sortTypes array

SortableTable.prototype.sort = function (nColumn, bDescending, sSortType) {
	if (!this.tBody) return;
	if (sSortType == null)
		sSortType = this.getSortType(nColumn);

	// exit if None
	if (sSortType == "None")
		return;

	if (bDescending == null) {
		if (this.sortColumn != nColumn)
			this.descending = this.defaultDescending;
		else
			this.descending = !this.descending;
	}
	else
		this.descending = bDescending;

	this.sortColumn = nColumn;

	if (typeof this.onbeforesort == "function")
		this.onbeforesort();

	var f = this.getSortFunction(sSortType, nColumn);
	var a = this.getCache(sSortType, nColumn);
	var tBody = this.tBody;

	a.sort(f);

	if (this.descending)
		a.reverse();

	if (SortableTable.removeBeforeSort) {
		// remove from doc
		var nextSibling = tBody.nextSibling;
		var p = tBody.parentNode;
		p.removeChild(tBody);
	}

	// insert in the new order
	var l = a.length;
	for (var i = 0; i < l; i++)
		tBody.appendChild(a[i].element);

	if (SortableTable.removeBeforeSort) {
		// insert into doc
		p.insertBefore(tBody, nextSibling);
	}

//	this.updateHeaderArrows();

	this.destroyCache(a);

	if (typeof this.onsort == "function")
		this.onsort();
};

SortableTable.prototype.asyncSort = function (nColumn, bDescending, sSortType) {
	var oThis = this;
	this._asyncsort = function () {
		oThis.sort(nColumn, bDescending, sSortType);
	};
	window.setTimeout(this._asyncsort, 1);
};

SortableTable.prototype.getCache = function (sType, nColumn) {
	if (!this.tBody) return [];
	var rows = this.tBody.rows;
	var l = rows.length;
	var a = new Array(l);
	var r;
	for (var i = 0; i < l; i++) {
		r = rows[i];
		a[i] = {
			value:		this.getRowValue(r, sType, nColumn),
			element:	r
		};
	};
	return a;
};

SortableTable.prototype.destroyCache = function (oArray) {
	var l = oArray.length;
	for (var i = 0; i < l; i++) {
		oArray[i].value = null;
		oArray[i].element = null;
		oArray[i] = null;
	}
};

SortableTable.prototype.getRowValue = function (oRow, sType, nColumn) {
	// if we have defined a custom getRowValue use that
	if (this._sortTypeInfo[sType] && this._sortTypeInfo[sType].getRowValue)
		return this._sortTypeInfo[sType].getRowValue(oRow, nColumn);

	var s;
	var c = oRow.cells[nColumn];
	if (typeof c.innerText != "undefined")
		s = c.innerText;
	else
		s = SortableTable.getInnerText(c);
	return this.getValueFromString(s, sType);
};

SortableTable.getInnerText = function (oNode) {
	var s = "";
	var cs = oNode.childNodes;
	var l = cs.length;
	for (var i = 0; i < l; i++) {
		switch (cs[i].nodeType) {
			case 1: //ELEMENT_NODE
				s += SortableTable.getInnerText(cs[i]);
				break;
			case 3:	//TEXT_NODE
				s += cs[i].nodeValue;
				break;
		}
	}
	return s;
};

SortableTable.prototype.getValueFromString = function (sText, sType) {
	if (this._sortTypeInfo[sType])
		return this._sortTypeInfo[sType].getValueFromString( sText );
	return sText;
	/*
	switch (sType) {
		case "Number":
			return Number(sText);
		case "StringCI":
			return sText.toUpperCase();
		case "Date":
			var parts = sText.split("-");
			var d = new Date(0);
			d.setFullYear(parts[0]);
			d.setDate(parts[2]);
			d.setMonth(parts[1] - 1);
			return d.valueOf();
	}
	return sText;
	*/
	};

SortableTable.prototype.getSortFunction = function (sType, nColumn) {
	if (this._sortTypeInfo[sType])
		return this._sortTypeInfo[sType].compare;
	return SortableTable.basicCompare;
};

SortableTable.prototype.destroy = function () {
	this.uninitHeader();
	var win = this.document.parentWindow;
	if (win && typeof win.detachEvent != "undefined") {	// only IE needs this
		win.detachEvent("onunload", this._onunload);
	}
	this._onunload = null;
	this.element = null;
	this.tHead = null;
	this.tBody = null;
	this.document = null;
	this._headerOnclick = null;
	this.sortTypes = null;
	this._asyncsort = null;
	this.onsort = null;
};

// Adds a sort type to all instance of SortableTable
// sType : String - the identifier of the sort type
// fGetValueFromString : function ( s : string ) : T - A function that takes a
//    string and casts it to a desired format. If left out the string is just
//    returned
// fCompareFunction : function ( n1 : T, n2 : T ) : Number - A normal JS sort
//    compare function. Takes two values and compares them. If left out less than,
//    <, compare is used
// fGetRowValue : function( oRow : HTMLTRElement, nColumn : int ) : T - A function
//    that takes the row and the column index and returns the value used to compare.
//    If left out then the innerText is first taken for the cell and then the
//    fGetValueFromString is used to convert that string the desired value and type

SortableTable.prototype.addSortType = function (sType, fGetValueFromString, fCompareFunction, fGetRowValue) {
	this._sortTypeInfo[sType] = {
		type:				sType,
		getValueFromString:	fGetValueFromString || SortableTable.idFunction,
		compare:			fCompareFunction || SortableTable.basicCompare,
		getRowValue:		fGetRowValue
	};
};

// this removes the sort type from all instances of SortableTable
SortableTable.prototype.removeSortType = function (sType) {
	delete this._sortTypeInfo[sType];
};

SortableTable.basicCompare = function compare(n1, n2) {
	if (n1.value < n2.value)
		return -1;
	if (n2.value < n1.value)
		return 1;
	return 0;
};

SortableTable.idFunction = function (x) {
	return x;
};

SortableTable.toUpperCase = function (s) {
	return s.toUpperCase();
};

SortableTable.toDate = function (s) {
	var parts = s.split("-");
	var d = new Date(0);
	d.setFullYear(parts[0]);
	d.setDate(parts[2]);
	d.setMonth(parts[1] - 1);
	return d.valueOf();
};


// add sort types
SortableTable.prototype.addSortType("Number", Number);
SortableTable.prototype.addSortType("StringCI", SortableTable.toUpperCase);
SortableTable.prototype.addSortType("Date", SortableTable.toDate);
SortableTable.prototype.addSortType("String");
// None is a special case


// the row coloring functions

function addClassName(el, sClassName)
{
	var s = el.className;
	var p = s.split(' ');
	var l = p.length;
	for (var i = 0; i < l; i++) {
		if (p[i] == sClassName)
			return;
	}
	p[p.length] = sClassName;
	el.className = p.join(' ');
}

function removeClassName(el, sClassName)
{
	var s = el.className;
	var p = s.split(' ');
	var np = [];
	var l = p.length;
	var j = 0;
	for (var i = 0; i < l; i++) {
		if (p[i] != sClassName)
			np[j++] = p[i];
	}
	el.className = np.join(' ');
}


// Other custom sort types
// -----------------------------------

// Thanks to Bernhard Wagner for submitting this function

function replace8a8(str) {
	str = str.toUpperCase();
	var splitstr = "____";
	var ar = str.replace(
		/(([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?)(.*)/,
	 "$1"+splitstr+"$4").split(splitstr);
	var num = Number(ar[0]).valueOf();
	var ml = ar[1].replace(/\s*([KMGB])\s*/, "$1");

	if (ml == "K")
		num *= 1024;
	else if(ml == "M")
		num *= 1024 * 1024;
	else if (ml == "G")
		num *= 1024 * 1024 * 1024;
	else if (ml == "T")
		num *= 1024 * 1024 * 1024 * 1024;
	// B and no prefix

	num *= base;

	return num;
}

SortableTable.prototype.addSortType( "NumberK", replace8a8 );

// -----------------------------------

// Thanks to Brian K. Cantwell for the initial code

function usCurrencyConverter( s )
{
	var n = s;
	var i = s.indexOf( '$' );
	if ( i == -1 ) {
		i = s.indexOf( ',' );
	}
	
	if ( i != -1 ) {
		var p1 = s.substr( 0, i );
		var p2 = s.substr( i + 1, s.length );
		return usCurrencyConverter( p1 + p2 );
	}

	return parseFloat( n );
}

SortableTable.prototype.addSortType( "UsCurrency", usCurrencyConverter );

// -----------------------------------

// Benjam's own date-time sorter

// must be in largest to smallest order and 24hr format
// ie 2007-12-25 13:45:51
function dateTimeConverter(subject)
{
	var myregexp = /\D/ig;
	var result = subject.replace(myregexp, '');
	return parseFloat(result);
}

SortableTable.prototype.addSortType( "DateTime", dateTimeConverter );

// -----------------------------------

// Benjam's own exclusive number sorter

// removes any non-numeric character (except decimal point ( . ) )
// and calculates from remaining digits
function toNumber(subject)
{
	var myregexp = /[^\d\.]/ig;
	var result = subject.replace(myregexp, '');
	return parseFloat(result);
}

SortableTable.prototype.addSortType( "ExclNumber", toNumber );

// -----------------------------------

// Benjam's own zip code sorter

// 5-4 OK, converts any non-digit to a decimal point
function zipCode(subject)
{
	var myregexp = /\D/ig;
	var result = subject.replace(myregexp, '.');
	return parseFloat(result);
}

SortableTable.prototype.addSortType( "ZipCode", zipCode );

// -----------------------------------

// Benjam's own initial number sorter

// sorts by the first integer it finds
function initNumber(subject)
{
	var result = 0;

	var myregexp = /\d+/i;
	var match = myregexp.exec(subject);

	if (match != null) {
		result = match[0];
	}

	return Number(result);
}

SortableTable.prototype.addSortType( "FirstNumber", initNumber );

// -----------------------------------

// Benjam's own long date sorter

// takes dates formatted as [S[unday][,] ]Jan[uary] 14[th][,][ 2007][ 12[:45[:41]][ ][p[m]]]
// or you may switch the order of the month and day as follows:
// [S[unday][,] ]14[th] Jan[uary][,][ 2007][ 12:45[:41][ ][p[m]]]
// you may also separate the elements with a dash ( - )
// or a forward slash ( / ) as follows:
// 14-Jan-2007  OR  Jan-14-2007  OR  Jan/14/2007  OR  14/Jan/2007
// with or without the [optional bits]
// case insensitive
function typedDate(subject)
{
	// set some default values (in case we are missing any, like time or sec)
	var year  = '0000';
	var month = '00';
	var day   = '00';
	var hour  = '00';
	var min   = '00';
	var sec   = '00';
	
	subject = subject.toLowerCase( )+' '; // add a trailing space (for the instance of "Jan 14", does not affect any others)

	// grab the entire thing in one massive regex
	// group 1,4,5-month ; 2,3-day ; 5-year ; 6-hour ; 7-minute ; 8-second ; 9-meridiem
	var myregexp = /\s*(?:(?:[a-z]+,?\s+)?([a-z]{3})(?:[a-z]*)?[-\s]+(\d{1,2})(?:[^\d\s]*)(?=\D)|(\d{1,2})(?:[^\d\s]*)[-\/\s]+([a-z]{3})(?:[a-z]*)?,?|([a-z]{3})(?:[a-z]*)?,?)(?:[^a-z\d]*(\d{4}))?(?:[-\/\s]+(\d{1,2})(?::(\d{2})(?::(\d{2}))?)?(?:\s*([apm]+))?)?/i;
	var match = myregexp.exec(subject);

	// now parse through it and get the various bits out to play with
	// make sure we have a match
	if (null == match) {
		return 0;
	}

	// grab the month
	if (null != match[1]) {
		month = match[1];
	}
	else if (null != match[4]) {
		month = match[4];
	}
	else if (null != match[5]) {
		month = match[5];
	}

	var months = {
		'jan' : '01' , 'feb' : '02' , 'mar' : '03' ,
		'apr' : '04' , 'may' : '05' , 'jun' : '06' ,
		'jul' : '07' , 'aug' : '08' , 'sep' : '09' ,
		'oct' : '10' , 'nov' : '11' , 'dec' : '12'
	};
	month = months[month];
	
	// make sure we have a month
	if (null == month) {
		month = '00';
	}

	// grab the day
	if (null != match[2]) {
		day = match[2];
	}
	else if (null != match[3]) {
		day = match[3];
	}

	if (1 == day.length) {
		day = '0' + day;
	}

	// grab the year
	if (null != match[6]) {
		year = match[6];
	}

	// grab the hour
	if (null != match[7]) {
		hour = match[7];

		if (null != match[10]) {
			if ((12 > Number(hour)) && (-1 != match[10].indexOf('p'))) {
				hour = (Number(hour) + 12) + ''; // add 12 hours for pm (if not noon)
			}
			else if ((12 == Number(hour)) && (-1 != match[10].indexOf('a'))) {
				hour = '00'; // set 12 am as 00
			}
		}

		if (1 == hour.length) {
			hour = '0' + hour;
		}
	}

	// grab the minute
	if (null != match[8]) {
		min = match[8];
	}

	// grab the second
	if (null != match[9]) {
		sec = match[9];
	}

	var result = year + month + day + hour + min + sec + ''; // largest to smallest

//	alert(subject+' = '+result);
	return Number(result);
}

SortableTable.prototype.addSortType( "LongDate", typedDate );

